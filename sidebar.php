<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// Sidebar template
$lafka_sidebar_choice = apply_filters( 'lafka_has_sidebar', '' );
?>

<?php if ( 'none' !== $lafka_sidebar_choice && is_active_sidebar( $lafka_sidebar_choice ) ) : ?>
	<div class="sidebar">
		<?php dynamic_sidebar( $lafka_sidebar_choice ); ?>
	</div>
	<?php
endif;