<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class VisitSignature {
		private string $_signature;

		private array $_signals = array();

		public function __construct(string $signature, array $signals){
			$this->_signals = $signals;
			$this->_signature = $signature;
		}

		public function getSignals(): array {
			return $this->_signals;
		}

		public function getSignature(): string {
			return $this->_signature;
		}
	}
}