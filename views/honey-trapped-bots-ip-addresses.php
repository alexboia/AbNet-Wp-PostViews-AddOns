<?php
/** @var array<string, int> $ipAddresses */
if (!defined('ABSPATH')) {
	exit;
}
?>
<h2 id="abnet-honey-trap-modal-title"><?php echo esc_html__('IP addresses', 'abnet-wp-post-views-addons'); ?></h2>
<p><code><?php echo esc_html($signature); ?></code></p>
<?php if (empty($ipAddresses)): ?>
	<p><?php echo esc_html__('No IP addresses were found for this signature.', 'abnet-wp-post-views-addons'); ?></p>
<?php else: ?>
	<table class="widefat striped"><thead><tr>
		<th><?php echo esc_html__('IP address', 'abnet-wp-post-views-addons'); ?></th>
		<th><?php echo esc_html__('Occurrences', 'abnet-wp-post-views-addons'); ?></th>
	</tr></thead><tbody>
	<?php foreach ($ipAddresses as $ipAddress => $count): ?><tr>
		<td><code><?php echo esc_html($ipAddress); ?></code></td>
		<td><?php echo esc_html(number_format_i18n($count)); ?></td>
	</tr><?php endforeach; ?>
	</tbody></table>
<?php endif; ?>
