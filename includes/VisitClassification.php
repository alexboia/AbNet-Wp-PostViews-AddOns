<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class VisitClassification {
		public const string WELL_KNOWN_BOT = 'well_known_bot';

		public const string HONEY_TRAPPED_BOT = 'honey_trapped_bot';

		public const string FUZZY_BOT = 'fuzzy_bot';

		public const string DEBUG_BOT = 'debug_bot';

		public const string BOT = 'bot';

		public const string FILTERED_REFERER = 'filtered_referer';

		public static function classify(VisitContext $context): string|null {
			$classification = null;
			if ($context->isBot()) {
				if ($context->maybeWellKnownBot()) {
					$classification = self::WELL_KNOWN_BOT;
				} else if ($context->isPreviouslyHoneyTrappedBot()) {
					$classification = self::HONEY_TRAPPED_BOT;
				} else if ($context->getFuzzyBotScore() > 0) {
					$classification = self::FUZZY_BOT;
				} else if ($context->isDebugBot()) {
					$classification = self::DEBUG_BOT;
				} else {
					$classification = self::BOT;
				}
			} else if ($context->isFilteredReferer()) {
				$classification = self::FILTERED_REFERER;
			}

			return $classification;
		}
	}
}