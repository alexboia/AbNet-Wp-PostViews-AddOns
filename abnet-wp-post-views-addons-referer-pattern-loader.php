<?php
declare(strict_types=1);

if (! defined( 'ABSPATH' ) ) {
	exit;
}

final class ABNet_WP_Post_Views_Addons_RefererPatternLoader {
	/**
	 * @var string
	 */
	private string $_pluginFile = '';

	private static array|null $_cachedBotFileEntries = null;

	/**
	 * @param string $pluginFile
	 */
	public function __construct(string $pluginFile) {
		$this->_pluginFile = (string)$pluginFile;
	}

	/**
	 * @return string[]
	 */
	public function getBotPatterns(): array {
		$decoded = $this->_readBotFileEntries();
		
		$entries = array();
		if (isset($decoded['bots']) && is_array($decoded['bots'])) {
			$entries = $decoded['bots'];
		} elseif (isset($decoded['user_agents']) && is_array($decoded['user_agents'])) {
			$entries = $decoded['user_agents'];
		} elseif (array_values($decoded) === $decoded) {
			$entries = $decoded;
		}

		$defaultBotPatterns = $this->_normalizePatterns($entries);

		/**
		 * Filters the list of bot user-agent patterns used to detect bot traffic.
		 *
		 * @param string[] $defaultBotPatterns The default bot patterns loaded from the plugin's bots JSON file.
		 * @return string[] The filtered array of bot user-agent pattern strings. If a non-array is returned it is ignored and the default patterns are used instead.
		 */
		$botPatterns = apply_filters('abnet_wpv_addons_bot_patterns',
			$defaultBotPatterns);
		
		if (!is_array($botPatterns)) {
			$botPatterns = $defaultBotPatterns;
		}

		return $botPatterns;
	}

	private function _readBotFileEntries(): array {
		if (self::$_cachedBotFileEntries === null) {
			$decoded = abnet_wp_post_views_addons_read_bots_json( $this->_pluginFile );
			if (! is_array( $decoded ) ) {
				$decoded = array();
			}
			self::$_cachedBotFileEntries = $decoded;
		}

		return self::$_cachedBotFileEntries;
	}

	/**
	 * @return string[]
	 */
	public function loadPatterns(): array {
		$decoded = $this->_readBotFileEntries();

		$entries = array();
		if (isset($decoded['referers']) && is_array($decoded['referers'])) {
			$entries = $decoded['referers'];
		} elseif (isset( $decoded['referrers']) && is_array($decoded['referrers'])) {
			$entries = $decoded['referrers'];
		} elseif (isset($decoded['social_referrers']) && is_array($decoded['social_referrers'])) {
			$entries = $decoded['social_referrers'];
		}

		$defaultPatterns = $this->_normalizePatterns($entries);

		/**
		 * Filters the list of referer patterns used to identify social/referral traffic.
		 *
		 * @param string[] $defaultPatterns The default referer patterns loaded from the plugin's bots JSON file.
		 * @return string[] The filtered array of referer pattern strings. If a non-array is returned it is ignored and the default patterns are used instead.
		 */
		$refererPatterns = apply_filters('abnet_wpv_addons_referer_patterns',
			$defaultPatterns);
		
		if (!is_array($refererPatterns)) {
			$refererPatterns = $defaultPatterns;
		}

		return $refererPatterns;
	}

	/**
	 * @param array $entries
	 * @return string[]
	 */
	private function _normalizePatterns(array $entries): array {
		$patterns = array();

		foreach ($entries as $entry) {
			if (is_string($entry)) {
				$entry = trim($entry);
				if (!empty($entry)) {
					$patterns[] = $entry;
				}
				continue;
			}

			if (is_array($entry)) {
				if (isset($entry['pattern']) && is_string($entry['pattern'])) {
					$pattern = trim($entry['pattern']);
					if (!empty($pattern)) {
						$patterns[] = $pattern;
					}
				}

				if (isset($entry['domain'] ) && is_string($entry['domain'])) {
					$domain = trim($entry['domain']);
					if (!empty($domain)) {
						$patterns[] = $domain;
					}
				}
			}
		}

		return array_values(array_unique($patterns));
	}
}