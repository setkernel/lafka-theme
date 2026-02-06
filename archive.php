<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// The Archive template file.

get_header();

$lafka_sidebar_choice = apply_filters( 'lafka_has_sidebar', '' );

if ( $lafka_sidebar_choice != 'none' ) {
	$lafka_has_sidebar = is_active_sidebar( $lafka_sidebar_choice );
} else {
	$lafka_has_sidebar = false;
}

$lafka_offcanvas_sidebar_choice = apply_filters( 'lafka_has_offcanvas_sidebar', '' );

if ( $lafka_offcanvas_sidebar_choice != 'none' ) {
	$lafka_has_offcanvas_sidebar = is_active_sidebar( $lafka_offcanvas_sidebar_choice );
} else {
	$lafka_has_offcanvas_sidebar = false;
}

$lafka_sidebar_classes = array();
if ( $lafka_has_sidebar ) {
	$lafka_sidebar_classes[] = 'has-sidebar';
}
if ( $lafka_has_offcanvas_sidebar ) {
	$lafka_sidebar_classes[] = 'has-off-canvas-sidebar';
}

// Sidebar position
$lafka_sidebar_classes[] = apply_filters( 'lafka_left_sidebar_position_class', '' );

$lafka_title_background_image = lafka_get_option( 'blog_title_background_imgid' );

if ( $lafka_title_background_image ) {
	$lafka_img                    = wp_get_attachment_image_src( $lafka_title_background_image, 'full' );
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
?>
<?php if ( $lafka_has_offcanvas_sidebar ): ?>
	<?php get_sidebar( 'offcanvas' ); ?>
<?php endif; ?>
    <div id="content" <?php if ( ! empty( $lafka_sidebar_classes ) ) {
		echo 'class="' . esc_attr( implode( ' ', $lafka_sidebar_classes ) ) . '"';
	} ?> >
        <div id="lafka_page_title"
             class="lafka_title_holder <?php if ( $lafka_title_background_image ): ?>title_has_image<?php endif; ?>">
			<?php if ( $lafka_title_background_image ): ?>
                <div class="lafka-zoomable-background"
                     style="background-image: url('<?php echo esc_url( $lafka_title_background_image ) ?>');"></div><?php endif; ?>
            <div class="inner fixed">
                <div class="lafka-title-text-container">
                    <!-- BREADCRUMB -->
                    <?php lafka_breadcrumb() ?>
                    <!-- END OF BREADCRUMB -->
                    <h1 class="heading-title">
                        <?php the_archive_title() ?>
                    </h1>
                </div>
            </div>
        </div>
        <div class="inner">
            <!-- CONTENT WRAPPER -->
            <div id="main" class="fixed box box-common">
                <div class="content_holder">
					<?php if ( have_posts() ): ?>
						<?php echo term_description(); ?>
                        <div class="lafka-category-posts<?php if($lafka_general_blog_style) echo ' '.esc_attr($lafka_general_blog_style); ?>">
                            <?php while ( have_posts() ) : the_post(); ?>
                                <!-- BLOG POST -->
                                <?php get_template_part( 'content', get_post_format() ); ?>
                                <!-- END OF BLOG POST -->
                            <?php endwhile; ?>
                        </div>
					<?php else: ?>
                        <p><?php esc_html_e( 'No posts were found. Sorry!', 'lafka' ); ?></p>
					<?php endif; ?>

                    <!-- PAGINATION -->
                    <div class="box box-common">
						<?php
						if ( function_exists( 'lafka_pagination' ) ) : lafka_pagination();
						else :
							?>

                            <div class="navigation group">
                                <div class="alignleft"><?php next_posts_link( __( 'Next &raquo;', 'lafka' ) ) ?></div>
                                <div class="alignright"><?php previous_posts_link( __( '&laquo; Back', 'lafka' ) ) ?></div>
                            </div>

						<?php endif; ?>
                    </div>
                    <!-- END OF PAGINATION -->
                </div>
                <!-- SIDEBARS -->
				<?php if ( $lafka_has_sidebar ): ?>
					<?php get_sidebar(); ?>
				<?php endif; ?>
				<?php if ( $lafka_has_offcanvas_sidebar ): ?>
                    <a class="sidebar-trigger" href="#"><?php echo esc_html__( 'show', 'lafka' ) ?></a>
				<?php endif; ?>
                <!-- END OF SIDEBARS -->
                <div class="clear"></div>

            </div>
            <!-- END OF CONTENT WRAPPER -->
        </div>
    </div>
<?php
get_footer();
