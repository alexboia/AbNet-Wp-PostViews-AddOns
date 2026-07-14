<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (! defined( 'ABSPATH' ) ) {
		exit;
	}

	final class PostVisitEvaluator {
		private VisitInfoCollector $_visitCollector;

		private FuzzyBotScoreCalculator $_fuzzyBotScoreCalculator;

		private RefererPatternLoader $_refererPatternLoader;

		private Datasource $_dataSource;

		/**
		 * @var string[]|null
		 */
		private array|null $_botPatterns = null;

		/**
		 * @var string[]|null
		 */
		private array|null $_refererPatterns = null;

		private bool $_strictMode;

		public function __construct(bool $strictMode) {
			$this->_visitCollector = new VisitInfoCollector();
			$this->_fuzzyBotScoreCalculator = new FuzzyBotScoreCalculator();
			$this->_refererPatternLoader = new RefererPatternLoader();
			$this->_dataSource = new Datasource();
			$this->_strictMode = $strictMode;
		}

		public function evaluatePostVisit(VisitContext $context): VisitInfo {
			$visitInfo = $this->_visitCollector->collectVisitInfo($context);

			abnet_write_log(sprintf("Strict mode: %s.", 
				$this->_strictMode ? "Yes" : "No"));

			$userAgent = $visitInfo->getUserAgent();
			$referer = $visitInfo->getReferer();

			$userAgentDesc = !empty($userAgent) ? $userAgent : "<NO USER AGENT>";
			$refererDesc = !empty($referer) ? $referer : "<NO REFERER>";

			if ($this->_matchesAnyBotPattern($userAgent, $this->_getBotPatterns())) {
				abnet_write_log("Request {$userAgentDesc}/{$refererDesc} matches bot patterns.");
				$context->flagWellKnownBot();
			}

			if ($this->_matchesAnyRefererPattern($referer, $this->_getRefererPatterns(), $this->_strictMode)) {
				abnet_write_log("Request {$userAgentDesc}/{$refererDesc} matches referer patterns.");	
				$context->flagFilteredReferer();
			}

			$visitInfo = $visitInfo->updateFromVisitContext($context);

			if (!$context->isBot()) {
				$isFuzzyBot = $this->_evaluateFuzzyBot($visitInfo, $context);
				if ($isFuzzyBot) {
					abnet_write_log("Request {$userAgentDesc}/{$refererDesc} has fuzzy boy score above trheshold ({$context->getFuzzyBotScore()}).");
				}
			}

			if (!$context->isBot()) {
				$honeyTrappedBot = $this->_evaluteIfInTrappedBotList($visitInfo, $context);
				if ($honeyTrappedBot) {
					abnet_write_log("Request {$userAgentDesc}/{$refererDesc} has been found in honey trapped bot list.");	
				}
			}

			if (!$context->isBot() && $context->isDebugBot()) {
				$context->flagBot();
			}

			$visitInfo = $visitInfo->updateFromVisitContext($context);
			return $visitInfo;
		}

		private function _evaluateFuzzyBot(VisitInfo $visitInfo, VisitContext $context): bool {
			$fuzzyBotScore = $this->_fuzzyBotScoreCalculator->calculate($visitInfo);
			$fuzzyBotScore = $this->_filterFuzzyBotScoreScore($fuzzyBotScore, $visitInfo);
			$context->setFuzzyBotScore($fuzzyBotScore->getScore());

			$isFuzzyBot = false;
			$autoFlagScoreThreshold = $this->_flagBotIfFuzzyScoreMoreThan();
			
			if ($context->getFuzzyBotScore() >= $autoFlagScoreThreshold) {
				$context->flagBot();
				$isFuzzyBot = true;
			}
			
			return $isFuzzyBot;
		}

		private function _evaluteIfInTrappedBotList(VisitInfo $visitInfo, VisitContext $context): bool {
			$honeyTrappedBot = false;
			
			$signatureOptions = $this->_buildSignatureOptions();
			$signatureCalculator = new VisitSignatureCalculator($signatureOptions);
			
			$visitSignature = $signatureCalculator->compute($visitInfo);
			$signatureString = $visitSignature->getSignature();

			if ($this->_dataSource->isHoneyTrappedBot($signatureString)) {
				$context->flagPreviouslyHoneyTrappedBot($signatureString);
				$honeyTrappedBot = true;
			}

			return $honeyTrappedBot;
		}

		private function _buildSignatureOptions(): VisitSignatureOptions {
			return SignatureHelper::buildSignatureOptions();
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

		/**
		 * @return string[]
		 */
		private function _getBotPatterns(): array {
			if (null !== $this->_botPatterns) {
				return $this->_botPatterns;
			}

			$this->_botPatterns = $this->_refererPatternLoader->getBotPatterns();
			return $this->_botPatterns;
		}

		/**
		 * @return string[]
		 */
		private function _getRefererPatterns(): array {
			if (null !== $this->_refererPatterns) {
				return $this->_refererPatterns;
			}

			$this->_refererPatterns = $this->_refererPatternLoader->loadRefererPatterns();
			return $this->_refererPatterns;
		}
	}
}