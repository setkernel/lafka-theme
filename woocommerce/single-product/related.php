<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     10.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $related_products ) :
	/**
	 * Ensure all images of related products are lazy loaded by increasing the
	 * current media count to WordPress's lazy loading threshold if needed.
	 * Because wp_increase_content_media_count() is a private function, we
	 * check for its existence before use.
	 */
	if ( function_exists( 'wp_increase_content_media_count' ) ) {
		$lafka_content_media_count = wp_increase_content_media_count( 0 );
		if ( $lafka_content_media_count < wp_omit_loading_attr_threshold() ) {
			wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $lafka_content_media_count );
		}
	}
	?>

	<section class="related products">
		<?php $lafka_heading = apply_filters( 'woocommerce_product_related_products_heading', __( 'Related products', 'lafka' ) ); ?>
		<?php if ( $lafka_heading ) : ?>
			<h2><?php echo wp_kses_post( $lafka_heading ); ?></h2>
		<?php endif; ?>

		<?php
		// v5.84.0: a11y — the carousel container <div> previously sat
		// INSIDE the <ul class="products">, which is invalid DOM (only
		// <li> may be a child of <ul>). Screen readers stopped
		// announcing the products as a list. Wrapper moved outside
		// the loop start/end so the structure is
		// <div.lafka-related-carousel><ul class="products"><li>card</li></ul></div>
		// instead of <ul class="products"><div><li></div></ul>.
		?>
		<div class="lafka-related-carousel">
			<?php woocommerce_product_loop_start(); ?>

				<?php
				// A real loop over the related products, so WordPress sets the post globals itself.
				$lafka_related_loop = new WP_Query(
					array(
						'post_type'           => 'product',
						'post__in'            => array_map(
							static function ( $lafka_related_product ) {
								return $lafka_related_product->get_id();
							},
							$related_products
						),
						'orderby'             => 'post__in',
						'posts_per_page'      => count( $related_products ),
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
				while ( $lafka_related_loop->have_posts() ) :
					$lafka_related_loop->the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;
				wp_reset_postdata();
				?>

			<?php woocommerce_product_loop_end(); ?>
		</div>

	</section>

	<?php
endif;

wp_reset_postdata();
