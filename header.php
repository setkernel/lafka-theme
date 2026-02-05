<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
		<meta name="viewport" content="width=device-width, maximum-scale=1" />
		<link rel="profile" href="http://gmpg.org/xfn/11" />
		<link rel="pingback" href="<?php esc_url(bloginfo('pingback_url')); ?>" />
		<?php wp_head(); ?>
	</head>

	<body <?php body_class(); ?>>
		<?php if (lafka_get_option('show_preloader')): ?>
		<div class="mask">
				<div id="spinner"><div class="double-bounce1"></div><div class="double-bounce2"></div>
				</div>
			</div>
		<?php endif; ?>
		<?php if (lafka_get_option('add_to_cart_sound')): ?>
            <audio id="cart_add_sound" controls preload="auto" hidden="hidden">
                <source src="<?php echo LAFKA_IMAGES_PATH ?>cart_add.wav" type="audio/wav">
            </audio>
		<?php endif; ?>
		<?php
		global $lafka_is_blank;

		// Set main menu as mobile if no mobile menu was set
		$mobile_menu_id  = 'primary';
		if ( has_nav_menu('mobile') ) {
			$mobile_menu_id = "mobile";
		}

		if (!$lafka_is_blank) {
			// Top mobile menu
			$lafka_top_nav_mobile_args = array(
					'theme_location' => $mobile_menu_id,
					'container' => 'div',
					'container_id' => 'menu_mobile',
					'menu_id' => 'mobile-menu',
					'items_wrap' => lafka_build_mobile_menu_items_wrap(),
					'fallback_cb' => '',
					'walker' => new LafkaMobileMenuWalker()
			);
			wp_nav_menu($lafka_top_nav_mobile_args);
		}

		// Are search or cart enabled or is account page
		$lafka_is_search_or_cart_or_account = false;
		if (lafka_get_option('show_searchform') || (LAFKA_IS_WOOCOMMERCE && lafka_get_option('show_shopping_cart'))|| (LAFKA_IS_WOOCOMMERCE && get_option( 'woocommerce_myaccount_page_id' ))) {
			$lafka_is_search_or_cart_or_account = true;
		}

		$lafka_general_layout = lafka_get_option('general_layout');
		$lafka_specific_layout = get_post_meta(get_queried_object_id(), 'lafka_layout', true);

		$lafka_meta_show_top_header = get_post_meta(get_queried_object_id(), 'lafka_top_header', true);
		if (!$lafka_meta_show_top_header) {
			$lafka_meta_show_top_header = 'default';
		}

		$lafka_featured_slider = get_post_meta(get_queried_object_id(), 'lafka_rev_slider', true);
		if (!$lafka_featured_slider) {
			$lafka_featured_slider = 'none';
		}

		$lafka_rev_slider_before_header = get_post_meta(get_queried_object_id(), 'lafka_rev_slider_before_header', true);
		if (!$lafka_rev_slider_before_header) {
			$lafka_rev_slider_before_header = 0;
		}
		?>
		<?php if (lafka_get_option('show_searchform')): ?>
            <div id="search">
				<?php $lafka_search_options = lafka_get_option('search_options'); ?>
				<?php if (LAFKA_IS_WOOCOMMERCE && isset($lafka_search_options['only_products']) && $lafka_search_options['only_products']): ?>
					<?php get_product_search_form(true) ?>
				<?php else: ?>
					<?php get_search_form(); ?>
				<?php endif; ?>
            </div>
		<?php endif; ?>
		<!-- MAIN WRAPPER -->
		<div id="container">
			<?php if (!$lafka_is_blank): ?>

				<?php if (is_page() && $lafka_featured_slider != 'none' && function_exists('putRevSlider') && $lafka_rev_slider_before_header): ?>
					<div class="lafka-intro slideshow">
						<div class="inner">
							<?php putRevSlider($lafka_featured_slider) ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if (lafka_get_option('enable_pre_header') && is_active_sidebar('pre_header_sidebar')): ?>
					<div id="pre_header"> <a href="#" class="toggler" id="toggle_switch" title="<?php esc_attr_e('Show/Hide', 'lafka') ?>"><?php esc_html_e('Slide toggle', 'lafka') ?></a>
						<div id="togglerone" class="inner">
							<!-- Pre-Header widget area -->
							<?php dynamic_sidebar('pre_header_sidebar') ?>
							<div class="clear"></div>
						</div>
					</div>
				<?php endif; ?>

				<!-- HEADER -->
				<?php
				$lafka_should_show_top_header = false;
				if ( lafka_get_option( 'enable_top_header' ) && $lafka_meta_show_top_header == 'default' || $lafka_meta_show_top_header == 'show' ) {
					$lafka_should_show_top_header = true;
				}

				$lafka_header_classes = array();
				if ( $lafka_should_show_top_header ) {
					$lafka_header_classes[] = 'lafka-has-header-top';
				}

				$lafka_is_text_logo = lafka_is_text_logo( lafka_get_option( 'theme_logo' ) );
				if ( $lafka_is_text_logo ) {
					$lafka_header_classes[] = 'lafka_text_logo';
				}
				?>
				<div id="header" <?php if(!empty($lafka_header_classes)):?>class="<?php echo esc_attr(implode(' ', $lafka_header_classes)) ?>" <?php endif; ?> >
					<?php if ( $lafka_should_show_top_header ): ?>
						<div id="header_top" class="fixed">
							<div class="inner<?php if(has_nav_menu("secondary")) echo " has-top-menu" ?>">
								<?php if (function_exists('icl_get_languages')): ?>
									<div id="language">
										<?php lafka_language_selector_flags(); ?>
									</div>
								<?php endif; ?>
								<?php
								/* Top Right Menu */
								$lafka_top_right_menu_args = array(
									'theme_location' => 'top-right',
									'container' => 'div',
									'container_id' => 'lafka-top-right-menu-container',
									'menu_class' => 'lafka-top-menu',
									'menu_id' => 'lafka-top-right-menu',
									'fallback_cb' => '',
								);
								wp_nav_menu($lafka_top_right_menu_args);

                                get_template_part('partials/logo');

								/* Top Left Menu */
								$lafka_top_left_menu_args = array(
									'theme_location' => 'top-left',
									'container' => 'div',
									'container_id' => 'lafka-top-left-menu-container',
									'menu_class' => 'lafka-top-menu',
									'menu_id' => 'lafka-top-left-menu',
									'fallback_cb' => '',
								);
								wp_nav_menu($lafka_top_left_menu_args);
								?>
							</div>
						</div>
					<?php endif; ?>

					<div class="inner main_menu_holder fixed<?php if(has_nav_menu('primary')) echo ' has-main-menu' ?>">
						<?php if (lafka_get_option('top_bar_message') || lafka_get_option('top_bar_message_phone')): ?>
                            <div class="lafka-top-bar-message">
	                            <?php if (lafka_get_option('top_bar_message')): ?>
                                    <span class="lafka-top-bar-message-text">
                                        <?php echo esc_html(lafka_get_option('top_bar_message')) ?>
                                    </span>
	                            <?php endif; ?>
								<?php if (lafka_get_option('top_bar_message_phone')): ?>
                                    <span class="lafka-top-bar-phone">
												<?php if ( lafka_get_option( 'top_bar_message_phone_link' ) ): ?><a href="tel:<?php echo preg_replace( "/[^0-9+-]/", "", esc_html( lafka_get_option( 'top_bar_message_phone' ) ) ) ?>"><?php endif; ?>
											<?php echo esc_html( lafka_get_option( 'top_bar_message_phone' ) ) ?>
											<?php if ( lafka_get_option( 'top_bar_message_phone_link' ) ): ?></a><?php endif; ?>
											</span>
								<?php endif; ?>
                            </div>
						<?php endif; ?>

						<?php if ( !$lafka_should_show_top_header ): ?>
							<?php get_template_part('partials/logo'); ?>
						<?php endif; ?>

						<a class="mob-menu-toggle" href="#"><i class="fa fa-bars"></i></a>

						<?php if ($lafka_is_search_or_cart_or_account): ?>
							<div class="lafka-search-cart-holder">
								<?php if (lafka_get_option('show_searchform')): ?>
                                    <div class="lafka-search-trigger">
                                        <a href="#" title="<?php echo esc_attr__('Search', 'lafka') ?>"><i class="fa fa-search"></i></a>
                                    </div>
								<?php endif; ?>

								<!-- SHOPPING CART -->
								<?php if (LAFKA_IS_WOOCOMMERCE && lafka_get_option('show_shopping_cart')): ?>
									<ul id="cart-module" class="site-header-cart">
										<?php lafka_cart_link(); ?>
										<li>
											<?php the_widget('WC_Widget_Cart', 'title='); ?>
										</li>
									</ul>
								<?php endif; ?>
								<!-- END OF SHOPPING CART -->

								<?php if (lafka_should_show_wishlist_icon()): ?>
									<div class="lafka-wishlist-counter">
										<a href="<?php echo esc_url(YITH_WCWL()->get_wishlist_url()); ?>" title="<?php echo esc_attr__('Favorites', 'lafka') ?>">
											<i class="fa fa-heart"></i>
											<?php if ( method_exists( 'YITH_WCWL_Wishlists', 'count_items_in_wishlist' ) ): ?>
                                                <span class="lafka-wish-number"><?php echo esc_html( yith_wcwl_wishlists()->count_items_in_wishlist() ); ?></span>
											<?php else: ?>
                                                <span class="lafka-wish-number"><?php echo esc_html( YITH_WCWL()->count_products() ); ?></span>
											<?php endif; ?>
										</a>
									</div>
								<?php endif; ?>

								<?php global $current_user; ?>

								<?php $lafka_has_content_woocommerce_my_account = false; ?>
								<?php if(isset($post->post_content) && has_shortcode($post->post_content, 'woocommerce_my_account') ): ?>
									<?php $lafka_has_content_woocommerce_my_account = true; ?>
								<?php endif; ?>

								<?php if (lafka_should_show_account_icon() && (is_user_logged_in() || (!is_user_logged_in() && !$lafka_has_content_woocommerce_my_account))): ?>
									<?php wp_get_current_user(); ?>
									<?php
									$lafka_account_holder_classes = array();
									if(is_user_logged_in()) {
										$lafka_account_holder_classes[] = 'lafka-user-is-logged';
									} else {
										$lafka_account_holder_classes[] = 'lafka-user-not-logged';
									}
									?>
                                    <div id="lafka-account-holder" <?php if(count($lafka_account_holder_classes)) echo 'class="'.implode(' ', $lafka_account_holder_classes).'"' ?> >
                                        <a href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>" title="<?php esc_attr_e( 'My Account', 'lafka' ); ?>">
                                            <i class="fa fa-user"></i>
                                        </a>
                                        <div class="lafka-header-account-link-holder">
		                                    <?php if(is_user_logged_in()): ?>
                                                <ul>
                                                    <li>
                                                        <span class="lafka-header-user-data">
                                                            <?php echo get_avatar($current_user->ID, 60); ?>
                                                            <small><?php echo esc_html($current_user->display_name); ?></small>
                                                        </span>
                                                    </li>
				                                    <?php if (LAFKA_IS_WC_MARKETPLACE && is_user_wcmp_vendor($current_user)): ?>
                                                        <li class="lafka-header-account-wcmp-dash">
	                                                        <?php $lafka_wcmp_dashboard_page_link = wcmp_vendor_dashboard_page_id() ? get_permalink(wcmp_vendor_dashboard_page_id()) : '#'; ?>
	                                                        <?php echo apply_filters('wcmp_vendor_goto_dashboard', '<a href="' . esc_url($lafka_wcmp_dashboard_page_link) . '">' . esc_html__('Vendor Dashboard', 'lafka') . '</a>'); ?>
                                                        </li>
				                                    <?php elseif(LAFKA_IS_WC_VENDORS_PRO && WCV_Vendors::is_vendor( $current_user->ID )): ?>
                                                        <li class="lafka-header-account-vcvendors-pro-dash">
						                                    <?php $lafka_wcv_pro_dashboard_page 	= WCVendors_Pro::get_option( 'dashboard_page_id' ); ?>
						                                    <?php if($lafka_wcv_pro_dashboard_page): ?>
                                                                <a href="<?php echo esc_url(get_permalink($lafka_wcv_pro_dashboard_page)); ?>"><?php echo esc_html__('Vendor Dashboard', 'lafka'); ?></a>
						                                    <?php endif; ?>
                                                        </li>
				                                    <?php elseif(LAFKA_IS_WC_VENDORS && WCV_Vendors::is_vendor( $current_user->ID )): ?>
                                                        <li class="lafka-header-account-vcvendors-dash">
						                                    <?php $lafka_wcv_free_dashboard_page 	= WC_Vendors::$pv_options->get_option( 'vendor_dashboard_page' ); ?>
						                                    <?php if($lafka_wcv_free_dashboard_page): ?>
                                                                <a href="<?php echo esc_url(get_permalink($lafka_wcv_free_dashboard_page)); ?>"><?php echo esc_html__('Vendor Dashboard', 'lafka'); ?></a>
						                                    <?php endif; ?>
                                                        </li>
				                                    <?php endif; ?>
				                                    <?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
                                                        <li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
                                                            <a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
                                                        </li>
				                                    <?php endforeach; ?>
                                                </ul>
		                                    <?php elseif(!$lafka_has_content_woocommerce_my_account): ?>
			                                    <?php echo do_shortcode('[woocommerce_my_account]'); ?>
		                                    <?php endif; ?>
                                        </div>
                                    </div>
								<?php endif; ?>

							</div>
						<?php endif; ?>
						<?php
						// Top menu
						$lafka_top_menu_container_class = 'menu-main-menu-container';

						$lafka_top_nav_args = array(
								'theme_location' => 'primary',
								'container' => 'div',
								'container_id' => 'main-menu',
								'container_class' => $lafka_top_menu_container_class,
								'menu_id' => 'main_nav',
								'fallback_cb' => '',
								'walker' => new LafkaFrontWalker()
						);
						wp_nav_menu($lafka_top_nav_args);
						?>
					</div>
				</div>
				<!-- END OF HEADER -->
			<?php endif; ?>
