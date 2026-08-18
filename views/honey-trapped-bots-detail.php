<?php
/** @var \ABNetWpPostViewsAddons\HoneyTrappedBotGroup $group */
/** @var \ABNetWpPostViewsAddons\HoneyTrappedBot[] $bots */
/** @var int $page */
/** @var bool $hasNextPage */
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="abnet-honey-trap-detail-heading">
	<div><h2><?php echo esc_html__('Trapped bots', 'abnet-wp-post-views-addons'); ?></h2><code><?php echo esc_html($group->botSignature); ?></code></div>
	<button type="button" class="button" data-close-detail><?php echo esc_html__('Close', 'abnet-wp-post-views-addons'); ?></button>
</div>
<?php if (empty($bots)): ?>
	<p><?php echo esc_html__('No trapped bots were found in this group.', 'abnet-wp-post-views-addons'); ?></p>
<?php else: ?>
	<div class="abnet-honey-trap-table-scroll">
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php echo esc_html__('Trapped at', 'abnet-wp-post-views-addons'); ?></th>
					<th><?php echo esc_html__('IP address', 'abnet-wp-post-views-addons'); ?></th>
					<th><?php echo esc_html__('Request', 'abnet-wp-post-views-addons'); ?></th>
					<th><?php echo esc_html__('User agent', 'abnet-wp-post-views-addons'); ?></th>
					<th><?php echo esc_html__('Trap URL', 'abnet-wp-post-views-addons'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($bots as $bot): ?>
					<tr>
						<td><?php echo esc_html($bot->getTrappedAt()); ?></td>
						<td><code><?php echo esc_html($bot->getIpAddress()); ?></code></td>
						<td><?php echo esc_html(trim($bot->getRequestMethod() . ' ' . $bot->getProtocol())); ?></td>
						<td class="abnet-honey-trap-wrap"><?php echo esc_html($bot->getUserAgent() ?? '—'); ?></td>
						<td class="abnet-honey-trap-wrap"><?php echo esc_html($bot->getTrapUrl()); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<div class="tablenav bottom"><div class="tablenav-pages">
	<?php if ($page > 1): ?>
		<button type="button" class="button" data-detail-page="<?php echo esc_attr((string)($page - 1)); ?>">&lsaquo; <?php echo esc_html__('Previous', 'abnet-wp-post-views-addons'); ?></button>
	<?php endif; ?>
	<span class="paging-input"><?php echo esc_html(sprintf(__('Page %d', 'abnet-wp-post-views-addons'), $page)); ?></span>
	<?php if ($hasNextPage): ?>
		<button type="button" class="button" data-detail-page="<?php echo esc_attr((string)($page + 1)); ?>"><?php echo esc_html__('Next', 'abnet-wp-post-views-addons'); ?> &rsaquo;</button>
	<?php endif; ?>
</div></div>
