<?php
declare(strict_types=1);

if (!defined( 'ABSPATH')) {
	exit;
}

final class ABNet_WP_Post_Views_Addons_Db {
	public static function getVisitInfoTableName(): string {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;
		return $wpdb->prefix . 'abnet_wp_post_views_addons_visits';
	}

	public static function setup(): void {
		self::_migrateVisitInfoTable();
	}

	public static function logLastDbError(string $contextMessage = 'Database error'): void {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;
		$errorMessage = !empty($wpdb->last_error)
			? $wpdb->last_error
			: 'Unknown database error';

		abnet_write_log(sprintf('%s: %s', 
			$contextMessage, 
			$errorMessage));
	}

	private static function _migrateVisitInfoTable(): void {
		self::_createVisitInfoTable();
		self::_migrateVisitInfoColumns();
	}

	private static function _migrateVisitInfoColumns(): void {
		$columns = array(
			'accept_content' => 'VARCHAR(255) NULL',
			'cf_ip_country' => 'VARCHAR(8) NULL',
			'cf_ip_as_name' => 'VARCHAR(255) NULL',
			'computed_country' => 'VARCHAR(8) NULL',
			'computed_asn' => 'VARCHAR(64) NULL',
			'computed_network_name' => 'VARCHAR(255) NULL',
			'fuzzy_bot_score' => "INT(11) NOT NULL DEFAULT '0'",
			'was_processed' => "TINYINT(1) NOT NULL DEFAULT '0'",
			'was_discarded' => "TINYINT(1) NOT NULL DEFAULT '0'",
		);

		foreach ($columns as $columnName => $definition) {
			self::_ensureColumn($columnName, $definition);
		}
	}

	private static function _ensureColumn(string $columnName, string $definition): void {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$tableName = self::getVisitInfoTableName();
		$columnExists = $wpdb->get_var($wpdb->prepare(
			'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
			DB_NAME,
			$tableName,
			$columnName
		));

		if (!$columnExists) {
			$wpdb->query("ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$definition}");
		}
	}

	private static function _createVisitInfoTable(): void {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$tableName = self::getVisitInfoTableName();
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $tableName (
			visit_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED DEFAULT NULL,
			visit_url VARCHAR(2048) NOT NULL DEFAULT '',
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			referer TEXT NULL,
			referer_is_local TINYINT(1) NOT NULL DEFAULT '0',
			user_agent TEXT NULL,
			visit_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			request_method VARCHAR(16) NOT NULL DEFAULT 'GET',
			protocol VARCHAR(16) NOT NULL DEFAULT 'HTTP',
			cookies LONGTEXT NULL,
			visit_language VARCHAR(64) NULL,
			is_bot TINYINT(1) NOT NULL DEFAULT '0',
			is_filtered_referer TINYINT(1) NOT NULL DEFAULT '0',
			is_spam_referer TINYINT(1) NOT NULL DEFAULT '0',
			search_term VARCHAR(255) NULL,
			utm_source VARCHAR(255) NULL,
			utm_medium VARCHAR(255) NULL,
			utm_campaign VARCHAR(255) NULL,
			utm_term VARCHAR(255) NULL,
			utm_content VARCHAR(255) NULL,
			accept_content VARCHAR(255) NULL,
			cf_ip_country VARCHAR(8) NULL,
			cf_ip_as_name VARCHAR(255) NULL,
			computed_country VARCHAR(8) NULL,
			computed_asn VARCHAR(64) NULL,
			computed_network_name VARCHAR(255) NULL,
			fuzzy_bot_score INT(11) NOT NULL DEFAULT '0',
			was_counted TINYINT(1) NOT NULL DEFAULT '0',
			was_processed TINYINT(1) NOT NULL DEFAULT '0',
			was_discarded TINYINT(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (visit_id),
			KEY key_post_id (post_id),
			KEY key_visit_timestamp (visit_language)
		) $charsetCollate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}