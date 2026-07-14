<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {

	if (!defined('ABSPATH')) {
		exit;
	}

	final class SignatureHelper {
		public static function buildSignatureOptions(): VisitSignatureOptions {
			$key = self::getSignatureKey();
			return new VisitSignatureOptions(array(
				VisitSignatureOptions::KEY_KEY => $key,
			));
		}

		public static function getSignatureKey(): string {
			$sigKeyManager = new SignatureKeyManager();
			return $sigKeyManager->getSignatureKey();
		}
	}
}