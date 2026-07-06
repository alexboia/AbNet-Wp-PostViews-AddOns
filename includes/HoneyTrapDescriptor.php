<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class HoneyTrapDescriptor {
		private string $_slug;

		private int $_id;

		public function __construct(string $slug, int $id){
			$this->_slug = $slug;
			$this->_id = $id;
		}

		public function getSlug(): string {
			return $this->_slug;
		}

		public function getId(): int {
			return $this->_id;
		}
	}
}