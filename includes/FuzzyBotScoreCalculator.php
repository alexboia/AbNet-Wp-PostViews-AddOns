<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class FuzzyBotScoreCalculator {
		public function calculate(VisitInfo $visitInfo): FuzzyBotScore {
			$score = 0;
			$signals = array();

			if ($visitInfo->isBot()) {
				$score = 100;
				$signals['in_bot_list'] = 100;
				return new FuzzyBotScore($score, $signals);
			}

			$userAgentScore = $this->_scoreUserAgent($visitInfo->getUserAgent());
			if ($userAgentScore !== 0) {
				$signals['user_agent'] = $userAgentScore;
				$score += $userAgentScore;
			}

			$acceptScore = $this->_scoreAcceptContent($visitInfo->getAcceptContent());
			if ($acceptScore !== 0) {
				$signals['accept_content'] = $acceptScore;
				$score += $acceptScore;
			}

			if (empty($visitInfo->getLanguage())) {
				$signals['missing_accept_language'] = 10;
				$score += 10;
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

			return new FuzzyBotScore($score, $signals);

		}

		private function _scoreUserAgent(string|null $userAgent): int {
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
        	if (preg_match('/Chrome\/([0-9]+)\./i', $normalizedUserAgent, $matches)) {
				$chromeVersion = (int) $matches[1];
				if ($chromeVersion < 70) {
					return 45;
				}

				if ($chromeVersion < 100) {
					return 25;
				}

				if ($chromeVersion > 190) {
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

			if ($visitInfo->isRefererLocal()) {
				return -10;
			}

			if (!empty($visitInfo->getSearchTerm())) {
				return -10;
			}

			return 0;
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
			if (!empty($cfIpCountry) && !preg_match('/^[A-Z]{2}$/', $cfIpCountry)) {
				$score += 10;
			}

			if ($cfIpCountry === 'RO') {
				$score -= 5;
			}
			
			$networkName = trim(implode(' ', array_filter(array(
				$visitInfo->getCfIpAsName(),
				$visitInfo->getComputedNetworkName(),
				$visitInfo->getComputedAsn(),
			))));

			if (empty($networkName)) {
				return $score;
			}

			$normalizedNetworkName = strtolower($networkName);
			$suspiciousNetworkTokens = array(
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
				'digitalocean' => 12,
				'hetzner' => 12,
				'ovh' => 12,
				'linode' => 12,
				'akamai' => 8,
				'tencent' => 12,
				'alibaba' => 12,
				'oracle cloud' => 12,
				'leaseweb' => 12,
				'contabo' => 12,
			);

			foreach ($suspiciousNetworkTokens as $token => $weight) {
				if (str_contains($normalizedNetworkName, $token)) {
					$score += $weight;
					break;
				}
			}

			return $score;
		}
	}
}
