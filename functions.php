<?php
declare(strict_types = 1);

if (!defined('ABSPATH')) {
	exit;
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
	function abnet_wp_post_views_addons_read_bots_json(string $pluginFile): ?array {
		$jsonPath = plugin_dir_path( $pluginFile ) . 'data' . DIRECTORY_SEPARATOR . 'bots.json';

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