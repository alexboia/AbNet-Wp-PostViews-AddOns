<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class HoneyTrappedBot {
		public const string KEY_TRAPPED_BOT_ID = 'trapped_bot_id';

		public const string KEY_IP_ADDRESS = 'ip_address';

		public const string KEY_REFERER = 'referer';

		public const string KEY_USER_AGENT = 'user_agent';

		public const string KEY_REQUEST_METHOD = 'request_method';

		public const string KEY_ACCEPT_CONTENT = 'accept_content';

		public const string KEY_PROTOCOL = 'protocol';

		public const string KEY_LANGUAGE = 'language';

		public const string KEY_ALL_SIGNALS = 'all_signals';

		public const string KEY_BOT_SIGNATURE = 'bot_signature';

		public const string KEY_SIGNATURE_PARTS = 'signature_parts';

		public const string KEY_TRAP_URL = 'trap_url';

		public const string KEY_TRAP_SLUG = 'trap_slug';

		public const string KEY_TRAP_IDENTIFIER = 'trap_identifier';

		public const string KEY_TRAP_TYPE = 'trap_type';

		public const string KEY_TRAPPED_AT = 'trapped_at';

		private int $_trappedBotId;

		private string $_ipAddress;

		private string|null $_referer;

		private string|null $_userAgent;

		private string $_requestMethod;

		private string|null $_acceptContent;

		private string $_protocol;

		private string $_language;

		private array $_allSignals = array();

		//Does NOT include IP Address
		private string $_botSignature;

		private array $_signatureParts;

		private string $_trapUrl;

		private string $_trapSlug;

		private int|null $_trapIdentifier;

		private string $_trapType;

		private string $_trappedAt;

		public function __construct(array $data) {
			$this->_trappedBotId = isset($data[self::KEY_TRAPPED_BOT_ID])
				? (int)$data[self::KEY_TRAPPED_BOT_ID]
				: 0;

			$this->_ipAddress = (string)($data[self::KEY_IP_ADDRESS] ?? '');
			$this->_referer = isset($data[self::KEY_REFERER])
				? (string)$data[self::KEY_REFERER]
				: null;
			$this->_userAgent = isset($data[self::KEY_USER_AGENT])
				? (string)$data[self::KEY_USER_AGENT]
				: null;
			$this->_requestMethod = (string)($data[self::KEY_REQUEST_METHOD] ?? '');
			$this->_acceptContent = isset($data[self::KEY_ACCEPT_CONTENT])
				? (string)$data[self::KEY_ACCEPT_CONTENT]
				: null;
			$this->_protocol = (string)($data[self::KEY_PROTOCOL] ?? '');
			$this->_language = (string)($data[self::KEY_LANGUAGE] ?? '');
			$this->_allSignals = isset($data[self::KEY_ALL_SIGNALS]) && is_array($data[self::KEY_ALL_SIGNALS])
				? $data[self::KEY_ALL_SIGNALS]
				: array();
			$this->_botSignature = (string)($data[self::KEY_BOT_SIGNATURE] ?? '');
			$this->_signatureParts = isset($data[self::KEY_SIGNATURE_PARTS]) && is_array($data[self::KEY_SIGNATURE_PARTS])
				? $data[self::KEY_SIGNATURE_PARTS]
				: array();
			$this->_trapUrl = (string)($data[self::KEY_TRAP_URL] ?? '');
			$this->_trapSlug = (string)($data[self::KEY_TRAP_SLUG] ?? '');
			$this->_trapIdentifier = isset($data[self::KEY_TRAP_IDENTIFIER])
				? (int)$data[self::KEY_TRAP_IDENTIFIER]
				: 0;
			$this->_trapType = (string)($data[self::KEY_TRAP_TYPE] ?? '');
			$this->_trappedAt = (string)($data[self::KEY_TRAPPED_AT] ?? '');
		}

		public static function fromDbArray(array $data): self {
			if (is_string($data[self::KEY_SIGNATURE_PARTS])) {
				$signatureParts = json_decode($data[self::KEY_SIGNATURE_PARTS], true);
				if (is_array($signatureParts)) {
					$data[self::KEY_SIGNATURE_PARTS] = $signatureParts;
				} else {
					$data[self::KEY_SIGNATURE_PARTS] = array();
				}
			}


			if (is_string($data[self::KEY_ALL_SIGNALS])) {
				$signalsParts = json_decode($data[self::KEY_ALL_SIGNALS], true);
				if (is_array($signalsParts)) {
					$data[self::KEY_ALL_SIGNALS] = $signalsParts;
				} else {
					$data[self::KEY_ALL_SIGNALS] = array();
				}
			}

			return self::fromArray($data);
		}

		public static function fromArray(array $data): self {
			return new self($data);
		}

		public function toArray(): array {
			return array(
				self::KEY_TRAPPED_BOT_ID => $this->_trappedBotId,
				self::KEY_IP_ADDRESS => $this->_ipAddress,
				self::KEY_REFERER => $this->_referer,
				self::KEY_USER_AGENT => $this->_userAgent,
				self::KEY_REQUEST_METHOD => $this->_requestMethod,
				self::KEY_ACCEPT_CONTENT => $this->_acceptContent,
				self::KEY_PROTOCOL => $this->_protocol,
				self::KEY_LANGUAGE => $this->_language,
				self::KEY_ALL_SIGNALS => $this->_allSignals,
				self::KEY_BOT_SIGNATURE => $this->_botSignature,
				self::KEY_SIGNATURE_PARTS => $this->_signatureParts,
				self::KEY_TRAP_URL => $this->_trapUrl,
				self::KEY_TRAP_SLUG => $this->_trapSlug,
				self::KEY_TRAP_IDENTIFIER => $this->_trapIdentifier,
				self::KEY_TRAP_TYPE => $this->_trapType,
				self::KEY_TRAPPED_AT => $this->_trappedAt,
			);
		}

		public function toDbArray(): array {
			$data = $this->toArray();
			$data[self::KEY_SIGNATURE_PARTS] = json_encode($data[self::KEY_SIGNATURE_PARTS]);
			$data[self::KEY_ALL_SIGNALS] = json_encode($data[self::KEY_ALL_SIGNALS]);
			return $data;
		}

		public function getTrappedBotId(): int {
			return $this->_trappedBotId;
		}

		public function getIpAddress(): string {
			return $this->_ipAddress;
		}

		public function getReferer(): string|null {
			return $this->_referer;
		}

		public function getUserAgent(): string|null {
			return $this->_userAgent;
		}

		public function getRequestMethod(): string {
			return $this->_requestMethod;
		}

		public function getAcceptContent(): string|null {
			return $this->_acceptContent;
		}

		public function getProtocol(): string {
			return $this->_protocol;
		}

		public function getLanguage(): string {
			return $this->_language;
		}

		/**
		 * @return array<string, mixed>
		 */
		public function getAllSignals(): array {
			return $this->_allSignals;
		}

		public function getBotSignature(): string {
			return $this->_botSignature;
		}

		/**
		 * @return string[]
		 */
		public function getSignatureParts(): array {
			return $this->_signatureParts;
		}

		public function getTrapUrl(): string {
			return $this->_trapUrl;
		}

		public function getTrapSlug(): string {
			return $this->_trapSlug;
		}

		public function getTrapIdentifier(): int {
			return $this->_trapIdentifier;
		}

		public function getTrapType(): string {
			return $this->_trapType;
		}

		public function getTrappedAt(): string {
			return $this->_trappedAt;
		}
	}
}