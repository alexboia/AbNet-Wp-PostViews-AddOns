<?php
declare(strict_types=1);

namespace ABNetWpPostViewsAddons {
	if (!defined( 'ABSPATH')) {
		exit;
	}

	class RobotsTxtWriter {
		public function disallowUrls(array $urls) {
			$robotsTxtPath = $this->_getRobotsTxtPath();
			$block = $this->_buildRobotsBlock($urls);

			$currentContent = '';
			if (file_exists($robotsTxtPath)) {
				$readContent = file_get_contents($robotsTxtPath);
				if ($readContent === false) {
					abnet_write_log(sprintf('Could not read robots.txt at %s.', $robotsTxtPath));
					return;
				}

				$currentContent = (string)$readContent;
			}

			$updatedContent = $this->_replaceManagedBlock($currentContent, $block);
			if ($updatedContent === $currentContent) {
				return;
			}

			$result = file_put_contents($robotsTxtPath, $updatedContent);
			if ($result === false) {
				abnet_write_log(sprintf('Could not write robots.txt at %s.', $robotsTxtPath));
			}
		}

		private function _getRobotsTxtPath(): string {
			return ABSPATH . 'robots.txt';
		}

		private  function _buildRobotsBlock(array $urls): string {
			$lines = array(
				'# BEGIN ABNet WP-PostViews Addons',
				'User-agent: *'
			);

			foreach ($urls as $url) {
				if (!empty($url)) {
					$lines[] = $url;
				}
			}

			$lines[]= '# END ABNet WP-PostViews Addons';

			return implode(PHP_EOL, $lines) . PHP_EOL;
		}

		private function _replaceManagedBlock(string $content, string $block): string {
			$pattern = '/# BEGIN ABNet WP-PostViews Addons\\R.*?# END ABNet WP-PostViews Addons\\R?/s';
			if (preg_match($pattern, $content) === 1) {
				$replaced = preg_replace($pattern, $block, $content, 1);
				if (is_string($replaced)) {
					return $replaced;
				}

				abnet_write_log('Failed replacing HoneyTrap block in robots.txt.');
				return $content;
			}

			if ($content === '') {
				return $block;
			}

			return rtrim($content) . PHP_EOL . PHP_EOL . $block;
		}
	}
}