<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined('ABSPATH')) {
		exit;
	}

	final class HoneyTrapManager {
		private const string PAGE_SLUG = 'abnet-honey-trapped-bots';
		private const string AJAX_MASTER = 'abnet_honey_trap_master';
		private const string AJAX_DETAIL = 'abnet_honey_trap_detail';
		private const string AJAX_IP_ADDRESSES = 'abnet_honey_trap_ip_addresses';
		private const string NONCE_ACTION = 'abnet_honey_trap_admin';
		private const int PAGE_SIZE = 30;

		private Datasource $_dataSource;
		private string $_pageHook = '';
		private View $_view;

		public function __construct(Datasource|null $dataSource = null, View|null $view = null) {
			$this->_dataSource = $dataSource ?? new Datasource();
			$this->_view = $view ?? new View();
		}

		public function init(): void {
			add_action('admin_menu', array($this, 'registerAdminPage'));
			add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
			add_action('wp_ajax_' . self::AJAX_MASTER, array($this, 'ajaxMaster'));
			add_action('wp_ajax_' . self::AJAX_DETAIL, array($this, 'ajaxDetail'));
			add_action('wp_ajax_' . self::AJAX_IP_ADDRESSES, array($this, 'ajaxIpAddresses'));
		}

		public function registerAdminPage(): void {
			$this->_pageHook = (string)add_dashboard_page(
				__('Honey trapped bots', 'abnet-wp-post-views-addons'),
				__('Honey trapped bots', 'abnet-wp-post-views-addons'),
				'manage_options',
				self::PAGE_SLUG,
				array($this, 'renderAdminPage')
			);
		}

		public function enqueueAdminAssets(string $hookSuffix): void {
			if ($hookSuffix !== $this->_pageHook) {
				return;
			}

			wp_enqueue_style('abnet-honey-trap-admin',
				plugins_url('assets/css/admin-honey-trap.css', ABNET_WP_POST_VIEWS_PLUGIN_FILE),
				array('abnet-wp-post-views-admin-common'), ABNET_WP_POST_VIEWS_VERSION);

			wp_enqueue_script('abnet-honey-trap-admin',
				plugins_url('assets/js/admin-honey-trap.js', ABNET_WP_POST_VIEWS_PLUGIN_FILE),
				array(), ABNET_WP_POST_VIEWS_VERSION, true);

			wp_localize_script('abnet-honey-trap-admin', 'abnetHoneyTrapAdmin', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce(self::NONCE_ACTION),
				'actions' => array(
					'master' => self::AJAX_MASTER,
					'detail' => self::AJAX_DETAIL,
					'ipAddresses' => self::AJAX_IP_ADDRESSES,
				),
				'errorMessage' => __('The requested data could not be loaded.', 'abnet-wp-post-views-addons'),
			));
		}

		public function renderAdminPage(): void {
			$this->_requireCapability();
			$this->_view->render('honey-trapped-bots-page.php');
		}

		public function ajaxMaster(): void {
			$this->_verifyAjaxRequest();
			$page = $this->_getPageFromRequest();

			//Request one more just so that we know whether we have a next page
			$groups = $this->_dataSource->getHoneyTrappedBotGroups(
				($page - 1) * self::PAGE_SIZE, 
				self::PAGE_SIZE + 1
			);

			$hasNextPage = count($groups) > self::PAGE_SIZE;
			$groups = array_slice($groups, 0, self::PAGE_SIZE);

			$this->_sendView('honey-trapped-bots-master.php', array(
				'groups' => $groups,
				'page' => $page,
				'hasNextPage' => $hasNextPage,
			));
		}

		public function ajaxDetail(): void {
			$this->_verifyAjaxRequest();
			$group = $this->_getGroupFromRequest();
			$page = $this->_getPageFromRequest();
			$bots = $this->_dataSource->getHoneyTrappedBotsByGroup(
				$group, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE + 1);
			$hasNextPage = count($bots) > self::PAGE_SIZE;

			$this->_sendView('honey-trapped-bots-detail.php', array(
				'group' => $group,
				'bots' => array_slice($bots, 0, self::PAGE_SIZE),
				'page' => $page,
				'hasNextPage' => $hasNextPage,
			));
		}

		public function ajaxIpAddresses(): void {
			$this->_verifyAjaxRequest();
			$signature = $this->_getSignatureFromRequest();
			$this->_sendView('honey-trapped-bots-ip-addresses.php', array(
				'signature' => $signature,
				'ipAddresses' => $this->_dataSource->getHoneyTrappedIpAddressesBySignature($signature),
			));
		}

		private function _verifyAjaxRequest(): void {
			$this->_requireCapability();
			check_ajax_referer(self::NONCE_ACTION, 'nonce');
		}

		private function _requireCapability(): void {
			if (!current_user_can('manage_options')) {
				wp_die(
					esc_html__('You are not allowed to access this page.', 'abnet-wp-post-views-addons'),
					'',
					array('response' => 403)
				);
			}
		}

		private function _getPageFromRequest(): int {
			return max(1, isset($_POST['page']) 
				? absint($_POST['page']) 
				: 1);
		}

		private function _getSignatureFromRequest(): string {
			$signature = isset($_POST['signature'])
				? sanitize_text_field(wp_unslash((string)$_POST['signature'])) 
				: '';

			if (empty($signature)) {
				wp_send_json_error(array(
					'message' => __('A bot signature is required.', 'abnet-wp-post-views-addons'),
				), 400);
			}

			return $signature;
		}

		private function _getGroupFromRequest(): HoneyTrappedBotGroup {
			$trapType = isset($_POST['trapType'])
				? sanitize_text_field(wp_unslash((string)$_POST['trapType'])) 
				: '';

			if (empty($trapType)) {
				wp_send_json_error(array(
					'message' => __('A trap type is required.', 'abnet-wp-post-views-addons'),
				), 400);
			}

			return new HoneyTrappedBotGroup(
				$this->_getSignatureFromRequest(), 
				$trapType, 
				0
			);
		}

		private function _sendView(string $fileName, array $variables): void {
			wp_send_json_success(array(
				'html' => $this->_view->renderToString($fileName, $variables)
			));
		}
	}
}
