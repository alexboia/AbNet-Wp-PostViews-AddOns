<?php
/** @var \ABNetWpPostViewsAddons\VisitStatsInfo[] $stats */
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="abnet-visit-stats" data-abnet-stats>
	<?php if (empty($stats)): ?>
		<p><?php echo esc_html__('No visit statistics are available yet.', 'abnet-wp-post-views-addons'); ?></p>
	<?php else: ?>
		<div class="abnet-visit-stats-chart" role="img" 
			aria-label="<?php echo esc_attr__('All-time post views by visitor type', 'abnet-wp-post-views-addons'); ?>">
			<?php foreach ($stats as $stat): ?>
				<div class="abnet-visit-stats-bar-row <?php echo esc_attr($stat->key) ?>" 
					data-value="<?php echo esc_attr((string)$stat->value); ?>">
					<span class="abnet-visit-stats-label"><?php echo esc_html($stat->label); ?></span>
					<span class="abnet-visit-stats-track"><span class="abnet-visit-stats-bar"></span></span>
					<strong><?php echo esc_html(number_format_i18n($stat->value)); ?></strong>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
