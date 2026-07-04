<?php
declare(strict_types=1);

if (!defined( 'ABSPATH')) {
	exit;
}

final class ABNet_WP_Post_Views_Addons_VisitInfoCollector {
	private $_currentVisitInfo = null;

	public function getCurrentVisitInfo(): ABNet_WP_Post_Views_Addons_VisitInfo {
		if ($this->_currentVisitInfo === null) {
			$context = ABNet_WP_Post_Views_Addons_VisitContext::getInstance();
			$this->_currentVisitInfo = $this->collectVisitInfo($context);
		}
		
		return $this->_currentVisitInfo;
	}

	public function collectVisitInfo(ABNet_WP_Post_Views_Addons_VisitContext $context): ABNet_WP_Post_Views_Addons_VisitInfo {
		$data = array();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_VISIT_ID] = 0;

		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_POST_ID] = 
			$context->getCurrentPostId();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_URL] = 
			$this->_getCurrentUrlRelative();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_IP_ADDRESS] =
			$this->_getIpAddress();

		$referer = $this->_getReferer();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_REFERER] = $referer;
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_REFERER_IS_LOCAL] = 
			$this->_refererIsLocal($referer);
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_REQUEST_METHOD] =
			$this->_getRequestMethod();

		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_USER_AGENT] =
			$this->_getUserAgent();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_COOKIES] =
			$this->_getCookies();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_PROTOCOL] =
			$this->_getProtocol();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_LANGUAGE] =
			$this->_getAcceptLanguage();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_ACCEPT_CONTENT] =
			$this->_getAcceptContent();

		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_CF_IP_COUNTRY] =
			$this->_getCfIpCountry();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_CF_IP_AS_NAME] =
			$this->_getCfIpAsName();

		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_IS_BOT] = 
			$context->isBot();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_IS_FILTERED_REFERER] = 
			$context->isFilteredReferer();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_IS_SPAM_REFERER] = 
			$context->isSpamReferer();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_WAS_COUNTED] = 
			$context->wasCounted();

		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_SEARCH_TERM] =
			$this->_determineInboundSearchTerm();
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_UTM_SOURCE] =
			$this->_getRequestParamString('utm_source');
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_UTM_MEDIUM] =
			$this->_getRequestParamString('utm_medium');
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_UTM_CAMPAIGN] =
			$this->_getRequestParamString('utm_campaign');
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_UTM_TERM] =
			$this->_getRequestParamString('utm_term');
		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_UTM_CONTENT] =
			$this->_getRequestParamString('utm_content');

		$data[ABNet_WP_Post_Views_Addons_VisitInfo::KEY_TIMESTAMP] =
			gmdate('Y-m-d H:i:s');
		
		return new ABNet_WP_Post_Views_Addons_VisitInfo($data);
	}

	private function _getCookies(): string|null {
		if (empty($_SERVER['HTTP_COOKIE'])) {
			return null;
		}

		$cookies = sanitize_text_field((string)$_SERVER['HTTP_COOKIE']);
		$cookies = trim($cookies);

		return !empty($cookies)
			? $cookies
			: null;
	}

	private function _getIpAddress(): string|null {
		if (empty($_SERVER['REMOTE_ADDR'])) {
			return null;
		}

		$ipAddress = sanitize_text_field((string)$_SERVER['REMOTE_ADDR']);
		$ipAddress = trim($ipAddress);

		return !empty($ipAddress)
			? $ipAddress
			: null;
	}

	private function _getUserAgent(): string|null {
		return !empty($_SERVER['HTTP_USER_AGENT']) 
			? sanitize_text_field((string) $_SERVER['HTTP_USER_AGENT'])
			: null;
	}

	private function _determineInboundSearchTerm(): string|null {
		$referer = $this->_getReferer();
		if (!empty($referer) && !$this->_refererIsLocal($referer)) {
			$query = $this->_parseUrl($referer, PHP_URL_QUERY);
			if (is_string($query) && $query !== '') {
				$searchTerm = $this->_extractSearchTermFromQuery($query);
				if (!empty($searchTerm)) {
					return $searchTerm;
				}
			}
		}

		$searchTerm = $this->_getRequestParamString('s');
		if (!empty($searchTerm)) {
			return $searchTerm;
		}

		$searchTerm = $this->_getRequestParamString('q');
		if (!empty($searchTerm)) {
			return $searchTerm;
		}

		return $this->_getRequestParamString('query');
	}

	private function _extractSearchTermFromQuery(string $query): string|null {
		$params = array();
		if (function_exists('wp_parse_str')) {
			wp_parse_str($query, $params);
		} else {
			parse_str($query, $params);
		}

		if (!is_array($params) || empty($params)) {
			return null;
		}

		$searchKeys = array('q', 'query', 'p', 'text', 'wd', 'keyword', 'search', 'k', 'kw', 'qry');
		foreach ($searchKeys as $key) {
			if (!array_key_exists($key, $params)) {
				continue;
			}

			$value = $params[$key];
			if (is_array($value)) {
				continue;
			}

			$value = sanitize_text_field((string)$value);
			$value = trim($value);
			if ($value !== '') {
				return $value;
			}
		}

		return null;
	}

	private function _getRequestParamString(string $key): string|null {
		if (empty($_GET[$key])) {
			return null;
		}

		$value = $_GET[$key];
		if (is_array($value)) {
			return null;
		}

		$value = sanitize_text_field((string)$value);
		$value = trim($value);

		return !empty($value) 
		? $value 
		: null;
	}

	private function _getCurrentUrlRelative(): string {
		/**
		 * @var \WP $wp
		 */
		global $wp;
		if (!empty($wp) && $wp instanceof \WP) {
			$url = $wp->request;
		} else {
			$url = $this->_getCurrentUrlRelativeFailover();
		}

		return sanitize_text_field($url);
	}

	private function _getCurrentUrlRelativeFailover(): string {
		// Stage 1: read the raw request URI when available.
		$requestUri = !empty($_SERVER['REQUEST_URI'])
			? (string)$_SERVER['REQUEST_URI']
			: '';

		// Stage 2: fallback to PHP_SELF + QUERY_STRING when REQUEST_URI is missing.
		if (empty($requestUri)) {
			$path = isset($_SERVER['PHP_SELF'])
				? (string)$_SERVER['PHP_SELF']
				: '/';

			$query = isset($_SERVER['QUERY_STRING'])
				? trim((string)$_SERVER['QUERY_STRING'])
				: '';

			if ($path === '') {
				$path = '/';
			}

			if ($path[0] !== '/') {
				$path = '/' . $path;
			}

			return !empty($query)
				? $path . '?' . $query 
				: $path;
		}

		// Stage 3: parse request URI into path/query components.
		$parsedPath = $this->_parseUrl($requestUri, PHP_URL_PATH);
		$parsedQuery = $this->_parseUrl($requestUri, PHP_URL_QUERY);

		$path = is_string($parsedPath) && $parsedPath !== ''
			? $parsedPath
			: $requestUri;

		// Stage 4: recover path/query manually for malformed or partial values.
		if (empty($path) && str_contains($requestUri, '?')) {
			$parts = explode('?', $requestUri, 2);
			$path = !empty($parts[0]) ? $parts[0] : '/';
			if (!is_string($parsedQuery) || empty($parsedQuery)) {
				$parsedQuery = $parts[1] ?? '';
			}
		}

		// Stage 5: normalize output as a relative URL with optional query string.
		if (!is_string($path) || $path === '') {
			$path = '/';
		}

		if ($path[0] !== '/') {
			$path = '/' . ltrim($path, '/');
		}

		$query = is_string($parsedQuery) 
			? trim($parsedQuery) 
			: '';

		return $query !== '' 
			? $path . '?' . $query 
			: $path;
	}

	private function _getReferer(): string|null {
		$referer = false;
		if (function_exists('wp_get_referer')) {
			$referer = wp_get_referer();
		} 

		if (empty($referer)) {
			$referer = isset($_SERVER['HTTP_REFERER']) 
				? $_SERVER['HTTP_REFERER'] 
				: null;
		}

		return sanitize_text_field($referer);
	}

	private function _refererIsLocal(string|null $referer = null): bool {
		$referer = !empty($referer) 
			? $referer 
			: $this->_getReferer();

		if (empty($referer)) {
			return false;
		}

		$refererHost = $this->_parseUrl($referer, PHP_URL_HOST);
		if (!is_string($refererHost) || empty($refererHost)) {
			return str_starts_with($referer, '/');
		}

		$siteUrl = function_exists('home_url')
			? home_url('/')
			: (function_exists('site_url') ? site_url('/') : '');

		$siteHost = is_string($siteUrl)
			? $this->_parseUrl($siteUrl, PHP_URL_HOST)
			: null;

		if (!is_string($siteHost) || empty($siteHost)) {
			return false;
		}

		$refererHost = strtolower(ltrim(trim($refererHost), '.'));
		$siteHost = strtolower(ltrim(trim($siteHost), '.'));

		if ($refererHost === $siteHost) {
			return true;
		}

		$siteSuffix = '.' . $siteHost;
		return substr($refererHost, -strlen($siteSuffix)) 
			=== $siteSuffix;
	}

	private function _parseUrl(string $url, int $component) {
		return function_exists('wp_parse_url')
			? wp_parse_url($url, $component)
			: parse_url($url, $component);
	}

	private function _getRequestMethod(): string {
		return !empty($_SERVER['REQUEST_METHOD']) 
			? strtoupper(sanitize_text_field($_SERVER['REQUEST_METHOD']) )
			: 'GET';
	}

	private function _getProtocol(): string {
		$protocol = 'HTTP';
		if (!empty($_SERVER['SERVER_PROTOCOL'])) {
			$protocol = strtoupper($_SERVER['SERVER_PROTOCOL']);
		}

		if (empty($protocol) && !empty($_SERVER['HTTPS'])) {
			$protocol = 'HTTPS';
		}

		return sanitize_text_field($protocol);
	}

	private function _getAcceptLanguage(): string|null {
		if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return null;
		}

		$acceptLanguage = sanitize_text_field((string)$_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$acceptLanguage = trim($acceptLanguage);
		if (empty($acceptLanguage)) {
			return null;
		}

		$parts = explode(',', $acceptLanguage, 2);
		$firstLanguageRange = !empty($parts[0]) ? trim($parts[0]) : null;
		if (empty($firstLanguageRange)) {
			return null;
		}

		$parts = explode(';', $firstLanguageRange, 2);
		$language = !empty($parts[0]) ? trim($parts[0]) : null;

		return !empty($language)
			? $language
			: null;
	}

	private function _getAcceptContent(): string|null {
		return !empty($_SERVER['HTTP_ACCEPT'])
			? sanitize_text_field($_SERVER['HTTP_ACCEPT'])
			: null;
	}

	private function _getCfIpCountry(): string|null {
		return !empty($_SERVER['HTTP_CF_IPCOUNTRY'])
			? sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY'])
			: null;
	}

	private function _getCfIpAsName(): string|null {
		return !empty($_SERVER['HTTP_CF_IPAS_NAME'])
			? sanitize_text_field($_SERVER['HTTP_CF_IPAS_NAME'])
			: null;
	}
}