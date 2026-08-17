<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class VisitStatsInfoManager {
		private const DASHBOARD_WIDGET_ID = 'abnet-wp-post-views-stats';

		private const POST_META_BOX_ID = 'abnet-wp-post-views-post-stats';

		private VisitStatsInfoDataSource $_dataSource;
		private View $_view;

		public function __construct(VisitStatsInfoDataSource|null $dataSource = null, View|null $view = null) {
			$this->_dataSource = $dataSource ?? new VisitStatsInfoDataSource();
			$this->_view = $view ?? new View();
		}

		public function init(): void {
			add_action('wp_dashboard_setup', 
				array($this, 'registerDashboardWidget'));
			add_action('add_meta_boxes', 
				array($this, 'registerPostMetaBox'));
			add_action('admin_enqueue_scripts', 
				array($this, 'enqueueAdminAssets'));
		}

		public function registerDashboardWidget(): void {
			if (!current_user_can('edit_posts')) {
				return;
			}

			wp_add_dashboard_widget(
				self::DASHBOARD_WIDGET_ID,
				__('Post views: bots vs humans', 'abnet-wp-post-views-addons'),
				array($this, 'renderDashboardWidget')
			);
		}

		public function registerPostMetaBox(string $postType): void {
			if (!post_type_supports($postType, 'editor')) {
				return;
			}

			add_meta_box(
				self::POST_META_BOX_ID,
				__('Post view statistics', 'abnet-wp-post-views-addons'),
				array($this, 'renderPostMetaBox'),
				$postType,
				'normal',
				'default'
			);
		}

		public function enqueueAdminAssets(string $hookSuffix): void {
			if ($hookSuffix !== 'index.php' 
				&& $hookSuffix !== 'post.php' 
				&& $hookSuffix !== 'post-new.php') {
				return;
			}

			wp_enqueue_style(
				'abnet-wp-post-views-admin-stats',
				plugins_url('assets/css/admin-stats.css', ABNET_WP_POST_VIEWS_PLUGIN_FILE),
				array(),
				ABNET_WP_POST_VIEWS_VERSION
			);

			wp_enqueue_script(
				'abnet-wp-post-views-admin-stats',
				plugins_url('assets/js/admin-stats.js', ABNET_WP_POST_VIEWS_PLUGIN_FILE),
				array(),
				ABNET_WP_POST_VIEWS_VERSION,
				true
			);
		}

		public function renderDashboardWidget(): void {
			$stats = $this->_dataSource->getAllTimeBotVsHumanStats();
			$this->_view->render('dashboard-visit-stats.php', 
				array('stats' => $stats));
		}

		public function renderPostMetaBox(\WP_Post $post): void {
			$botStats = $this->_dataSource->getPostStats((int)$post->ID, true);
			$humanStats = $this->_dataSource->getPostStats((int)$post->ID, false);

			$this->_view->render('post-visit-stats.php', array(
				'botStats' => $botStats,
				'humanStats' => $humanStats,
			));
		}

	}
}
