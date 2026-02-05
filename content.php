<?php
//  The default template for displaying content. Used for both single/archive/search/shortcode.

$lafka_custom_options = get_post_custom(get_the_ID());

$lafka_featured_slider = 'none';

if (isset($lafka_custom_options['lafka_rev_slider']) && trim($lafka_custom_options['lafka_rev_slider'][0]) != '' && function_exists('putRevSlider')) {
	$lafka_featured_slider = $lafka_custom_options['lafka_rev_slider'][0];
}
$lafka_rev_slider_before_header = 0;
if (isset($lafka_custom_options['lafka_rev_slider_before_header']) && trim($lafka_custom_options['lafka_rev_slider_before_header'][0]) != '') {
	$lafka_rev_slider_before_header = $lafka_custom_options['lafka_rev_slider_before_header'][0];
}

$lafka_featured_flex_slider_imgs = lafka_get_more_featured_images(get_the_ID());

// Blog style
$lafka_general_blog_style = lafka_get_option('general_blog_style');

// Featured image size
$lafka_featured_image_size = 'lafka-foodmenu-single-thumb';

// If is latest posts
if (isset($lafka_is_latest_posts) && $lafka_is_latest_posts) { // If is latest post shortcode
    $lafka_featured_image_size = 'lafka-640x640';
}

$lafka_post_classes = array('blog-post');
if (!has_post_thumbnail()) {
	array_push($lafka_post_classes, 'lafka-post-no-image');
}

// Show or not the featured image in single post view
if(is_singular(array('post'))) {
	$lafka_show_feat_image_in_post = 'yes';
	if (isset($lafka_custom_options['lafka_show_feat_image_in_post']) && trim($lafka_custom_options['lafka_show_feat_image_in_post'][0]) != '') {
		$lafka_show_feat_image_in_post = $lafka_custom_options['lafka_show_feat_image_in_post'][0];
	}
}
?>
<div id="post-<?php the_ID(); ?>" <?php post_class($lafka_post_classes); ?>>
	<?php // Featured content for post list ?>
    <?php if (!empty($lafka_featured_flex_slider_imgs) && is_singular()): // if there is slider or featured image attached and it is single post view, display it  ?>
        <div class="lafka_flexslider post_slide">
            <ul class="slides">
                <?php if (has_post_thumbnail()): ?>
                    <li>
                        <?php echo wp_get_attachment_image(get_post_thumbnail_id(), $lafka_featured_image_size); ?>
                    </li>
                <?php endif; ?>

                <?php foreach ($lafka_featured_flex_slider_imgs as $lafka_img_att_id): ?>
                    <li>
                        <?php echo wp_get_attachment_image($lafka_img_att_id, $lafka_featured_image_size); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (!is_single()): ?>
                <div class="foodmenu-unit-info">
                    <a class="go_to_page go_to_page_blog" title="<?php esc_attr_e('View', 'lafka') ?>" href="<?php echo esc_url(get_permalink()) ?>"><?php the_title() ?></a>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (!$lafka_rev_slider_before_header && $lafka_featured_slider != 'none' && function_exists('putRevSlider')): ?>
        <div class="slideshow">
            <?php putRevSlider($lafka_featured_slider) ?>
        </div>
    <?php elseif (has_post_thumbnail() && (!is_single() || is_singular(array('post')) && $lafka_show_feat_image_in_post == 'yes')): ?>
        <div class="post-unit-holder">
            <?php the_post_thumbnail($lafka_featured_image_size); ?>
            <?php if (!is_single()): ?>
                <div class="foodmenu-unit-info">
                    <a class="go_to_page go_to_page_blog" title="<?php esc_attr_e('View', 'lafka') ?>" href="<?php echo esc_url(get_permalink()) ?>"><?php the_title() ?></a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
	<?php // End Featured content for post list ?>

	<div class="lafka_post_data_holder">
		<?php if ( ! is_singular() ): ?>
			<?php get_template_part( 'partials/blog-post-meta-top' ); ?>
		<?php endif; ?>
		<?php if (!is_single()): ?>
			<h2	class="heading-title">
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
			</h2>
		<?php endif; ?>

		<?php if ( ! is_singular() ): ?>
			<?php get_template_part( 'partials/blog-post-meta-bottom' ); ?>
		<?php endif; ?>

		<?php // SINGLE POST CONTENT ?>
		<?php if (is_single()): ?>
			<?php the_content(); ?>
			<div class="clear"></div>
			<?php if (lafka_get_option('show_author_info') && (trim(get_the_author_meta('description')))): ?>
				<div class="lafka-author-info">
					<div class="title">
						<h2><?php echo esc_html__('About the Author:', 'lafka'); ?> <?php the_author_posts_link(); ?></h2>
					</div>
					<div class="lafka-author-content">
						<div class="avatar">
							<?php echo get_avatar(get_the_author_meta('email'), 160); ?>
						</div>
						<div class="description">
							<?php the_author_meta("description"); ?>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			<?php endif; ?>
			<?php wp_link_pages(array('before' => '<div class="page-links">' . esc_html__('Pages:', 'lafka'), 'after' => '</div>')); ?>
		<?php else: ?>
			<?php // BLOG / ARCHIVE / CATEGORY / TAG / SEARCH / SHORTCODE POST CONTENT ?>
            <div class="blog-post-excerpt">
				<?php
				if(isset($post->post_content) && strpos( $post->post_content, '<!--more-->' ) ) {
					the_content();
				}
				else {
					echo '<div class="lafka-defined-excerpt">';
                    the_excerpt();
					echo '</div>';
				}
				?>
            </div>
		<?php endif; ?>
	</div>
</div>