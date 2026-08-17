<?php
/** @var \ABNetWpPostViewsAddons\View $view */
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap abnet-honey-trap" data-abnet-honey-trap>
	<h1><?php echo esc_html__('Honey trapped bots', 'abnet-wp-post-views-addons'); ?></h1>
	<?php
		$view->render('common/admin-master-detail.php', array(
			'masterAriaLabel' => __('Bot groups', 'abnet-wp-post-views-addons'),
			'modalLabelledBy' => 'abnet-honey-trap-modal-title',
			'masterContent' => '<div class="abnet-honey-trap-loading"><span class="spinner is-active"></span></div>',
			'classNames' => array(
				'layout' => 'abnet-honey-trap-layout',
				'master' => 'abnet-honey-trap-master',
				'detail' => 'abnet-honey-trap-detail',
				'modal' => 'abnet-honey-trap-modal',
				'modalBackdrop' => 'abnet-honey-trap-modal-backdrop',
				'modalDialog' => 'abnet-honey-trap-modal-dialog',
				'modalClose' => 'abnet-honey-trap-modal-close',
				'modalContent' => 'abnet-honey-trap-modal-content',
			),
		));
	?>
</div>
