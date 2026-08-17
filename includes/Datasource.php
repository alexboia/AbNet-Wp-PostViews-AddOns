<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class Datasource {
		private function _getTableName(): string {
			return Db::getVisitInfoTableName();
		}

		private function _getHoneyTrappedBotsTableName(): string {
			return Db::getHoneyTrappedBotsTableName();
		}

		public function saveVisitInfo(VisitInfo $visitInfo): int|false {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$data = $visitInfo->toDbArray();
			if (!isset($data[VisitInfo::KEY_VISIT_ID])
				|| (int)$data[VisitInfo::KEY_VISIT_ID] <= 0) {
				unset($data[VisitInfo::KEY_VISIT_ID]);
			}

			$tableName = $this->_getTableName();
			$result = $wpdb->insert($tableName, $data);
			if (false === $result) {
				return false;
			}

			return (int)$wpdb->insert_id;
		}

		public function getVisitInfoById(int $visitId): VisitInfo|null {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$tableName = $this->_getTableName();
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$tableName} WHERE visit_id = %d",
					$visitId
				),
				ARRAY_A
			);

			if (!is_array($row) || empty($row)) {
				return null;
			}

			return VisitInfo::fromDbArray($row);
		}

		/**
		 * @return VisitInfo[]
		 */
		public function getVisitInfosByPostId(int $postId, int $limit = 100, int $offset = 0): array {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$tableName = $this->_getTableName();
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$tableName} WHERE post_id = %d ORDER BY timestamp DESC LIMIT %d OFFSET %d",
					$postId,
					$limit,
					$offset
				),
				ARRAY_A
			);

			if (!is_array($rows) || empty($rows)) {
				return array();
			}

			$visitInfos = array();
			foreach ($rows as $row) {
				if (is_array($row)) {
					$visitInfos[] = VisitInfo::fromDbArray($row);
				}
			}

			return $visitInfos;
		}

		public function saveHoneyTrappedBot(HoneyTrappedBot $honeyTrappedBot): int {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$data = $honeyTrappedBot->toDbArray();
			if (!isset($data[HoneyTrappedBot::KEY_TRAPPED_BOT_ID])
				|| (int)$data[HoneyTrappedBot::KEY_TRAPPED_BOT_ID] <= 0) {
				unset($data[HoneyTrappedBot::KEY_TRAPPED_BOT_ID]);
			}

			$tableName = $this->_getHoneyTrappedBotsTableName();
			$result = $wpdb->insert($tableName, $data);
			if (false === $result) {
				Db::logLastDbError('Failed to save honey trapped bot');
				return 0;
			}

			return (int)$wpdb->insert_id;
		}

		public function isHoneyTrappedBot(string $signature): bool {
			if (empty($signature)) {
				return false;
			}

			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$tableName = $this->_getHoneyTrappedBotsTableName();
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS trapped_bot_count
						FROM {$tableName} 
						WHERE bot_signature = %s",
					$signature
				),
				ARRAY_A
			);

			return is_array($row) 
				&& !empty($row) 
				&& !empty($row['trapped_bot_count']) 
				&& intval($row['trapped_bot_count']) > 0;
		}

		/**
		 * @return HoneyTrappedBotGroup[]
		 */
		public function getHoneyTrappedBotGroups(int $offset = 0, int $limit = 30): array {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$offset = max(0, $offset);
			$tableName = Db::getHoneyTrappedBotsTableName();

			if ($limit <= 0) {
				$limit = 30;
			}

			$sql = $wpdb->prepare(
				"SELECT bot_signature, trap_type, COUNT(*) AS bot_count 
				FROM `{$tableName}` 
				GROUP BY bot_signature, trap_type 
				ORDER BY bot_count DESC, bot_signature ASC, trap_type ASC
				LIMIT %d, %d",
				$offset, 
				$limit
			);

			/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above. */
			$rawResults = $wpdb->get_results($sql, ARRAY_A);
			if (!is_array($rawResults)) {
				return array();
			}

			$results = array();
			foreach ($rawResults as $row) {
				$results[] = HoneyTrappedBotGroup::fromDbArray($row);
			}

			return $results;
		}

		/**
		 * @return HoneyTrappedBot[]
		 */
		public function getHoneyTrappedBotsByGroup(HoneyTrappedBotGroup $group, int $offset = 0, int $limit = 30): array {
			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$offset = max(0, $offset);
			$tableName = Db::getHoneyTrappedBotsTableName();

			if ($limit <= 0) {
				$limit = 30;
			}	

			$sql = $wpdb->prepare(
				"SELECT * FROM `{$tableName}` 
				WHERE bot_signature = %s
					AND trap_type = %s
				ORDER BY trapped_at DESC, trapped_bot_id DESC
				LIMIT %d, %d",
				$group->botSignature,
				$group->trapType,
				$offset, 
				$limit
			);

			/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above. */
			$rawResults = $wpdb->get_results($sql, ARRAY_A);
			if (!is_array($rawResults)) {
				return array();
			}
		
			$results = array();
			foreach ($rawResults as $row) {
				$results[] = HoneyTrappedBot::fromDbArray($row);
			}

			return $results;
		}

		/**
		 * Get a map of counts for each IP address a bot has been trapped from
		 * 
		 * @return array<string, int>
		 */
		public function getHoneyTrappedIpAddressesBySignature(string $botSignature): array {
			$botSignature = trim($botSignature);
			if (empty($botSignature)) {
				return array();
			}

			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$tableName = $this->_getHoneyTrappedBotsTableName();
			$sql = $wpdb->prepare(
				"SELECT ip_address, COUNT(*) AS ip_address_count
				FROM `{$tableName}`
				WHERE bot_signature = %s
					AND ip_address <> ''
				GROUP BY ip_address
				ORDER BY ip_address ASC",
				$botSignature
			);

			/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared immediately above. */
			$rawResults = $wpdb->get_results($sql, ARRAY_A);
			if (!is_array($rawResults)) {
				return array();
			}

			$results = array();
			foreach ($rawResults as $row) {
				$ipAddress = trim((string)$row['ip_address']);
				if (!empty($ipAddress)) {
					$results[$ipAddress] = intval($row['ip_address_count']);
				}
			}

			uasort($results, function (int $cntA, int $cntB): int {
				if ($cntA == $cntB) {
					return 0;
				}
				return ($cntA < $cntB) ? -1 : 1;
			});

			return $results;
		}
	}
}
