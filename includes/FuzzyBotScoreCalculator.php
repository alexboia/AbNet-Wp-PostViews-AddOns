<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class FuzzyBotScoreCalculator {
		//Chrome 120 went stable on 2023-12-05;
		//	since then a new major version every ~31.5 days
		private const int CHROME_REFERENCE_VERSION = 120;

		private const string CHROME_REFERENCE_DATE = '2023-12-05';

		private const float CHROME_DAYS_PER_VERSION = 31.5;

		private const int CHROME_STALE_VERSION_DISTANCE = 24;

		private const int CHROME_FUTURE_VERSION_DISTANCE = 8;

		private const array HOSTING_ASN_WEIGHTS = array(
			16509 => 10,	//Amazon (AWS)
			14618 => 10,	//Amazon (AWS)
			396982 => 10,	//Google Cloud
			8075 => 10,		//Microsoft (Azure)
			31898 => 12,	//Oracle Cloud
			24940 => 12,	//Hetzner
			213230 => 12,	//Hetzner Cloud
			16276 => 12,	//OVH
			14061 => 12,	//DigitalOcean
			63949 => 12,	//Linode (Akamai)
			45102 => 12,	//Alibaba Cloud
			132203 => 12,	//Tencent Cloud
			136907 => 12,	//Huawei Cloud
			51167 => 12,	//Contabo
			60781 => 12,	//Leaseweb
			12876 => 12,	//Scaleway
			20473 => 12,	//Vultr (Choopa)
			9009 => 15,		//M247 (VPN/proxy heavy)
			212238 => 15,	//Datacamp (VPN/proxy heavy)
		);

		private const array HOSTING_NETWORK_TOKEN_WEIGHTS = array(
			'datacenter' => 12,
			'data center' => 12,
			'hosting' => 10,
			'vps' => 10,
			'proxy' => 15,
			'vpn' => 12,
			'amazon' => 10,
			'aws' => 10,
			'google cloud' => 10,
			'microsoft azure' => 10,
			'microsoft corp msn' => 10,
			'digitalocean' => 12,
			'hetzner' => 12,
			'ovh' => 12,
			'linode' => 12,
			'akamai' => 8,
			'tencent' => 12,
			'alibaba' => 12,
			'hwclouds' => 12,
			'oracle cloud' => 12,
			'oracle bmc' => 12,
			'leaseweb' => 12,
			'contabo' => 12,
			'scaleway' => 12,
			'vultr' => 12,
			'choopa' => 12,
			'm247' => 15,
			'datacamp' => 15,
		);

		private const array SEARCH_ENGINE_HOSTS = array(
			'google.',
			'bing.com',
			'duckduckgo.com',
			'search.yahoo.com',
			'yandex.',
			'ecosia.org',
			'search.brave.com',
			'startpage.com',
			'qwant.com',
			'kagi.com',
			'baidu.com',
		);

		public function calculate(VisitInfo $visitInfo): FuzzyBotScore {
			$score = 0;
			$signals = array();

			if ($visitInfo->isBot()) {
				$score = 100;
				$signals['in_bot_list'] = 100;
				return new FuzzyBotScore($score, $signals);
			}

			$referenceTime = $this->_getReferenceTime($visitInfo);
			$userAgentScore = $this->_scoreUserAgent($visitInfo->getUserAgent(),
				$referenceTime);
			if ($userAgentScore !== 0) {
				$signals['user_agent'] = $userAgentScore;
				$score += $userAgentScore;
			}

			$browserHeadersScore = $this->_scoreBrowserHeaders($visitInfo);
			if ($browserHeadersScore !== 0) {
				$signals['browser_headers'] = $browserHeadersScore;
				$score += $browserHeadersScore;
			}

			$acceptScore = $this->_scoreAcceptContent($visitInfo->getAcceptContent());
			if ($acceptScore !== 0) {
				$signals['accept_content'] = $acceptScore;
				$score += $acceptScore;
			}

			if (empty($visitInfo->getLanguage())) {
				$signals['missing_accept_language'] = 10;
				$score += 10;
			} else {
				$languageScore = $this->_scoreLanguage($visitInfo);
				if ($languageScore !== 0) {
					$signals['accept_language'] = $languageScore;
					$score += $languageScore;
				}
			}

			$requestMethodScore = $this->_scoreRequestMethod($visitInfo->getRequestMethod());
			if ($requestMethodScore !== 0) {
				$signals['request_method'] = $requestMethodScore;
				$score += $requestMethodScore;
			}

			$protocolScore = $this->_scoreProtocol($visitInfo->getProtocol());
			if ($protocolScore !== 0) {
				$signals['protocol'] = $protocolScore;
				$score += $protocolScore;
			}

			$refererScore = $this->_scoreReferer($visitInfo);
			if ($refererScore !== 0) {
				$signals['referer'] = $refererScore;
				$score += $refererScore;
			}

			if (empty($visitInfo->getCookies()) && $visitInfo->isRefererLocal()) {
				$signals['missing_cookies'] = 1;
				$score += 1;
			}

			$campaignScore = $this->_scoreCampaignHints($visitInfo);
			if ($campaignScore !== 0) {
				$signals['campaign_hints'] = $campaignScore;
				$score += $campaignScore;
			}

			$networkScore = $this->_scoreNetworkHints($visitInfo);
			if ($networkScore !== 0) {
				$signals['network_hints'] = $networkScore;
				$score += $networkScore;
			}

			//A desktop/mobile browser coming from a hosting network
			//	is rarely a human (VPNs aside)
			if ($this->_getHostingNetworkWeight($visitInfo) > 0
				&& $this->_claimsToBeBrowser($visitInfo->getUserAgent())) {
				$signals['browser_from_hosting'] = 20;
				$score += 20;
			}

			return new FuzzyBotScore($score, $signals);

		}

		private function _getReferenceTime(VisitInfo $visitInfo): int {
			//Use the visit time, not the current time,
			//	so that re-processing old visits yields the same score
			$timestamp = $visitInfo->getTimestamp();
			if (!empty($timestamp)) {
				$time = strtotime($timestamp . ' UTC');
				if ($time !== false) {
					return $time;
				}
			}

			return time();
		}

		private function _scoreLanguage(VisitInfo $visitInfo): int {
			//VisitInfoCollector only keeps the first language range
			//	(e.g. "zh-CN" out of "zh-CN,zh;q=0.9,en;q=0.8")
			$language = $visitInfo->getLanguage();
			if (empty($language)) {
				return 0;
			}

			$normalizedLanguage = trim(strtoupper($language));
			if (empty($normalizedLanguage)) {
				return 0;
			}

			$weights = array(
				'ZH-CN' => 15
			);

			foreach ($weights as $w => $score) {
				if (stripos($w, $normalizedLanguage) !== false) {
					return $score;
				}
			}

			return 0;
		}

		private function _scoreUserAgent(string|null $userAgent, int $referenceTime): int {
			if (empty($userAgent)) {
				return 35;
			}

			$normalizedUserAgent = strtolower(trim($userAgent));
			if (empty($normalizedUserAgent)) {
				return 35;
			}

			if (false !== stripos($normalizedUserAgent, 'Macintosh')
				&& $this->_isSafariLike($normalizedUserAgent)) {
				//Structural check. Real modern Safari contains "AppleWebKit" and "Version/"
				if (false === stripos($normalizedUserAgent, 'Version/')) {
					return 35;
				}
			}

			// Check for ancient Chrome/Chromium versions
			//	used by headless tools (e.g., Chrome/87, Chrome/80)
			$chromeVersion = $this->_getChromeMajorVersion($normalizedUserAgent);
			if ($chromeVersion > 0) {
				$currentChromeVersion = $this->_estimateCurrentChromeMajorVersion($referenceTime);

				if ($chromeVersion < 70) {
					return 45;
				}

				if ($chromeVersion < $currentChromeVersion - self::CHROME_STALE_VERSION_DISTANCE) {
					return 25;
				}

				if ($chromeVersion > $currentChromeVersion + self::CHROME_FUTURE_VERSION_DISTANCE) {
					return 25; // future fantasy
				}
			}

			// Catch old abandoned operating systems running desktop masks
			if (str_contains($normalizedUserAgent, 'windows nt 5.1')
				|| str_contains($normalizedUserAgent, 'windows nt 5.2')
				|| str_contains($normalizedUserAgent, 'windows nt 6.0')) {
				return 45;
			}

			if (false !== stripos($normalizedUserAgent, 'Windows NT 6.1')
				|| false !== stripos($normalizedUserAgent, 'Windows NT 6.2')
				|| false !== stripos($normalizedUserAgent, 'Windows NT 6.3')) {
				return 25;
			}

			return 0;
		}

		private function _estimateCurrentChromeMajorVersion(int $referenceTime): int {
			$referenceDate = strtotime(self::CHROME_REFERENCE_DATE . ' 00:00:00 UTC');
			$elapsedDays = max(0, ($referenceTime - $referenceDate) / 86400);

			return self::CHROME_REFERENCE_VERSION
				+ (int)floor($elapsedDays / self::CHROME_DAYS_PER_VERSION);
		}

		private function _getChromeMajorVersion(string $userAgent): int {
			if (preg_match('/Chrome\/([0-9]+)\./i', $userAgent, $matches)) {
				return (int)$matches[1];
			}

			return 0;
		}

		private function _getFirefoxMajorVersion(string $userAgent): int {
			if (preg_match('/Firefox\/([0-9]+)\./i', $userAgent, $matches)) {
				return (int)$matches[1];
			}

			return 0;
		}

		private function _getSafariMajorVersion(string $userAgent): int {
			if ($this->_isSafariLike($userAgent)
				&& preg_match('/Version\/([0-9]+)\./i', $userAgent, $matches)) {
				return (int)$matches[1];
			}

			return 0;
		}

		private function _claimsToBeBrowser(string|null $userAgent): bool {
			if (empty($userAgent)) {
				return false;
			}

			$normalizedUserAgent = strtolower(trim($userAgent));
			return str_starts_with($normalizedUserAgent, 'mozilla/5.0')
				&& (str_contains($normalizedUserAgent, 'chrome/')
					|| str_contains($normalizedUserAgent, 'firefox/')
					|| str_contains($normalizedUserAgent, 'safari/'));
		}

		private function _scoreBrowserHeaders(VisitInfo $visitInfo): int {
			//Modern browsers always send Sec-Fetch-* headers
			//	(Chrome 76+, Firefox 90+, Safari 16.4+), and Chromium 89+
			//	also sends Sec-CH-UA over HTTPS. Many scrapers fake
			//	a browser User-Agent, but not these.
			$userAgent = $visitInfo->getUserAgent();
			if (!$this->_claimsToBeBrowser($userAgent)) {
				return 0;
			}

			$normalizedUserAgent = strtolower(trim($userAgent));
			$chromeVersion = $this->_getChromeMajorVersion($normalizedUserAgent);
			$secChUa = strtolower(trim((string)$visitInfo->getSecChUa()));

			if (!$visitInfo->hasSecFetchHeaders()) {
				if ($chromeVersion >= 90) {
					return 40;
				}

				if ($this->_getFirefoxMajorVersion($normalizedUserAgent) >= 90) {
					return 35;
				}

				if ($this->_getSafariMajorVersion($normalizedUserAgent) >= 17) {
					return 30;
				}

				return 0;
			}

			if (!empty($secChUa)) {
				if (str_contains($secChUa, 'headless')) {
					return 40;
				}

				//User-Agent says Chrome/X, client hints say Chromium Y
				$hintedVersion = $this->_getChromiumVersionFromClientHints($secChUa);
				if ($chromeVersion > 0 && $hintedVersion > 0 && $chromeVersion !== $hintedVersion) {
					return 20;
				}
			}

			return -3;
		}

		private function _getChromiumVersionFromClientHints(string $secChUa): int {
			//e.g. "Chromium";v="141", "Google Chrome";v="141", "Not?A_Brand";v="8"
			if (preg_match('/"chromium";\s*v="([0-9]+)"/i', $secChUa, $matches)) {
				return (int)$matches[1];
			}

			return 0;
		}

		private function _isSafariLike(string $userAgent): bool {
			$userAgent = strtolower($userAgent);
			return str_contains($userAgent, 'safari/')
				&& str_contains($userAgent, 'applewebkit/')
				&& !str_contains($userAgent, 'chrome/')
				&& !str_contains($userAgent, 'chromium/')
				&& !str_contains($userAgent, 'crios/')
				&& !str_contains($userAgent, 'edg/')
				&& !str_contains($userAgent, 'opr/')
				&& !str_contains($userAgent, 'firefox/');
		}

		private function _scoreAcceptContent(string|null $acceptContent): int {
			if (empty($acceptContent)) {
				return 25;
			}

			$normalized = strtolower(trim($acceptContent));
			if (empty($normalized) || $normalized === '*/*') {
				return 25;
			}

			if (!str_contains($normalized, 'text/html')) {
				return 15;
			}

			if (str_contains($normalized, 'application/xhtml+xml')
				|| str_contains($normalized, 'image/avif')
				|| str_contains($normalized, 'image/webp')) {
				return -6;
			}

			return 0;
		}

		private function _scoreRequestMethod(string $requestMethod): int {
			$requestMethod = strtoupper(trim($requestMethod));
			if ($requestMethod === 'GET') {
				return 0;
			}

			if ($requestMethod === 'HEAD') {
				return 10;
			}

			return 15;
		}

		private function _scoreProtocol(string $protocol): int {
			$protocol = strtoupper(trim($protocol));
			if (empty($protocol)) {
				return 5;
			}

			if (str_contains($protocol, 'HTTP/2')
				|| str_contains($protocol, 'HTTP/3')) {
				return -3;
			}

			if (str_contains($protocol, 'HTTP/1.0')) {
				return 8;
			}

			return 0;
		}

		private function _scoreReferer(VisitInfo $visitInfo): int {
			$referer = $visitInfo->getReferer();
			if (empty($referer)) {
				return 5;
			}

			if ($this->_isShortlinkRedirectReferer($visitInfo)) {
				return 15;
			}

			if ($this->_isRefererSameAsTargetUrl($visitInfo)) {
				//Comment submissions redirect back to the post with the post
				//	itself as referer, so don't penalize those
				return $this->_isLikelyCommentRoundTrip($visitInfo)
					? 0
					: 20;
			}

			if ($visitInfo->isRefererLocal()) {
				return -10;
			}

			if (!empty($visitInfo->getSearchTerm())) {
				return -10;
			}

			//Search engines no longer pass the search terms along,
			//	so look at the referer host instead
			if ($this->_isSearchEngineReferer($referer)) {
				return -10;
			}

			return 0;
		}

		private function _isLikelyCommentRoundTrip(VisitInfo $visitInfo): bool {
			$cookies = strtolower($visitInfo->getCookies());
			if (str_contains($cookies, 'comment_author_')
				|| str_contains($cookies, 'wordpress_logged_in_')) {
				return true;
			}

			return $visitInfo->hasSecFetchHeaders()
				&& strtolower((string)$visitInfo->getSecFetchSite()) === 'same-origin';
		}

		private function _isSearchEngineReferer(string $referer): bool {
			$refererHost = parse_url($referer, PHP_URL_HOST);
			if (!is_string($refererHost) || empty($refererHost)) {
				return false;
			}

			$refererHost = strtolower($refererHost);
			foreach (self::SEARCH_ENGINE_HOSTS as $searchEngineHost) {
				$hostMatches = str_ends_with($searchEngineHost, '.')
					//e.g. google. matches www.google.com, google.ro, but not google.example.com
					? preg_match('/(^|\.)' . preg_quote($searchEngineHost, '/') . '[a-z]{2,3}(\.[a-z]{2})?$/', $refererHost) === 1
					: ($refererHost === $searchEngineHost
						|| str_ends_with($refererHost, '.' . $searchEngineHost));

				if ($hostMatches) {
					return true;
				}
			}

			return false;
		}

		private function _isRefererSameAsTargetUrl(VisitInfo $visitInfo): bool {
			//Check for referers such as:
			// - target url: /wpabnet/system-information/
			// - referer: https://alexboia.net/wpabnet/system-information

			$referer = $visitInfo->getReferer();
			if (empty($referer)) {
				return false;
			}

			$targetUrl = $visitInfo->getUrl();
			if (empty($targetUrl)) {
				return false;
			}

			$refererParts = parse_url($referer);
			$targetParts = parse_url($targetUrl);

			$refererPath = isset($refererParts['path'])
				? trim(trim($refererParts['path'], '/'))
				: '';
			$targetPath = isset($targetParts['path'])
				? trim(trim($targetParts['path'], '/'))
				: '';

			return !empty($refererPath)
				&& !empty($targetPath)
				&& strtolower($refererPath) === strtolower($targetPath);
		}

		private function _isShortlinkRedirectReferer(VisitInfo $visitInfo): bool {
			$referer = $visitInfo->getReferer();
			if (empty($referer) || !$visitInfo->isRefererLocal()) {
				return false;
			}

			$parts = parse_url($referer);
			if (empty($parts['query'])) {
				return false;
			}

			$query = array();
			parse_str($parts['query'], $query);

			$postId = (int) $visitInfo->getPostId();
			$queryPostId = !empty($query['p']) ? (int) $query['p'] : 0;

			return $queryPostId > 0 && $queryPostId === $postId;
		}

		private function _scoreCampaignHints(VisitInfo $visitInfo): int {
			$score = 0;

			if (!empty($visitInfo->getUtmSource())) {
				$score -= 3;
			}

			if (!empty($visitInfo->getUtmMedium())) {
				$score -= 3;
			}

			if (!empty($visitInfo->getUtmCampaign())) {
				$score -= 3;
			}

			return $score;
		}

		private function _scoreNetworkHints(VisitInfo $visitInfo): int {
			$score = 0;

			$cfIpCountry = strtoupper(trim((string) $visitInfo->getCfIpCountry()));
			if ($cfIpCountry === 'T1') {
				//Cloudflare's code for Tor exit nodes
				$score += 20;
			} else if ($cfIpCountry === 'XX') {
				//Cloudflare's code for unknown country
				$score += 5;
			} else if (!empty($cfIpCountry) && !preg_match('/^[A-Z]{2}$/', $cfIpCountry)) {
				$score += 10;
			}

			if ($cfIpCountry === 'RO') {
				$score -= 5;
			}

			$score += $this->_getHostingNetworkWeight($visitInfo);
			return $score;
		}

		private function _getHostingNetworkWeight(VisitInfo $visitInfo): int {
			$asn = $this->_parseAsnNumber($visitInfo->getComputedAsn());
			if ($asn > 0 && isset(self::HOSTING_ASN_WEIGHTS[$asn])) {
				return self::HOSTING_ASN_WEIGHTS[$asn];
			}

			$networkName = trim(implode(' ', array_filter(array(
				$visitInfo->getCfIpAsName(),
				$visitInfo->getComputedNetworkName(),
				$visitInfo->getComputedAsn(),
			))));

			if (empty($networkName)) {
				return 0;
			}

			//AS names are usually like GOOGLE-CLOUD-PLATFORM or ORACLE-BMC-31898
			$normalizedNetworkName = strtolower($networkName);
			$normalizedNetworkName = preg_replace('/[\s\-_]+/', ' ', $normalizedNetworkName);

			foreach (self::HOSTING_NETWORK_TOKEN_WEIGHTS as $token => $weight) {
				if (str_contains($normalizedNetworkName, $token)) {
					return $weight;
				}
			}

			return 0;
		}

		private function _parseAsnNumber(string|null $asn): int {
			//Accepts both "24940" and "AS24940"
			if (empty($asn) || !preg_match('/^\s*(?:AS)?([0-9]+)\s*$/i', $asn, $matches)) {
				return 0;
			}

			return (int)$matches[1];
		}
	}
}
