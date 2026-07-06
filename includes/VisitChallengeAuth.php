<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	class VisitChallengeAuth {
		private int $_authId;

		private int $_visitId;

		private string $_authorizedVisitThumbPrint;

		private string $_challengeToken;

		private string $_status;

		private string $_dateGenerated;

		private string $_dateChallengeUnlocked;

		private string $_lastAutoAuthSource;

		private string $_lastAutoAuthAt;

		private int|null $_lastAutoAutVisitId;
	}
}