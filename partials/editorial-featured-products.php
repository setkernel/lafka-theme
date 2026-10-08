<?php
/**
 * Partial: Editorial featured products section (dark background, 3-up grid).
 *
 * Pulls WooCommerce products marked as "featured". Renders nothing if WC is
 * inactive or returns no featured products (graceful degradation).
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_products' ) ) {
	return; // WooCommerce not active
}

$lafka_products = wc_get_products(
	array(
		'featured' => true,
		'limit'    => 3,
		'status'   => 'publish',
		'orderby'  => 'date',
		'order'    => 'DESC',
	)
);

if ( empty( $lafka_products ) ) {
	return;
}
?>
<section class="featured-section">
	<div class="section-head">
		<div>
			<div class="label"><?php esc_html_e( '&mdash; Most ordered this week', 'lafka' ); ?></div>
			<h2><?php esc_html_e( 'Our featured items', 'lafka' ); ?></h2>
		</div>
	</div>

	<div class="products-grid">
		<?php
		foreach ( $lafka_products as $lafka_product ) :
			$lafka_thumb_html = function_exists( 'lafka_card_image_html' ) ? lafka_card_image_html( $lafka_product, array( 'class' => '' ) ) : '';
			$lafka_price_html = $lafka_product->get_price_html();
			$lafka_name       = $lafka_product->get_name();
			$lafka_desc       = wp_strip_all_tags( $lafka_product->get_short_description() );
			$lafka_url        = get_permalink( $lafka_product->get_id() );
			?>
		<article class="product-card">
			<a href="<?php echo esc_url( $lafka_url ); ?>" class="product-photo">
				<?php
				echo wp_kses( $lafka_thumb_html, lafka_allowed_html() );
				?>
			</a>
			<h3><a href="<?php echo esc_url( $lafka_url ); ?>"><?php echo esc_html( $lafka_name ); ?></a></h3>
			<?php if ( $lafka_desc ) : ?>
			<p class="product-desc"><?php echo esc_html( $lafka_desc ); ?></p>
			<?php endif; ?>
			<div class="price-row">
				<span class="product-price"><?php echo wp_kses_post( $lafka_price_html ); ?></span>
				<a href="<?php echo esc_url( $lafka_url ); ?>" class="btn btn-primary" style="font-size:0.8rem;padding:0.6rem 1rem;">
					<?php esc_html_e( 'View', 'lafka' ); ?>
				</a>
			</div>
		</article>
		<?php endforeach; ?>
	</div>
</section>
