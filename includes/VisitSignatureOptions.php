<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class VisitSignatureOptions {
		public const string KEY_FLAGS = 'flags';

		public const string KEY_KEY = 'key';

		public const string FLAG_USE_IP_ADDRESS = 'use_ip_address';

		public const string FLAG_USE_REFERER = 'use_referer';

		public const string FLAG_USE_USER_AGENT = 'use_user_agent';

		public const string FLAG_USE_REQUEST_METHOD = 'use_request_method';

		public const string FLAG_USE_PROTOCOL = 'use_protocol';

		public const string FLAG_USE_ACCEPT = 'use_accept';

		public const string FLAG_USE_LANGUAGE = 'use_language';

		//IP Address and referer are for future-pondering only, not currently supported
		private bool $_useIpAddress = false;

		private bool $_useReferer = false;

		private bool $_useUserAgent = true;

		private bool $_useRequestMethod = true;

		private bool $_useProtocol = true;

		private bool $_useAccept = true;

		private bool $_useLanguage = true;

		private string $_key;

		public function __construct(array $options = array()) {
			$flags = isset($options[self::KEY_FLAGS]) && is_array($options[self::KEY_FLAGS])
				? $options[self::KEY_FLAGS]
				: array();

			$this->_useIpAddress = $this->_getFlag($flags, 
				self::FLAG_USE_IP_ADDRESS, 
				$this->_useIpAddress);

			$this->_useReferer = $this->_getFlag($flags, 
				self::FLAG_USE_REFERER, 
				$this->_useReferer);
			
			$this->_useUserAgent = $this->_getFlag($flags, 
				self::FLAG_USE_USER_AGENT, 
				$this->_useUserAgent);
			
			$this->_useRequestMethod = $this->_getFlag($flags, 
				self::FLAG_USE_REQUEST_METHOD, 
				$this->_useRequestMethod);
			
			$this->_useProtocol = $this->_getFlag($flags, 
				self::FLAG_USE_PROTOCOL, 
				$this->_useProtocol);
			
			$this->_useAccept = $this->_getFlag($flags, 
				self::FLAG_USE_ACCEPT, 
				$this->_useAccept);
			
			$this->_useLanguage = $this->_getFlag($flags, 
				self::FLAG_USE_LANGUAGE, 
				$this->_useLanguage);

			$this->_key = isset($options[self::KEY_KEY])
				? (string)$options[self::KEY_KEY]
				: '';
		}

		/**
		 * @param array<string, mixed> $flags
		 */
		private function _getFlag(array $flags, string $flagKey, bool $defaultValue): bool {
			if (!array_key_exists($flagKey, $flags)) {
				return $defaultValue;
			}

			return (bool)$flags[$flagKey];
		}

		public function getUseIpAddress(): bool {
			return $this->_useIpAddress;
		}

		public function getUseReferer(): bool {
			return $this->_useReferer;
		}

		public function getUseUserAgent(): bool {
			return $this->_useUserAgent;
		}

		public function getUseRequestMethod(): bool {
			return $this->_useRequestMethod;
		}

		public function getUseProtocol(): bool {
			return $this->_useProtocol;
		}

		public function getUseAccept(): bool {
			return $this->_useAccept;
		}

		public function getUseLanguage(): bool {
			return $this->_useLanguage;
		}

		public function getKey(): string {
			return $this->_key;
		}
	}
}