<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class Db {
		public static function getVisitInfoTableName(): string {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;
			return $wpdb->prefix . 'abnet_wp_post_views_addons_visits';
		}

		public static function getHoneyTrappedBotsTableName(): string {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;
			return $wpdb->prefix . 'abnet_wp_post_views_addons_honeytrapped_bots';
		}

		public static function setup(): void {
			self::_migrateVisitInfoTable();
			self::_migrateHoneyTrappedBotsTable();
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
				'classification' => 'VARCHAR(64) NULL',
				'trapped_bot_signature' => 'VARCHAR(64) NULL'
			);

			foreach ($columns as $columnName => $definition) {
				self::_ensureVisitInfoColumn($columnName, 
					$definition);
			}
		}

		private static function _ensureVisitInfoColumn(string $columnName, string $definition): void {
			self::_ensureColumnForTable(self::getVisitInfoTableName(), 
				$columnName, 
				$definition);
		}

		private static function _ensureColumnForTable(string $tableName, string $columnName, string $definition): void {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$columnExists = $wpdb->get_var($wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
				DB_NAME,
				$tableName,
				$columnName
			));

			if (!$columnExists) {
				$result = $wpdb->query("ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$definition}");
				self::logLastDbError();
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
				classification VARCHAR(64) NULL,
				trapped_bot_signature VARCHAR(64) NULL,
				PRIMARY KEY (visit_id),
				KEY key_post_id (post_id),
				KEY key_visit_timestamp (visit_timestamp)
			) $charsetCollate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta($sql);
		}

		private static function _migrateHoneyTrappedBotsTable(): void {
			self::_createHoneyTrappedBotsTable();
			self::_migrateHoneyTrappedBotsColumns();
		}

		private static function _migrateHoneyTrappedBotsColumns(): void {
			$tableName = self::getHoneyTrappedBotsTableName();
			$columns = array();

			foreach ($columns as $columnName => $definition) {
				self::_ensureColumnForTable($tableName, 
					$columnName, 
					$definition);
			}
		}

		private static function _createHoneyTrappedBotsTable(): void {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$tableName = self::getHoneyTrappedBotsTableName();
			$charsetCollate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS $tableName (
				trapped_bot_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				ip_address VARCHAR(45) NOT NULL DEFAULT '',
				referer TEXT NULL,
				user_agent TEXT NULL,
				request_method VARCHAR(16) NOT NULL DEFAULT 'GET',
				accept_content VARCHAR(255) NULL,
				protocol VARCHAR(16) NOT NULL DEFAULT 'HTTP',
				language VARCHAR(64) NULL,
				all_signals LONGTEXT NULL,
				bot_signature VARCHAR(64) NOT NULL DEFAULT '',
				signature_parts LONGTEXT NULL,
				trap_url VARCHAR(2048) NOT NULL DEFAULT '',
				trap_slug VARCHAR(255) NOT NULL DEFAULT '',
				trap_identifier BIGINT(20) UNSIGNED NULL,
				trap_type VARCHAR(128) NOT NULL DEFAULT '',
				trapped_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (trapped_bot_id),
				KEY key_bot_signature (bot_signature),
				KEY key_trapped_at (trapped_at)
			) $charsetCollate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta($sql);
		}
	}
}