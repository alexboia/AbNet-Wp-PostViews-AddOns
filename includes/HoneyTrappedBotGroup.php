<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final readonly class HoneyTrappedBotGroup {
		public const string KEY_BOT_SIGNATURE = HoneyTrappedBot::KEY_BOT_SIGNATURE;

		public const string KEY_TRAP_TYPE = HoneyTrappedBot::KEY_TRAP_TYPE;

		public const string KEY_BOT_COUNT = 'bot_count';

		public function __construct(public string $botSignature,
			public string $trapType, 
			public int $botCount){
			return;
		}

		public static function fromArray(array $data): self {
			return new self(
				(string)($data[self::KEY_BOT_SIGNATURE] ?? ''),
				(string)($data[self::KEY_TRAP_TYPE] ?? ''),
				(int)($data[self::KEY_BOT_COUNT] ?? 0)
			);
		}

		public static function fromDbArray(array $data): self {
			return self::fromArray($data);
		}

		public function toArray(): array {
			return [
				self::KEY_BOT_SIGNATURE => $this->botSignature,
				self::KEY_TRAP_TYPE => $this->trapType,
				self::KEY_BOT_COUNT => $this->botCount,
			];
		}
	}
}
