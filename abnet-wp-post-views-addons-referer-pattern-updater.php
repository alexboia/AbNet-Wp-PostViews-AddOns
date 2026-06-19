<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

final class ABNet_WP_Post_Views_Addons_RefererPatternUpdater {
	public const STATUS_UPDATED = 'updated';
	public const STATUS_NO_CHANGES = 'no_changes';
	public const STATUS_NO_SOURCE = 'no_source';
	public const STATUS_NO_BOTS = 'no_bots';

	private string $_aiRobotsTxtSourceUrl;

	private bool $_useLastDownloadedFile = false;

	private string $_pluginFile = '';

	/**
	 * @param string $pluginFile
	 * @param bool   $useLastDownloadedFile
	 * @param string $aiRobotsTxtSourceUrl
	 */
	public function __construct( string $pluginFile, 
		bool $useLastDownloadedFile = false, 
		string $aiRobotsTxtSourceUrl = '' ) {

		$this->_pluginFile = $pluginFile;
		$this->_useLastDownloadedFile = $useLastDownloadedFile;

		if ('' !== trim($aiRobotsTxtSourceUrl)) {
			$this->_aiRobotsTxtSourceUrl = trim($aiRobotsTxtSourceUrl);
		} else {
			$this->_aiRobotsTxtSourceUrl = defined('ABNET_WP_POST_VIEWS_AI_ROBOTS_TXT_URL')
				? ABNET_WP_POST_VIEWS_AI_ROBOTS_TXT_URL 
				: ABNET_WP_POST_VIEWS_AI_ROBOTS_TXT_URL_DEFAULT;
		}
	}

	public function updateFromAiRobotsTxt(): string {
		$sourcePayload = $this->_downloadSourcePayload();
		if (null === $sourcePayload && $this->_useLastDownloadedFile) {
			$sourcePayload = $this->_readLastDownloadedPayload();
		}

		if (null === $sourcePayload || '' === trim($sourcePayload)) {
			return self::STATUS_NO_SOURCE;
		}

		$downloadedBots = $this->_extractBotsFromPayload($sourcePayload);
		if ( empty( $downloadedBots ) ) {
			return self::STATUS_NO_BOTS;
		}

		$currentConfig = $this->_readLocalConfig();
		$currentBots = array();
		if (isset($currentConfig['bots']) && is_array($currentConfig['bots'])) {
			$currentBots = $this->_normalizeBots( $currentConfig['bots'] );
		}

		$mergedBots = $this->_mergeUniqueBots($currentBots, $downloadedBots);
		if ( $mergedBots === $currentBots ) {
			return self::STATUS_NO_CHANGES;
		}

		$currentConfig['bots'] = $mergedBots;
		$this->_writeLocalConfig($currentConfig);

		return self::STATUS_UPDATED;
	}

	/**
	 * @return string|null
	 */
	private function _downloadSourcePayload(): ?string {
		if (!function_exists('wp_remote_get')) {
			return null;
		}

		$response = wp_remote_get(
			$this->_aiRobotsTxtSourceUrl,
			array(
				'timeout' => 30,
				'user-agent' => $this->_buildUserAgent(),
			)
		);

		if (is_wp_error($response)) {
			return null;
		}

		$statusCode = (int)wp_remote_retrieve_response_code($response);
		if ($statusCode < 200 || $statusCode >= 300) {
			return null;
		}

		$body = wp_remote_retrieve_body($response);
		if (!is_string($body) || '' === trim($body)) {
			return null;
		}

		$this->_saveLastDownloadedPayload($body);
		return $body;
	}

	private function _buildUserAgent(): string {
		return 'ABNet-WP-PostViews-Addons/' . ABNET_WP_POST_VIEWS_VERSION . ' (+' . site_url() . ')';
	}

	/**
	 * @return string|null
	 */
	private function _readLastDownloadedPayload(): ?string {
		$path = $this->_determineLastDownloadedFilePath();
		if (!file_exists($path) || !is_readable($path)) {
			return null;
		}

		$content = file_get_contents($path);
		if (false === $content || '' === trim($content)) {
			return null;
		}

		return $content;
	}

	/**
	 * @param string $payload
	 * @return void
	 */
	private function _saveLastDownloadedPayload(string $payload): void {
		$directoryPath = $this->_determineLastDownloadedDirectoryPath();
		if (!file_exists($directoryPath)) {
			wp_mkdir_p($directoryPath);
		}

		if (!is_dir($directoryPath) || !is_writable($directoryPath)) {
			return;
		}

		file_put_contents($this->_determineLastDownloadedFilePath(), $payload);
	}

	/**
	 * @param string $payload
	 * @return string[]
	 */
	private function _extractBotsFromPayload(string $payload): array {
		$decoded = json_decode($payload, true);
		if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
			return array();
		}

		$candidates = array();

		if (isset($decoded['bots']) && is_array($decoded['bots'])) {
			$candidates = $decoded['bots'];
		} elseif (isset( $decoded['user_agents']) && is_array($decoded['user_agents'])) {
			$candidates = $decoded['user_agents'];
		} else {
			$candidates = array_keys($decoded);
		}

		return $this->_normalizeBots($candidates);
	}

	/**
	 * @return array
	 */
	private function _readLocalConfig(): array {
		$decoded = abnet_wp_post_views_addons_read_bots_json($this->_pluginFile);
		return is_array($decoded) ? $decoded : array();
	}

	/**
	 * @param array $config
	 * @return void
	 */
	private function _writeLocalConfig(array $config): void {
		$jsonPath = $this->_determineJsonPath();
		$encoded = wp_json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($encoded ) || '' === $encoded) {
			return;
		}

		file_put_contents($jsonPath, $encoded . PHP_EOL);
	}

	/**
	 * @param array $bots
	 * @return string[]
	 */
	private function _normalizeBots(array $bots): array {
		$normalized = array();

		foreach ($bots as $bot) {
			if (!is_string($bot)) {
				continue;
			}

			$bot = trim($bot);
			if ('' === $bot) {
				continue;
			}

			$normalized[] = $bot;
		}

		return array_values(array_unique($normalized));
	}

	/**
	 * @param string[] $currentBots
	 * @param string[] $newBots
	 * @return string[]
	 */
	private function _mergeUniqueBots(array $currentBots, array $newBots): array {
		$merged = array();
		$seen = array();

		foreach ($currentBots as $bot) {
			$key = strtolower(trim( (string) $bot));
			if (empty($key) || isset($seen[$key])) {
				continue;
			}

			$seen[$key] = true;
			$merged[] = $bot;
		}

		foreach ($newBots as $bot) {
			$key = strtolower(trim( (string)$bot));
			if (empty($key) || isset($seen[$key])) {
				continue;
			}

			$seen[$key] = true;
			$merged[] = $bot;
		}

		return $merged;
	}

	/**
	 * @return string
	 */
	private function _determineLastDownloadedDirectoryPath(): string {
		return plugin_dir_path($this->_pluginFile) 
			. 'data' . DIRECTORY_SEPARATOR 
			. 'last-downloaded-sources' . DIRECTORY_SEPARATOR 
			. 'ai-robots-txt';
	}

	/**
	 * @return string
	 */
	private function _determineLastDownloadedFilePath() {
		return trailingslashit($this->_determineLastDownloadedDirectoryPath()) . 'robots.json';
	}

	/**
	 * @return string
	 */
	private function _determineJsonPath() {
		return plugin_dir_path($this->_pluginFile) 
			. 'data' . DIRECTORY_SEPARATOR 
			. 'bots.json';
	}
}