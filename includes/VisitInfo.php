<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class VisitInfo {
		public const string KEY_VISIT_ID = 'visit_id';

		public const string KEY_POST_ID = 'post_id';

		public const string KEY_URL = 'visit_url';

		public const string KEY_IP_ADDRESS = 'ip_address';

		public const string KEY_REFERER = 'referer';

		public const string KEY_REFERER_IS_LOCAL = 'referer_is_local';

		public const string KEY_USER_AGENT = 'user_agent';

		public const string KEY_TIMESTAMP = 'visit_timestamp';

		public const string KEY_REQUEST_METHOD = 'request_method';

		public const string KEY_PROTOCOL = 'protocol';

		public const string KEY_COOKIES = 'cookies';

		public const string KEY_LANGUAGE = 'visit_language';

		public const string KEY_IS_BOT = 'is_bot';

		public const string KEY_IS_FILTERED_REFERER = 'is_filtered_referer';

		public const string KEY_IS_SPAM_REFERER = 'is_spam_referer';

		public const string KEY_SEARCH_TERM = 'search_term';

		public const string KEY_UTM_SOURCE = 'utm_source';

		public const string KEY_UTM_MEDIUM = 'utm_medium';

		public const string KEY_UTM_CAMPAIGN = 'utm_campaign';

		public const string KEY_UTM_TERM = 'utm_term';

		public const string KEY_UTM_CONTENT = 'utm_content';

		public const string KEY_ACCEPT_CONTENT = 'accept_content';

		public const string KEY_CF_IP_COUNTRY = 'cf_ip_country';

		public const string KEY_CF_IP_AS_NAME = 'cf_ip_as_name';

		public const string KEY_COMPUTED_COUNTRY = 'computed_country';

		public const string KEY_COMPUTED_ASN = 'computed_asn';

		public const string KEY_COMPUTED_NETWORK_NAME = 'computed_network_name';

		public const string KEY_FUZZY_BOT_SCORE = 'fuzzy_bot_score';

		public const string KEY_WAS_PROCESSED = 'was_processed';

		public const string KEY_WAS_DISCARDED = 'was_discarded';

		public const string KEY_WAS_COUNTED = 'was_counted';

		public const string KEY_CLASSIFICATION = 'classification';

		public const string KEY_TRAPPED_BOT_SIGNATURE = 'trapped_bot_signature';

		public const string KEY_DEBUG_BOT = 'is_debug_bot';

		private int|null $_visitId;

		//private string $_visitThumbPrint;

		private int|null $_postId;

		//private int|null $_userId;

		private string $_url;

		private string $_ipAddress;

		private string|null $_referer;

		private bool $_refererIsLocal;

		private string|null $_userAgent;
		
		private string $_timestamp;

		private string $_requestMethod;

		private string $_protocol;

		private string $_cookies;

		private string $_language;

		private bool $_isBot;

		private bool $_isFilteredReferer;

		private bool $_isSpamReferer;

		private string|null $_searchTerm;

		private string|null $_utmSource;

		private string|null $_utmMedium;

		private string|null $_utmCampaign;

		private string|null $_utmTerm;

		private string|null $_utmContent;

		private string|null $_acceptContent;

		//private string|null $_xForwardedFor;

		private string|null $_cfIpCountry;

		private string|null $_cfIpAsName;

		//private string|null $_cfRay;

		private string|null $_computedCountry;

		private string|null $_computedCity;

		private string|null $_computedAsn;

		private string|null $_computedNetworkName;

		private int $_fuzzyBotScore;

		private bool $_wasCounted;

		private bool $_wasProcessed;

		private bool $_wasDiscarded;

		//private bool $_challengeShown;

		//private int $_challengeReason;

		//private string|null $_challengeToken;

		//private int|null $_challengeFinalAuthId;

		//private string|null $_challengeUnlockedAt;

		//private string|null $_challengeTokenExpiresAt;

		private string|null $_trappedBotSignature;

		private string|null $_classification;

		private bool $_isDebugBot;

		public function __construct(array $info){
			$this->_visitId = !empty($info[self::KEY_VISIT_ID]) 
				? (int)$info[self::KEY_VISIT_ID] 
				: null;

			$this->_postId = !empty($info[self::KEY_POST_ID]) 
				? (int)$info[self::KEY_POST_ID] 
				: null;

			$this->_url = (string)($info[self::KEY_URL] ?? '');
			$this->_ipAddress = (string)($info[self::KEY_IP_ADDRESS] ?? '');
			
			$this->_referer = !empty($info[self::KEY_REFERER]) 
				? (string)$info[self::KEY_REFERER] 
				: null;
			
			$this->_refererIsLocal = (bool)($info[self::KEY_REFERER_IS_LOCAL] ?? false);

			$this->_userAgent = !empty($info[self::KEY_USER_AGENT]) 
				? (string)$info[self::KEY_USER_AGENT] 
				: null;
			
			$this->_timestamp = (string)($info[self::KEY_TIMESTAMP] ?? '');
			$this->_requestMethod = (string)($info[self::KEY_REQUEST_METHOD] ?? '');
			$this->_protocol = (string)($info[self::KEY_PROTOCOL] ?? '');
			$this->_cookies = (string)($info[self::KEY_COOKIES] ?? '');
			$this->_language = (string)($info[self::KEY_LANGUAGE] ?? '');
			$this->_isBot = (bool)($info[self::KEY_IS_BOT] ?? false);
			$this->_isFilteredReferer = (bool)($info[self::KEY_IS_FILTERED_REFERER] ?? false);
			$this->_isSpamReferer = (bool)($info[self::KEY_IS_SPAM_REFERER] ?? false);

			$this->_searchTerm = !empty($info[self::KEY_SEARCH_TERM])
				? (string)$info[self::KEY_SEARCH_TERM]
				: null;

			$this->_utmSource = !empty($info[self::KEY_UTM_SOURCE])
				? (string)$info[self::KEY_UTM_SOURCE]
				: null;

			$this->_utmMedium = !empty($info[self::KEY_UTM_MEDIUM])
				? (string)$info[self::KEY_UTM_MEDIUM]
				: null;

			$this->_utmCampaign = !empty($info[self::KEY_UTM_CAMPAIGN])
				? (string)$info[self::KEY_UTM_CAMPAIGN]
				: null;
	
			$this->_utmTerm = !empty($info[self::KEY_UTM_TERM])
				? (string)$info[self::KEY_UTM_TERM]
				: null;

			$this->_utmContent = !empty($info[self::KEY_UTM_CONTENT])
				? (string)$info[self::KEY_UTM_CONTENT]
				: null;

			$this->_acceptContent = !empty($info[self::KEY_ACCEPT_CONTENT])
				? (string)$info[self::KEY_ACCEPT_CONTENT]
				: null;

			$this->_cfIpCountry = !empty($info[self::KEY_CF_IP_COUNTRY])
				? (string)$info[self::KEY_CF_IP_COUNTRY]
				: null;

			$this->_cfIpAsName = !empty($info[self::KEY_CF_IP_AS_NAME])
				? (string)$info[self::KEY_CF_IP_AS_NAME]
				: null;

			$this->_computedCountry = !empty($info[self::KEY_COMPUTED_COUNTRY])
				? (string)$info[self::KEY_COMPUTED_COUNTRY]
				: null;

			$this->_computedAsn = !empty($info[self::KEY_COMPUTED_ASN])
				? (string)$info[self::KEY_COMPUTED_ASN]
				: null;

			$this->_computedNetworkName = !empty($info[self::KEY_COMPUTED_NETWORK_NAME])
				? (string)$info[self::KEY_COMPUTED_NETWORK_NAME]
				: null;

			$this->_fuzzyBotScore = !empty($info[self::KEY_FUZZY_BOT_SCORE])
				? (int)$info[self::KEY_FUZZY_BOT_SCORE]
				: 0;

			$this->_wasCounted =(bool)($info[self::KEY_WAS_COUNTED] ?? false);
			$this->_wasProcessed = (bool)($info[self::KEY_WAS_PROCESSED] ?? false);
			$this->_wasDiscarded = (bool)($info[self::KEY_WAS_DISCARDED] ?? false);

			$this->_classification = !empty($info[self::KEY_CLASSIFICATION])
				? (string)$info[self::KEY_CLASSIFICATION]
				: null;

			$this->_trappedBotSignature = !empty($info[self::KEY_TRAPPED_BOT_SIGNATURE])
				? (string)$info[self::KEY_TRAPPED_BOT_SIGNATURE]
				: null;

			$this->_isDebugBot = (bool)($info[self::KEY_DEBUG_BOT] ?? false);
		}

		public static function fromDbArray(array $data): self {
			$data[self::KEY_REFERER_IS_LOCAL] = !empty($data[self::KEY_REFERER_IS_LOCAL]) 
				&& intval($data[self::KEY_REFERER_IS_LOCAL]) === 1;
			$data[self::KEY_IS_BOT] = !empty($data[self::KEY_IS_BOT]) 
				&& intval($data[self::KEY_IS_BOT]) === 1;
			$data[self::KEY_IS_FILTERED_REFERER] = !empty($data[self::KEY_IS_FILTERED_REFERER])
				&& intval($data[self::KEY_IS_FILTERED_REFERER]) === 1;
			$data[self::KEY_IS_SPAM_REFERER] = !empty($data[self::KEY_IS_SPAM_REFERER])
				&& intval($data[self::KEY_IS_SPAM_REFERER]) === 1;
			$data[self::KEY_WAS_COUNTED] = !empty($data[self::KEY_WAS_COUNTED])
				&& intval($data[self::KEY_WAS_COUNTED]) === 1;
			$data[self::KEY_WAS_PROCESSED] = !empty($data[self::KEY_WAS_PROCESSED])
				&& intval($data[self::KEY_WAS_PROCESSED]) === 1;
			$data[self::KEY_WAS_DISCARDED] = !empty($data[self::KEY_WAS_DISCARDED])
				&& intval($data[self::KEY_WAS_DISCARDED]) === 1;
			$data[self::KEY_DEBUG_BOT] = !empty($data[self::KEY_DEBUG_BOT])
				&& intval($data[self::KEY_DEBUG_BOT]) === 1;

			return new self($data);
		}

		public function toArray(): array {
			return [
				self::KEY_VISIT_ID => $this->_visitId,
				self::KEY_POST_ID => $this->_postId,
				self::KEY_URL => $this->_url,
				self::KEY_IP_ADDRESS => $this->_ipAddress,
				self::KEY_REFERER => $this->_referer,
				self::KEY_REFERER_IS_LOCAL => $this->_refererIsLocal,
				self::KEY_USER_AGENT => $this->_userAgent,
				self::KEY_TIMESTAMP => $this->_timestamp,
				self::KEY_REQUEST_METHOD => $this->_requestMethod,
				self::KEY_PROTOCOL => $this->_protocol,
				self::KEY_COOKIES => $this->_cookies,
				self::KEY_LANGUAGE => $this->_language,
				self::KEY_IS_BOT => $this->_isBot,
				self::KEY_IS_FILTERED_REFERER => $this->_isFilteredReferer,
				self::KEY_IS_SPAM_REFERER => $this->_isSpamReferer,
				self::KEY_SEARCH_TERM => $this->_searchTerm,
				self::KEY_UTM_SOURCE => $this->_utmSource,
				self::KEY_UTM_MEDIUM => $this->_utmMedium,
				self::KEY_UTM_CAMPAIGN => $this->_utmCampaign,
				self::KEY_UTM_TERM => $this->_utmTerm,
				self::KEY_UTM_CONTENT => $this->_utmContent,
				self::KEY_ACCEPT_CONTENT => $this->_acceptContent,
				self::KEY_CF_IP_COUNTRY => $this->_cfIpCountry,
				self::KEY_CF_IP_AS_NAME => $this->_cfIpAsName,
				self::KEY_COMPUTED_COUNTRY => $this->_computedCountry,
				self::KEY_COMPUTED_ASN => $this->_computedAsn,
				self::KEY_COMPUTED_NETWORK_NAME => $this->_computedNetworkName,
				self::KEY_FUZZY_BOT_SCORE => $this->_fuzzyBotScore,
				self::KEY_WAS_COUNTED => $this->_wasCounted,
				self::KEY_WAS_PROCESSED => $this->_wasProcessed,
				self::KEY_WAS_DISCARDED => $this->_wasDiscarded,
				self::KEY_CLASSIFICATION => $this->_classification,
				self::KEY_TRAPPED_BOT_SIGNATURE => $this->_trappedBotSignature,
				self::KEY_DEBUG_BOT => $this->_isDebugBot
			];
		}

		public function toDbArray(): array {
			$data = $this->toArray();

			$data[self::KEY_REFERER_IS_LOCAL] = $data[self::KEY_REFERER_IS_LOCAL] ? 1 : 0;
			$data[self::KEY_IS_BOT] = $data[self::KEY_IS_BOT] ? 1 : 0;
			$data[self::KEY_IS_FILTERED_REFERER] = $data[self::KEY_IS_FILTERED_REFERER] ? 1 : 0;
			$data[self::KEY_IS_SPAM_REFERER] = $data[self::KEY_IS_SPAM_REFERER] ? 1 : 0;
			$data[self::KEY_WAS_COUNTED] = $data[self::KEY_WAS_COUNTED] ? 1 : 0;
			$data[self::KEY_WAS_PROCESSED] = $data[self::KEY_WAS_PROCESSED] ? 1 : 0;
			$data[self::KEY_WAS_DISCARDED] = $data[self::KEY_WAS_DISCARDED] ? 1 : 0;
			$data[self::KEY_DEBUG_BOT] = $data[self::KEY_DEBUG_BOT] ? 1 : 0;

			return $data;
		}

		public function updateFromVisitContext(VisitContext $context): VisitInfo {
			$data = $this->toArray();

			$data[self::KEY_POST_ID] = $context->getCurrentPostId();
			$data[self::KEY_IS_BOT] = $context->isBot();
			$data[self::KEY_IS_FILTERED_REFERER] = $context->isFilteredReferer();
			$data[self::KEY_IS_SPAM_REFERER] = $context->isSpamReferer();
			$data[self::KEY_WAS_COUNTED] = $context->wasCounted();
			$data[self::KEY_FUZZY_BOT_SCORE] = $context->getFuzzyBotScore();

			$data[self::KEY_CLASSIFICATION] = VisitClassification::classify($context);
			$data[self::KEY_TRAPPED_BOT_SIGNATURE] = $context->getPreviouslyHoneyTrappedBotSignature();
			$data[self::KEY_DEBUG_BOT] = $context->isDebugBot();

			return new self($data);
		}

		public function getVisitId(): int|null {
			return $this->_visitId;
		}

		public function getPostId(): int|null {
			return $this->_postId;
		}

		public function getUrl(): string {
			return $this->_url;
		}

		public function getIpAddress(): string {
			return $this->_ipAddress;
		}

		public function getReferer(): string|null {
			return $this->_referer;
		}

		public function isRefererLocal(): bool {
			return $this->_refererIsLocal;
		}

		public function getUserAgent(): string|null {
			return $this->_userAgent;
		}

		public function getTimestamp(): string {
			return $this->_timestamp;
		}

		public function getRequestMethod(): string {
			return $this->_requestMethod;
		}

		public function getProtocol(): string {
			return $this->_protocol;
		}

		public function getCookies(): string {
			return $this->_cookies;
		}

		public function getLanguage(): string {
			return $this->_language;
		}

		public function isBot(): bool {
			return $this->_isBot;
		}

		public function isFilteredReferer(): bool {
			return $this->_isFilteredReferer;
		}

		public function isSpamReferer(): bool {
			return $this->_isSpamReferer;
		}

		public function getSearchTerm(): string|null {
			return $this->_searchTerm;
		}

		public function getUtmSource(): string|null {
			return $this->_utmSource;
		}

		public function getUtmMedium(): string|null {
			return $this->_utmMedium;
		}

		public function getUtmCampaign(): string|null {
			return $this->_utmCampaign;
		}

		public function getUtmTerm(): string|null {
			return $this->_utmTerm;
		}

		public function getUtmContent(): string|null {
			return $this->_utmContent;
		}

		public function wasCounted(): bool {
			return $this->_wasCounted;
		}

		public function wasProcessed(): bool {
			return $this->_wasProcessed;
		}

		public function wasDiscarded(): bool {
			return $this->_wasDiscarded;
		}

		public function getAcceptContent(): string|null {
			return $this->_acceptContent;
		}

		public function getCfIpCountry(): string|null {
			return $this->_cfIpCountry;
		}

		public function getCfIpAsName(): string|null {
			return $this->_cfIpAsName;
		}

		public function getComputedCountry(): string|null {
			return $this->_computedCountry;
		}

		public function getComputedAsn(): string|null {
			return $this->_computedAsn;
		}

		public function getComputedNetworkName(): string|null {
			return $this->_computedNetworkName;
		}

		public function getFuzzyBotScore(): int {
			return $this->_fuzzyBotScore;
		}

		public function getTrappedBotSignature(): ?string {
			return $this->_trappedBotSignature;
		}

		public function getClassification() : string {
			return $this->_classification;
		}

		public function isDebugBot(): bool {
			return $this->_isDebugBot;
		}
	}
}
