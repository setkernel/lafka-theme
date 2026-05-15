<?php
/**
 * Partial: Home featured products
 *
 * Renders WC "Featured" products as a horizontal scroll on mobile,
 * 4-up grid on desktop. Empty state hidden (graceful degradation).
 *
 * Reads:
 *  - lafka_home_featured_eyebrow  (default: "Tonight's specials")
 *  - lafka_home_featured_headline (default: "Top picks")
 *  - lafka_home_featured_limit    (int, default: 8)
 *
 * @package Lafka
 * @since   5.46.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_products' ) ) {
	return;
}

$lafka_feat_eyebrow  = (string) get_theme_mod( 'lafka_home_featured_eyebrow', __( "Tonight's specials", 'lafka' ) );
$lafka_feat_headline = (string) get_theme_mod( 'lafka_home_featured_headline', __( 'Top picks', 'lafka' ) );
$lafka_feat_limit    = (int) get_theme_mod( 'lafka_home_featured_limit', 8 );

$lafka_feat_products = wc_get_products(
	array(
		'featured' => true,
		'status'   => 'publish',
		'limit'    => max( 1, $lafka_feat_limit ),
		'orderby'  => 'menu_order',
		'order'    => 'ASC',
	)
);

// Smart fallback: if operator hasn't marked any products as "Featured",
// fall through to top sellers (highest total_sales). This way the
// section ALWAYS has meaningful content on first install.
if ( empty( $lafka_feat_products ) ) {
	$lafka_feat_products = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => max( 1, $lafka_feat_limit ),
			'orderby' => 'meta_value_num',
			'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'   => 'DESC',
		)
	);
	// Replace the eyebrow/headline so the user knows what they're seeing.
	$lafka_feat_eyebrow  = (string) get_theme_mod( 'lafka_home_featured_eyebrow_fallback', __( 'Most ordered', 'lafka' ) );
	$lafka_feat_headline = (string) get_theme_mod( 'lafka_home_featured_headline_fallback', __( 'Customer favorites', 'lafka' ) );
}

if ( empty( $lafka_feat_products ) ) {
	return;
}
?>
<section class="lafka-home-featured" aria-labelledby="lafka-home-featured-heading">
	<div class="lafka-home-featured__inner">

		<header class="lafka-home-featured__head">
			<?php if ( '' !== $lafka_feat_eyebrow ) : ?>
				<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_feat_eyebrow ); ?></p>
			<?php endif; ?>
			<h2 id="lafka-home-featured-heading" class="lafka-home-featured__headline"><?php echo esc_html( $lafka_feat_headline ); ?></h2>
		</header>

		<ul class="lafka-home-featured__grid products" role="list">
			<?php
			foreach ( $lafka_feat_products as $lafka_feat_product ) :
				$lafka_feat_id    = $lafka_feat_product->get_id();
				$lafka_feat_url   = get_permalink( $lafka_feat_id );
				$lafka_feat_name  = $lafka_feat_product->get_name();
				$lafka_feat_desc  = wp_strip_all_tags( $lafka_feat_product->get_short_description() );
				$lafka_feat_thumb = get_the_post_thumbnail_url( $lafka_feat_id, 'woocommerce_thumbnail' );
				$lafka_feat_price = $lafka_feat_product->get_price_html();
				?>
				<li class="lafka-product-card product">
					<a class="lafka-product-card__link" href="<?php echo esc_url( $lafka_feat_url ); ?>" aria-label="<?php echo esc_attr( $lafka_feat_name ); ?>">
						<div class="lafka-product-card__img-wrap">
							<?php if ( $lafka_feat_thumb ) : ?>
								<img
									class="lafka-product-card__img"
									src="<?php echo esc_url( $lafka_feat_thumb ); ?>"
									alt="<?php echo esc_attr( $lafka_feat_name ); ?>"
									loading="lazy"
									decoding="async"
								>
							<?php endif; ?>
						</div>
						<div class="lafka-product-card__body">
							<div class="lafka-product-card__head">
								<h3 class="lafka-product-card__title"><?php echo esc_html( $lafka_feat_name ); ?></h3>
								<?php if ( '' !== $lafka_feat_desc ) : ?>
									<p class="lafka-product-card__desc"><?php echo esc_html( $lafka_feat_desc ); ?></p>
								<?php endif; ?>
							</div>
							<div class="lafka-product-card__bottom">
								<span class="lafka-product-card__price"><?php echo wp_kses_post( $lafka_feat_price ); ?></span>
							</div>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
