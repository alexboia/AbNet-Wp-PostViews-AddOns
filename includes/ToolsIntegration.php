<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (!defined('ABSPATH')) {
		exit;
	}

	final class ToolsIntegration {
		private const PAGE_SLUG = 'abnet-wp-post-views-addons-tools';

		private const TAB_UPDATE = 'update';
		private const TAB_BOTS_JSON = 'bots-json';
		
		private const ACTION_UPDATE_BOTS = 'abnet_wp_post_views_addons_update_bots';
		private const NONCE_ACTION = 'abnet_wp_post_views_addons_update_bots_nonce_action';
		private const NONCE_NAME = 'abnet_wp_post_views_addons_update_bots_nonce';

		private const LAST_SUCCESSFUL_UPDATE_OPTION = 'abnet_wp_post_views_addons_last_successful_update';

		private RefererPatternUpdater|null $_updater = null;

		private View $_view;

		public function __construct(View|null $view = null) {
			$this->_view = $view ?? new View();
		}

		public function init(): void {
			add_action('admin_menu', 
				array( $this, 'registerToolsPage'));
			add_action('admin_post_' . self::ACTION_UPDATE_BOTS, 
				array( $this, 'handleUpdateBotsAction'));
		}

		public function registerToolsPage(): void {
			add_management_page(
				__('ABNet Post Views Addons', 'abnet-wp-post-views-addons'),
				__('ABNet Post Views Addons', 'abnet-wp-post-views-addons'),
				'manage_options',
				self::PAGE_SLUG,
				array($this, 'renderToolsPage')
			);
		}

		public function renderToolsPage(): void {
			if (!$this->_isAuthorized()) {
				$this->_notAuthorizedErrorAndDie();
			}

			$status = isset($_GET['status']) 
				? sanitize_key((string) $_GET['status']) 
				: '';

			$activeTab = isset($_GET['tab']) 
				? sanitize_key((string) $_GET['tab']) 
				: self::TAB_UPDATE;
			
			if (!in_array($activeTab, array(self::TAB_UPDATE, self::TAB_BOTS_JSON), true)) {
				$activeTab = self::TAB_UPDATE;
			}

			$actionUrl = admin_url('admin-post.php');
			$actionName = self::ACTION_UPDATE_BOTS;
			
			$nonceAction = self::NONCE_ACTION;
			$nonceName = self::NONCE_NAME;
			
			$lastSuccessfulUpdateMessage = $this->_getLastSuccessfulUpdateMessage();
			
			$tabs = $this->_getTabs();
			$botsJsonSections = $this->_getPresentableBotsJsonSections();

			$this->_view->render('tools-page.php', array(
				'status' => $status,
				'activeTab' => $activeTab,
				'actionUrl' => $actionUrl,
				'actionName' => $actionName,
				'nonceAction' => $nonceAction,
				'nonceName' => $nonceName,
				'lastSuccessfulUpdateMessage' => $lastSuccessfulUpdateMessage,
				'tabs' => $tabs,
				'botsJsonSections' => $botsJsonSections,
			));
		}

		private function _isAuthorized(): bool {
			return current_user_can('manage_options');
		}

		public function handleUpdateBotsAction(): void {
			if (!$this->_isAuthorized()) {
				$this->_notAuthorizedErrorAndDie();
			}

			check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);

			$useLastDownloadedFile = $this->_shouldUseLastDownloadedFile();
			$status = $this->_getUpdater($useLastDownloadedFile)
				->updateFromAiRobotsTxt();

			if ($this->_isSuccessfulUpdateStatus($status)) {
				$this->_logSuccessfulUpdate();
			}

			wp_safe_redirect($this->_redirectToAfterBotUpdateUrl($status));
			exit;
		}

		private function _redirectToAfterBotUpdateUrl(string $status): string {
			return add_query_arg(
				array(
					'page' => self::PAGE_SLUG,
					'status' => $status,
				),
				admin_url('tools.php')
			);
		}

		private function _shouldUseLastDownloadedFile(): bool {
			return isset( $_POST['use_last_downloaded_file'] ) 
				&& (string) $_POST['use_last_downloaded_file'] === '1';
		}

		private function _notAuthorizedErrorAndDie(): void {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'abnet-wp-post-views-addons' ) );
		}

		private function _isSuccessfulUpdateStatus( string $status ): bool {
			return $status === RefererPatternUpdater::STATUS_UPDATED
				|| $status === RefererPatternUpdater::STATUS_NO_CHANGES;
		}

		/**
		 * @return array<string, string>
		 */
		private function _getTabs(): array {
			return array(
				self::TAB_UPDATE => __('Update', 'abnet-wp-post-views-addons'),
				self::TAB_BOTS_JSON => __('Current bots.json', 'abnet-wp-post-views-addons'),
			);
		}

		/**
		 * @return array<int, array<string, mixed>>
		 */
		private function _getPresentableBotsJsonSections(): array {
			$decoded = abnet_wp_post_views_addons_read_bots_json();
			if (!is_array( $decoded)) {
				return array();
			}

			$sections = array();
			foreach ($decoded as $sectionKey => $sectionData) {
				$sectionTitle = is_string($sectionKey) ? $sectionKey : (string)$sectionKey;
				$sections[] = array(
					'key' => $sectionTitle,
					'title' => ucfirst($sectionTitle),
					'rows' => $this->_normalizeBotFileSectionRowsForPresentation( $sectionData ),
				);
			}

			return $sections;
		}

		/**
		 * @param mixed $sectionData
		 * @return array<int, string>
		 */
		private function _normalizeBotFileSectionRowsForPresentation($sectionData): array {
			$rows = array();

			if (is_array($sectionData)) {
				foreach ($sectionData as $item) {
					if (is_string( $item)) {
						$rows[] = trim($item);
						continue;
					}

					if ( is_scalar( $item ) || null === $item ) {
						$rows[] = (string) $item;
						continue;
					}

					$encoded = wp_json_encode($item, JSON_UNESCAPED_SLASHES 
						| JSON_UNESCAPED_UNICODE);

					$rows[] = is_string($encoded) 
						? $encoded 
						: __('[unserializable value]', 'abnet-wp-post-views-addons');
				}
			} elseif (is_scalar($sectionData) || null === $sectionData) {
				$rows[] = (string) $sectionData;
			}

			$rows = array_values(
				array_filter(
					$rows,
					static function (string $value): bool {
						return '' !== trim( $value );
					}
				)
			);

			return $rows;
		}

		private function _logSuccessfulUpdate(): void {
			$currentUser = wp_get_current_user();
			$userDisplayName = '';

			if ($currentUser instanceof \WP_User) {
				$userDisplayName = (string) $currentUser->display_name;
				if ( '' === $userDisplayName ) {
					$userDisplayName = (string)$currentUser->user_login;
				}
			}

			update_option(
				self::LAST_SUCCESSFUL_UPDATE_OPTION,
				array(
					'timestamp_gmt' => current_time( 'timestamp', true ),
					'user_id' => get_current_user_id(),
					'user_display_name' => $userDisplayName,
				),
				false
			);
		}

		private function _getLastSuccessfulUpdateMessage(): string {
			$data = get_option(self::LAST_SUCCESSFUL_UPDATE_OPTION);
			if (!is_array($data)) {
				return '';
			}

			$timestamp = isset($data['timestamp_gmt']) ? (int)$data['timestamp_gmt'] : 0;
			if ($timestamp <= 0) {
				return '';
			}

			$userDisplayName = isset($data['user_display_name']) 
				? trim( (string)$data['user_display_name']) 
				: '';

			if ('' === $userDisplayName && isset( $data['user_id'])) {
				$user = get_user_by('id', (int)$data['user_id']);
				if ($user instanceof \WP_User) {
					$userDisplayName = (string)$user->display_name;
				}
			}

			if ('' === $userDisplayName) {
				$userDisplayName = __('Unknown user', 'abnet-wp-post-views-addons');
			}

			$formattedDate = wp_date(
				get_option('date_format' ) . ' ' . get_option('time_format'),
				$timestamp
			);

			$lastUpdateMessage = sprintf(
				/* translators: 1: date/time, 2: username */
				__('Last successfully updated on %1$s by %2$s.', 'abnet-wp-post-views-addons'),
				$formattedDate,
				$userDisplayName
			);

			/**
			 * Filters the message shown on the tools page describing when the bots list was last successfully updated.
			 *
			 * @param string $lastUpdateMessage The default formatted message, e.g. "Last successfully updated on <date> by <user>.".
			 * @param string $userDisplayName Display name of the user who performed the last update.
			 * @param string $formattedDate Date/time of the last update, formatted according to the site's date and time format settings.
			 * @return string The filtered message string.
			 */
			$lastUpdateMessage = apply_filters('abnet_wpv_addons_last_update_message',
				$lastUpdateMessage,
				$userDisplayName,
				$formattedDate);

			return $lastUpdateMessage;
		}

		/**
		 * @param bool $useLastDownloadedFile
		 * @return RefererPatternUpdater
		 */
		private function _getUpdater(bool $useLastDownloadedFile): RefererPatternUpdater {
			$this->_updater = new RefererPatternUpdater(
				$useLastDownloadedFile
			);

			return $this->_updater;
		}
	}
}
