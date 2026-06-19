<?php
	if (!defined( 'ABSPATH')) {
		exit;
	}

	$botsJsonSections = isset($botsJsonSections) && is_array( $botsJsonSections ) 
		? $botsJsonSections 
		: array();
?>
<p><?php echo esc_html__('Current content from data/bots.json grouped by section.', 'abnet-wp-post-views-addons'); ?></p>

<?php if (empty($botsJsonSections)): ?>
	<div class="notice notice-warning"><p><?php echo esc_html__('No sections found in data/bots.json.', 'abnet-wp-post-views-addons'); ?></p></div>
<?php else: ?>
	<?php foreach ($botsJsonSections as $section): ?>
		<?php
			$sectionKey = isset($section['key'])
				? (string)$section['key']
				: '';

			$sectionTitle = isset($section['title']) 
				? (string)$section['title'] 
				: '';

			$rows = isset($section['rows']) && is_array($section['rows']) 
				? $section['rows'] 
				: array();
		?>
		<h2><?php echo esc_html($sectionTitle); ?> 
			<?php if (!empty($sectionKey)): ?>
				(<code><?php echo esc_html($section['key']) ?></code>)
			<?php endif; ?>
		</h2>
		<table class="widefat striped fixed" style="margin-bottom: 20px;">
			<thead>
				<tr>
					<th style="width: 90px;"><?php echo esc_html__('#', 'abnet-wp-post-views-addons'); ?></th>
					<th><?php echo esc_html__('Value', 'abnet-wp-post-views-addons'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($rows)): ?>
					<tr>
						<td colspan="2"><?php echo esc_html__('No values in this section.', 'abnet-wp-post-views-addons'); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ($rows as $index => $rowValue): ?>
						<tr>
							<td><?php echo esc_html((string) ($index + 1)); ?></td>
							<td><code><?php echo esc_html((string) $rowValue); ?></code></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
<?php endif; ?>
