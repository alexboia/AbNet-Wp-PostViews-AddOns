<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class Plugin {
		/**
		 * @var Plugin|null
		 */
		private static $_instance = null;

		/**
		 * @var string[]|null
		 */
		private $_botPatterns = null;

		/**
		 * @var string[]|null
		 */
		private $_refererPatterns = null;

		/**
		 * @var RefererPatternLoader|null
		 */
		private $_refererPatternLoader = null;

		/**
		 * @var ToolsIntegration|null
		 */
		private $_toolsIntegration = null;

		/**
		 * @var Datasource
		 */
		private $_dataSource;

		/**
		 * @return Plugin
		 */
		public static function getInstance(): Plugin {
			if ( null === self::$_instance ) {
				register_activation_hook(__FILE__, array(self::class, 'activate'));
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public static function activate() {
			Db::setup();
		}

		private function __construct() {
			$this->_dataSource = new Datasource();
		}

		public function run(): void {
			add_action('init', array($this, 'onWpInit'), 10);
			add_filter( 'postviews_should_count', array( $this, 'filterPostviewsShouldCount' ), 20, 2 );
			$this->_getToolsIntegration()->init();
		}

		public function onWpInit(): void {
			$this->_migrateTablesIfVersionDiffers();
		}

		private function _migrateTablesIfVersionDiffers() {
			$registered = $this->_getRegisteredVersion();
			if (version_compare($registered, ABNET_WP_POST_VIEWS_VERSION, '!=')) {
				Db::setup();
				$this->_updateRegisteredVersion(ABNET_WP_POST_VIEWS_VERSION);
			}
		}

		private function _getRegisteredVersion(): string|null {
			return get_option('abnet_wp_post_views_addons_version', '1.0');
		}

		private function _updateRegisteredVersion(string $version): void {
			update_option('abnet_wp_post_views_addons_version', $version, true);
		}

		/**
		 * Blocks post views counting if request matches stuff defined in data/bots.json
		 *
		 * @param bool $shouldCount
		 * @param int  $postId
		 * @return bool
		 */
		public function filterPostviewsShouldCount($shouldCount, $postId): bool {	
			$trackingEnabled = $this->_isTrackingEnabled();
			$botExclusionEnabled = $this->_isWpPostviewsBotExclusionEnabled();
			$context = VisitContext::getInstance();

			$countView = $shouldCount;
			$visitInfo = $this->_evaluatePostVisit($context);

			if ($botExclusionEnabled) {
				$countView = $countView 
					&& !$context->isBot() 
					&& !$context->isFilteredReferer() 
					&& !$context->isSpamReferer();
			}

			if ($countView) {
				$context->flagCounted();
			}

			if ($trackingEnabled) {
				$this->_processPostVisit($visitInfo);
			}

			return $countView;
		}

		private function _evaluatePostVisit(VisitContext $context): VisitInfo {
			$collector = new VisitInfoCollector();
			$calculator = new FuzzyBotScoreCalculator();

			$strictMode = $this->_isStrictMode();
			$visitInfo = $collector->collectVisitInfo($context);

			abnet_write_log(sprintf("Strict mode: %s.", 
				$strictMode ? "Yes" : "No"));

			$userAgent = $visitInfo->getUserAgent();
			$referer = $visitInfo->getReferer();

			$userAgentDesc = !empty($userAgent) ? $userAgent : "<NO USER AGENT>";
			$refererDesc = !empty($referer) ? $referer : "<NO REFERER>";

			if ($this->_matchesAnyBotPattern($userAgent, $this->_getBotPatterns())) {
				abnet_write_log("Request {$userAgentDesc}/{$refererDesc} matches bot patterns.");
				$context->flagWellKnownBot();
			}

			if ($this->_matchesAnyRefererPattern($referer, $this->_getRefererPatterns(), $strictMode)) {
				abnet_write_log("Request {$userAgentDesc}/{$refererDesc} matches referer patterns.");	
				$context->flagFilteredReferer();
			}

			$visitInfo = $visitInfo->updateFromVisitContext($context);
			if (!$context->isBot()) {
				$fuzzyBotScore = $calculator->calculate($visitInfo);
				$fuzzyBotScore = $this->_filterFuzzyBotScoreScore($fuzzyBotScore, $visitInfo);
				$context->setFuzzyBotScore($fuzzyBotScore->getScore());

				$autoFlagScoreThreshold = $this->_flagBotIfFuzzyScoreMoreThan();
				if (!$context->isBot() && $context->getFuzzyBotScore() >= $autoFlagScoreThreshold) {
					abnet_write_log("Request {$userAgentDesc}/{$refererDesc} has fuzzy boy score above trheshold ({$context->getFuzzyBotScore()}).");	
					$context->flagBot();
				}

				$visitInfo = $visitInfo->updateFromVisitContext($context);
			}

			return $visitInfo;
		}

		private function _processPostVisit(VisitInfo $visitInfo) {
			try {
				$result = $this->_dataSource->saveVisitInfo($visitInfo);
				if (false === $result) {
					Db::logLastDbError('Failed to save visit info');
				}
			} catch (\Throwable $throwable) {
				abnet_write_throwable($throwable, 'Failed to track post view: %s in %s on line %d');
			}
		}

		private function _filterFuzzyBotScoreScore(FuzzyBotScore $score, VisitInfo $visitInfo): FuzzyBotScore {
			/**
			 * Filters the computed fuzzy bot score.
			 *
			 * @param FuzzyBotScore $score Bot probability score, from 0 to 100.
			 * @param VisitInfo $visitInfo Visit information used to compute the score.
			 */
			$filteredScore = apply_filters('abnet_wpv_addons_fuzzy_bot_score',
				$score,
				$visitInfo);

			return $filteredScore instanceof FuzzyBotScore 
				? $filteredScore 
				: $score;
		}

		private function _matchesAnyBotPattern(string|null $value, array $patterns): bool {
			if (empty($value) || empty($patterns)) {
				return false;
			}

			return $this->_matchesAnyPattern($value, $patterns);
		}

		/**
		 * @param string   $value
		 * @param string[] $patterns
		 * @return bool
		 */
		private function _matchesAnyPattern(string $value, array $patterns): bool {
			if (empty($value) || empty($patterns)) {
				return false;
			}

			foreach ($patterns as $pattern) {
				if (!empty($pattern) && false !== stripos($value, $pattern)) {
					return true;
				}
			}

			return false;
		}

		private function _matchesAnyRefererPattern(string|null $value, array $patterns, $strict = false): bool {
			if (empty($value) || empty($patterns)) {
				return false;
			}

			if ($strict) {
				return $this->_matchesAnyRefererDomain($value, $patterns);
			}

			return $this->_matchesAnyPattern($value, $patterns);
		}

		/**
		 * Strict mode is active only when ABNET_WP_POST_VIEWS_STRICT exists and is boolean true.
		 */
		private function _isStrictMode(): bool {
			return defined('ABNET_WP_POST_VIEWS_STRICT')
				&& \constant('ABNET_WP_POST_VIEWS_STRICT') === true;
		}

		private function _isTrackingEnabled(): bool {
			return abnet_wp_post_views_addons_tracking_enabled() === true;
		}

		private function _matchesAnyRefererDomain(string $referer, array $patterns): bool {
			$refererHost = wp_parse_url($referer, PHP_URL_HOST);
			if (!is_string($refererHost) || empty($refererHost)) {
				return false;
			}

			$refererHost = strtolower(ltrim(trim($refererHost), '.'));

			foreach ($patterns as $pattern) {
				$domain = $this->_normalizeDomain($pattern);
				if (empty($domain)) {
					continue;
				}

				if ($this->_matchesDomainOrSubdomain($refererHost, $domain)) {
					return true;
				}
			}

			return false;
		}

		private function _matchesDomainOrSubdomain(string $refererHost, string $domain): bool {
			$subdomainSuffix = '.' . $domain;
			return $refererHost === $domain 
				|| substr($refererHost, -strlen($subdomainSuffix)) 
					=== $subdomainSuffix;
		}

		private function _normalizeDomain(string $domain): string|null {
			$domain = trim(strtolower((string) $domain));
			if (empty($domain)) {
				return '';
			}

			$domain = preg_replace('#^https?://#', '', $domain);
			if (!is_string($domain) || empty($domain)) {
				return '';
			}

			$domain = preg_replace('#/.*$#', '', $domain);
			if (!is_string($domain) || empty($domain)) {
				return '';
			}

			$defaultNormalizedDomain = ltrim(trim($domain), '.');

			/**
			 * Filters a domain string after it has been normalized (lowercased, protocol stripped, path stripped, leading dots removed).
			 *
			 * @param string $defaultNormalizedDomain The normalized domain string, e.g. "example.com".
			 * @return string|null The filtered domain string. Any non-string is ignored and the default is used instead. A null return value will cause the domain to be skipped altogether.
			 */
			$normalizedDomain = apply_filters('abnet_wpv_addons_normalized_domain',
				$defaultNormalizedDomain);

			return is_string($normalizedDomain) || $normalizedDomain === null
				? $normalizedDomain 
				: $defaultNormalizedDomain;
		}

		/**
		 * Obey native WP-PostViews "Exclude Bot Views".
		 *
		 * @return bool
		 */
		private function _isWpPostviewsBotExclusionEnabled(): bool {
			return abnet_wp_post_views_addons_bot_exclusion_enabled();
		}

		/**
		 * @return string[]
		 */
		private function _getBotPatterns(): array {
			if (null !== $this->_botPatterns) {
				return $this->_botPatterns;
			}

			$this->_botPatterns = $this->_getRefererPatternLoader()->getBotPatterns();
			return $this->_botPatterns;
		}

		/**
		 * @return string[]
		 */
		private function _getRefererPatterns(): array {
			if (null !== $this->_refererPatterns) {
				return $this->_refererPatterns;
			}

			$this->_refererPatterns = $this->_getRefererPatternLoader()->loadRefererPatterns();
			return $this->_refererPatterns;
		}

		/**
		 * @return RefererPatternLoader
		 */
		private function _getRefererPatternLoader(): RefererPatternLoader {
			if (null === $this->_refererPatternLoader) {
				$this->_refererPatternLoader = new RefererPatternLoader(__FILE__);
			}

			return $this->_refererPatternLoader;
		}

		/**
		 * @return ToolsIntegration
		 */
		private function _getToolsIntegration(): ToolsIntegration {
			if (null === $this->_toolsIntegration) {
				$this->_toolsIntegration = new ToolsIntegration(__FILE__);
			}

			return $this->_toolsIntegration;
		}

		private function _flagBotIfFuzzyScoreMoreThan(): int {
			//abnet_wpv_addons_fuzzy_bot_score
			$min = 0;
			if (defined('ABNET_WP_POST_VIEWS_FUZZY_BOT_SCORE_THRESHOLD')) {
				$value = constant('ABNET_WP_POST_VIEWS_FUZZY_BOT_SCORE_THRESHOLD');
				if (is_int($value)) {
					$min = $value;
				}
			}

			if ($min <= 0) {
				$min = 65;
			}

			return $min;
		}
	}
}
