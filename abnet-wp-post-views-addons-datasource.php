<?php
declare(strict_types=1);

if (!defined( 'ABSPATH')) {
	exit;
}

final class ABNet_WP_Post_Views_Addons_Datasource {
	private function _getTableName(): string {
		return ABNet_WP_Post_Views_Addons_Db::getVisitInfoTableName();
	}

	public function saveVisitInfo(ABNet_WP_Post_Views_Addons_VisitInfo $visitInfo): int|false {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$data = $visitInfo->toDbArray();
		if (!isset($data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_VISIT_ID])
			|| (int)$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_VISIT_ID] <= 0) {
			unset($data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_VISIT_ID]);
		}

		$tableName = $this->_getTableName();
		$result = $wpdb->insert($tableName, $data);
		if (false === $result) {
			return false;
		}

		return (int)$wpdb->insert_id;
	}

	public function getVisitInfoById(int $visitId): ABNet_WP_Post_Views_Addons_VisitInfo|null {
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

		return ABNet_WP_Post_Views_Addons_VisitInfo::fromDbArray($row);
	}

	/**
	 * @return ABNet_WP_Post_Views_Addons_VisitInfo[]
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
				$visitInfos[] = ABNet_WP_Post_Views_Addons_VisitInfo::fromDbArray($row);
			}
		}

		return $visitInfos;
	}
}