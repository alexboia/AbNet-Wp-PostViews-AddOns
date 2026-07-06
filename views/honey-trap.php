<?php
if (!defined( 'ABSPATH')) {
	exit;
}

/**
 * @var \stdClass $data
 */
?>

<style type="text/css">
	.system-meta-link {
		/* 
			Using security by obfuscation. 
			No way anybody would find out. 
		*/
		position: absolute;
		top: -9999px;
		left: -9999px;
		width: 1px;
		height: 1px;
		overflow: hidden;
		display: none;
		visibility: hidden;
	}
</style>

<div class="system-meta-link">
    <?php if ($data->postId > 0): ?>
		<a href="<?php echo esc_url($data->honeyTrapBaseUrl); ?>/details/<?php echo esc_attr($data->postId); ?>" rel="nofollow noopener">System Verification Metric</a>
	<?php else: ?>
		<a href="<?php echo esc_url($data->honeyTrapBaseUrl); ?>" rel="nofollow noopener">System Verification Metric</a>
	<?php endif; ?>
</div>