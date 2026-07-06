<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	final class SignatureKeyManager {
		public function getSignatureKey(): string {
			$keyFilePath = ABNET_WP_POST_VIEWS_DATA_DIR
				. DIRECTORY_SEPARATOR
				. 'key.php';

			$existingKey = $this->_readPersistedSignatureKey($keyFilePath);
			if ($existingKey !== null) {
				return $existingKey;
			}

			$generatedKey = $this->_generateSignatureKey();
			$this->_persistSignatureKey($keyFilePath, $generatedKey);

			return $generatedKey;
		}

		private function _readPersistedSignatureKey(string $keyFilePath): string|null {
			if (!is_file($keyFilePath) || !is_readable($keyFilePath)) {
				return null;
			}

			$keyValue = require $keyFilePath;
			if (!is_string($keyValue)) {
				return null;
			}

			$keyValue = trim($keyValue);
			return $keyValue !== ''
				? $keyValue
				: null;
		}

		private function _generateSignatureKey(): string {
			if (defined('AUTH_KEY')) {
				return (string)constant('AUTH_KEY');
			}

			if (defined('SECURE_AUTH_KEY')) {
				return (string)constant('SECURE_AUTH_KEY');
			}

			if (function_exists('wp_salt')) {
				return (string)wp_salt('auth');
			}

			if (function_exists('wp_generate_password')) {
				return wp_generate_password(64, true, true);
			}

			if (function_exists('random_bytes')) {
				return bin2hex(random_bytes(32));
			}

			return sha1(ABNET_WP_POST_VIEWS_VERSION . ':' . microtime(true));
		}

		private function _persistSignatureKey(string $keyFilePath, string $key): void {
			$directoryPath = dirname($keyFilePath);
			if (!is_dir($directoryPath)) {
				if (function_exists('wp_mkdir_p')) {
					wp_mkdir_p($directoryPath);
				} else {
					@mkdir($directoryPath, 0755, true);
				}
			}

			$keyExport = var_export($key, true);
			$fileContents = "<?php\n"
				. "if (!defined('ABSPATH')) {\n"
				. "    exit;\n"
				. "}\n\n"
				. 'return ' . $keyExport . ";\n";

			$result = @file_put_contents($keyFilePath, $fileContents, LOCK_EX);
			if ($result === false) {
				abnet_write_log(sprintf('Could not write honey trap key file at %s.', $keyFilePath));
			}
		}
	}
}