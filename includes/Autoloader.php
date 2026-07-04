<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	class Autoloader {
		private static bool $registered = false;
		private static array $prefixMapping = [];

		/**
		 * Note: prefix mapping iteration order in the autoloader matters when multiple
		 * 	prefixes overlap. More specific prefixes should be registered first to ensure
		 * 	correct class resolution without ambiguity.
		 * 
		 * @param array $prefixMapping 
		 */
		public static function init(array $prefixMapping) {
			self::$prefixMapping = self::normalizePrefixMapping($prefixMapping);

			if (self::$registered) {
				return;
			}

			spl_autoload_register([self::class, 'autoloadClass']);
			self::$registered = true;
		}

		private static function normalizePrefixMapping(array $prefixMapping): array {
			$normalized = [];
			foreach ($prefixMapping as $prefix => $baseDir) {
				$prefix = trim((string) $prefix);
				$baseDir = rtrim((string) $baseDir, '/\\') . DIRECTORY_SEPARATOR;
				if ($prefix === '' || $baseDir === DIRECTORY_SEPARATOR) {
					continue;
				}

				$normalizedPrefix = rtrim($prefix, '\\') . '\\';
				$normalized[$normalizedPrefix] = $baseDir;
			}

			return $normalized;
		}

		private static function autoloadClass(string $className): void {
			foreach (self::$prefixMapping as $prefix => $baseDir) {
				if (strpos($className, $prefix) !== 0) {
					continue;
				}

				$relativeClassName = substr($className, strlen($prefix));
				if ($relativeClassName === false || $relativeClassName === '') {
					continue;
				}

				$filePath = $baseDir
					. str_replace('\\', DIRECTORY_SEPARATOR, $relativeClassName)
					. '.php';

				if (!is_file($filePath)) {
					continue;
				}

				require_once $filePath;
				return;
			}
		}
	}
}