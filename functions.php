<?php
declare(strict_types = 1);

if (!defined('ABSPATH')) {
	exit;
}

function abnet_wp_post_views_addons_init_autoload() {
	require_once ABNET_WP_POST_VIEWS_INC_DIR . 'Autoloader.php';
	\ABNetWpPostViewsAddons\Autoloader::init([
		'ABNetWpPostViewsAddons' => ABNET_WP_POST_VIEWS_INC_DIR,
	]);
}

if (!function_exists('abnet_wp_post_views_addons_tracking_enabled')) {
	function abnet_wp_post_views_addons_tracking_enabled(): bool {
		return defined('ABNET_WP_POST_VIEWS_TRACKING_ENABLED')
			? ABNET_WP_POST_VIEWS_TRACKING_ENABLED === true
			: true;
	}
}

if (!function_exists('abnet_wp_post_views_addons_bot_exclusion_enabled')) {
	function abnet_wp_post_views_addons_bot_exclusion_enabled(): bool {
		$viewsOptions = get_option( 'views_options' );

		if (!is_array($viewsOptions)) {
			return true;
		}

		if (!array_key_exists('exclude_bots', $viewsOptions)) {
			return true;
		}

		return (int)$viewsOptions['exclude_bots'] === 1;
	}
}

if (!function_exists('abnet_wp_post_views_addons_read_bots_json')) {
	/**
	 * @param string $pluginFile
	 * @return array|null
	 */
	function abnet_wp_post_views_addons_read_bots_json(): ?array {
		$jsonPath = ABNET_WP_POST_VIEWS_DATA_DIR . DIRECTORY_SEPARATOR . 'bots.json';

		if (!file_exists($jsonPath) || !is_readable($jsonPath)) {
			return null;
		}

		$content = file_get_contents($jsonPath);
		if (false === $content || '' === trim($content)) {
			return null;
		}

		$decoded = json_decode($content, true);
		if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
			return null;
		}

		return $decoded;
	}
}

if (!function_exists('abnet_write_log')) {
	function abnet_write_log(mixed $data) {
		if (defined('WP_DEBUG') && true === WP_DEBUG) {
			if ( is_array($data) || is_object($data)) {
				error_log(print_r( $data, true));
			} else {
				error_log($data);
			}
		}
	}
}

if (!function_exists('abnet_write_throwable')) {
	function abnet_write_throwable(\Throwable $throwable, string $contextMessage) {
		abnet_write_log(sprintf(
			$contextMessage,
			$throwable->getMessage(),
			$throwable->getFile(),
			$throwable->getLine()
		));
	}
}

function abnet_wp_post_views_addons_run() {
	abnet_wp_post_views_addons_init_autoload();	
	\ABNetWpPostViewsAddons\Plugin::getInstance()->run();
}