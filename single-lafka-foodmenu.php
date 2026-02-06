<?php defined( 'ABSPATH' ) || exit; ?>
<?php
get_header();

// The lafka-foodmenu CPT template file.
// Get the lafka custom options
$lafka_page_options = get_post_custom( get_the_ID() );

$lafka_show_title_page          = 'yes';
$lafka_show_breadcrumb          = 'yes';
$lafka_featured_slider          = 'none';
$lafka_rev_slider_before_header = 0;
$lafka_subtitle                 = '';
$lafka_show_title_background    = 0;
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
<?php
while ( have_posts() ) :
	the_post();
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

			<div id="lafka_page_title" class="lafka_title_holder <?php echo esc_attr( $lafka_title_alignment ); ?>
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
				<?php putRevSlider( $lafka_featured_slider ); ?>
			</div>
			<!-- END OF FEATURED REVOLUTION SLIDER -->
		<?php endif; ?>
		<div class="inner">
			<!-- CONTENT WRAPPER -->
			<div id="main" class="fixed box box-common">
				<div class="content_holder">
					<?php $lafka_curr_foodmenu_id = get_the_ID(); ?>
					<?php $lafka_foodmenu_custom = get_post_custom(); ?>
					<?php
					$lafka_main_price            = isset( $lafka_foodmenu_custom['lafka_item_single_price'] ) ? $lafka_foodmenu_custom['lafka_item_single_price'][0] : '';
					$lafka_ingredients           = isset( $lafka_foodmenu_custom['lafka_ingredients'] ) ? $lafka_foodmenu_custom['lafka_ingredients'][0] : '';
					$lafka_allergens             = isset( $lafka_foodmenu_custom['lafka_allergens'] ) ? $lafka_foodmenu_custom['lafka_allergens'][0] : '';
					$lafka_ext_link_button_title = isset( $lafka_foodmenu_custom['lafka_ext_link_button_title'] ) ? $lafka_foodmenu_custom['lafka_ext_link_button_title'][0] : '';
					$lafka_ext_link_url          = isset( $lafka_foodmenu_custom['lafka_ext_link_url'] ) ? $lafka_foodmenu_custom['lafka_ext_link_url'][0] : '';

					// What gallery to be used
					$lafka_prtfl_gallery = isset( $lafka_foodmenu_custom['lafka_prtfl_gallery'] ) ? $lafka_foodmenu_custom['lafka_prtfl_gallery'][0] : 'flex';
					// Custom content
					$lafka_use_custom_content = isset( $lafka_foodmenu_custom['lafka_prtfl_custom_content'] ) ? $lafka_foodmenu_custom['lafka_prtfl_custom_content'][0] : 0;
					?>
					<?php if ( ! $lafka_use_custom_content ) : ?>
						<div class="foodmenu_top
						<?php
						if ( $lafka_prtfl_gallery == 'list' ) :
							?>
							lafka_image_list_foodmenu<?php endif; ?>" >
							<?php if ( ! lafka_get_option( 'hide_foodmenu_images' ) ) : ?>
								<div class="two_third foodmenu-main-image-holder">
									<?php if ( $lafka_prtfl_gallery == 'cloud' && has_post_thumbnail() ) : ?>
										<!-- Cloud Zoom -->
										<?php
										$lafka_featured_image_id = get_post_thumbnail_id();

										if ( $lafka_featured_image_id ) {
											array_unshift( $lafka_featured_flex_slider_imgs, $lafka_featured_image_id );
										}

										$lafka_image_title = the_title_attribute(
											array(
												'post' => get_post_thumbnail_id(),
												'echo' => false,
											)
										);
										$lafka_image_link  = wp_get_attachment_url( get_post_thumbnail_id() );
										$lafka_image       = get_the_post_thumbnail( null, 'lafka-foodmenu-single-thumb' );
										?>
										<?php printf( '<a id="zoom1" href="%s" itemprop="image" class="cloud-zoom " title="%s"  rel="position: \'inside\' , showTitle: false, adjustX:-4, adjustY:-4">%s</a>', esc_url( $lafka_image_link ), esc_attr( $lafka_image_title ), $lafka_image ); ?>

										<?php if ( ! empty( $lafka_featured_flex_slider_imgs ) ) : // If there are additional images show CloudZoom gallery ?>
											<ul class="additional-images">
												<?php foreach ( $lafka_featured_flex_slider_imgs as $lafka_img_id ) : ?>
													<?php
													$lafka_image_title      = the_title_attribute(
														array(
															'post' => $lafka_img_id,
															'echo' => false,
														)
													);
													$lafka_image_link       = wp_get_attachment_url( $lafka_img_id );
													$lafka_small_image_link = wp_get_attachment_url( $lafka_img_id, 'lafka-foodmenu-single-thumb' );
													$lafka_thumb_image      = wp_get_attachment_image( $lafka_img_id, 'lafka-widgets-thumb' );
													?>
													<li>
														<?php printf( '<a rel="useZoom: \'zoom1\', smallImage: \'%s\'" title="%s" class="cloud-zoom-gallery" href="%s">%s</a>', esc_url( $lafka_small_image_link ), esc_attr( $lafka_image_title ), esc_url( $lafka_image_link ), $lafka_thumb_image ); ?>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									<?php elseif ( $lafka_prtfl_gallery == 'flex' && ! empty( $lafka_featured_flex_slider_imgs ) ) : ?>
										<!-- FEATURED SLIDER/IMAGE -->
										<div class="lafka_flexslider">
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
									<?php elseif ( $lafka_prtfl_gallery == 'list' && has_post_thumbnail() ) : ?>
										<!-- Image List -->
										<div class="lafka_image_list">
											<?php if ( has_post_thumbnail() ) : ?>
												<?php $lafka_attach_url = wp_get_attachment_url( get_post_thumbnail_id() ); ?>
												<?php
												$lafka_image_title = the_title_attribute(
													array(
														'post' => get_post_thumbnail_id(),
														'echo' => false,
													)
												);
												?>
												<?php $lafka_img_tag = wp_get_attachment_image( get_post_thumbnail_id(), 'lafka-foodmenu-single-thumb' ); ?>
												<?php printf( '<a href="%s" class="lafka-magnific-gallery-item" title="%s" >%s</a>', esc_url( $lafka_attach_url ), esc_attr( $lafka_image_title ), $lafka_img_tag ); ?>
											<?php endif; ?>
											<?php foreach ( $lafka_featured_flex_slider_imgs as $lafka_img_att_id ) : ?>
												<?php $lafka_attach_url = wp_get_attachment_url( $lafka_img_att_id ); ?>
												<?php
												$lafka_image_title = the_title_attribute(
													array(
														'post' => $lafka_img_att_id,
														'echo' => false,
													)
												);
												?>
												<?php $lafka_img_tag = wp_get_attachment_image( $lafka_img_att_id, 'lafka-foodmenu-single-thumb' ); ?>
												<?php printf( '<a href="%s" class="lafka-magnific-gallery-item" title="%s" >%s</a>', esc_url( $lafka_attach_url ), esc_attr( $lafka_image_title ), $lafka_img_tag ); ?>
											<?php endforeach; ?>
										</div>
									<?php elseif ( has_post_thumbnail() ) : ?>
										<?php the_post_thumbnail( 'lafka-foodmenu-single-thumb' ); ?>
									<?php else : ?>
										<img src="<?php echo esc_url( LAFKA_IMAGES_PATH . 'cat_not_found.png' ); ?>" alt="<?php esc_html_e( 'No image available', 'lafka' ); ?>"/>
									<?php endif; ?>
									<!-- END OF FEATURED SLIDER/IMAGE -->
								</div>
							<?php endif; ?>
							<div class="one_third last project-data">
								<div class="project-data-holder">
									<?php if ( $lafka_foodmenu_custom['lafka_add_description'][0] || ( ! empty( $lafka_foodmenu_custom['lafka_item_weight'][0] ) && ! empty( $lafka_foodmenu_custom['lafka_item_weight_unit'][0] ) ) ) : ?>
										<div class="more-details">
											<?php if ( ! empty( $lafka_foodmenu_custom['lafka_add_description'][0] ) ) : ?>
												<span class="lafka-more-details-text">
												<?php echo wp_kses_post( $lafka_foodmenu_custom['lafka_add_description'][0] ); ?>
												</span>
											<?php endif; ?>
											<?php if ( ! empty( $lafka_foodmenu_custom['lafka_item_weight'][0] ) && ! empty( $lafka_foodmenu_custom['lafka_item_weight_unit'][0] ) ) : ?>
												<ul class="lafka-item-weight-holder">
													<li>
														<span class="lafka-item-weight">
															<?php esc_html_e( 'Serving size', 'lafka' ); ?>:
															<span class="lafka-item-weight-values">
																<?php echo esc_html( $lafka_foodmenu_custom['lafka_item_weight'][0] . ' ' . $lafka_foodmenu_custom['lafka_item_weight_unit'][0] ); ?>
															</span>
														</span>
													</li>
												</ul>
											<?php endif; ?>
										</div>
									<?php endif; ?>
									<?php
									if ( $lafka_main_price || $lafka_ingredients || $lafka_allergens || $lafka_ext_link_button_title || $lafka_ext_link_url ) :
										?>
										<div class="project-details">
											<ul class="simple-list-underlined">
												<?php if ( $lafka_ingredients ) : ?>
													<li class="lafka-foodmenu-ingredients"><strong><?php esc_html_e( 'Ingredients', 'lafka' ); ?>:</strong> <?php echo esc_html( $lafka_ingredients ); ?></li>
												<?php endif; ?>

												<?php $lafka_nutrition_list = get_nutrition_list_for_foodmenu_entry( $lafka_foodmenu_custom ); ?>
												<?php if ( class_exists( 'Lafka_Nutrition_Config' ) && count( $lafka_nutrition_list ) ) : ?>
													<li class="lafka-foodmenu-nutrition-list">
														<ul>
															<?php foreach ( $lafka_nutrition_list as $lafka_nutrition_name => $lafka_nutrition_value ) : ?>
																<li 
																<?php
																if ( $lafka_nutrition_name === 'lafka_nutrition_energy' ) :
																	?>
																	class="lafka-nutrition-energy" <?php endif; ?> >
																	<span class="lafka-nutrition-list-label"><?php echo esc_html( Lafka_Nutrition_Config::$nutrition_meta_fields[ $lafka_nutrition_name ]['frontend_label'] ); ?></span>
																	<?php echo esc_html( $lafka_nutrition_value ); ?> <?php echo esc_html( Lafka_Nutrition_Config::$nutrition_meta_fields[ $lafka_nutrition_name ]['frontend_label_weight'] ); ?>
																	<span class="lafka-nutrition-list-label"><?php esc_html_e( 'DI', 'lafka' ); ?>*</span>
																	<?php echo esc_html( round( $lafka_nutrition_value / Lafka_Nutrition_Config::$nutrition_meta_fields[ $lafka_nutrition_name ]['DI'] * 100 ) ); ?>
																	%
																</li>
															<?php endforeach; ?>
														</ul>
														<span class="lafka-nutrition-di-legend">*<?php esc_html_e( 'DI', 'lafka' ); ?>: <?php esc_html_e( 'Recommended Daily Intake based on 2000 calories diet', 'lafka' ); ?></span>
													</li>
												<?php endif; ?>

												<?php if ( $lafka_allergens ) : ?>
													<li class="lafka-nutrition-allergens"><?php esc_html_e( 'Allergens', 'lafka' ); ?>: <?php echo esc_html( $lafka_allergens ); ?></li>
												<?php endif; ?>

												<?php if ( $lafka_main_price ) : ?>
													<li>
														<span class="lafka-foodmenu-main-price"><?php echo wp_kses_post( lafka_get_formatted_price( $lafka_main_price ) ); ?></span>
													</li>
												<?php endif; ?>

												<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
													<?php if ( isset( $lafka_foodmenu_custom[ 'lafka_item_size' . $i ] ) && $lafka_foodmenu_custom[ 'lafka_item_size' . $i ][0] ) : ?>
														<li class="lafka-foodmenu-options-list">
															<span class="lafka-foodmenu-option"><?php echo esc_html( $lafka_foodmenu_custom[ 'lafka_item_size' . $i ][0] ); ?></span>
															<?php if ( isset( $lafka_foodmenu_custom[ 'lafka_item_price' . $i ] ) && $lafka_foodmenu_custom[ 'lafka_item_price' . $i ][0] ) : ?>
																<?php $lafka_item_price = lafka_get_formatted_price( $lafka_foodmenu_custom[ 'lafka_item_price' . $i ][0] ); ?>
																<span class="lafka-foodmenu-price">
																	<?php echo wp_kses_post( $lafka_item_price ); ?>
																</span>
															<?php endif; ?>
														</li>
													<?php endif; ?>
												<?php endfor; ?>

												<?php if ( ( $lafka_ext_link_button_title && $lafka_ext_link_url ) ) : ?>
													<li>
														<?php if ( $lafka_ext_link_button_title && $lafka_ext_link_url ) : ?>
															<a class="button" target="_blank" href="<?php echo esc_url( $lafka_ext_link_url ); ?>" title="<?php echo esc_attr( $lafka_ext_link_button_title ); ?>"><?php echo esc_attr( $lafka_ext_link_button_title ); ?></a>
														<?php endif; ?>
													</li>
												<?php endif; ?>
											</ul>
										</div>
									<?php endif; ?>


								</div>
							</div>
							<div class="clear"></div>
						</div>
					<?php endif; ?>

					<?php if ( $post->post_content != '' ) : ?>
						<div class="full_width lafka-project-description
						<?php
						if ( $lafka_use_custom_content ) {
							echo ' lafka-custom-content';}
						?>
						">
							<?php the_content(); ?>
						</div>
					<?php endif; ?>
					<?php
					// Get random foodmenu projects from the same category as the current one
					$lafka_get_foodmenu_args = array(
						'posts_per_page' => '6',
						'post__not_in'   => array( $lafka_curr_foodmenu_id ),
						'orderby'        => 'rand',
						'post_type'      => 'lafka-foodmenu',
						'post_status'    => 'publish',
					);

					$lafka_get_terms_args          = array(
						'orderby' => 'name',
						'order'   => 'ASC',
					);
					$lafka_foodmenu_categories     = wp_get_object_terms( get_the_ID(), 'lafka_foodmenu_category', $lafka_get_terms_args );
					$lafka_foodmenu_first_category = null;
					if ( array_key_exists( 0, $lafka_foodmenu_categories ) ) {
						$lafka_foodmenu_first_category        = $lafka_foodmenu_categories[0];
						$lafka_get_foodmenu_args['tax_query'] = array(
							array(
								'taxonomy' => 'lafka_foodmenu_category',
								'field'    => 'slug',
								'terms'    => $lafka_foodmenu_first_category,
							),
						);
					}

					wp_reset_postdata();

					$lafka_similar_foodmenus = new WP_Query( $lafka_get_foodmenu_args );
					?>

					<?php if ( lafka_get_option( 'show_related_menu_entries' ) ) : ?>
						<?php if ( $lafka_similar_foodmenus->have_posts() ) : ?>
							<div class="similar_projects full_width">
								<h4>
									<?php esc_html_e( 'Other', 'lafka' ); ?>
									<?php if ( $lafka_foodmenu_first_category !== null ) : ?>
										<a class="lafka-related-browse"
											href="<?php echo esc_url( get_term_link( $lafka_foodmenu_first_category ) ); ?>"
											title="<?php printf( esc_attr__( 'Browse more "%s"', 'lafka' ), $lafka_foodmenu_first_category->name ); ?>">
											<?php echo esc_html( $lafka_foodmenu_first_category->name ); ?>
										</a>
									<?php else : ?>
										<?php esc_html_e( 'Items', 'lafka' ); ?>
									<?php endif; ?>
									<?php esc_html_e( 'you\'ll love', 'lafka' ); ?>
								</h4>

								<div>
								<?php endif; ?>

								<?php while ( $lafka_similar_foodmenus->have_posts() ) : ?>
									<?php $lafka_similar_foodmenus->the_post(); ?>
									<?php global $post; ?>
									<div class="foodmenu-unit lafka-none-overlay grid-unit">
										<div class="foodmenu-unit-holder">
											<?php if ( ! lafka_get_option( 'hide_foodmenu_images' ) ) : ?>
												<?php if ( has_post_thumbnail() ) : ?>
													<?php if ( ! lafka_get_option( 'foodmenu_simple_menu' ) ) : ?>
														<a title="<?php esc_attr_e( 'View more', 'lafka' ); ?>" href="<?php echo esc_url( get_the_permalink( get_the_ID() ) ); ?>" class="lafka-foodmenu-image-link">
													<?php endif; ?>
														<?php the_post_thumbnail( 'lafka-640x640' ); ?>
													<?php if ( ! lafka_get_option( 'foodmenu_simple_menu' ) ) : ?>
														</a>
													<?php endif; ?>
												<?php else : ?>
													<img src="<?php echo esc_attr( LAFKA_IMAGES_PATH . 'cat_not_found-small.png' ); ?>" alt="<?php esc_html_e( 'No image available', 'lafka' ); ?>" />
												<?php endif; ?>
											<?php endif; ?>
											<div class="foodmenu-unit-info">
												<a 
												<?php
												if ( ! lafka_get_option( 'foodmenu_simple_menu' ) ) :
													?>
													title="<?php esc_attr_e( 'View more', 'lafka' ); ?>" <?php endif; ?>
													href="
													<?php
													if ( ! lafka_get_option( 'foodmenu_simple_menu' ) ) {
														the_permalink();
													} else {
														echo '#';}
													?>
													"
													class="foodmenu-link">
													<h4>
														<?php the_title(); ?>
														<?php if ( $post->lafka_item_single_price ) : ?>
															<span><?php echo lafka_get_formatted_price( $post->lafka_item_single_price ); ?></span>
														<?php endif; ?>
													</h4>
												</a>
											</div>

										</div>
									</div>
								<?php endwhile; ?>

								<?php wp_reset_postdata(); ?>

								<?php if ( $lafka_similar_foodmenus->have_posts() ) : ?>
								</div>
								<div class="clear"></div>
							</div>
						<?php endif; ?>
					<?php endif; ?>
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
<?php endwhile; ?>
<!-- END OF MAIN CONTENT -->

<?php
get_footer();