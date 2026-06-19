<?php
if (!defined( 'ABSPATH')) {
	exit;
}

$activeTab = isset($activeTab) 
	? (string)$activeTab 
	: 'update';

$tabs = isset($tabs) && is_array($tabs) 
	? $tabs 
	: array();

$baseViewsDir = plugin_dir_path(__FILE__);

$tabBaseUrl = add_query_arg(
	array(
		'page' => 'abnet-wp-post-views-addons-tools',
	),
	admin_url('tools.php')
);
?>
<div class="wrap">
	<h1><?php echo esc_html__('ABNet WP-PostViews Addons', 'abnet-wp-post-views-addons'); ?></h1>

	<nav class="nav-tab-wrapper" aria-label="<?php echo esc_attr__('ABNet WP-PostViews Addons Tabs', 'abnet-wp-post-views-addons'); ?>">
		<?php foreach ($tabs as $tabKey => $tabLabel) : ?>
			<?php
				$tabClass = $activeTab === $tabKey ? 'nav-tab nav-tab-active' : 'nav-tab';
				$tabUrl = add_query_arg(
					array(
						'page' => 'abnet-wp-post-views-addons-tools',
						'tab' => $tabKey,
					),
					$tabBaseUrl
				);
			?>
			<a class="<?php echo esc_attr($tabClass); ?>" href="<?php echo esc_url($tabUrl); ?>">
				<?php echo esc_html((string) $tabLabel); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div style="margin-top: 16px;">
		<?php if ('bots-json' === $activeTab): ?>
			<?php require $baseViewsDir . 'tools-tab-bots-json.php'; ?>
		<?php else : ?>
			<?php require $baseViewsDir . 'tools-tab-update.php'; ?>
		<?php endif; ?>
	</div>
</div>
