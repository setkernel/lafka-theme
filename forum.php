<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// The Default Page template file.

get_header();

// Get the lafka custom options
$lafka_page_options = get_post_custom( get_the_ID() );

$lafka_show_title_page          = 'yes';
$lafka_show_breadcrumb          = 'yes';
$lafka_featured_slider          = 'none';
$lafka_rev_slider_before_header = 0;
$lafka_subtitle                 = '';
$lafka_title_background_image   = '';
$lafka_title_alignment          = 'left_title';

if ( isset( $lafka_page_options['lafka_show_title_page'] ) && trim( $lafka_page_options['lafka_show_title_page'][0] ) != '' ) {
	$lafka_show_title_page = $lafka_page_options['lafka_show_title_page'][0];
}

if ( isset( $lafka_page_options['lafka_show_breadcrumb'] ) && trim( $lafka_page_options['lafka_show_breadcrumb'][0] ) != '' ) {
	$lafka_show_breadcrumb = $lafka_page_options['lafka_show_breadcrumb'][0];
}

if ( isset( $lafka_page_options['lafka_rev_slider'] ) && trim( $lafka_page_options['lafka_rev_slider'][0] ) != '' ) {
	$lafka_featured_slider = $lafka_page_options['lafka_rev_slider'][0];
}

if ( isset( $lafka_page_options['lafka_rev_slider_before_header'] ) && trim( $lafka_page_options['lafka_rev_slider_before_header'][0] ) != '' ) {
	$lafka_rev_slider_before_header = $lafka_page_options['lafka_rev_slider_before_header'][0];
}

if ( isset( $lafka_page_options['lafka_page_subtitle'] ) && trim( $lafka_page_options['lafka_page_subtitle'][0] ) != '' ) {
	$lafka_subtitle = $lafka_page_options['lafka_page_subtitle'][0];
}

if ( isset( $lafka_page_options['lafka_title_background_imgid'] ) && trim( $lafka_page_options['lafka_title_background_imgid'][0] ) != '' ) {
	$lafka_img                    = wp_get_attachment_image_src( $lafka_page_options['lafka_title_background_imgid'][0], 'full' );
	$lafka_title_background_image = $lafka_img ? $lafka_img[0] : $lafka_img;
}

if ( isset( $lafka_page_options['lafka_title_alignment'] ) && trim( $lafka_page_options['lafka_title_alignment'][0] ) != '' ) {
	$lafka_title_alignment = $lafka_page_options['lafka_title_alignment'][0];
}

$lafka_featured_flex_slider_imgs = lafka_get_more_featured_images( get_the_ID() );

$lafka_sidebar_choice = apply_filters( 'lafka_has_sidebar', '' );

if ( $lafka_sidebar_choice != 'none' ) {
	$lafka_has_sidebar = is_active_sidebar( $lafka_sidebar_choice );
} else {
	$lafka_has_sidebar = false;
}

// For Forum subtitle and image background
if ( LAFKA_IS_BBPRESS && bbp_is_forum_archive() ) {
	$lafka_subtitle               = lafka_get_option( 'forum_subtitle' );
	$lafka_title_background_image = lafka_get_option( 'forum_title_background_imgid' );
	$lafka_title_alignment        = lafka_get_option( 'forum_title_alignment' );
	if ( $lafka_title_background_image ) {
		$lafka_img                    = wp_get_attachment_image_src( $lafka_title_background_image, 'full' );
		$lafka_title_background_image = $lafka_img ? $lafka_img[0] : $lafka_img;
	}
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
?>
<?php if ( $lafka_has_offcanvas_sidebar ) : ?>
	<?php get_sidebar( 'offcanvas' ); ?>
<?php endif; ?>
<div id="content" 
<?php
if ( ! empty( $lafka_sidebar_classes ) ) {
	echo 'class="' . esc_attr( implode( ' ', $lafka_sidebar_classes ) ) . '"';}
?>
>

	<?php if ( $lafka_show_title_page == 'yes' || $lafka_show_breadcrumb == 'yes' ) : ?>
		<div id="lafka_page_title" class="lafka_title_holder <?php echo sanitize_html_class( $lafka_title_alignment ); ?>
		<?php
		if ( $lafka_title_background_image ) :
			?>
			title_has_image<?php endif; ?>">
			<?php
			if ( $lafka_title_background_image ) :
				?>
				<div class="lafka-zoomable-background" style="background-image: url('<?php echo esc_url( $lafka_title_background_image ); ?>');"></div><?php endif; ?>
			<div class="inner fixed">
				<div class="lafka-title-text-container">
					<!-- BREADCRUMB -->
					<?php if ( $lafka_show_breadcrumb == 'yes' ) : ?>
						<?php lafka_breadcrumb(); ?>
					<?php endif; ?>
					<!-- END OF BREADCRUMB -->
					<!-- TITLE -->
					<?php if ( $lafka_show_title_page == 'yes' ) : ?>
						<h1 class="heading-title"><?php the_title(); ?></h1>
						<?php if ( $lafka_subtitle ) : ?>
							<h6><?php echo esc_html( $lafka_subtitle ); ?></h6>
						<?php endif; ?>
					<?php endif; ?>
					<!-- END OF TITLE -->
				</div>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( $lafka_featured_slider != 'none' && function_exists( 'putRevSlider' ) && ! $lafka_rev_slider_before_header ) : ?>
		<!-- FEATURED REVOLUTION SLIDER -->
		<div class="slideshow">
			<div class="inner">
				<?php putRevSlider( $lafka_featured_slider ); ?>
			</div>
		</div>
		<!-- END OF FEATURED REVOLUTION SLIDER -->
	<?php endif; ?>
	<div class="inner">
		<!-- CONTENT WRAPPER -->
		<div id="main" class="fixed box box-common">
			<div class="content_holder">
				<?php if ( ! empty( $lafka_featured_flex_slider_imgs ) ) : ?>
					<div class="lafka_flexslider post_slide">
						<ul class="slides">
							<?php if ( has_post_thumbnail() ) : ?>
								<li>
									<?php echo wp_get_attachment_image( get_post_thumbnail_id(), 'lafka-foodmenu-single-thumb' ); ?>
								</li>
							<?php endif; ?>

							<?php foreach ( $lafka_featured_flex_slider_imgs as $lafka_img_att_id ) : ?>
								<li>
									<?php echo wp_get_attachment_image( $lafka_img_att_id, 'lafka-foodmenu-single-thumb' ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php elseif ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'lafka-foodmenu-single-thumb' ); ?>
				<?php endif; ?>

				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<?php get_template_part( 'content', 'page' ); ?>
					<?php comments_template( '', true ); ?>
				<?php endwhile; // end of the loop. ?>
			</div>

			<!-- SIDEBARS -->
			<?php if ( $lafka_has_sidebar ) : ?>
				<?php get_sidebar(); ?>
			<?php endif; ?>
			<?php if ( $lafka_has_offcanvas_sidebar ) : ?>
				<a class="sidebar-trigger" href="#"><?php echo esc_html__( 'show', 'lafka' ); ?></a>
			<?php endif; ?>
			<!-- END OF SIDEBARS -->
			<div class="clear"></div>
			<?php if ( function_exists( 'lafka_share_links' ) ) : ?>
				<?php lafka_share_links( the_title_attribute( 'echo=0' ), get_permalink() ); ?>
			<?php endif; ?>
		</div>

		<!-- Previous / Next links -->
		<?php if ( lafka_get_option( 'show_prev_next' ) ) : ?>
			<?php echo lafka_post_nav(); ?>
		<?php endif; ?>
	</div>
</div>
<!-- END OF MAIN CONTENT -->
<?php get_footer(); ?>