<?php
/**
 * Plugin Name: ABNet WP-PostViews Addons
 * Plugin URI: https://github.com/alexboia/AbNet-Wp-PostViews-AddOns
 * Description: Extends WP-PostViews with bot filtering based on the list in data/bots.json.
 * Version: 1.0.0
 * Author: Alexandru Boia
 * Author URI: https://alexboia.net
 * Text Domain: abnet-wp-post-views-addons
 * License: Modified BSD License
 * License URI: https://directory.fsf.org/wiki/License:BSD-3-Clause
 */

declare(strict_types=1);

if (!defined( 'ABSPATH')) {
	exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'constants.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'abnet-wp-post-views-addons-referer-pattern-loader.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'abnet-wp-post-views-addons-referer-pattern-updater.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'abnet-wp-post-views-addons-tools-integration.php';

final class ABNet_WP_Post_Views_Addons {
	/**
	 * @var ABNet_WP_Post_Views_Addons|null
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
	 * @var ABNet_WP_Post_Views_Addons_RefererPatternLoader|null
	 */
	private $_refererPatternLoader = null;

	/**
	 * @var ABNet_WP_Post_Views_Addons_Tools_Integration|null
	 */
	private $_toolsIntegration = null;

	/**
	 * @return ABNet_WP_Post_Views_Addons
	 */
	public static function getInstance(): ABNet_WP_Post_Views_Addons {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {
		return;
	}

	public function init(): void {
		add_filter( 'postviews_should_count', array( $this, 'filterPostviewsShouldCount' ), 20, 2 );
		$this->_getToolsIntegration()->init();
	}

	/**
	 * Blocks post views counting if request matches stuff defined in data/bots.json
	 *
	 * @param bool $shouldCount
	 * @param int  $postId
	 * @return bool
	 */
	public function filterPostviewsShouldCount($shouldCount, $postId): bool {
		if (!$shouldCount) {
			return false;
		}

		if (!$this->_isWpPostviewsBotExclusionEnabled()) {
			return true;
		}

		$strictMode = $this->_isStrictMode();
		
		abnet_write_log(sprintf("Stric mode: %s.", 
			$strictMode ? "Yes" : "No"));

		$userAgent = $this->_determineUserAgent();
		$referer = $this->_determineReferer();

		$userAgentDesc = !empty($userAgent) ? $userAgent : "<NO USER AGENT>";
		$refererDesc = !empty($referer) ? $referer : "<NO REFERER>";

		if ($this->_matchesAnyPattern($userAgent, $this->_getBotPatterns())) {
			abnet_write_log("Request {$userAgentDesc}/{$refererDesc} rejected because it matches bot patterns.");
			return false;
		}

		if ($this->_matchesAnyPattern($referer, $this->_getRefererPatterns(), $strictMode)) {
			abnet_write_log("Request {$userAgentDesc}/{$refererDesc} rejected because it matches referer patterns.");
			return false;
		}

		return true;
	}

	private function _determineUserAgent(): string {
		return isset($_SERVER['HTTP_USER_AGENT']) 
			? (string) $_SERVER['HTTP_USER_AGENT'] 
			: '';
	}

	private function _determineReferer(): string {
		return isset($_SERVER['HTTP_REFERER']) 
			? (string) $_SERVER['HTTP_REFERER'] 
			: '';
	}

	/**
	 * @param string   $value
	 * @param string[] $patterns
	 * @return bool
	 */
	private function _matchesAnyPattern($value, array $patterns, $strict = false): bool {
		if (empty($value) || empty($patterns)) {
			return false;
		}

		if ($strict) {
			return $this->_matchesAnyRefererDomain($value, $patterns);
		}

		foreach ($patterns as $pattern) {
			if (!empty($pattern) && false !== stripos($value, $pattern)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Strict mode is active only when ABNET_WP_POST_VIEWS_STRICT exists and is boolean true.
	 *
	 * @return bool
	 */
	private function _isStrictMode(): bool {
		return defined( 'ABNET_WP_POST_VIEWS_STRICT' ) 
			&& ABNET_WP_POST_VIEWS_STRICT === true;
	}

	/**
	 * @param string   $referer
	 * @param string[] $patterns
	 * @return bool
	 */
	private function _matchesAnyRefererDomain($referer, array $patterns): bool {
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

	/**
	 * @param string $domain
	 * @return string
	 */
	private function _normalizeDomain($domain): string|null {
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

		$this->_refererPatterns = $this->_getRefererPatternLoader()->loadPatterns();
		return $this->_refererPatterns;
	}

	/**
	 * @return ABNet_WP_Post_Views_Addons_RefererPatternLoader
	 */
	private function _getRefererPatternLoader(): ABNet_WP_Post_Views_Addons_RefererPatternLoader {
		if (null === $this->_refererPatternLoader) {
			$this->_refererPatternLoader = new ABNet_WP_Post_Views_Addons_RefererPatternLoader(__FILE__);
		}

		return $this->_refererPatternLoader;
	}

	/**
	 * @return ABNet_WP_Post_Views_Addons_Tools_Integration
	 */
	private function _getToolsIntegration(): ABNet_WP_Post_Views_Addons_Tools_Integration {
		if (null === $this->_toolsIntegration) {
			$this->_toolsIntegration = new ABNet_WP_Post_Views_Addons_Tools_Integration(__FILE__);
		}

		return $this->_toolsIntegration;
	}

}

ABNet_WP_Post_Views_Addons::getInstance()->init();
