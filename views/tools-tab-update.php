<?php
	if (!defined('ABSPATH')) {
		exit;
	}

	$status = isset($status) ? (string) $status : '';
	$actionUrl = isset($actionUrl) ? (string) $actionUrl : '';
	$actionName = isset($actionName) ? (string) $actionName : '';
	$nonceAction = isset($nonceAction) ? (string) $nonceAction : '';
	$nonceName = isset($nonceName) ? (string) $nonceName : '';
	$lastSuccessfulUpdateMessage = !empty($lastSuccessfulUpdateMessage) 
		? (string) $lastSuccessfulUpdateMessage 
		: '';
?>
<p><?php echo esc_html__('Download latest bot agents from ai-robots-txt and merge them into data/bots.json key bots.', 'abnet-wp-post-views-addons'); ?></p>

<?php if (!empty($lastSuccessfulUpdateMessage)) : ?>
	<div class="notice notice-info inline is-dismissible"><p><?php echo esc_html( $lastSuccessfulUpdateMessage ); ?></p></div>
<?php endif; ?>

<?php if ( $status === ABNet_WP_Post_Views_Addons_RefererPatternUpdater::STATUS_UPDATED ) : ?>
	<div class="notice notice-success inline is-dismissible"><p><?php echo esc_html__( 'Bots list was updated successfully.', 'abnet-wp-post-views-addons' ); ?></p></div>
<?php elseif ( $status === ABNet_WP_Post_Views_Addons_RefererPatternUpdater::STATUS_NO_CHANGES ) : ?>
	<div class="notice notice-info inline is-dismissible"><p><?php echo esc_html__( 'No new bot entries were found.', 'abnet-wp-post-views-addons' ); ?></p></div>
<?php elseif ( $status === ABNet_WP_Post_Views_Addons_RefererPatternUpdater::STATUS_NO_SOURCE ) : ?>
	<div class="notice notice-error inline is-dismissible"><p><?php echo esc_html__( 'Could not download source file and no fallback source was available.', 'abnet-wp-post-views-addons' ); ?></p></div>
<?php elseif ( $status === ABNet_WP_Post_Views_Addons_RefererPatternUpdater::STATUS_NO_BOTS ) : ?>
	<div class="notice notice-warning inline is-dismissible"><p><?php echo esc_html__( 'Source file does not contain bot entries in the expected format.', 'abnet-wp-post-views-addons' ); ?></p></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url($actionUrl); ?>">
	<input type="hidden" name="action" value="<?php echo esc_attr($actionName); ?>" />
	<?php wp_nonce_field($nonceAction, $nonceName); ?>
	<p>
		<label>
			<input type="checkbox" name="use_last_downloaded_file" value="1" />
			<?php echo esc_html__('Use last downloaded file when online download fails', 'abnet-wp-post-views-addons'); ?>
		</label>
	</p>
	<p>
		<button type="submit" class="button button-primary">
			<?php echo esc_html__('Update bots list', 'abnet-wp-post-views-addons'); ?>
		</button>
	</p>
</form>
