<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// Woocommerce specific functions
/** @var $product WC_Product */

// Disable WooCommerce styles
if ( version_compare( WC_VERSION, '2.1' ) >= 0 ) {
	add_filter( 'woocommerce_enqueue_styles', '__return_false' );
} else {
	define( 'WOOCOMMERCE_USE_CSS', false );
}

add_filter( 'woocommerce_breadcrumb_defaults', 'lafka_woocommerce_breadcrumb_defaults' );
if ( ! function_exists( 'lafka_woocommerce_breadcrumb_defaults' ) ) {
	function lafka_woocommerce_breadcrumb_defaults( $args ) {
		$args['delimiter']   = ' <span class="lafka-breadcrumb-delimiter">/</span> ';
		$args['wrap_before'] = '<div class="breadcrumb">';
		$args['wrap_after']  = '</div>';

		return $args;
	}
}

add_filter( 'woocommerce_breadcrumb_home_url', 'lafka_woocommerce_breadcrumb_home_url' );
if ( ! function_exists( 'lafka_woocommerce_breadcrumb_home_url' ) ) {
	function lafka_woocommerce_breadcrumb_home_url( $home_url ) {
		return lafka_wpml_get_home_url();
	}
}

// removed breadcrumb from hook and call explicitly in wrapper-start
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

/**
 * Display the image part of the product in loop
 *
 * Takes into account product_hover_onproduct theme option
 */
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

// v5.17.0: suppress the loop add-to-cart button. Whole card is the tap
// target (link to PDP). Operators wanting the button back can re-add it
// from their child theme via add_action().
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
add_filter( 'woocommerce_before_shop_loop_item', 'lafka_shop_loop_image', 10 );

// v5.17.0: legacy lafka_shop_loop_image (registered just above) emits its
// own .image > a > img block, which would duplicate the thumbnail rendered
// directly in the new content-product.php template via
// lafka_product_card_image_html(). Removed AFTER the add_filter so the
// hook is actually unregistered. Operators with custom templates that
// depend on the legacy markup can re-add it from a child theme.
remove_filter( 'woocommerce_before_shop_loop_item', 'lafka_shop_loop_image', 10 );

if ( ! function_exists( 'lafka_shop_loop_image' ) ) {

	function lafka_shop_loop_image() {
		global $post, $product;
		echo '<div class="image">';

		?>

		<a href="<?php the_permalink(); ?>">
			<?php woocommerce_template_loop_product_thumbnail(); ?>
			<?php
			$second_image = lafka_get_second_product_image_id( $product ? $product : $post );
			// If we have swap image enabled and second image:
			if ( get_theme_mod( 'lafka_product_hover_onproduct', 'lafka-prodhover-zoom' ) == 'lafka-prodhover-swap' && $second_image ) :
				?>
				<?php
				$image_size = apply_filters( 'single_product_archive_thumbnail_size', 'shop_catalog' );

				$alt   = get_post_meta( $second_image, '_wp_attachment_image_alt', true );
				$title = get_the_title( $second_image );
				echo wp_get_attachment_image(
					$second_image,
					$image_size,
					false,
					array(
						'title' => $title,
						'alt'   => $alt ? $alt : $title,
					)
				);
				?>
			<?php endif; ?>
		</a>
		<?php
		// Append Add to wishlist shortcode if it exists
		if ( shortcode_exists( 'yith_wcwl_add_to_wishlist' ) ) {
			echo do_shortcode( '[yith_wcwl_add_to_wishlist]' );
		}
		echo '</div>';
	}

}

if ( ! function_exists( 'lafka_get_second_product_image_id' ) ) {
	/**
	 * Returns the second product image ID (if any)
	 * Else returns false
	 *
	 * @param mixed $post Post object or post ID of the product.
	 *
	 * @return int|bool false if no second image OR the attachment ID of the image
	 */
	function lafka_get_second_product_image_id( $post ) {
		$product = is_a( $post, 'WC_Product' ) ? $post : wc_get_product( $post );
		if ( ! $product ) {
			return false;
		}
		$imageIds = $product->get_gallery_image_ids();

		return isset( $imageIds[0] ) ? $imageIds[0] : false;
	}
}

/**
 * Checks if the product is in the new period
 *
 * @param WC_Product $product
 *
 * @return boolean
 */
if ( ! function_exists( 'lafka_is_product_new' ) ) {

	function lafka_is_product_new( $product ) {
		/** @var $product WC_Product */

		$days_product_is_new = get_theme_mod( 'lafka_new_label_period', 45 );

		if ( $days_product_is_new != 0 ) {
			$post_date_dt = date_create( $product->get_date_created() );
			$curr_date_dt = date_create( 'now' );
			$post_date_ts = $post_date_dt->format( 'Y-m-d' );
			$curr_date_ts = $curr_date_dt->format( 'Y-m-d' );

			$diff  = abs( strtotime( $post_date_ts ) - strtotime( $curr_date_ts ) );
			$diff /= 3600 * 24;

			if ( $diff < $days_product_is_new ) {
				return true;
			}
		}

		return false;
	}

}

/**
 * Returns the "not sale" price.
 * Used by lafka_get_product_saving()
 *
 * @param WC_Product $product
 *
 * @return type
 */
if ( ! function_exists( 'lafka_get_product_not_sale_price' ) ) {

	function lafka_get_product_not_sale_price( $product ) {
		/** @var $product WC_Product */
		if ( $product->is_type( 'variable' ) ) {
			return $product->get_variation_regular_price( 'min' );
		} else {
			return $product->get_regular_price();
		}
	}

}

if ( ! function_exists( 'lafka_get_product_sale_price' ) ) {
	function lafka_get_product_sale_price( $product ) {
		/** @var $product WC_Product */
		if ( $product->is_type( 'variable' ) ) {
			return $product->get_variation_sale_price();
		} else {
			return $product->get_price();
		}
	}
}

/**
 * Gets product saving
 *
 * @param WC_Product $product
 *
 * @return type
 */
if ( ! function_exists( 'lafka_get_product_saving' ) ) {

	function lafka_get_product_saving( $product ) {
		/** @var $product WC_Product */
		if ( $product->is_on_sale() ) {
			$sale_price     = lafka_get_product_sale_price( $product );
			$not_sale_price = lafka_get_product_not_sale_price( $product );

			$saving = 100 - $sale_price / $not_sale_price * 100;

			return round( $saving );
		}
	}

}

// Unload PrettyPhoto init for Woocommerce only
add_action( 'wp_enqueue_scripts', 'lafka_remove_wc_prettyphoto' );

if ( ! function_exists( 'lafka_remove_wc_prettyphoto' ) ) {

	function lafka_remove_wc_prettyphoto() {
		wp_dequeue_script( 'prettyPhoto-init' );
	}

}

// remove result count showing on top of category
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

// Display content holder
add_action( 'woocommerce_before_shop_loop', 'lafka_add_content_holder', 5 );
if ( ! function_exists( 'lafka_add_content_holder' ) ) {

	function lafka_add_content_holder() {

		echo '<div class="content_holder">';

		$style_class = 'columns-' . get_theme_mod( 'lafka_category_columns_num', '3' );

		if ( get_theme_mod( 'lafka_enable_shop_cat_carousel', true ) ) {
			// owl carousel
			wp_localize_script(
				'lafka-libs-config',
				'lafka_owl_carousel_cat',
				array(
					'columns' => esc_js( get_theme_mod( 'lafka_category_columns_num', '3' ) ),
				)
			);

			$style_class = 'owl-carousel lafka-owl-carousel';
		}

		// Get display mode - compatible with WC 3.3+
		if ( function_exists( 'woocommerce_get_loop_display_mode' ) ) {
			$display_type = woocommerce_get_loop_display_mode();
		} else {
			$display_type = is_product_category()
				? get_option( 'woocommerce_category_archive_display', '' )
				: get_option( 'woocommerce_shop_page_display', '' );
		}
		if ( 'subcategories' === $display_type || 'both' === $display_type ) {
			$before_categories_html = '<div class="lafka_woo_categories_shop woocommerce ' . esc_attr( $style_class ) . '">';
			if ( function_exists( 'woocommerce_maybe_show_product_subcategories' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- woocommerce_maybe_show_product_subcategories() returns WC core HTML with per-piece escaping.
				echo woocommerce_maybe_show_product_subcategories( $before_categories_html );
			}
			echo '</div>';
		}

		$options_branches = get_option( 'lafka_shipping_areas_branches' );
		if ( isset( $options_branches['show_branches_info_in'] ) && in_array( 'shop', $options_branches['show_branches_info_in'], true ) && class_exists( 'Lafka_Branch_Locations' ) ) {
			Lafka_Branch_Locations::show_change_branch();
		}

		// Check if products will display - compatible with WC 4.0+
		$lafka_products_will_display = true;
		if ( function_exists( 'woocommerce_products_will_display' ) ) {
			$lafka_products_will_display = woocommerce_products_will_display();
		} else {
			$lafka_products_will_display = ( 'subcategories' !== $display_type || is_search() || is_paged() );
		}
		if ( get_theme_mod( 'lafka_show_refine_area', true ) && $lafka_products_will_display ) {
			echo '<div class="box-sort-filter' . ( is_active_sidebar( 'lafka_product_filters_sidebar' ) ? ' lafka-product-filters-has-widgets' : '' ) . '">';
			echo '<div class="product-filter">';
			if ( is_active_sidebar( 'lafka_product_filters_sidebar' ) ) {
				echo '<a title="' . esc_attr__( 'More Filters', 'lafka' ) . '" class="lafka-filter-widgets-triger" href="#">' . esc_html__( 'Filter', 'lafka' ) . '</a>';
			}
		}
	}

}

add_filter( 'woocommerce_price_filter_widget_step', 'lafka_set_price_filter_widget_step' );
if ( ! function_exists( 'lafka_set_price_filter_widget_step' ) ) {
	function lafka_set_price_filter_widget_step() {
		return get_theme_mod( 'lafka_price_filter_widget_step', 10 );
	}
}

// Price filter on category pages
if ( get_theme_mod( 'lafka_show_pricefilter', true ) && get_theme_mod( 'lafka_show_refine_area', true ) ) {
	add_action( 'woocommerce_before_shop_loop', 'lafka_price_filter', 10 );
}

if ( ! function_exists( 'lafka_price_filter' ) ) {

	function lafka_price_filter() {
		global $wp;

		if ( ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}

		// If there are not posts and we're not filtering, hide the widget.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public shop archive widget gating on presence of public WC query params; no state mutation.
		if ( ! WC()->query->get_main_query()->post_count && ! isset( $_GET['min_price'] ) && ! isset( $_GET['max_price'] ) ) {
			return;
		}

		wp_enqueue_style( 'jquery-ui' );
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'lafka-price-slider', get_template_directory_uri() . '/js/lafka-price-slider' . $suffix . '.js', array( 'jquery-ui-slider', 'wc-jquery-ui-touchpunch', 'accounting' ), lafka_asset_version( '/js/lafka-price-slider' . $suffix . '.js' ), true );

		// Round values to nearest 10 by default.
		$step = max( apply_filters( 'woocommerce_price_filter_widget_step', 10 ), 1 );

		// Find min and max price in current result set.
		$prices    = lafka_get_filtered_price();
		$min_price = $prices->min_price;
		$max_price = $prices->max_price;

		// Check to see if we should add taxes to the prices if store are excl tax but display incl.
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		if ( wc_tax_enabled() && ! wc_prices_include_tax() && 'incl' === $tax_display_mode ) {
			$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
			$tax_rates = WC_Tax::get_rates( $tax_class );

			if ( $tax_rates ) {
				$min_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min_price, $tax_rates ) );
				$max_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $max_price, $tax_rates ) );
			}
		}

		$min_price = apply_filters( 'woocommerce_price_filter_widget_min_amount', floor( $min_price / $step ) * $step );
		$max_price = apply_filters( 'woocommerce_price_filter_widget_max_amount', ceil( $max_price / $step ) * $step );

		// If both min and max are equal, we don't need a slider.
		if ( $min_price === $max_price ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public shop archive query param; floatval() + wp_unslash() sanitize to a numeric.
		$current_min_price = isset( $_GET['min_price'] ) ? floor( floatval( wp_unslash( $_GET['min_price'] ) ) / $step ) * $step : $min_price;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public shop archive query param; floatval() + wp_unslash() sanitize to a numeric.
		$current_max_price = isset( $_GET['max_price'] ) ? ceil( floatval( wp_unslash( $_GET['max_price'] ) ) / $step ) * $step : $max_price;

		// Remember current filters/search
		$fields = '';

		if ( get_search_query() ) {
			$fields .= '<input type="hidden" name="s" value="' . get_search_query() . '" />';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- price-filter widget renders hidden inputs preserving public shop archive URL params; no state mutation.
		if ( ! empty( $_GET['post_type'] ) ) {
			$fields .= '<input type="hidden" name="post_type" value="' . esc_attr( $_GET['post_type'] ) . '" />';
		}

		if ( ! empty( $_GET['product_cat'] ) ) {
			$fields .= '<input type="hidden" name="product_cat" value="' . esc_attr( $_GET['product_cat'] ) . '" />';
		}

		if ( ! empty( $_GET['product_tag'] ) ) {
			$fields .= '<input type="hidden" name="product_tag" value="' . esc_attr( $_GET['product_tag'] ) . '" />';
		}

		if ( ! empty( $_GET['orderby'] ) ) {
			$fields .= '<input type="hidden" name="orderby" value="' . esc_attr( $_GET['orderby'] ) . '" />';
		}

		if ( ! empty( $_GET['min_rating'] ) ) {
			$fields .= '<input type="hidden" name="min_rating" value="' . esc_attr( $_GET['min_rating'] ) . '" />';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes() ) {
			foreach ( $_chosen_attributes as $attribute => $data ) {
				$taxonomy_filter = 'filter_' . str_replace( 'pa_', '', $attribute );

				$fields .= '<input type="hidden" name="' . esc_attr( $taxonomy_filter ) . '" value="' . esc_attr( implode( ',', $data['terms'] ) ) . '" />';

				if ( 'or' == $data['query_type'] ) {
					$fields .= '<input type="hidden" name="' . esc_attr( str_replace( 'pa_', 'query_type_', $attribute ) ) . '" value="or" />';
				}
			}
		}

		if ( '' === get_option( 'permalink_structure' ) ) {
			$form_action = remove_query_arg( array( 'page', 'paged', 'product-page' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		} else {
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $fields built with esc_attr() per piece in lines above; remaining values escaped inline via esc_attr/esc_url/esc_html__.
		echo '<form id="lafka-price-filter-form" data-currency_pos="' . esc_attr( get_option( 'woocommerce_currency_pos' ) ) . '" data-currency_symbol="' . esc_attr( get_woocommerce_currency_symbol() ) . '"  method="get" action="' . esc_url( $form_action ) . '">
									<div id="price-filter" class="price_slider_wrapper">
										<div class="price_slider_amount" data-step="' . esc_attr( $step ) . '">
												<input type="hidden" id="min_price" name="min_price" value="' . esc_attr( $current_min_price ) . '" data-min="' . esc_attr( $min_price ) . '" placeholder="' . esc_attr__( 'Min price', 'lafka' ) . '" />
												<input type="hidden" id="max_price" name="max_price" value="' . esc_attr( $current_max_price ) . '" data-max="' . esc_attr( $max_price ) . '" placeholder="' . esc_attr__( 'Max price', 'lafka' ) . '" />
												<div class="price_label">
														<p>
																' . esc_html__( 'Price range:', 'lafka' ) . ' <span id="lafka_price_range"><span class="from"></span> &mdash; <span class="to"></span></span>
														</p>
												</div>
												' . $fields . '
												<div class="clear"></div>
										</div>
										<div class="price_slider"></div>
								</div>
						</form>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}

if ( ! function_exists( 'lafka_get_filtered_price' ) ) {

	function lafka_get_filtered_price() {
		global $wpdb;

		$args       = WC()->query->get_main_query()->query_vars;
		$tax_query  = isset( $args['tax_query'] ) ? $args['tax_query'] : array();
		$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

		if ( ! is_post_type_archive( 'product' ) && ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
			$tax_query[] = WC()->query->get_main_tax_query();
		}

		foreach ( $meta_query + $tax_query as $key => $query ) {
			if ( ! empty( $query['price_filter'] ) || ! empty( $query['rating_filter'] ) ) {
				unset( $meta_query[ $key ] );
			}
		}

		// Cache the result per unique query within this request
		$cache_key = 'lafka_price_' . md5( wp_json_encode( array( $tax_query, $meta_query, isset( $args['s'] ) ? $args['s'] : '' ) ) );
		$cached    = wp_cache_get( $cache_key, 'lafka' );
		if ( false !== $cached ) {
			return $cached;
		}

		$meta_query = new WP_Meta_Query( $meta_query );
		$tax_query  = new WP_Tax_Query( $tax_query );
		$search     = WC_Query::get_main_search_query_sql();

		$meta_query_sql   = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
		$tax_query_sql    = $tax_query->get_sql( $wpdb->posts, 'ID' );
		$search_query_sql = $search ? ' AND ' . $search : '';

		$sql = "
			SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
			FROM {$wpdb->wc_product_meta_lookup}
			WHERE product_id IN (
				SELECT ID FROM {$wpdb->posts}
				" . $tax_query_sql['join'] . $meta_query_sql['join'] . "
				WHERE {$wpdb->posts}.post_type IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', array( 'product' ) ) ) ) . "')
					AND {$wpdb->posts}.post_status = 'publish'
				" . $tax_query_sql['where'] . $meta_query_sql['where'] . $search_query_sql . '
			)';

		$sql = apply_filters( 'woocommerce_price_filter_sql', $sql, $meta_query_sql, $tax_query_sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- All inputs pre-escaped: $tax_query_sql/$meta_query_sql come from WP_Tax_Query::get_sql()/WP_Meta_Query::get_sql() which return safe SQL; $search from WC_Query::get_main_search_query_sql(); post types wrapped in esc_sql(). Mirrors WC core class-wc-widget-price-filter.php pattern.
		$result = $wpdb->get_row( $sql );
		wp_cache_set( $cache_key, $result, 'lafka' );
		return $result;
	}
}

add_action( 'woocommerce_after_shop_loop', 'lafka_wrap_after_shop_loop', 5 );
if ( ! function_exists( 'lafka_wrap_after_shop_loop' ) ) {

	function lafka_wrap_after_shop_loop() {
		echo '</div>'; // closes box-products
		echo '</div>'; // closes box-products container
	}

}
add_action( 'woocommerce_after_shop_loop', 'lafka_shop_sidebar', 15 );
if ( ! function_exists( 'lafka_shop_sidebar' ) ) {

	function lafka_shop_sidebar() {
		echo '</div>'; // closes content_holder
		if ( get_theme_mod( 'lafka_show_sidebar_shop', false ) ) {
			do_action( 'woocommerce_sidebar' );
			echo '<div class="clear"></div>';
		}
	}

}

// Disable the redirect on single search result
add_filter( 'woocommerce_redirect_single_search_result', '__return_false' );

add_action( 'woocommerce_before_shop_loop', 'lafka_wrap_before_shop_loop_after', 60 );
if ( ! function_exists( 'lafka_wrap_before_shop_loop_after' ) ) {

	function lafka_wrap_before_shop_loop_after() {
		$shop_default_product_columns = get_theme_mod( 'lafka_shop_default_product_columns', 'columns-3' );

		$uri_parts = explode( '?', esc_url_raw( $_SERVER['REQUEST_URI'] ), 2 ); // Reading only. Stripped to domain name. Used for redirection.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public shop URL param for filter-reset link; no state mutation.
		$post_type_url_param  = isset( $_GET['post_type'] ) ? esc_attr( $_GET['post_type'] ) : '';
		$lafka_search_query   = get_search_query();
		$reset_params_to_keep = '';
		if ( $lafka_search_query && $post_type_url_param ) {
			$reset_params_to_keep = '?s=' . $lafka_search_query . '&post_type=' . $post_type_url_param;
		} elseif ( $lafka_search_query ) {
			$reset_params_to_keep = '?s=' . $lafka_search_query;
		} elseif ( $post_type_url_param ) {
			$reset_params_to_keep = '?post_type=' . $post_type_url_param;
		}

		$lafka_reset_filter_url = $uri_parts[0];
		if ( $reset_params_to_keep ) {
			$lafka_reset_filter_url .= $reset_params_to_keep;
		}

		if ( get_theme_mod( 'lafka_show_refine_area', true ) && woocommerce_products_will_display() ) {
			// Define widget area here for filters
			if ( is_active_sidebar( 'lafka_product_filters_sidebar' ) ) {
				echo '<div class="lafka-filter-widgets-holder">';
				echo '<div id="lafka-filter-widgets" ' . ( 'opened' == get_theme_mod( 'lafka_refine_area_state', 'opened' ) ? 'class="lafka_active_filter_area"' : '' ) . ' >';
				dynamic_sidebar( 'lafka_product_filters_sidebar' );
				echo '</div>';
				echo '<a href="' . esc_url( $lafka_reset_filter_url ) . '" data-lafka_reset_query="' . esc_js( $reset_params_to_keep ) . '" class="lafka-reset-filters">' . esc_html__( 'Reset All Filters', 'lafka' ) . '</a>';
				echo '</div>';
			}

			echo '<div class="clear"></div>';
			echo '</div>';
			echo '</div>';
		}

		echo '<div class="box-product-list">';
		echo '<div class="box-products woocommerce ' . esc_attr( $shop_default_product_columns ) . '">';
	}

}

// Changing products per page
add_filter( 'loop_shop_per_page', 'lafka_set_products_per_page', 20 );

if ( ! function_exists( 'lafka_set_products_per_page' ) ) {

	function lafka_set_products_per_page() {
		$per_page = get_theme_mod( 'lafka_products_per_page', 12 );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public shop per_page filter URL param; no state mutation.
		if ( array_key_exists( 'per_page', $_GET ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public shop per_page filter URL param; no state mutation.
			$per_page = esc_attr( $_GET['per_page'] );
		}

		return $per_page;
	}

}

/**
 * Return the start and end sales dates for product on sale
 * If not on sale, return false
 *
 * @param type $post
 *
 * @return boolean
 */
if ( ! function_exists( 'lafka_get_product_sales_dates' ) ) {

	function lafka_get_product_sales_dates( $post ) {
		/** @var $product WC_Product */
		$start_sales_date = 9999999999;
		$end_sales_date   = 0;

		$product = is_a( $post, 'WC_Product' ) ? $post : wc_get_product( $post );
		if ( ! $product || ! $product->is_on_sale() ) {
			return false;
		}

		$child_products = $product->get_children();
		// If is variation product
		if ( count( $child_products ) ) {
			// Prime the meta cache for all children in one query
			update_meta_cache( 'post', $child_products );
			foreach ( $child_products as $child_id ) {
				$sale_price_dates_from = get_post_meta( $child_id, '_sale_price_dates_from', true );
				$sale_price_dates_to   = get_post_meta( $child_id, '_sale_price_dates_to', true );

				if ( $sale_price_dates_from && $sale_price_dates_from < $start_sales_date ) {
					$start_sales_date = $sale_price_dates_from;
				}

				if ( $sale_price_dates_to && $sale_price_dates_to > $end_sales_date ) {
					$end_sales_date = $sale_price_dates_to;
				}
			}
		} else {
			$start_sales_date = get_post_meta( $post->ID, '_sale_price_dates_from', true );
			$end_sales_date   = get_post_meta( $post->ID, '_sale_price_dates_to', true );
		}

		return array(
			'from' => $start_sales_date,
			'to'   => $end_sales_date,
		);
	}

}

// Show countdown for sales on product list
if ( ! function_exists( 'lafka_shop_sale_countdown' ) ) {

	function lafka_shop_sale_countdown() {
		/**
		 * @var WC_Product $product
		 */
		global $post, $product;

		if ( lafka_get_option( 'use_countdown', 'enabled' ) == 'enabled' && $product->is_on_sale() ) {
			$sales_dates = lafka_get_product_sales_dates( $post );
			$now         = time();
			if ( $sales_dates['to'] && $now < $sales_dates['to'] ) {
				$random_num = uniqid();
				?>
				<div class="count_holder_small" data-countdown-id="<?php echo esc_js( '#lafkaCountSmallLatest' . $post->ID . $random_num ); ?>"
					data-countdown-to="<?php echo esc_js( date( 'F j, Y G:i:s', $sales_dates['to'] ) ); ?>">
					<div class="count_info"><?php esc_html_e( 'Offer ends in', 'lafka' ); ?>:</div>
					<div id="lafkaCountSmallLatest<?php echo esc_attr( $post->ID . $random_num ); ?>"></div>
					<div class="clear"></div>
				</div>
				<?php
			}
		}
	}

}

// Show countdown for sales on the product page
add_filter( 'woocommerce_single_product_summary', 'lafka_product_sale_countdown', 9 );

if ( ! function_exists( 'lafka_product_sale_countdown' ) ) {

	function lafka_product_sale_countdown() {
		global $post, $product;

		if ( lafka_get_option( 'use_countdown', 'enabled' ) == 'enabled' && $product->is_on_sale() ) {
			$sales_dates = lafka_get_product_sales_dates( $post );
			$now         = time();

			if ( $sales_dates['to'] && $now < $sales_dates['to'] ) {
				$sales_dates = lafka_get_product_sales_dates( $post );
				$now         = time();

				$unique_id = uniqid( 'lafka_sale_countdown' );
				?>
				<div class="count_holder"><span class="offer_title"><?php esc_html_e( 'Offer ends in', 'lafka' ); ?>:</span>
					<div id="<?php echo esc_attr( $unique_id ); ?>"></div>
					<div class="clear"></div>
				</div>
				<script>
					(function ($) {
						"use strict";
						$(window).on("load lafka_quickview_loaded", function () {
							$('#<?php echo esc_attr( $unique_id ); ?>').countdown({
								until: new Date("<?php echo esc_js( date( 'F j, Y G:i:s', $sales_dates['to'] ) ); ?>"),
								compact: false,
								layout: '<span class="countdown_time_tiny">{dn} {dl} {hn}:{mnn}:{snn}</span>'
							});
						});
					})(window.jQuery);
				</script>
				<?php
			}
		}
	}

}

// Wrap cart with div before
add_filter( 'woocommerce_before_cart_table', 'lafka_wrap_cart_before', 10 );

if ( ! function_exists( 'lafka_wrap_cart_before' ) ) {

	function lafka_wrap_cart_before() {
		echo '<div class="cart-info">';
	}

}

// Wrap cart with div after
add_filter( 'woocommerce_after_cart_table', 'lafka_wrap_cart_after', 10 );

if ( ! function_exists( 'lafka_wrap_cart_after' ) ) {

	function lafka_wrap_cart_after() {
		echo '</div>';
	}

}

// Ensure the cart-count surfaces refresh when products are added via AJAX.
//
// f053: the previous fragment was keyed on `li.lafka-cart-link-item` and
// rendered lafka_cart_link() — legacy markup the handoff header (header.php)
// and the cart drawer (partials/cart-drawer.php) no longer output. With no
// matching node in the DOM, WC's fragment replacement was a silent no-op, so
// the header bag numeral and the cart-drawer title pill stayed at their
// server-rendered value (e.g. "0") until a full page reload after a quick-add.
// We now key the fragments on the nodes those templates ACTUALLY render:
//   - span.lafka-header__cart-count       (header bag badge, header.php)
//   - a.lafka-header__cart                 (header anchor — refreshes the SR
//                                           aria-label so the count in
//                                           "View cart, N items" stays
//                                           accurate; WCAG 2.5.3 Label in Name)
//   - span.lafka-cart-drawer__count-badge  (drawer title pill, cart-drawer.php)
// Each fragment's value root element matches its selector key, so WC's
// replaceWith swaps the node cleanly and stays idempotent across repeated
// refreshes — no <li><li> nesting like the pre-P6-A11Y-7 `a.cart-contents`
// regression. This markup is theme-owned, so the callback lives in the theme
// (the plugin stays theme-agnostic).
add_filter( 'woocommerce_add_to_cart_fragments', 'lafka_header_add_to_cart_fragment' );
if ( ! function_exists( 'lafka_header_add_to_cart_fragment' ) ) {

	function lafka_header_add_to_cart_fragment( $fragments ) {
		// `WC()->cart` is null in some early-template / REST contexts. Guard so
		// the filter never fatals while computing the count.
		$lafka_cart_count = ( function_exists( 'WC' ) && WC() && WC()->cart )
			? (int) WC()->cart->get_cart_contents_count()
			: 0;

		// Header bag badge. The .lafka-header__cart-count class is unique — the
		// sticky bar uses .lafka-sticky-cart__count — so this targets only the
		// header numeral, no collision.
		$fragments['span.lafka-header__cart-count'] = '<span class="lafka-header__cart-count" data-lafka-cart-count aria-hidden="true">' . esc_html( (string) $lafka_cart_count ) . '</span>';

		// Header cart anchor — mirrors header.php so the screen-reader
		// aria-label count refreshes alongside the visible badge (the inner
		// span here is re-applied idempotently by the badge fragment above).
		/* translators: %d: number of items in the cart */
		$lafka_cart_aria                   = sprintf( _n( 'View cart, %d item', 'View cart, %d items', $lafka_cart_count, 'lafka' ), $lafka_cart_count );
		$fragments['a.lafka-header__cart'] = '<a class="lafka-header__cart" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="' . esc_attr( $lafka_cart_aria ) . '" data-lafka-cart-open>'
			. '<i class="fa fa-shopping-bag" aria-hidden="true"></i>'
			. '<span class="lafka-header__cart-count" data-lafka-cart-count aria-hidden="true">' . esc_html( (string) $lafka_cart_count ) . '</span>'
			. '</a>';

		// Cart-drawer title count pill (partials/cart-drawer.php).
		$fragments['span.lafka-cart-drawer__count-badge'] = '<span class="lafka-cart-drawer__count-badge" data-lafka-cart-count-pill>' . esc_html( (string) $lafka_cart_count ) . '</span>';

		return $fragments;
	}

}

/**
 * Custom taxonomy archive description (replaces WooCommerce default).
 *
 * @return void
 */
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
add_action( 'woocommerce_archive_description', 'lafka_taxonomy_archive_description', 10 );
function lafka_taxonomy_archive_description() {
	if ( is_tax( array( 'product_cat', 'product_tag' ) ) && get_query_var( 'paged' ) == 0 ) {
		$description = wpautop( do_shortcode( term_description() ) );

		$thumbnail_id = get_metadata( 'woocommerce_term', get_queried_object()->term_id, 'thumbnail_id', true );
		$image        = wp_get_attachment_url( $thumbnail_id );

		if ( $description || $image ) {
			if ( $image ) {
				$output = '<img class="pic-cat-main" src="' . esc_url( $image ) . '" alt="' . esc_attr( single_term_title( '', false ) ) . '" />' . $description;
			} else {
				$output = $description;
			}

			echo '<div class="term-description fixed ' . esc_attr( sanitize_html_class( lafka_get_option( 'category_description_position' ) ) ) . '">' . wp_kses_post( $output ) . '</div>';
		}
	}
}

/**
 * Custom product archive description (replaces WooCommerce default).
 *
 * @subpackage    Archives
 */
remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
add_action( 'woocommerce_archive_description', 'lafka_product_archive_description', 10 );
function lafka_product_archive_description() {
	if ( is_post_type_archive( 'product' ) && get_query_var( 'paged' ) == 0 ) {
		$shop_page = get_post( wc_get_page_id( 'shop' ) );
		if ( $shop_page ) {
			$description = wc_format_content( $shop_page->post_content );
			if ( $description ) {
				echo '<div class="page-description fixed">' . wp_kses_post( $description ) . '</div>';
			}
		}
	}
}

// Override Woocommerce Compare add link
// if Woocompare is activated
if ( defined( 'YITH_WOOCOMPARE' ) ) {
	global $yith_woocompare;

	$woocompareFrontEnd = $yith_woocompare->obj;
	remove_action( 'woocommerce_after_shop_loop_item', array( $woocompareFrontEnd, 'add_compare_link' ), 20 );

	if ( ! function_exists( 'lafka_add_compare_link' ) ) {

		function lafka_add_compare_link( $product_id = false, $args = array() ) {
			global $yith_woocompare;
			$woocompareFrontEnd = $yith_woocompare->obj;

			if ( ! method_exists( $woocompareFrontEnd, 'add_product_url' ) ) {
				return false;
			}

			if ( ! $product_id ) {
				global $product;
				$product_id = ( $product->get_id() ) && $product->exists() ? $product->get_id() : 0;
			}

			// return if product doesn't exist
			if ( empty( $product_id ) ) {
				return;
			}

			$button_or_link = isset( $args['button_or_link'] ) ? $args['button_or_link'] : '';
			$button_text    = isset( $args['button_text'] ) ? $args['button_text'] : '';

			$is_button = ! $button_or_link ? get_option( 'yith_woocompare_is_button' ) : $button_or_link;

			if ( ! $button_text || $button_text == 'default' ) {
				$button_text = get_option( 'yith_woocompare_button_text', esc_html__( 'Compare', 'lafka' ) );
				$button_text = function_exists( 'icl_translate' ) ? icl_translate( 'Plugins', 'plugin_yit_compare_button_text', $button_text ) : $button_text;
			}

			printf( '<a href="%s" class="%s" data-product_id="%d" title="%s"><i class="fa fa-tasks"></i></a>', esc_url( $woocompareFrontEnd->add_product_url( $product_id ) ), 'compare' . ( $is_button == 'button' ? ' button' : '' ), esc_attr( $product_id ), esc_attr( $button_text ) );
		}

	}
}

// Move woocommerce_template_loop_price to be below the title
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

// If related products are set to zero hide them
if ( get_theme_mod( 'lafka_number_related_products', 6 ) == 0 ) {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
}

add_filter( 'woocommerce_output_related_products_args', 'lafka_related_products_args' );
if ( ! function_exists( 'lafka_related_products_args' ) ) {

	/**
	 * WooCommerce Extra Feature
	 * --------------------------
	 *
	 * Change number of related products on product page
	 * Set your own value for 'posts_per_page'
	 *
	 */
	function lafka_related_products_args( $args ) {

		$args['posts_per_page'] = get_theme_mod( 'lafka_number_related_products', 6 ); // number_related_products theme option
		$args['columns']        = 1; // arranged in 1 columns

		return $args;
	}

}

add_action( 'woocommerce_before_single_product_summary', 'lafka_add_this_share', 99 );
if ( ! function_exists( 'lafka_add_this_share' ) ) {

	/**
	 * Display share links on product pages
	 */
	function lafka_add_this_share() {
		if ( function_exists( 'lafka_share_links' ) ) {
			lafka_share_links( the_title_attribute( 'echo=0' ), get_permalink() );
		}
	}

}

/**
 * Cart Link
 * Displayed a link to the cart including the number of items present and the cart total
 *
 * @param array $settings Settings
 *
 * @return array           Settings
 */
if ( ! function_exists( 'lafka_cart_link' ) ) {

	function lafka_cart_link() {
		if ( is_cart() ) {
			$class = 'current-menu-item';
		} else {
			$class = '';
		}
		?>
		<?php
		// `WC()->cart` is null in some early-template / REST contexts. Without
		// the guard the page fatals — debug.log on local end-to-end testing
		// (Session 4) confirmed the NPE in production-equivalent runs.
		$lafka_cart_count = ( function_exists( 'WC' ) && WC() && WC()->cart )
			? (int) WC()->cart->get_cart_contents_count()
			: 0;
		?>
		<li class="lafka-cart-link-item <?php echo sanitize_html_class( $class ); ?>">
			<a id="lafka_quick_cart_link" class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'lafka' ); ?>">
				<span class="count"><?php echo esc_html( (string) $lafka_cart_count ); ?></span>
			</a>
		</li>
		<?php
	}

}

// Quickview ajax actions
if ( ! function_exists( 'lafka_quickview' ) ) {

	function lafka_quickview() {
		check_ajax_referer( 'lafka_ajax_nonce', 'security' );

		global $post, $product, $authordata;
		$prod_id = absint( $_POST['productid'] );
		$post    = get_post( $prod_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_die();
		}
		$product    = wc_get_product( $prod_id );
		$authordata = get_userdata( $post->post_author );

		if ( function_exists( 'Lafka_WCVS' ) ) {
			Lafka_WCVS();
		}
		if ( class_exists( 'Lafka_WC_Variation_Swatches_Frontend' ) ) {
			Lafka_WC_Variation_Swatches_Frontend::instance();
		}

		if ( function_exists( 'YITH_WCWL_Frontend' ) ) {
			$wishlist = YITH_WCWL_Frontend();
			$wishlist->add_button();
		}

		// We also need the wp.template for this script :)
		wc_get_template( 'single-product/add-to-cart/variation.php' );
		wc_get_template( 'content-single-product-lafka-quickview.php' );

		wp_die();
	}

}

add_action( 'wp_ajax_lafka_quickview', 'lafka_quickview' );
add_action( 'wp_ajax_nopriv_lafka_quickview', 'lafka_quickview' );

// Move description before title
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 6 );

// Ajax add to cart on product single
if ( ! function_exists( 'lafka_wc_add_cart_ajax' ) ) {

	function lafka_wc_add_cart_ajax() {
		check_ajax_referer( 'lafka_ajax_nonce', 'security' );

		$wc_notices = WC()->session->get( 'wc_notices' );
		WC()->session->set( 'wc_notices', array() );

		if ( is_array( $wc_notices ) ) {
			foreach ( $wc_notices as $notice_level => $notice ) {
				if ( $notice_level === 'error' ) {
					$notice_message = is_array( $notice[0] ) ? $notice[0]['notice'] : $notice[0];

					// regex to remove html tags and content
					$regex         = '/<[^>]*>[^<]*<[^>]*>/';
					$alert_message = html_entity_decode( preg_replace( $regex, '', $notice_message ) );
					$response      = array(
						'error_message' => $alert_message,
					);

					wp_send_json( $response );
				}
			}
		}

		WC_AJAX::get_refreshed_fragments();

		wp_die();
	}
}

add_action( 'wp_ajax_lafka_wc_add_cart', 'lafka_wc_add_cart_ajax' );
add_action( 'wp_ajax_nopriv_lafka_wc_add_cart', 'lafka_wc_add_cart_ajax' );

// Force variable attributes to show below the product
add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );

// Specifically for Lafka, for the gallery images use the main image size as flexslider is disabled
add_filter(
	'woocommerce_gallery_image_size',
	function () {
		return 'woocommerce_single';
	}
);

if ( get_theme_mod( 'lafka_only_free_delivery', false ) ) {
	add_filter( 'woocommerce_package_rates', 'lafka_hide_shipping_when_free_is_available', 100 );
}

if ( ! function_exists( 'lafka_hide_shipping_when_free_is_available' ) ) {
	/**
	 * Hide shipping rates when free shipping is available.
	 * Updated to support WooCommerce 2.6 Shipping Zones.
	 *
	 * @param array $rates Array of rates found for the package.
	 *
	 * @return array
	 */
	function lafka_hide_shipping_when_free_is_available( $rates ) {
		$free = array();
		foreach ( $rates as $rate_id => $rate ) {
			if ( 'free_shipping' === $rate->method_id ) {
				$free[ $rate_id ] = $rate;
				break;
			}
		}

		return ! empty( $free ) ? $free : $rates;
	}
}

// Move single product sale flash to summary section
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_show_product_sale_flash', 1 );

// WooCommerce single product gallery type
add_action( 'wp', 'lafka_apply_effective_gallery_type_setting' );
if ( ! function_exists( 'lafka_apply_effective_gallery_type_setting' ) ) {
	function lafka_apply_effective_gallery_type_setting() {
		$effective_gallery_type_setting = lafka_get_effective_gallery_type_setting();

		if ( in_array( $effective_gallery_type_setting, array( 'image_list', 'mosaic_images' ), true ) ) {
			remove_theme_support( 'wc-product-gallery-zoom' );
			remove_theme_support( 'wc-product-gallery-slider' );
		}
	}
}

if ( ! function_exists( 'lafka_get_gallery_type_classes' ) ) {
	function lafka_get_gallery_type_classes() {
		$effective_gallery_type_setting = lafka_get_effective_gallery_type_setting();
		$classes                        = array();

		switch ( $effective_gallery_type_setting ) {
			case 'woo_default':
				$classes[] = 'lafka-standard-product-gallery';
				break;
			case 'image_list':
				$classes[] = 'lafka-image-list-product-gallery';
				break;
			case 'mosaic_images':
				$classes[] = 'lafka-image-list-product-gallery';
				$classes[] = 'lafka-mosaic-gallery';
				break;
		}

		return $classes;
	}
}

if ( ! function_exists( 'lafka_get_effective_gallery_type_setting' ) ) {
	function lafka_get_effective_gallery_type_setting() {
		global $post;

		$per_product_gallery_type_setting = '';
		$global_gallery_type_setting      = get_theme_mod( 'lafka_single_product_gallery_type', 'woo_default' );

		if ( is_product() ) {
			$per_product_gallery_type_setting = get_post_meta( $post->ID, 'lafka_single_product_gallery_type', true );
		}

		if ( $per_product_gallery_type_setting && $per_product_gallery_type_setting != 'default' ) {
			$effective_gallery_type_setting = $per_product_gallery_type_setting;
		} else {
			$effective_gallery_type_setting = $global_gallery_type_setting;
		}

		return $effective_gallery_type_setting;
	}
}

add_action( 'woocommerce_before_add_to_cart_form', 'lafka_add_to_cart_separator', 99 );
if ( ! function_exists( 'lafka_add_to_cart_separator' ) ) {
	function lafka_add_to_cart_separator() {
		echo '<span class="lafka-separator"></span>';
	}
}

// Move ratings a bit so we have place for countdown and promo between price and rating
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 8 );

// v5.77.0: removed the lafka_superscript_wc_formatted_price filter that
// wrapped cents in <sup>. Superscript cents read as a typographical
// error on addon labels (e.g. "+$3.⁰⁰" beside a topping). The plugin's
// lafka_normalize_price_html stripped <sup> from public price HTML
// already; with the source filter gone, no downstream stripping is
// needed and addon templates render clean prices ($3.00) inline.

add_filter( 'yith_wcwl_positions', 'lafka_redefine_wishlist_link_position', 10 );
if ( ! function_exists( 'lafka_redefine_wishlist_link_position' ) ) {
	function lafka_redefine_wishlist_link_position( $positions ) {

		$positions['add-to-cart'] = array(
			'hook'     => 'woocommerce_after_add_to_cart_button',
			'priority' => 98,
		);

		return $positions;
	}
}

if ( ! function_exists( 'lafka_get_chosen_category_for_related' ) ) {
	/**
	 * Get product category to be used for title on related products
	 *
	 * @param $product WC_Product
	 *
	 * @return mixed|WP_Term|null
	 */
	function lafka_get_chosen_category_for_related( $product ) {
		$lafka_product_categories = get_the_terms( $product->get_id(), 'product_cat' );
		$to_return                = null;

		if ( is_array( $lafka_product_categories ) && count( $lafka_product_categories ) ) {
			// Get first parent category, if exists
			foreach ( $lafka_product_categories as $category ) {
				if ( $category->parent == 0 ) {
					$to_return = $category;
					break;
				}
			}

			// If no parent category then just get the first one
			if ( $to_return === null ) {
				$to_return = $lafka_product_categories[0];
			}
		}

		return $to_return;
	}
}

add_filter( 'woocommerce_product_single_add_to_cart_text', 'lafka_change_single_add_to_cart_to_order' );
if ( ! function_exists( 'lafka_change_single_add_to_cart_to_order' ) ) {
	function lafka_change_single_add_to_cart_to_order() {
		return esc_html__( 'Order', 'lafka' );
	}
}

add_filter( 'woocommerce_loop_add_to_cart_link', 'lafka_show_variations_in_listings', 99 );
if ( ! function_exists( 'lafka_show_variations_in_listings' ) ) {
	/**
	 * Modifies the add to cart link in product listings,
	 * when there is default variation set.
	 * Lists all variations with weight, attribute and price
	 *
	 * @param $add_to_cart_link
	 *
	 * @return false|string
	 */
	function lafka_show_variations_in_listings( $add_to_cart_link ) {

		global /** @var WC_Product $product */
		$product;

		if ( lafka_is_product_eligible_for_variation_in_listings( $product ) ) {
			/** @var WC_Product_Variable $lafka_variable_product */
			$lafka_variable_product = $product;

			// Prime meta cache for all variation IDs to avoid N+1 queries
			$child_ids = $lafka_variable_product->get_children();
			if ( $child_ids ) {
				update_meta_cache( 'post', $child_ids );
			}

			// Load addons once outside the loop (was previously inside = N queries for N variations)
			$product_addons = array();
			if ( class_exists( 'WC_Product_Addons_Helper' ) ) {
				$product_addons = WC_Product_Addons_Helper::get_product_addons( $product->get_id() );
			}

			// PERF-C06: Batch-fetch all attribute terms for all variations at once,
			// instead of calling get_term_by() per attribute per variation (N+1).
			$available_variations    = $lafka_variable_product->get_available_variations();
			$lafka_term_lookup       = array();
			$lafka_slugs_by_taxonomy = array();
			foreach ( $available_variations as $variation ) {
				if ( ! empty( $variation['attributes'] ) ) {
					foreach ( $variation['attributes'] as $attribute_name => $attribute_slug ) {
						if ( $attribute_slug ) {
							$taxonomy = str_replace( 'attribute_', '', rawurldecode( $attribute_name ) );
							$lafka_slugs_by_taxonomy[ $taxonomy ][ $attribute_slug ] = true;
						}
					}
				}
			}
			foreach ( $lafka_slugs_by_taxonomy as $taxonomy => $slugs ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'slug'       => array_keys( $slugs ),
						'hide_empty' => false,
					)
				);
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$lafka_term_lookup[ $taxonomy . '|' . $term->slug ] = $term->name;
					}
				}
			}

			// Resolve the cross-repo catalog-visibility meta key from the plugin's
			// SSOT accessor, falling back to the literal so the theme still renders
			// the in-catalog variations when the plugin is deactivated.
			$lafka_in_catalog_meta_key = function_exists( 'lafka_meta_variable_in_catalog' ) ? lafka_meta_variable_in_catalog() : '_lafka_variable_in_catalog';

			ob_start();
			?>
			<?php foreach ( $available_variations as $variation ) : ?>
				<?php if ( get_post_meta( $variation['variation_id'], $lafka_in_catalog_meta_key, true ) ) : ?>
					<?php
					$default_addon_option_pairs = array();
					foreach ( $product_addons as $addon ) {
						if ( isset( $addon['options'] ) ) {
							foreach ( $addon['options'] as $option ) {
								if ( $option['default'] ) {
									$default_addon_option_pairs[] = array(
										'addon'  => $addon,
										'option' => $option,
									);
								}
							}
						}
					}
					?>
					<form class="lafka-variations-in-catalog cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
							method="post"
							enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>">

						<span class="lafka-list-variation-label">
							<?php
							$variation_label_array = array();
							if ( isset( $variation['attributes'] ) ) {
								foreach ( $variation['attributes'] as $attribute_name => $attribute_slug ) {
									// PERF-C06: Use pre-fetched term lookup instead of get_term_by() per attribute
									$taxonomy   = str_replace( 'attribute_', '', rawurldecode( $attribute_name ) );
									$lookup_key = $taxonomy . '|' . $attribute_slug;
									if ( isset( $lafka_term_lookup[ $lookup_key ] ) ) {
										$variation_label_array[] = $lafka_term_lookup[ $lookup_key ];
									}
								}
							}
							?>
							<?php if ( count( $variation_label_array ) ) : ?>
								<?php echo esc_html( implode( ' ', $variation_label_array ) ); ?>
							<?php endif; ?>
						</span>

						<?php if ( isset( $variation['weight'] ) && $variation['weight'] ) : ?>
							<span class="lafka-list-variation-weight"><?php echo esc_html( $variation['weight_html'] ); ?></span>
						<?php endif; ?>

						<span class="lafka-list-variation-price">
							<?php
							$variation_accumulated_price = $variation['display_price'];
							foreach ( $default_addon_option_pairs as $addon_option_pair ) {
								$option_price = '';
								foreach ( $variation['attributes'] as $name => $value ) {
									$option_price = $addon_option_pair['option']['price'][ str_replace( 'attribute_', '', $name ) ][ $value ] ?? '';
								}
								$variation_accumulated_price += floatval( $option_price );
							}
							echo wp_kses_post( wc_price( $variation_accumulated_price ) );
							?>
						</span>
						<button type="submit" class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

						<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

						<?php foreach ( $variation['attributes'] as $attribute_name => $attribute_slug ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $attribute_name ); ?>" value="<?php echo esc_attr( $attribute_slug ); ?>"/>
						<?php endforeach; ?>
						<input type="hidden" name="quantity" value="<?php echo esc_attr( $variation['min_qty'] ); ?>"/>
						<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>"/>
						<input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>"/>
						<input type="hidden" name="variation_id" class="variation_id" value="<?php echo esc_attr( $variation['variation_id'] ); ?>"/>
						<?php foreach ( $default_addon_option_pairs as $addon_option_pair ) : ?>
							<input type="hidden" name="addon-<?php esc_attr_e( $addon_option_pair['addon']['field-name'] ); ?>[]"
									value="<?php esc_attr_e( sanitize_title( $addon_option_pair['option']['label'] ) ); ?>"/>
						<?php endforeach; ?>
					</form>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php
			return ob_get_clean();
		} else {
			return $add_to_cart_link;
		}
	}
}

if ( ! function_exists( 'lafka_is_product_eligible_for_variation_in_listings' ) ) {
	/**
	 * Check if product is eligible for Lafka style
	 * - show variations in product listings
	 * - hide main price
	 *
	 * @param WC_Product $product
	 *
	 * @return bool
	 */
	function lafka_is_product_eligible_for_variation_in_listings( $product ) {
		static $cache = array();
		$pid          = $product->get_id();
		if ( isset( $cache[ $pid ] ) ) {
			return $cache[ $pid ];
		}

		$result = false;
		if ( $product->get_type() === 'variable' ) {
			$children = $product->get_children();
			if ( $children ) {
				// Resolve the cross-repo catalog-visibility meta key from the plugin's
				// SSOT accessor, falling back to the literal when the plugin is inactive.
				$meta_key = function_exists( 'lafka_meta_variable_in_catalog' ) ? lafka_meta_variable_in_catalog() : '_lafka_variable_in_catalog';
				// Prime meta cache for all children in one query
				update_meta_cache( 'post', $children );
				foreach ( $children as $variation_id ) {
					if ( get_post_meta( $variation_id, $meta_key, true ) ) {
						$result = true;
						break;
					}
				}
			}
		}

		$cache[ $pid ] = $result;
		return $result;
	}
}

if ( ! function_exists( 'lafka_get_available_variation_ids' ) ) {
	/**
	 * Get all available variation ids
	 *
	 * @param WC_Product $product
	 *
	 * @return array|bool
	 */
	function lafka_get_available_variation_ids( $product ) {

		if ( $product->get_type() === 'variable' ) {
			/** @var WC_Product_Variable $lafka_variable_product */
			$variable_product = $product;

			$available_variations = array();

			// Get only available variations and visible in catalog
			// And only if there are less than 6 variations (There is no place to show all that info and response becomes slow
			$variations = $variable_product->get_children();
			if ( count( $variations ) < 6 ) {
				$hide_out_of_stock = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' );
				foreach ( $variations as $child_id ) {
					/** @var WC_Product_Variation $variation */
					$variation = wc_get_product( $child_id );

					// Hide out of stock variations if 'Hide out of stock items from the catalog' is checked.
					if ( ! $variation || ! $variation->exists() || ( $hide_out_of_stock && ! $variation->is_in_stock() ) ) {
						continue;
					}

					// Filter 'woocommerce_hide_invisible_variations' to optionally hide invisible variations (disabled variations and variations with empty price).
					if ( apply_filters( 'woocommerce_hide_invisible_variations', true, $variable_product->get_id(), $variation ) && ! $variation->variation_is_visible() ) {
						continue;
					}

					$available_variations[] = $variation->get_id();
				}
			}
			$available_variations = array_values( array_filter( $available_variations ) );

			if ( count( $available_variations ) ) {
				return $available_variations;
			} else {
				return false;
			}
		}

		return false;
	}
}

add_filter( 'wc_product_enable_dimensions_display', 'lafka_should_display_weight_in_additional_info' );
if ( ! function_exists( 'lafka_should_display_weight_in_additional_info' ) ) {
	function lafka_should_display_weight_in_additional_info() {
		global /** @var WC_Product $product */
		$product;

		return $product->has_dimensions();
	}
}

add_filter( 'woocommerce_product_related_products_heading', 'lafka_custom_related_products_heading' );
if ( ! function_exists( 'lafka_custom_related_products_heading' ) ) {
	function lafka_custom_related_products_heading() {
		/** @var WC_Product $product */
		global $product;
		$lafka_chosen_category = lafka_get_chosen_category_for_related( $product );

		$output = esc_html__( 'Other', 'lafka' );
		if ( $lafka_chosen_category !== null && $lafka_chosen_category->slug !== 'uncategorized' ) {
			ob_start();
			?>
			<a class="lafka-related-browse"
				href="<?php echo esc_url( get_term_link( $lafka_chosen_category ) ); ?>"
				title="<?php printf( esc_attr__( 'Browse more "%s"', 'lafka' ), esc_attr( $lafka_chosen_category->name ) ); ?>">
				<?php echo esc_html( $lafka_chosen_category->name ); ?>
			</a>
			<?php
			$output .= ob_get_clean();
		} else {
			$output .= esc_html__( 'Products', 'lafka' );
		}

		$output .= esc_html__( 'you\'ll love', 'lafka' );

		return $output;
	}
}

add_filter( 'single_product_archive_thumbnail_size', 'lafka_get_single_product_archive_thumbnail_size' );
if ( ! function_exists( 'lafka_get_single_product_archive_thumbnail_size' ) ) {
	/**
	 * For list view, use small images
	 *
	 * @param $size
	 *
	 * @return string
	 */
	function lafka_get_single_product_archive_thumbnail_size( $size ) {
		if ( lafka_is_product_listview() ) {
			return 'lafka-general-small-size-nocrop';
		}

		return $size;
	}
}

add_action(
	'woocommerce_before_shop_loop_item',
	function () {
		if ( lafka_is_product_listview() ) {
			echo '<div class="lafka-list-view-summary-wrap">';
		}
	},
	99
);
add_action(
	'woocommerce_after_shop_loop_item',
	function () {
		if ( lafka_is_product_listview() ) {
			echo '</div>';
		}
	},
	99
);

if ( ! function_exists( 'lafka_is_product_listview' ) ) {
	function lafka_is_product_listview(): bool {
		return ( is_product_category() || is_shop() ) && get_theme_mod( 'lafka_shop_default_product_columns', 'columns-3' ) == 'lafka-products-list-view';
	}
}

add_filter( 'lafka_links_before_add_to_cart', 'lafka_quantity_input_on_listing' );
if ( ! function_exists( 'lafka_quantity_input_on_listing' ) ) {
	function lafka_quantity_input_on_listing() {
		if ( get_theme_mod( 'lafka_show_quantity_on_listing', false ) && ! get_theme_mod( 'lafka_use_quickview', true ) ) {
			global $product;
			if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
				$product = wc_get_product( get_the_ID() );
			}

			if ( ! empty( $product ) && $product->is_purchasable() && ! $product->is_sold_individually() && $product->is_in_stock() && 'variable' != $product->get_type() && 'bundle' != $product->get_type() && 'combo' != $product->get_type() ) {
				woocommerce_quantity_input(
					[
						'min_value' => 1,
						'max_value' => $product->backorders_allowed() ? '' : $product->get_stock_quantity(),
					]
				);
			}
		}
	}
}

/**
 * P6-A11Y-6: Inject lafka product-loop classes via WC filter.
 *
 * content-product.php now calls wc_product_class( '', $product ) so that
 * the template regex matches <li <?php wc_product_class( '', $product ); ?>>.
 * This filter re-adds the theme-specific classes (prod_hold, hover behaviour,
 * variation flag, button-visibility) that were previously passed inline.
 *
 * The WC filter that runs inside wc_get_product_class() is 'woocommerce_post_class'
 * and receives (array $classes, WC_Product $product).
 *
 * @param array      $classes  Classes assembled by WooCommerce.
 * @param WC_Product $product  Current product object.
 * @return array
 */
add_filter( 'woocommerce_post_class', 'lafka_product_loop_item_class', 10, 2 );
if ( ! function_exists( 'lafka_product_loop_item_class' ) ) {
	function lafka_product_loop_item_class( array $classes, $product ): array {
		if ( ! is_a( $product, WC_Product::class ) ) {
			return $classes;
		}

		// Always add the lafka card holder class.
		$classes[] = 'prod_hold';

		// Hover behaviour.
		$hover = get_theme_mod( 'lafka_product_hover_onproduct', 'lafka-prodhover-zoom' );
		if ( $hover && 'none' !== $hover ) {
			if ( ! ( 'lafka-prodhover-swap' === $hover && ! lafka_get_second_product_image_id( $product ) ) ) {
				$classes[] = $hover;
			}
		}

		// Variation-in-catalog flag.
		if ( lafka_is_product_eligible_for_variation_in_listings( $product ) ) {
			$classes[] = 'lafka-variations-list-in-catalog';
		}

		// Button visibility modifier.
		$btn_visibility = get_theme_mod( 'lafka_product_list_buttons_visibility', 'lafka-visible-buttons' );
		if ( $btn_visibility ) {
			$classes[] = $btn_visibility;
		}

		return $classes;
	}
}
