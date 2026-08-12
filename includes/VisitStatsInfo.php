<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final readonly class VisitStatsInfo {
		public const string KEY_KEY = 'stats_key';

		public const string KEY_LABEL = 'stats_label';

		public const string KEY_VALUE = 'stats_value';

		public const string KEY_CATEGORY = 'stats_category';

		public function __construct(public string $key, 
			public string $label,
			public float $value, 
			public string|null $category = null) {
			return;
		}

		public static function fromArray(array $data): self {
			return new self(
				(string)($data[self::KEY_KEY] ?? ''),
				(string)($data[self::KEY_LABEL] ?? ''),
				(float)($data[self::KEY_VALUE] ?? 0),
				isset($data[self::KEY_CATEGORY])
					? (string)$data[self::KEY_CATEGORY]
					: null
			);
		}

		public function toArray(): array {
			return array(
				self::KEY_KEY => $this->key,
				self::KEY_LABEL => $this->label,
				self::KEY_VALUE => $this->value,
				self::KEY_CATEGORY => $this->category
			);
		}

		public function hasCategory(): bool {
			return !empty($this->category);
		}
	}
}
