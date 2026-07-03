<?php
declare(strict_types=1);

if (!defined( 'ABSPATH')) {
	exit;
}

final class ABNet_WP_Post_Views_Addons_VisitContext {
	/**
	 * @var ABNet_WP_Post_Views_Addons_VisitContext|null
	 */
	private static $_instance = null;

	private bool $_isBot = false;

	private bool $_isFilteredReferer = false;

	private bool $_isSpamReferer = false;

	private int|null|bool $_postId = false;

	private bool $_wasCounted = false;

	/**
	 * @return ABNet_WP_Post_Views_Addons_VisitContext
	 */
	public static function getInstance(): ABNet_WP_Post_Views_Addons_VisitContext {
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {
		return;
	}

	private function __clone() {
		throw new RuntimeException('Cannot clone visit context singleton.');
	}

	public function __wakeup() {
		throw new RuntimeException('Cannot unserialize visit context singleton.');
	}

	public function flagBot(): void {
		$this->_isBot = true;
	}

	public function isBot(): bool {
		return $this->_isBot;
	}

	public function flagFilteredReferer(): void {
		$this->_isFilteredReferer = true;
	}

	public function isFilteredReferer(): bool {
		return $this->_isFilteredReferer;
	}

	public function flagSpamReferer(): void { 
		$this->_isSpamReferer = true;
	}

	public function isSpamReferer(): bool {
		return $this->_isSpamReferer;
	}

	public function getCurrentPostId(): int|null {
		if ($this->_postId === false) {
			$this->_postId = $this->_determineCurrentPostId();
		}
		return $this->_postId;
	}

	private function _determineCurrentPostId(): int|null {
		$postId = function_exists('get_the_ID')
			? (int)get_the_ID()
			: 0;

		if ($postId > 0) {
			return $postId;
		}

		$postId = function_exists('get_queried_object_id')
			? (int)get_queried_object_id()
			: 0;

		if ($postId > 0) {
			return $postId;
		}

		/**
		 * @var WP_Post $post
		 */

		global $post;
		if (!empty($post) && $post instanceof \WP_Post && isset($post->ID)) {
			$postId = (int)$post->ID;
			if ($postId > 0) {
				return $postId;
			}
		}

		return null;
	}

	public function flagCounted(): void {
		$this->_wasCounted = true;
	}

	public function wasCounted(): bool {
		return $this->_wasCounted;
	}
}