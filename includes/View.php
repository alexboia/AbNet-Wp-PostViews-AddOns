<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	use InvalidArgumentException;
	use Throwable;

	if (!defined('ABSPATH')) {
		exit;
	}

	final readonly class View {
		private string $_baseDirectory;

		public function __construct(string|null $baseDirectory = null) {
			$baseDirectory = $baseDirectory ?? ABNET_WP_POST_VIEWS_VIEWS_FILES_DIR;
			$this->_baseDirectory = rtrim($baseDirectory, '/\\') . DIRECTORY_SEPARATOR;
		}

		public function render(string $fileName, array $variables = array()): void {
			$viewPath = $this->_resolvePath($fileName);
			$view = $this;
			extract($variables, EXTR_SKIP);
			require $viewPath;
		}

		public function renderToString(string $fileName, array $variables = array()): string {
			$initialBufferLevel = ob_get_level();
			ob_start();

			try {
				$this->render($fileName, $variables);
				return (string)ob_get_clean();
			} catch (Throwable $throwable) {
				while (ob_get_level() > $initialBufferLevel) {
					ob_end_clean();
				}
				throw $throwable;
			}
		}

		private function _resolvePath(string $fileName): string {
			$fileName = ltrim(trim($fileName), '/\\');
			if ($fileName === '' || str_contains($fileName, '..')) {
				throw new InvalidArgumentException('Invalid view file name.');
			}

			$viewPath = $this->_baseDirectory . $fileName;
			if (!is_file($viewPath)) {
				throw new InvalidArgumentException(sprintf('View file not found: %s', $fileName));
			}

			return $viewPath;
		}
	}
}
