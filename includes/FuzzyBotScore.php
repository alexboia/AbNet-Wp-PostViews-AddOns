<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	class FuzzyBotScore {
		private int $_score;

		private array $_signals;

		public function __construct(int $score, array $signals){
			$this->_score = $this->_normalizeScore($score);
			$this->_signals = $signals;
		}

		public function getScore(): int {
			return $this->_score;
		}

		public function getSignals(): array {
			return $this->_signals;
		}

		private function _normalizeScore(int $score): int {
			return min(100, max(0, $score));
		}
	}
}