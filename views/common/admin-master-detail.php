<?php
if (!defined('ABSPATH')) {
	exit;
}

$classNames = isset($classNames) && is_array($classNames) ? $classNames : array();
$className = static function (string $genericClass, string $key) use ($classNames): string {
	$specificClass = isset($classNames[$key]) ? trim((string)$classNames[$key]) : '';
	return trim($genericClass . ' ' . $specificClass);
};

$masterAriaLabel = isset($masterAriaLabel) ? (string)$masterAriaLabel : '';
$modalLabelledBy = isset($modalLabelledBy) ? (string)$modalLabelledBy : '';
$masterContent = isset($masterContent) ? (string)$masterContent : '';
$detailContent = isset($detailContent) ? (string)$detailContent : '';
$modalContent = isset($modalContent) ? (string)$modalContent : '';
?>
<div class="<?php echo esc_attr($className('abnet-admin-master-detail-layout', 'layout')); ?>" data-abnet-master-detail-layout>
	<section class="<?php echo esc_attr($className('abnet-admin-master-detail-master', 'master')); ?>" aria-label="<?php echo esc_attr($masterAriaLabel); ?>" data-abnet-master-detail-master>
		<?php echo $masterContent; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML supplied by the composing view. ?>
	</section>
	<aside class="<?php echo esc_attr($className('abnet-admin-master-detail-detail', 'detail')); ?>" aria-live="polite" data-abnet-master-detail-detail hidden>
		<?php echo $detailContent; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML supplied by the composing view. ?>
	</aside>
</div>
<div class="<?php echo esc_attr($className('abnet-admin-modal', 'modal')); ?>" role="dialog" aria-modal="true"<?php echo $modalLabelledBy !== '' ? ' aria-labelledby="' . esc_attr($modalLabelledBy) . '"' : ''; ?> data-abnet-admin-modal hidden>
	<div class="<?php echo esc_attr($className('abnet-admin-modal-backdrop', 'modalBackdrop')); ?>" data-abnet-modal-close></div>
	<div class="<?php echo esc_attr($className('abnet-admin-modal-dialog', 'modalDialog')); ?>">
		<button type="button" class="<?php echo esc_attr($className('abnet-admin-modal-close', 'modalClose')); ?>" data-abnet-modal-close data-abnet-modal-close-button aria-label="<?php echo esc_attr__('Close', 'abnet-wp-post-views-addons'); ?>">&times;</button>
		<div class="<?php echo esc_attr($className('abnet-admin-modal-content', 'modalContent')); ?>" data-abnet-admin-modal-content>
			<?php echo $modalContent; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML supplied by the composing view. ?>
		</div>
	</div>
</div>
