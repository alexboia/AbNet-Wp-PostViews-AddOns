<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class VisitSignatureCalculator {
		private VisitSignatureOptions $_options;

		public function __construct(VisitSignatureOptions $options){
			$this->_options = $options;
		}

		public function compute(VisitInfo $visitInfo): VisitSignature {
			$signals = array();
			$signatureParts = array();

			$this->_appendSignal(
				$signals,
				$signatureParts,
				VisitSignatureOptions::FLAG_USE_IP_ADDRESS,
				$visitInfo->getIpAddress(),
				$this->_options->getUseIpAddress()
			);

			$this->_appendSignal(
				$signals,
				$signatureParts,
				VisitSignatureOptions::FLAG_USE_REFERER,
				$visitInfo->getReferer(),
				$this->_options->getUseReferer()
			);

			$this->_appendSignal(
				$signals,
				$signatureParts,
				VisitSignatureOptions::FLAG_USE_USER_AGENT,
				$visitInfo->getUserAgent(),
				$this->_options->getUseUserAgent()
			);

			$this->_appendSignal(
				$signals,
				$signatureParts,
				VisitSignatureOptions::FLAG_USE_REQUEST_METHOD,
				$visitInfo->getRequestMethod(),
				$this->_options->getUseRequestMethod()
			);

			$this->_appendSignal(
				$signals,
				$signatureParts,
				VisitSignatureOptions::FLAG_USE_PROTOCOL,
				$visitInfo->getProtocol(),
				$this->_options->getUseProtocol()
			);

			$this->_appendSignal(
				$signals,
				$signatureParts,
				VisitSignatureOptions::FLAG_USE_ACCEPT,
				$visitInfo->getAcceptContent(),
				$this->_options->getUseAccept()
			);

			$this->_appendSignal(
				$signals,
				$signatureParts,
				VisitSignatureOptions::FLAG_USE_LANGUAGE,
				$visitInfo->getLanguage(),
				$this->_options->getUseLanguage()
			);

			$key = $this->_options->getKey();
			$payload = implode(";", $signatureParts);

			$signature = hash_hmac('sha256', $payload, $key);

			return new VisitSignature($signature, 
				$signals);
		}

		/**
		 * @param array<string, mixed> $signals
		 * @param string[] $signatureParts
		 */
		private function _appendSignal(array &$signals, array &$signatureParts,
			string $signalName,
			string|null $signalValue, 
			bool $enabled): void {

			if (!$enabled) {
				return;
			}

			$normalizedValue = $this->_normalizeSignalValue($signalValue);
			$signals[$signalName] = $normalizedValue;
			$signatureParts[] = $signalName . '=' . $normalizedValue;
		}

		private function _normalizeSignalValue(string|null $signalValue): string {
			return trim((string)$signalValue);
		}
	}
}