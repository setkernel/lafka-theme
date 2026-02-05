<?php
// Sidebar template
$lafka_sidebar_choice = apply_filters('lafka_has_sidebar', '');
?>

<?php if (function_exists('dynamic_sidebar') && $lafka_sidebar_choice != 'none' && is_active_sidebar($lafka_sidebar_choice) ) : ?>
	<div class="sidebar">
		<?php dynamic_sidebar($lafka_sidebar_choice); ?>
	</div>
<?php endif;