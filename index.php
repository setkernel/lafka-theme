<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// The main template file.

get_header();

// show Blog title
$lafka_show_blog_title = lafka_get_option('show_blog_title');
// get Blog title
$lafka_blog_title = lafka_get_option('blog_title');
// get Blog subtitle
$lafka_blog_subtitle = lafka_get_option('blog_subtitle');
$lafka_title_background_image = lafka_get_option('blog_title_background_imgid');

if ($lafka_title_background_image) {
	$lafka_img = wp_get_attachment_image_src($lafka_title_background_image, 'full');
	$lafka_title_background_image = $lafka_img ? $lafka_img[0] : $lafka_img;
}

// Blog style
$lafka_general_blog_style = lafka_get_option('general_blog_style');
switch ($lafka_general_blog_style) {
	case 'lafka_blog_masonry':
		// Isotope settings
		wp_localize_script('lafka-libs-config', 'lafka_masonry_settings', array(
				'include' => 'true'
		));
		break;
}

$lafka_sidebar_choice = apply_filters('lafka_has_sidebar', '');

if ($lafka_sidebar_choice != 'none') {
	$lafka_has_sidebar = is_active_sidebar($lafka_sidebar_choice);
} else {
	$lafka_has_sidebar = false;
}

$lafka_offcanvas_sidebar_choice = apply_filters('lafka_has_offcanvas_sidebar', '');

if ($lafka_offcanvas_sidebar_choice != 'none') {
	$lafka_has_offcanvas_sidebar = is_active_sidebar($lafka_offcanvas_sidebar_choice);
} else {
	$lafka_has_offcanvas_sidebar = false;
}

$lafka_sidebar_classes = array();
if ($lafka_has_sidebar) {
	$lafka_sidebar_classes[] = 'has-sidebar';
}
if ($lafka_has_offcanvas_sidebar) {
	$lafka_sidebar_classes[] = 'has-off-canvas-sidebar';
}

// Sidebar position
$lafka_sidebar_classes[] =  apply_filters('lafka_left_sidebar_position_class', '');
?>
<?php if ($lafka_has_offcanvas_sidebar): ?>
	<?php get_sidebar('offcanvas'); ?>
<?php endif; ?>
<div id="content" <?php if (!empty($lafka_sidebar_classes)) echo 'class="' . esc_attr(implode(' ', $lafka_sidebar_classes)) . '"'; ?> >
	<?php if (lafka_is_blog() && $lafka_show_blog_title || lafka_breadcrumb()): ?>
		<div id="lafka_page_title" class="lafka_title_holder <?php echo esc_attr(lafka_get_option('blog_title_alignment')) ?> <?php if ($lafka_title_background_image): ?>title_has_image<?php endif; ?>">
			<?php if ($lafka_title_background_image): ?><div class="lafka-zoomable-background" style="background-image: url('<?php echo esc_url($lafka_title_background_image) ?>');"></div><?php endif; ?>
			<div class="inner fixed">
                <div class="lafka-title-text-container">
                    <!-- BREADCRUMB -->
                    <?php lafka_breadcrumb() ?>
                    <!-- END OF BREADCRUMB -->
                    <!-- TITLE -->
                    <?php if (lafka_is_blog() && $lafka_show_blog_title): ?>
                        <h1 class="heading-title"><?php echo esc_html($lafka_blog_title); ?></h1>
                        <?php if ($lafka_blog_subtitle): ?>
                            <h6><?php echo esc_html($lafka_blog_subtitle) ?></h6>
                        <?php endif; ?>
                    <?php endif; ?>
                    <!-- END OF TITLE -->
                </div>
			</div>
		</div>
	<?php endif; ?>
	<div class="inner">
		<!-- CONTENT WRAPPER -->
		<div id="main" class="fixed box box-common">
			<div class="content_holder<?php if($lafka_general_blog_style) echo ' '.esc_attr($lafka_general_blog_style); ?>">
				<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

						<!-- BLOG POST -->
						<?php get_template_part('content', get_post_format()); ?>
						<!-- END OF BLOG POST -->

						<?php
					endwhile;
				else:
					?>
					<?php get_template_part('content', 'none'); ?>
				<?php endif; ?>

			<!-- PAGINATION -->
			<div class="box box-common">
				<?php
				if (function_exists('lafka_pagination')) : lafka_pagination();
				else :
					?>

					<div class="navigation group">
						<div class="alignleft"><?php next_posts_link(esc_html__('Next &raquo;', 'lafka')) ?></div>
						<div class="alignright"><?php previous_posts_link(esc_html__('&laquo; Back', 'lafka')) ?></div>
					</div>

				<?php endif; ?>
			</div>
			<!-- END OF PAGINATION -->
            </div>

            <!-- SIDEBARS -->
			<?php if ($lafka_has_sidebar): ?>
				<?php get_sidebar(); ?>
			<?php endif; ?>
			<?php if ($lafka_has_offcanvas_sidebar): ?>
                <a class="sidebar-trigger" href="#"><?php echo esc_html__('show', 'lafka') ?></a>
			<?php endif; ?>
            <!-- END OF SIDEBARS -->
            <div class="clear"></div>

		</div>
		<!-- END OF CONTENT WRAPPER -->
        <!-- Previous / Next links -->
		<?php if (lafka_get_option('show_prev_next')): ?>
			<?php echo lafka_post_nav(); ?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
