<?php
/**
 * Plugin Name: ABNet WP-PostViews Addons
 * Plugin URI: https://github.com/alexboia/AbNet-Wp-PostViews-AddOns
 * Description: Extends WP-PostViews with bot filtering based on the list in data/bots.json.
 * Version: 1.0.0
 * Author: Alexandru Boia
 * Author URI: https://alexboia.net
 * Text Domain: abnet-wp-post-views-addons
 * License: Modified BSD License
 * License URI: https://directory.fsf.org/wiki/License:BSD-3-Clause
 */

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'constants.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

abnet_wp_post_views_addons_run();