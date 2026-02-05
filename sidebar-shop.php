<?php
// Shop Sidebar template

wp_reset_postdata();
?>

<?php if (function_exists('dynamic_sidebar')) : ?>
	<div class="sidebar">

		<?php if (is_active_sidebar('shop')): ?>
			<?php dynamic_sidebar('shop'); ?>
		<?php endif; ?>

	</div>
<?php endif;