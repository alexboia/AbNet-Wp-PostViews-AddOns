<?php
if (!defined('ABSPATH')) {
	exit;
}

define('ABNET_WP_POST_VIEWS_VERSION', '1.6.0');
define('ABNET_WP_POST_VIEWS_PLUGIN_FILE', __DIR__ . '/abnet-wp-post-views-addons.php');
define('ABNET_WP_POST_VIEWS_INC_DIR', __DIR__ . '/includes/');
define('ABNET_WP_POST_VIEWS_VIEWS_FILES_DIR', __DIR__ . '/views/');
define('ABNET_WP_POST_VIEWS_DATA_DIR', __DIR__ . '/data/');

define('ABNET_WP_POST_VIEWS_AI_ROBOTS_TXT_URL_DEFAULT', 'https://raw.githubusercontent.com/ai-robots-txt/ai.robots.txt/main/robots.json');
define('ABNET_WP_POST_VIEWS_SPAM_REFERALS_URL_DEFAULT', 'https://raw.githubusercontent.com/matomo-org/referrer-spam-list/refs/heads/master/spammers.txt');
