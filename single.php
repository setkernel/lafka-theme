<?php defined( 'ABSPATH' ) || exit; ?>
<?php
get_header();

// Default to single post
// Get the lafka custom options
$lafka_page_options = get_post_custom(get_the_ID());

$lafka_show_title_page = 'yes';
$lafka_show_breadcrumb = 'yes';
$lafka_featured_slider = 'none';
$lafka_subtitle = '';
$lafka_show_title_background = 0;
$lafka_title_background_image = '';
$lafka_title_alignment = 'left_title';

if (isset($lafka_page_options['lafka_show_title_page']) && trim($lafka_page_options['lafka_show_title_page'][0]) != '') {
	$lafka_show_title_page = $lafka_page_options['lafka_show_title_page'][0];
}

if (isset($lafka_page_options['lafka_show_breadcrumb']) && trim($lafka_page_options['lafka_show_breadcrumb'][0]) != '') {
	$lafka_show_breadcrumb = $lafka_page_options['lafka_show_breadcrumb'][0];
}

if (isset($lafka_page_options['lafka_rev_slider']) && trim($lafka_page_options['lafka_rev_slider'][0]) != '') {
	$lafka_featured_slider = $lafka_page_options['lafka_rev_slider'][0];
}


if (isset($lafka_page_options['lafka_page_subtitle']) && trim($lafka_page_options['lafka_page_subtitle'][0]) != '') {
	$lafka_subtitle = $lafka_page_options['lafka_page_subtitle'][0];
}

if (isset($lafka_page_options['lafka_title_background_imgid']) && trim($lafka_page_options['lafka_title_background_imgid'][0]) != '') {
	$lafka_img = wp_get_attachment_image_src($lafka_page_options['lafka_title_background_imgid'][0], 'full');
	$lafka_title_background_image = $lafka_img ? $lafka_img[0] : $lafka_img;
}

if (isset($lafka_page_options['lafka_title_alignment']) && trim($lafka_page_options['lafka_title_alignment'][0]) != '') {
	$lafka_title_alignment = $lafka_page_options['lafka_title_alignment'][0];
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
$lafka_sidebar_classes[] = apply_filters('lafka_left_sidebar_position_class', '');
?>
<?php if ($lafka_has_offcanvas_sidebar): ?>
	<?php get_sidebar('offcanvas'); ?>
<?php endif; ?>
<div id="content" <?php if (!empty($lafka_sidebar_classes)) echo 'class="' . esc_attr(implode(' ', $lafka_sidebar_classes)) . '"'; ?> >
	<?php while (have_posts()) : the_post(); ?>
		<?php if ($lafka_show_title_page == 'yes' || $lafka_show_breadcrumb == 'yes'): ?>
			<div id="lafka_page_title" class="lafka_title_holder <?php echo esc_attr($lafka_title_alignment) ?> <?php if ($lafka_title_background_image): ?>title_has_image<?php endif; ?>">
				<?php if ($lafka_title_background_image): ?><div class="lafka-zoomable-background" style="background-image: url('<?php echo esc_url($lafka_title_background_image) ?>');"></div><?php endif; ?>
				<div class="inner fixed">
                    <div class="lafka-title-text-container">
                        <!-- BREADCRUMB -->
                        <?php if ($lafka_show_breadcrumb == 'yes'): ?>
                            <?php lafka_breadcrumb() ?>
                        <?php endif; ?>
                        <!-- END OF BREADCRUMB -->
                        <?php if ($lafka_show_title_page == 'yes'): ?>
                            <h1	class="heading-title">
                                <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
                            </h1>
                            <?php if ($lafka_subtitle): ?>
                                <h6><?php echo esc_html($lafka_subtitle) ?></h6>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php get_template_part( 'partials/blog-post-meta-bottom' ); ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="inner">
			<!-- CONTENT WRAPPER -->
			<div id="main" class="fixed box box-common">
				<div class="content_holder">
					<?php get_template_part('content', get_post_format()); ?>
					<?php
					if (comments_open() || get_comments_number()) :
						comments_template('', true);
					endif;
					?>
					<?php if (lafka_get_option('show_related_posts')): ?>
						<?php
						// Get random post from the same category as the current one
						$lafka_related_posts_args = array(
								'posts_per_page' => lafka_get_option('number_related_posts'),
								'post__not_in' => array($post->ID),
								'orderby' => 'rand',
								'post_type' => 'post',
								'post_status' => 'publish'
						);
						$lafka_get_terms_args = array(
								'orderby' => 'name',
								'order' => 'ASC',
								'fields' => 'slugs'
						);
						$lafka_categories = wp_get_post_terms($post->ID, 'category', $lafka_get_terms_args);
						if (!$lafka_categories instanceof WP_Error && !empty($lafka_categories)) {
							$lafka_related_posts_args['tax_query'] = array(array('taxonomy' => 'category', 'field' => 'slug', 'terms' => $lafka_categories));
						}

						$lafka_is_latest_posts = true;
						$lafka_related_query = new WP_Query($lafka_related_posts_args);
						?>
						<?php if ($lafka_related_query->have_posts()) : ?>
							<?php
							// owl carousel
							wp_localize_script('lafka-libs-config', 'lafka_owl_carousel', array(
									'include' => 'true'
							));
							?>
							<div class="lafka-related-blog-posts lafka_shortcode_latest_posts lafka_blog_masonry full_width">
								<h4><?php esc_html_e('Related posts', 'lafka') ?></h4>
								<div <?php if (lafka_get_option('owl_carousel')): ?> class="owl-carousel lafka-owl-carousel" <?php endif; ?>>

								<?php while ($lafka_related_query->have_posts()) : ?>
									<?php $lafka_related_query->the_post(); ?>
							        <?php get_template_part('content', 'related-posts'); ?>
								<?php endwhile; ?>

								</div>
								<div class="clear"></div>
							</div>
						<?php endif; ?>
					<?php endif; ?>
					<?php wp_reset_postdata(); ?>
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
				<?php if (function_exists('lafka_share_links')): ?>
					<?php lafka_share_links(the_title_attribute( 'echo=0' ), get_permalink()); ?>
				<?php endif; ?>
			</div>

            <!-- Previous / Next links -->
			<?php if (lafka_get_option('show_prev_next')): ?>
				<?php echo lafka_post_nav(); ?>
			<?php endif; ?>
		</div>
		<!-- END OF CONTENT WRAPPER -->
	<?php endwhile; // end of the loop.    ?>
</div>
<?php
get_footer();
