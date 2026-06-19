=== ABNet WP-PostViews Addons ===
Contributors: alexboia
Tags: wp-postviews, bot filtering, referer filtering, analytics
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: Modified BSD License
License URI: https://directory.fsf.org/wiki/License:BSD-3-Clause

Extends WP-PostViews with bot and referer filtering based on data/bots.json, including strict domain matching and an admin update tool.

== Description ==

ABNet WP-PostViews Addons extends WP-PostViews by deciding whether a view should be counted using rules loaded from data/bots.json.

Main capabilities:

* Blocks views for requests that match known bot User-Agent patterns.
* Blocks views for requests that match configured referer patterns.
* Supports strict mode for referer checks (exact domain or subdomain matching).
* Provides a Tools page to update the bots list from ai-robots-txt source.
* Supports fallback to last downloaded source file when online download fails.
* Displays current bots.json content in a dedicated Tools tab.

The plugin uses WordPress hooks and keeps behavior customizable through filters and constants.

== Installation ==

1. Upload plugin files to /wp-content/plugins/abnet-wp-post-views-addons/ or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Make sure WP-PostViews is installed and active.
4. Optional: open Tools > ABNet Post Views Addons and update the bots list.

== Frequently Asked Questions ==

= Does this plugin replace WP-PostViews? =

No. It extends WP-PostViews and hooks into its counting flow.

= What is strict mode? =

Strict mode applies host/domain matching for referers, so checks match only exact domain or subdomains (for example example.com and l.example.com), reducing false positives.

= Which constants can I define? =

You can define the following constants (in wp-config.php, before plugins load):

* ABNET_WP_POST_VIEWS_STRICT (bool): enables strict referer-domain matching when true.
* ABNET_WP_POST_VIEWS_AI_ROBOTS_TXT_URL (string): overrides the default source URL used by the updater.

= Which filters are available? =

* abnet_wpv_addons_bot_patterns
* abnet_wpv_addons_referer_patterns
* abnet_wpv_addons_normalized_domain
* abnet_wpv_addons_last_update_message

= How can I test the behavior? =

The repository includes support/test.php for CLI diagnostics and request simulation.
This script is for development/support workflows and is not distributed with the plugin package.

Usage mirror:

* php test.php <url> [--limit=NUMBER] [--auto-batch] [--no-delay]
* <url> is required.
* --limit=NUMBER is optional; 0 means all scenarios.
* --auto-batch skips confirmation between batches.
* --no-delay disables random delay between requests.

== Screenshots ==

1. Tools page - Update tab.
2. Tools page - Current bots.json tab.

== Changelog ==

= 1.0.0 =
* Initial release.
* Integrated with postviews_should_count to filter bot/referrer requests.
* Added strict referer-domain matching via constant.
* Added updater for bots list from ai-robots-txt with fallback source support.
* Added Tools page with tabbed layout and bots.json section visualization.
* Added public filters for patterns, domain normalization, and update message customization.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
