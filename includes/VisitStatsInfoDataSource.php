<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class VisitStatsInfoDataSource {
		/**
		 * @return VisitStatsInfo[]
		 */
		public function getPostStats(int $postId, bool $isBot, int $nDays = 30): array {
			if ($postId <= 0) {
				return array();
			}

			/**
			 * @var \wpdb $wpdb
			 * @see https://developer.wordpress.org/reference/classes/wpdb/
			 */
			global $wpdb;

			$tableName = Db::getVisitInfoTableName();
			if ($nDays <= 0 || $nDays > 90) {
				$nDays = 30;
			}

			$sql = $wpdb->prepare(
				"SELECT post_id, DATE(visit_timestamp) AS visit_date, COUNT(visit_id) AS visit_count 
				FROM `{$tableName}` 
				WHERE post_id = %d 
					AND is_bot = %d 
					AND visit_timestamp >= (NOW() - INTERVAL %d DAY)
				GROUP BY post_id, is_bot, DATE(visit_timestamp)
				ORDER BY visit_timestamp DESC",
				$postId,
				$isBot ? 1 : 0,
				$nDays
			);

			/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above. */
			$rawResults = $wpdb->get_results($sql, ARRAY_A);

			$results = array();
			$category = $isBot 
				? 'post_daily_bots' 
				: 'post_daily_humans';

			foreach ($rawResults as $row) {
				$key = sprintf('%s_%s', 
					$category, 
					$row['visit_date']);

				$label = mysql2date(get_option( 'date_format' ), 
					$row['visit_date']);

				$results[] = new VisitStatsInfo(
					$key,
					$row['visit_date'],
					intval($row['visit_count']),
					$category
				);
			}

			return $results;
		}

		/**
		 * @return VisitStatsInfo[] 
		 */
		public function getAllTimeBotVsHumanStats(): array {
			/**
			 * @var \wpdb $wpdb
			 * @see https://developer.wordpress.org/reference/classes/wpdb/
			 */
			global $wpdb;

			$tableName = Db::getVisitInfoTableName();
			$sql = "SELECT is_bot, COUNT(visit_id) AS visit_count 
				FROM `{$tableName}` 
				GROUP BY is_bot";
				
			$category = 'all_time_bots_vs_humans';
			$rawResults = $wpdb->get_results($sql, ARRAY_A);

			$results = array();
			foreach ($rawResults as $row) {
				$isBot = (int)$row['is_bot'] === 1;
				$key = sprintf('%s_%s', 
					$category, 
					$isBot 
						? 'is_bot' 
						: 'is_human');

				$label = $isBot 
					? __('Maybe bot', 'abnet-wp-post-views-addons') 
					: __('Maybe human', 'abnet-wp-post-views-addons');

				$results[] = new VisitStatsInfo($key, 
					$label, 
					intval($row['visit_count']),
					$category);
			}

			return $results;
		}
	}
}
