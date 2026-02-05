<?php
// Partial to use when displaying lafka_foodmenu_category category, archive and page template
global $wp;
// Define lafka_foodmenu_js_params with some value - to trigger the isotope
wp_localize_script('lafka-front', 'lafka_foodmenu_js_params', array());

$lafka_thumb_size = 'lafka-general-small-size-nocrop';

$lafka_subtitle = '';
$lafka_title_background_image = '';
$lafka_title_alignment = 'left_title';

if (is_page()) {
// Get the lafka custom options
	$lafka_page_options = get_post_custom(get_the_ID());

	$lafka_show_title_page = 'yes';
	$lafka_show_breadcrumb = 'yes';
	$lafka_featured_slider = 'none';
	$lafka_rev_slider_before_header = 0;

	if (isset($lafka_page_options['lafka_show_title_page']) && trim($lafka_page_options['lafka_show_title_page'][0]) != '') {
		$lafka_show_title_page = $lafka_page_options['lafka_show_title_page'][0];
	}

	if (isset($lafka_page_options['lafka_show_breadcrumb']) && trim($lafka_page_options['lafka_show_breadcrumb'][0]) != '') {
		$lafka_show_breadcrumb = $lafka_page_options['lafka_show_breadcrumb'][0];
	}

	if (isset($lafka_page_options['lafka_rev_slider']) && trim($lafka_page_options['lafka_rev_slider'][0]) != '') {
		$lafka_featured_slider = $lafka_page_options['lafka_rev_slider'][0];
	}

	if (isset($lafka_page_options['lafka_rev_slider_before_header']) && trim($lafka_page_options['lafka_rev_slider_before_header'][0]) != '') {
		$lafka_rev_slider_before_header = $lafka_page_options['lafka_rev_slider_before_header'][0];
	}

	$lafka_featured_flex_slider_imgs = lafka_get_more_featured_images(get_the_ID());

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

	<div id="lafka_page_title" class="lafka_title_holder <?php echo esc_attr($lafka_title_alignment) ?> <?php if ($lafka_title_background_image): ?>title_has_image<?php endif; ?>">
		<?php if ($lafka_title_background_image): ?><div class="lafka-zoomable-background" style="background-image: url('<?php echo esc_url($lafka_title_background_image) ?>');"></div><?php endif; ?>
		<div class="inner fixed">
            <div class="lafka-title-text-container">
                <!-- BREADCRUMB -->
                <?php if ((is_page() && $lafka_show_breadcrumb == 'yes') || !is_page()): ?>
                    <?php lafka_breadcrumb() ?>
                <?php endif; ?>
                <!-- END OF BREADCRUMB -->
                <?php if (is_tax()): ?>
                    <h1 class="heading-title"><?php single_term_title() ?></h1>
                <?php elseif (is_page() && $lafka_show_title_page == 'yes'): ?>
                    <h1 class="heading-title"><?php the_title(); ?></h1>
                    <?php if ($lafka_subtitle): ?>
                        <h6><?php echo esc_html($lafka_subtitle) ?></h6>
                    <?php endif; ?>
                <?php elseif (!is_page()): ?>
                    <h1 class="heading-title"><?php esc_html_e('Menu', 'lafka') ?></h1>
                <?php endif; ?>
            </div>
		</div>
	</div>
	<div class="inner">
		<!-- CONTENT WRAPPER -->
		<div id="main" class="fixed box box-common">
			<div class="content_holder">
				<?php if (is_page() && !empty($lafka_featured_flex_slider_imgs)): ?>
					<div class="lafka_flexslider  post_slide">
						<ul class="slides">
							<?php if (has_post_thumbnail()): ?>
								<li>
									<?php echo wp_get_attachment_image(get_post_thumbnail_id(), 'lafka-foodmenu-single-thumb'); ?>
								</li>
							<?php endif; ?>

							<?php foreach ($lafka_featured_flex_slider_imgs as $lafka_img_att_id): ?>
								<li>
									<?php echo wp_get_attachment_image($lafka_img_att_id, 'lafka-foodmenu-single-thumb'); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php elseif (is_page() && $lafka_featured_slider != 'none' && function_exists('putRevSlider') && !$lafka_rev_slider_before_header): ?>
					<!-- FEATURED REVOLUTION SLIDER -->
					<div class="slideshow">
						<?php putRevSlider($lafka_featured_slider) ?>
					</div>
					<!-- END OF FEATURED REVOLUTION SLIDER -->
				<?php elseif (is_page() && has_post_thumbnail()): ?>
					<?php the_post_thumbnail('lafka-foodmenu-single-thumb'); ?>
				<?php endif; ?>

				<?php if (is_tax()): ?>
					<?php if (term_description()): ?>
						<div class="foodmenu-cat-desc">
							<?php echo wp_kses_post(term_description()); ?>
						</div>
					<?php endif; ?>
					<?php $lafka_curr_category = get_queried_object(); ?>
					<?php $lafka_portgolio_categories = array($lafka_curr_category) ?>
					<?php $lafka_foodmenu_categories = array_merge($lafka_portgolio_categories, get_term_children($lafka_curr_category->term_id, 'lafka_foodmenu_category')); ?>
				<?php else: ?>
					<?php $lafka_foodmenu_categories = get_terms('lafka_foodmenu_category'); ?>
				<?php endif; ?>

				<?php if (count($lafka_foodmenu_categories) > 0): ?>
					<div class="lafka-foodmenu-categories">
						<ul>
							<?php if (!is_tax()): ?>
								<li><a class="is-checked" data-filter="*" href="#"><?php esc_html_e('show all', 'lafka') ?></a></li>
							<?php endif; ?>
							<?php foreach ($lafka_foodmenu_categories as $lafka_category): ?>
								<?php if (!is_object($lafka_category)) $lafka_category = get_term_by('id', $lafka_category, 'lafka_foodmenu_category') ?>
								<li><a <?php if (is_tax() && get_queried_object()->term_id == $lafka_category->term_id) echo 'class="is-checked"' ?> data-filter=".<?php echo esc_attr($lafka_category->slug) ?>" href="#"><?php echo esc_html($lafka_category->name) ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php
				global $query_string;

				if (is_page()) {
					//get all foodmenus
					$lafka_foodmenus = new WP_Query('post_type=lafka-foodmenu&post_status=publish&nopaging=true');
				} else {
					$lafka_foodmenus = new WP_Query($query_string . '&post_type=lafka-foodmenu&nopaging=true');
				}
				?>
				<div class="foodmenus">
					<?php while ($lafka_foodmenus->have_posts()): ?>
						<?php $lafka_foodmenus->the_post(); ?>
						<?php $lafka_foodmenu = get_post(); ?>
						<?php
						// Main Price
						$lafka_main_price = get_post_meta(get_the_ID(), 'lafka_item_single_price', true);
						// Weight
						$lafka_weight = get_post_meta(get_the_ID(), 'lafka_item_weight', true);
						// Weight Unit
						$lafka_weight_unit = get_post_meta(get_the_ID(), 'lafka_item_weight_unit', true);
						// Ingredients
						$lafka_ingredients = get_post_meta(get_the_ID(), 'lafka_ingredients', true);

						$lafka_terms_arr = array();
						$lafka_current_terms = get_the_terms($lafka_foodmenu->ID, 'lafka_foodmenu_category');
						$lafka_current_terms_as_simple_array = array();

						if ($lafka_current_terms) {
							foreach ($lafka_current_terms as $lafka_term) {
								$lafka_current_terms_as_simple_array[] = $lafka_term->name;

								$lafka_ancestors = lafka_get_lafka_foodmenu_category_parents($lafka_term->term_id);
								foreach ($lafka_ancestors as $lafka_term_ancestor) {
									$lafka_terms_arr[] = $lafka_term_ancestor->slug;
								}
							}
							$lafka_terms_arr = array_unique($lafka_terms_arr);
						}

						$lafka_featured_image_attr = wp_get_attachment_image_src(get_post_thumbnail_id($lafka_foodmenu->ID), 'full');
						$lafka_featured_image_src = '';
						if ($lafka_featured_image_attr) {
							$lafka_featured_image_src = $lafka_featured_image_attr[0];
						}
						?>
						<div class="foodmenu-unit lafka-none-overlay <?php echo esc_attr(implode(' ', $lafka_terms_arr)) ?>">
							<div class="foodmenu-unit-holder">

								<?php if(!lafka_get_option('hide_foodmenu_images')): ?>
                                    <?php if (has_post_thumbnail($lafka_foodmenu->ID)): ?>
										<?php if(!lafka_get_option('foodmenu_simple_menu')): ?>
                                            <a title="<?php esc_attr_e('View more', 'lafka') ?>" href="<?php echo esc_url(get_the_permalink($lafka_foodmenu->ID)); ?>" class="lafka-foodmenu-image-link">
                                        <?php endif; ?>
                                            <?php echo get_the_post_thumbnail($lafka_foodmenu->ID, $lafka_thumb_size); ?>
	                                    <?php if(!lafka_get_option('foodmenu_simple_menu')): ?>
                                            </a>
	                                    <?php endif; ?>
                                    <?php else: ?>
                                        <img src="<?php echo esc_attr(LAFKA_IMAGES_PATH . 'cat_not_found-small.png') ?>" alt="<?php esc_html_e('No image available', 'lafka') ?>" />
                                    <?php endif; ?>
								<?php endif; ?>

                                <div class="foodmenu-unit-info">
                                    <a <?php if(!lafka_get_option('foodmenu_simple_menu')):?> title="<?php esc_attr_e('View more', 'lafka') ?>" <?php endif; ?>
                                       href="<?php if(!lafka_get_option('foodmenu_simple_menu')) echo esc_url(get_the_permalink($lafka_foodmenu->ID)); else echo '#' ?>"
                                       class="foodmenu-link" >
                                        <h4>
                                            <?php echo esc_html(get_the_title($lafka_foodmenu->ID)); ?>
	                                        <?php if($lafka_weight && $lafka_weight_unit): ?>
                                                <span class="lafka-item-weight-list"><?php echo esc_html($lafka_weight . ' ' . $lafka_weight_unit)?></span>
	                                        <?php endif; ?>
	                                        <?php if($lafka_main_price): ?>
                                                <span><?php echo lafka_get_formatted_price($lafka_main_price) ?></span>
	                                        <?php endif; ?>
                                        </h4>
                                        <?php if ($lafka_ingredients): ?>
                                            <h6><?php echo esc_html($lafka_ingredients); ?></h6>
                                        <?php endif; ?>
	                                    <?php if ( lafka_has_foodmenu_options( $lafka_foodmenu ) ): ?>
		                                    <?php $lafka_foodmenu_options_array = lafka_get_foodmenu_options( $lafka_foodmenu ); ?>
                                            <ul>
			                                    <?php foreach ( $lafka_foodmenu_options_array as $option => $price ): ?>
                                                    <li>
					                                    <?php if ( $option ): ?>
                                                            <span class="lafka-foodmenu-option"><?php echo esc_html( $option ) ?></span>
					                                    <?php endif; ?>
					                                    <?php if ( $price ): ?>
                                                            <span class="lafka-foodmenu-price"><?php echo wp_kses_post( $price ) ?></span>
					                                    <?php endif; ?>
                                                    </li>
			                                    <?php endforeach; ?>
                                            </ul>
	                                    <?php endif; ?>
                                    </a>
                                    <?php if ($lafka_featured_image_src && lafka_get_option('show_light_menu_entries')): ?>
                                        <a class="foodmenu-lightbox-link" href="<?php echo esc_url($lafka_featured_image_src) ?>"><span></span></a>
                                    <?php endif; ?>
                                </div>

							</div>
						</div>
					<?php endwhile; ?>
				</div>
                <?php if (!$lafka_foodmenus->have_posts()): ?>
					<p><?php esc_html_e('No Foodmenu found. Sorry!', 'lafka'); ?></p>
				<?php endif; ?>
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
	</div>
</div>