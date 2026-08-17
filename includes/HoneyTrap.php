<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

    use stdClass;
    use WP_Post;

	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class HoneyTrap {
		private Datasource $_dataSource;
		private View $_view;
		
		public function __construct(Datasource|null $dataSource = null, View|null $view = null) {
			$this->_dataSource = $dataSource ?? new Datasource();
			$this->_view = $view ?? new View();
		}

		public function init(): void {
			if (!self::enabled()) {
				return;
			}

			//Hooker order is:
			//	1. init
			//	2. query_vars
			//	3. template_redirect

			add_action('init', function() {
				self::_registerRules();
			});

			add_filter('query_vars', function ($vars) {
				if (!is_array($vars)) {
					$vars = array();
				}

				$vars[] = 'abnet_honeytrap';
				$vars[] = 'abnet_honeytrap_id';

				return $vars;
			});

			add_action('template_redirect', function() {
				$slug = get_query_var('abnet_honeytrap');
				$id = intval(get_query_var('abnet_honeytrap_id', 0));

				if (!empty($slug)) {
					$descriptor = new HoneyTrapDescriptor($slug, $id);
					$this->_trap($descriptor);
				}
			});

			add_action('wp_footer', function() {
				$this->_layTrap();
			});
		}

		private function _layTrap(): void {
			$data = new stdClass();
			$data->honeyTrapBaseUrl = home_url('/wpabnet/system-information');
			$data->postId = $this->_getPostId();

			$this->_view->render('honey-trap.php', array('data' => $data));
		}

		private function _getPostId(): int {
			if (!is_page() && !is_single()) {
				return 0;
			}

			$post = isset($GLOBALS['post']) 
				? $GLOBALS['post'] 
				: null;

			if ($post instanceof WP_Post) {
				return $post->ID;
			} else {
				return 0;
			}
		}

		private function _trap(HoneyTrapDescriptor $descriptor) {
			$visitInfo = $this->_collectVisitInfo();
			$trappedBot = $this->_buildTrappedBotInfo($descriptor, 
				$visitInfo);

			$this->_dataSource->saveHoneyTrappedBot($trappedBot);
			$this->_renderHeadFakingHoneyTrapContents();
		}

		private function _renderHeadFakingHoneyTrapContents(): void {
			status_header(200);
			header('Content-Type: text/html');

			$this->_view->render('honey-trap-contents.php');
			exit;
		}

		private function _collectVisitInfo(): VisitInfo {
			$collector = new VisitInfoCollector();
		
			$context = VisitContext::getInstance();
			$context->flagBot();

			return $collector->collectVisitInfo($context);
		}

		private function _buildTrappedBotInfo(HoneyTrapDescriptor $descriptor, VisitInfo $visitInfo): HoneyTrappedBot {
			$signatureOptions = $this->_buildSignatureOptions();
			$signatureCalculator = new VisitSignatureCalculator($signatureOptions);
			$visitSignature = $signatureCalculator->compute($visitInfo);

			$signals = $visitSignature->getSignals();
			$trapSlug = trim($descriptor->getSlug());
			$trapIdentifier = $descriptor->getId();

			$data = array(
				HoneyTrappedBot::KEY_TRAPPED_BOT_ID => 0,
				HoneyTrappedBot::KEY_IP_ADDRESS => $visitInfo->getIpAddress(),
				HoneyTrappedBot::KEY_REFERER => $visitInfo->getReferer(),
				HoneyTrappedBot::KEY_USER_AGENT => $visitInfo->getUserAgent(),
				HoneyTrappedBot::KEY_REQUEST_METHOD => $visitInfo->getRequestMethod(),
				HoneyTrappedBot::KEY_ACCEPT_CONTENT => $visitInfo->getAcceptContent(),
				HoneyTrappedBot::KEY_PROTOCOL => $visitInfo->getProtocol(),
				HoneyTrappedBot::KEY_LANGUAGE => $visitInfo->getLanguage(),
				HoneyTrappedBot::KEY_ALL_SIGNALS => $signals,
				HoneyTrappedBot::KEY_BOT_SIGNATURE => $visitSignature->getSignature(),
				HoneyTrappedBot::KEY_SIGNATURE_PARTS => $visitSignature->getSignals(),
				HoneyTrappedBot::KEY_TRAP_URL => $visitInfo->getUrl(),
				HoneyTrappedBot::KEY_TRAP_SLUG => $trapSlug,
				HoneyTrappedBot::KEY_TRAP_IDENTIFIER => $trapIdentifier > 0 ? $trapIdentifier : null,
				HoneyTrappedBot::KEY_TRAP_TYPE => $this->_determineTrapType($descriptor),
				HoneyTrappedBot::KEY_TRAPPED_AT => $visitInfo->getTimestamp()
			);

			return new HoneyTrappedBot($data);
		}

		private function _buildSignatureOptions(): VisitSignatureOptions {
			return SignatureHelper::buildSignatureOptions();
		}

		private function _determineTrapType(HoneyTrapDescriptor $descriptor): string {
			return 'hidden_anchor';
		}	

		public static function setup(): void {
			if (!self::enabled()) {
				return;
			}

			//Mostly meant to be called on plugin activation
			self::_registerRules();
			self::_ensureRobotsTxtCorrect();

			flush_rewrite_rules();
		}

		public static function cleanup(): void {
			//Mostly meant to be called on plugin de-activation
			flush_rewrite_rules();
		}

		private static function _registerRules() {
			 add_rewrite_rule(
				'^wpabnet/system-information/details/([0-9]+)/?$',
				'index.php?abnet_honeytrap=system-information/details&abnet_honeytrap_id=$matches[1]',
				'top'
			);

			add_rewrite_rule(
				'^wpabnet/system-information/?$',
				'index.php?abnet_honeytrap=system-information',
				'top'
			);
		}

		private static function _ensureRobotsTxtCorrect(): void {
			$robotWriter = new RobotsTxtWriter();
			$robotWriter->disallowUrls($arrayName = [
				'/wpabnet/system-information',
				'/wpabnet/system-information/details/'
			]);
		}

		public static function enabled(): bool {
			return true;
		}
	}
}
