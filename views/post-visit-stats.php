<?php
/** @var \ABNetWpPostViewsAddons\VisitStatsInfo[] $botStats */
/** @var \ABNetWpPostViewsAddons\VisitStatsInfo[] $humanStats */

if (!defined('ABSPATH')) {
	exit;
}

$panels = array(
	'bots' => array(
		'label' => __('Maybe bots', 'abnet-wp-post-views-addons'), 
		'stats' => $botStats
	),
	'humans' => array(
		'label' => __('Maybe humans', 'abnet-wp-post-views-addons'), 
		'stats' => $humanStats
	),
);
?>
<div class="abnet-visit-stats" data-abnet-stats-tabs>
	<div class="abnet-visit-stats-tabs" role="tablist">
		<?php foreach ($panels as $panelKey => $panel): ?>
			<button type="button" 
				class="abnet-visit-stats-tab<?php echo $panelKey === 'bots' ? ' is-active' : ''; ?>" 
				role="tab" 
				aria-selected="<?php echo $panelKey === 'bots' ? 'true' : 'false'; ?>" 
				aria-controls="abnet-stats-panel-<?php echo esc_attr($panelKey); ?>">
				<?php echo esc_html($panel['label']); ?>
			</button>
		<?php endforeach; ?>
	</div>
	<?php foreach ($panels as $panelKey => $panel): ?>
		<div id="abnet-stats-panel-<?php echo esc_attr($panelKey); ?>" 
			class="abnet-visit-stats-panel abnet-visit-stats-<?php echo esc_attr($panelKey); ?>" 
			role="tabpanel" 
			<?php echo $panelKey !== 'bots' ? ' hidden="hidden"' : ''; ?>>

			<?php if (empty($panel['stats'])): ?>
				<p><?php echo esc_html__('No visits in the selected period.', 'abnet-wp-post-views-addons'); ?></p>
			<?php else: ?>
				<div class="abnet-visit-stats-daily-chart" role="img" aria-label="<?php echo esc_attr($panel['label']); ?>">
					<?php foreach (array_reverse($panel['stats']) as $stat): ?>
						<?php $title = sprintf('%s: %s', 
							$stat->label, 
							number_format_i18n($stat->value)); ?>

						<div class="abnet-visit-stats-day" 
							data-value="<?php echo esc_attr((string)$stat->value); ?>" 
							title="<?php echo esc_attr($title); ?>">
							<span class="abnet-visit-stats-column"></span>
							<span class="screen-reader-text"><?php echo esc_html($title); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
