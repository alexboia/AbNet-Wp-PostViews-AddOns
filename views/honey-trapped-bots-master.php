<?php
/** @var \ABNetWpPostViewsAddons\HoneyTrappedBotGroup[] $groups */
if (!defined('ABSPATH')) {
	exit;
}
?>
<h2><?php echo esc_html__('Bot groups', 'abnet-wp-post-views-addons'); ?></h2>
<?php if (empty($groups)): ?>
	<p><?php echo esc_html__('No honey trapped bots were found.', 'abnet-wp-post-views-addons'); ?></p>
<?php else: ?>
	<div class="abnet-honey-trap-table-scroll">
		<table class="widefat striped">
			<thead><tr>
				<th><?php echo esc_html__('Signature', 'abnet-wp-post-views-addons'); ?></th>
				<th><?php echo esc_html__('Trap type', 'abnet-wp-post-views-addons'); ?></th>
				<th><?php echo esc_html__('Count', 'abnet-wp-post-views-addons'); ?></th>
				<th><?php echo esc_html__('Actions', 'abnet-wp-post-views-addons'); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ($groups as $group): ?>
				<tr>
					<td><code title="<?php echo esc_attr($group->botSignature); ?>"><?php echo esc_html($group->botSignature); ?></code></td>
					<td><?php echo esc_html($group->trapType); ?></td>
					<td><?php echo esc_html(number_format_i18n($group->botCount)); ?></td>
					<td class="abnet-honey-trap-actions">
						<button type="button" class="button button-small" data-view-ips data-signature="<?php echo esc_attr($group->botSignature); ?>"><?php echo esc_html__('View IPs', 'abnet-wp-post-views-addons'); ?></button>
						<button type="button" class="button button-small button-primary" data-view-bots data-signature="<?php echo esc_attr($group->botSignature); ?>" data-trap-type="<?php echo esc_attr($group->trapType); ?>"><?php echo esc_html__('View bots', 'abnet-wp-post-views-addons'); ?></button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
<div class="tablenav bottom"><div class="tablenav-pages">
	<?php if ($page > 1): ?><button type="button" class="button" data-master-page="<?php echo esc_attr((string)($page - 1)); ?>">&lsaquo; <?php echo esc_html__('Previous', 'abnet-wp-post-views-addons'); ?></button><?php endif; ?>
	<span class="paging-input"><?php echo esc_html(sprintf(__('Page %d', 'abnet-wp-post-views-addons'), $page)); ?></span>
	<?php if ($hasNextPage): ?><button type="button" class="button" data-master-page="<?php echo esc_attr((string)($page + 1)); ?>"><?php echo esc_html__('Next', 'abnet-wp-post-views-addons'); ?> &rsaquo;</button><?php endif; ?>
</div></div>
