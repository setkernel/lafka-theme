<?php
/**
 * Partial: Home customer favourites (v5.60.0 handoff rebuild).
 *
 * Per handoff /design_handoff_peppery_ordering/README.md "Home page > 3. Customer favourites":
 *   - popular(8) items in 1/2/3/4 col grid at 0/600/1024/1280px
 *   - Cards: white, no border, radius-lg, soft 2-tier shadow
 *   - 4:3 image, optional "★ Popular" badge top-left
 *   - Fraunces title, muted 2-line description, "from $X.XX"
 *   - Dark pill "Customize" button
 *
 * Source priority:
 *   1. WC Featured products
 *   2. Best-sellers fallback (top order_count)
 *
 * @package Lafka
 * @since   5.60.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_products' ) ) {
	return;
}

$lafka_feat_eyebrow  = (string) get_theme_mod( 'lafka_home_featured_eyebrow', __( 'Most ordered', 'lafka' ) );
$lafka_feat_headline = (string) get_theme_mod( 'lafka_home_featured_headline', __( 'Customer favourites', 'lafka' ) );
$lafka_feat_limit    = (int) get_theme_mod( 'lafka_home_featured_limit', 8 );

$lafka_feat_products = wc_get_products(
	array(
		'status'   => 'publish',
		'limit'    => $lafka_feat_limit,
		'featured' => true,
		'orderby'  => 'menu_order',
		'order'    => 'ASC',
	)
);

// Fallback: best-sellers by total_sales meta.
if ( empty( $lafka_feat_products ) ) {
	$lafka_feat_products = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => $lafka_feat_limit,
			'orderby'  => 'meta_value_num',
			'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'    => 'DESC',
		)
	);
}

if ( empty( $lafka_feat_products ) ) {
	return;
}
?>
<section class="lafka-favs" aria-labelledby="lafka-favs-heading">
	<div class="lafka-container">

		<header class="lafka-section-head">
			<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_feat_eyebrow ); ?></p>
			<h2 id="lafka-favs-heading" class="lafka-section-headline"><?php echo esc_html( $lafka_feat_headline ); ?></h2>
		</header>

		<ul class="lafka-favs__grid" role="list">
			<?php
            foreach ( $lafka_feat_products as $lafka_feat_product ) :
				$lafka_feat_id    = $lafka_feat_product->get_id();
				$lafka_feat_url   = get_permalink( $lafka_feat_id );
				$lafka_feat_img   = get_the_post_thumbnail_url( $lafka_feat_id, 'medium_large' );
				$lafka_feat_name  = $lafka_feat_product->get_name();
				$lafka_feat_short = $lafka_feat_product->get_short_description();
				if ( '' === $lafka_feat_short ) {
					$lafka_feat_short = wp_trim_words( $lafka_feat_product->get_description(), 18 );
				}
				$lafka_feat_price = $lafka_feat_product->get_price_html();
				$lafka_feat_is_featured = $lafka_feat_product->is_featured();
				?>
				<li class="lafka-favs__item">
					<a class="lafka-favs__card" href="<?php echo esc_url( $lafka_feat_url ); ?>">
						<div class="lafka-favs__media">
							<?php if ( $lafka_feat_img ) : ?>
								<img class="lafka-favs__img" src="<?php echo esc_url( $lafka_feat_img ); ?>" alt="" loading="lazy" decoding="async">
							<?php else : ?>
								<span class="lafka-favs__img-placeholder" aria-hidden="true">🍕</span>
							<?php endif; ?>
							<?php if ( $lafka_feat_is_featured ) : ?>
								<span class="lafka-favs__badge">★ <?php esc_html_e( 'Popular', 'lafka' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="lafka-favs__body">
							<h3 class="lafka-favs__name"><?php echo esc_html( $lafka_feat_name ); ?></h3>
							<?php if ( '' !== $lafka_feat_short ) : ?>
								<p class="lafka-favs__desc"><?php echo esc_html( wp_strip_all_tags( $lafka_feat_short ) ); ?></p>
							<?php endif; ?>
							<div class="lafka-favs__foot">
								<span class="lafka-favs__price"><?php echo wp_kses_post( $lafka_feat_price ); ?></span>
								<span class="lafka-favs__cta"><?php esc_html_e( 'Customize', 'lafka' ); ?></span>
							</div>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
