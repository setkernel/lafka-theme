<?php
/**
 * Partial: Home category quick-pick grid
 *
 * Renders the top N WooCommerce product categories as visual tiles.
 * Auto-discovers categories with at least one published product, excludes
 * "Uncategorized" (uses lafka_uncategorized_excluded_ids() if available).
 *
 * Reads:
 *  - lafka_home_categories_eyebrow  (default: "Browse the menu")
 *  - lafka_home_categories_headline (default: "What are you craving?")
 *  - lafka_home_categories_limit    (int, default: 6)
 *  - lafka_home_categories_orderby  (default: 'count' — popular first)
 *
 * @package Lafka
 * @since   5.46.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}

$lafka_cat_eyebrow  = (string) get_theme_mod( 'lafka_home_categories_eyebrow', __( 'Browse the menu', 'lafka' ) );
$lafka_cat_headline = (string) get_theme_mod( 'lafka_home_categories_headline', __( 'What are you craving?', 'lafka' ) );
$lafka_cat_limit    = (int) get_theme_mod( 'lafka_home_categories_limit', 6 );
$lafka_cat_orderby  = (string) get_theme_mod( 'lafka_home_categories_orderby', 'count' );

$lafka_cat_args = array(
	'taxonomy'   => 'product_cat',
	'number'     => max( 1, $lafka_cat_limit ),
	'hide_empty' => true,
	'orderby'    => $lafka_cat_orderby,
	'order'      => 'count' === $lafka_cat_orderby ? 'DESC' : 'ASC',
);

if ( function_exists( 'lafka_uncategorized_excluded_ids' ) ) {
	$lafka_cat_args['exclude'] = lafka_uncategorized_excluded_ids();
}

$lafka_cat_terms = get_terms( $lafka_cat_args );
if ( is_wp_error( $lafka_cat_terms ) || empty( $lafka_cat_terms ) ) {
	return;
}
?>
<section class="lafka-home-categories" aria-labelledby="lafka-home-categories-heading">
	<div class="lafka-container">

		<header class="lafka-section-head">
			<?php if ( '' !== $lafka_cat_eyebrow ) : ?>
				<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_cat_eyebrow ); ?></p>
			<?php endif; ?>
			<h2 id="lafka-home-categories-heading" class="lafka-section-headline"><?php echo esc_html( $lafka_cat_headline ); ?></h2>
		</header>

		<ul class="lafka-home-categories__grid" role="list">
			<?php
			foreach ( $lafka_cat_terms as $lafka_cat_term ) :
				$lafka_cat_url   = get_term_link( $lafka_cat_term );
				$lafka_thumb_id  = (int) get_term_meta( $lafka_cat_term->term_id, 'thumbnail_id', true );
				$lafka_thumb_src = $lafka_thumb_id ? wp_get_attachment_image_url( $lafka_thumb_id, 'medium' ) : '';
				$lafka_cat_count = (int) $lafka_cat_term->count;
				?>
				<li class="lafka-home-categories__item">
					<a class="lafka-home-categories__tile" href="<?php echo esc_url( $lafka_cat_url ); ?>">
						<div class="lafka-home-categories__media">
							<?php if ( $lafka_thumb_src ) : ?>
								<img
									class="lafka-home-categories__image"
									src="<?php echo esc_url( $lafka_thumb_src ); ?>"
									alt=""
									loading="lazy"
									decoding="async"
								>
							<?php else : ?>
								<span class="lafka-home-categories__image-placeholder" aria-hidden="true">🍴</span>
							<?php endif; ?>
						</div>
						<div class="lafka-home-categories__body">
							<span class="lafka-home-categories__name"><?php echo esc_html( $lafka_cat_term->name ); ?></span>
							<span class="lafka-home-categories__count">
								<?php
								printf(
									esc_html( _n( '%d item', '%d items', $lafka_cat_count, 'lafka' ) ),
									(int) $lafka_cat_count
								);
								?>
							</span>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div><!-- .lafka-container -->
</section>
