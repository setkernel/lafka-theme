<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// Off Canvas Sidebar template
$lafka_sidebar_choice = apply_filters( 'lafka_has_offcanvas_sidebar', '' );
?>

<?php if ( 'none' !== $lafka_sidebar_choice && is_active_sidebar( $lafka_sidebar_choice ) ) : ?>
	<div class="sidebar off-canvas-sidebar">
		<a class="close-off-canvas" href="#"></a>
		<div class="off-canvas-wrapper">
			<?php dynamic_sidebar( $lafka_sidebar_choice ); ?>
		</div>
	</div>
<?php endif; ?>