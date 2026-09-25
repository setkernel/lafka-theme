<?php
/**
 * Product card images.
 *
 * Two entry points share one alt/loading policy:
 *
 *   - lafka_card_image_html()          — the handoff `.lafka-favs__*` cards
 *     (menu page, category/tag/shop archives, home favourites, editorial
 *     featured grid). Responsive srcset/sizes/width/height via
 *     wp_get_attachment_image(), real alt, first-row eager loading on
 *     archive/menu grids and fetchpriority on the very first card there.
 *   - lafka_product_card_image_html()  — the WooCommerce loop card
 *     (content-product.php) with its Customizer + bundled-SVG fallback chain.
 *
 * Alt text: the attachment's own alt (Media Library) wins; the product name is
 * the fallback so a card image is never announced as decorative-but-empty and
 * image search gets a meaningful label.
 *
 * @package Lafka\TemplateHelpers
 * @since   5.17.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_card_image_alt' ) ) {
	/**
	 * Alt text for a product card image: attachment alt, else the product name.
	 *
	 * Returned unescaped — wp_get_attachment_image() escapes attribute values.
	 *
	 * @param WC_Product $product       Product the image belongs to.
	 * @param int        $attachment_id Attachment being rendered.
	 * @return string
	 */
	function lafka_card_image_alt( $product, $attachment_id ) {
		$alt = '';
		if ( $attachment_id && function_exists( 'get_post_meta' ) ) {
			$alt = trim( wp_strip_all_tags( (string) get_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', true ) ) );
		}
		if ( '' === $alt && is_object( $product ) && method_exists( $product, 'get_name' ) ) {
			$alt = trim( wp_strip_all_tags( (string) $product->get_name() ) );
		}
		return $alt;
	}
}

if ( ! function_exists( 'lafka_card_image_sizes' ) ) {
	/**
	 * `sizes` for the 1 / 2 / 3 / 4-column card grid (0 / 600 / 1024 / 1280 px,
	 * container capped at --lafka-container-max = 1440px).
	 *
	 * @return string
	 */
	function lafka_card_image_sizes() {
		$sizes = '(min-width: 1440px) 340px, (min-width: 1280px) 25vw, (min-width: 1024px) 33vw, (min-width: 600px) 50vw, 100vw';
		/**
		 * Filter the `sizes` attribute of product card images.
		 *
		 * @since 7.2.0
		 * @param string $sizes Media-condition list matching the card grid.
		 */
		return (string) apply_filters( 'lafka_card_image_sizes', $sizes );
	}
}

if ( ! function_exists( 'lafka_card_image_next_index' ) ) {
	/**
	 * Zero-based position of the next "lead grid" card on this request.
	 *
	 * The counter spans every grid on the page (the grouped menu renders one
	 * grid per category), so "first row" means the first cards of the page.
	 *
	 * @param bool $reset Reset the counter (tests / a fresh render pass) and return 0.
	 * @return int
	 */
	function lafka_card_image_next_index( $reset = false ) {
		static $index = 0;
		if ( $reset ) {
			$index = 0;
			return 0;
		}
		return $index++;
	}
}

if ( ! function_exists( 'lafka_card_image_html' ) ) {
	/**
	 * Responsive <img> for a handoff product card, or '' when the product has
	 * no featured image (callers keep their own placeholder).
	 *
	 * Args:
	 *   - size      (string) registered image size, default 'medium_large'.
	 *   - class     (string) img class, default 'lafka-favs__img'.
	 *   - lead_grid (bool)   true on archive / menu grids near the top of the
	 *                        page: the first `lafka_product_card_eager_count`
	 *                        cards load eagerly and the very first one gets
	 *                        fetchpriority="high". False (default) = always lazy
	 *                        (home favourites sit below the hero, which is the LCP).
	 *
	 * @param WC_Product           $product Product to render.
	 * @param array<string, mixed> $args    See above.
	 * @return string Escaped <img> markup or ''.
	 */
	function lafka_card_image_html( $product, array $args = array() ) {
		$args = array_merge(
			array(
				'size'      => 'medium_large',
				'class'     => 'lafka-favs__img',
				'lead_grid' => false,
			),
			$args
		);

		// Every lead-grid card occupies a slot in the first row whether or not
		// it has an image, so advance the counter before bailing.
		$index = $args['lead_grid'] ? lafka_card_image_next_index() : -1;

		if ( ! is_object( $product ) || ! method_exists( $product, 'get_image_id' ) ) {
			return '';
		}
		$image_id = (int) $product->get_image_id();
		if ( ! $image_id ) {
			return '';
		}

		$attr = array(
			'alt'      => lafka_card_image_alt( $product, $image_id ),
			'decoding' => 'async',
			// GX4: a caller may pass its own `sizes` (e.g. the counter's fixed
			// 104/140 px row thumbnails); the default stays the grid-card value.
			'sizes'    => isset( $args['sizes'] ) && '' !== (string) $args['sizes'] ? (string) $args['sizes'] : lafka_card_image_sizes(),
			'loading'  => 'lazy',
		);
		if ( '' !== (string) $args['class'] ) {
			$attr = array( 'class' => (string) $args['class'] ) + $attr;
		}

		if ( $index >= 0 ) {
			/**
			 * How many lead-grid cards load eagerly (the first row at the widest,
			 * 4-column breakpoint). 0 makes every card lazy.
			 *
			 * @since 7.2.0
			 * @param int $count Eager card count.
			 */
			$eager_count = max( 0, (int) apply_filters( 'lafka_product_card_eager_count', 4 ) );
			if ( $index < $eager_count ) {
				$attr['loading']       = 'eager';
				// Pin the priority explicitly so core's loading-optimization
				// heuristics never promote a second card: only the first card
				// competes for the LCP slot.
				$attr['fetchpriority'] = 0 === $index ? 'high' : 'auto';
			}
		}

		$html = wp_get_attachment_image( $image_id, (string) $args['size'], false, $attr );
		return is_string( $html ) ? $html : '';
	}
}

if ( ! function_exists( 'lafka_product_card_image_html' ) ) {
	/**
	 * Render the <img> tag for a WooCommerce loop card with the fallback chain:
	 * product's featured image -> operator Customizer override -> bundled SVG.
	 *
	 * @param WC_Product $product The product to render the image for.
	 * @param string     $size    Registered image size (default: woocommerce_thumbnail).
	 * @return string             HTML of the <img> tag (already escaped).
	 */
	function lafka_product_card_image_html( $product, $size = 'woocommerce_thumbnail' ) {
		// Guard: if $product isn't a WC_Product (e.g. wc_get_product() returned
		// false for an invalid ID), fall through to the bundled SVG with empty
		// alt rather than fataling on get_name() in a product loop.
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return sprintf(
				'<img class="lafka-product-card__img lafka-product-card__img--fallback" src="%s" loading="lazy" decoding="async" alt="" width="200" height="200">',
				esc_url( get_template_directory_uri() . '/assets/images/product-card-fallback.svg' )
			);
		}

		$attr = array(
			'class'    => 'lafka-product-card__img',
			'loading'  => 'lazy',
			'decoding' => 'async',
		);

		// 1. Try the product's own featured image.
		$product_image_id = (int) $product->get_image_id();
		if ( $product_image_id ) {
			// Raw alt: wp_get_attachment_image() escapes attribute values itself.
			$html = wp_get_attachment_image( $product_image_id, $size, false, $attr + array( 'alt' => lafka_card_image_alt( $product, $product_image_id ) ) );
			if ( $html ) {
				return $html;
			}
		}

		// 2. Try the operator's Customizer override.
		$fallback_id = (int) get_theme_mod( 'lafka_product_card_fallback_image_id', 0 );
		if ( $fallback_id ) {
			// The generic fallback photo is not of this product: label it with
			// the product name, never the fallback attachment's own alt.
			$html = wp_get_attachment_image( $fallback_id, $size, false, $attr + array( 'alt' => $product->get_name() ) );
			if ( $html ) {
				return $html;
			}
		}

		// 3. Bundled SVG placeholder.
		return sprintf(
			'<img class="lafka-product-card__img lafka-product-card__img--fallback" src="%s" loading="lazy" decoding="async" alt="%s" width="200" height="200">',
			esc_url( get_template_directory_uri() . '/assets/images/product-card-fallback.svg' ),
			esc_attr( $product->get_name() )
		);
	}
}
