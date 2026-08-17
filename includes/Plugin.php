<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class Plugin {
		private static Plugin|null $_instance = null;

		private ToolsIntegration|null $_toolsIntegration = null;

		private Datasource $_dataSource;

		private HoneyTrap $_honeyTrap;

		private VisitStatsInfoManager $_visitStatsInfoManager;

		private HoneyTrapManager $_honeyTrapManager;

		private View $_view;

		/**
		 * @return Plugin
		 */
		public static function getInstance(): Plugin {
			if ( null === self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public static function activate() {
			Db::setup();
			HoneyTrap::setup();
		}

		private function __construct() {
			$this->_dataSource = new Datasource();
			$this->_view = new View();
			$this->_honeyTrap = new HoneyTrap($this->_dataSource, $this->_view);
			$this->_visitStatsInfoManager = new VisitStatsInfoManager(null, $this->_view);
			$this->_honeyTrapManager = new HoneyTrapManager($this->_dataSource, $this->_view);
		}

		public function run(): void {
			add_action('admin_enqueue_scripts',
				array($this, 'enqueueAdminCommonAssets'));

			add_action('init',
				array($this, 'onWpInit'), 
				10);

			add_filter( 'postviews_should_count', 
				array($this, 'filterPostviewsShouldCount'), 
				20, 
				2 );

			add_filter( 'wp_postviews_should_count', 
				array($this, 'filterPostviewsShouldCount'), 
				20, 
				2 );

			add_filter('comments_open', 
				array($this, 'disableCommentsIfBots'), 
				20, 
				2);

			$this->_getToolsIntegration()->init();
			$this->_getHoneyTrap()->init();
			$this->_visitStatsInfoManager->init();
			$this->_honeyTrapManager->init();
		}

		public function enqueueAdminCommonAssets(): void {
			wp_enqueue_style(
				'abnet-wp-post-views-admin-common',
				plugins_url('assets/css/admin-common.css', ABNET_WP_POST_VIEWS_PLUGIN_FILE),
				array(),
				ABNET_WP_POST_VIEWS_VERSION
			);
		}

		public function onWpInit(): void {
			$this->_migrateTablesIfVersionDiffers();
		}

		public function disableCommentsIfBots(bool $commentsOpen, int $postId): bool {
			if (!$commentsOpen) {
				return $commentsOpen;
			}

			$context = VisitContext::getInstance();

			return !$context->isBot() 
				&& !$context->isFilteredReferer() 
				&& !$context->isSpamReferer();
		}

		private function _migrateTablesIfVersionDiffers() {
			$registered = $this->_getRegisteredVersion();
			if (version_compare($registered, ABNET_WP_POST_VIEWS_VERSION, '!=')) {
				Db::setup();
				$this->_updateRegisteredVersion(ABNET_WP_POST_VIEWS_VERSION);
			}
		}

		private function _getRegisteredVersion(): string|null {
			return get_option('abnet_wp_post_views_addons_version', '1.0');
		}

		private function _updateRegisteredVersion(string $version): void {
			update_option('abnet_wp_post_views_addons_version', $version, true);
		}

		/**
		 * Blocks post views counting if request matches stuff defined in data/bots.json
		 *
		 * @param bool $shouldCount
		 * @param int  $postId
		 * @return bool
		 */
		public function filterPostviewsShouldCount($shouldCount, $postId): bool {	
			$trackingEnabled = $this->_isTrackingEnabled();
			$botExclusionEnabled = $this->_isWpPostviewsBotExclusionEnabled();
			$context = VisitContext::getInstance();

			$countView = $shouldCount;
			$visitInfo = $this->_evaluatePostVisit($context);

			if ($botExclusionEnabled) {
				$countView = $countView 
					&& !$context->isBot() 
					&& !$context->isFilteredReferer() 
					&& !$context->isSpamReferer();
			}

			if ($countView) {
				$context->flagCounted();
			}

			if ($trackingEnabled) {
				$this->_processPostVisit($visitInfo);
			}

			return $countView;
		}

		private function _evaluatePostVisit(VisitContext $context): VisitInfo {
			$strictMode = $this->_isStrictMode();
			$postVisitEvaluator = new PostVisitEvaluator($strictMode);
			return $postVisitEvaluator->evaluatePostVisit($context);
		}

		private function _processPostVisit(VisitInfo $visitInfo) {
			try {
				if (get_current_user_id() > 0 && !$visitInfo->isDebugBot()){
					return;
				}
				
				$result = $this->_dataSource->saveVisitInfo($visitInfo);
				if (false === $result) {
					Db::logLastDbError('Failed to save visit info');
				}
			} catch (\Throwable $throwable) {
				abnet_write_throwable($throwable, 'Failed to track post view: %s in %s on line %d');
			}
		}

		/**
		 * Strict mode is active only when ABNET_WP_POST_VIEWS_STRICT exists and is boolean true.
		 */
		private function _isStrictMode(): bool {
			return defined('ABNET_WP_POST_VIEWS_STRICT')
				&& \constant('ABNET_WP_POST_VIEWS_STRICT') === true;
		}

		private function _isTrackingEnabled(): bool {
			return abnet_wp_post_views_addons_tracking_enabled() === true;
		}

		/**
		 * Obey native WP-PostViews "Exclude Bot Views".
		 *
		 * @return bool
		 */
		private function _isWpPostviewsBotExclusionEnabled(): bool {
			return abnet_wp_post_views_addons_bot_exclusion_enabled();
		}

		/**
		 * @return ToolsIntegration
		 */
		private function _getToolsIntegration(): ToolsIntegration {
			if (null === $this->_toolsIntegration) {
				$this->_toolsIntegration = new ToolsIntegration($this->_view);
			}

			return $this->_toolsIntegration;
		}

		private function _getHoneyTrap(): HoneyTrap {
			return $this->_honeyTrap;
		}
	}
}
