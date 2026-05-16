<?php
/**
 * PDP ingredients + reviews 2-card grid (v5.91.0).
 *
 * Per handoff /#/product/<id>: two cards directly below the buy box,
 * stacked single-column on mobile, side-by-side at ≥768.
 *
 *   Left  — "What's in it" card: long description + Allergens chip row
 *   Right — "Reviews · 4.8" card: 2 testimonials + "Read more reviews" link
 *
 * Both cards operate on operator-tunable data:
 *   - Long description: WC product description (the_content)
 *   - Allergens: product_tag terms matching a known allergen list
 *   - Reviews: Customizer fields (lafka_pdp_review_1_*, lafka_pdp_review_2_*)
 *   - Rating avg/count: Customizer (lafka_pdp_rating_avg, lafka_pdp_rating_count)
 *
 * Hidden when there's no description AND no allergens (defensive).
 *
 * @package Lafka\Partials
 * @since   5.91.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! ( $product instanceof WC_Product ) ) {
	return;
}

$lafka_pdp_long_desc = (string) $product->get_description();
$lafka_pdp_short     = (string) $product->get_short_description();

// Allergens: any product_tag matching the known set acts as an allergen
// chip. Operators can extend via the `lafka_pdp_allergen_slugs` filter.
$lafka_pdp_allergen_slugs = (array) apply_filters(
	'lafka_pdp_allergen_slugs',
	array(
		'wheat'   => __( 'Wheat', 'lafka' ),
		'gluten'  => __( 'Gluten', 'lafka' ),
		'milk'    => __( 'Milk', 'lafka' ),
		'dairy'   => __( 'Dairy', 'lafka' ),
		'eggs'    => __( 'Egg', 'lafka' ),
		'egg'     => __( 'Egg', 'lafka' ),
		'soy'     => __( 'Soy', 'lafka' ),
		'peanuts' => __( 'Peanuts', 'lafka' ),
		'nuts'    => __( 'May contain nuts', 'lafka' ),
		'fish'    => __( 'Fish', 'lafka' ),
		'shellfish' => __( 'Shellfish', 'lafka' ),
	)
);

$lafka_pdp_tags  = function_exists( 'wp_get_post_terms' )
	? wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'slugs' ) )
	: array();
if ( is_wp_error( $lafka_pdp_tags ) ) {
	$lafka_pdp_tags = array();
}
$lafka_pdp_allergens = array();
foreach ( $lafka_pdp_tags as $lafka_pdp_tag ) {
	$lafka_pdp_tag_norm = strtolower( (string) $lafka_pdp_tag );
	if ( isset( $lafka_pdp_allergen_slugs[ $lafka_pdp_tag_norm ] ) ) {
		$lafka_pdp_allergens[] = $lafka_pdp_allergen_slugs[ $lafka_pdp_tag_norm ];
	}
}

// Reviews data from Customizer.
$lafka_pdp_rating_avg   = (float) get_theme_mod( 'lafka_pdp_rating_avg', 4.8 );
$lafka_pdp_rating_count = (int) get_theme_mod( 'lafka_pdp_rating_count', 312 );

$lafka_pdp_reviews = (array) apply_filters(
	'lafka_pdp_reviews',
	array(
		array(
			'quote'  => (string) get_theme_mod( 'lafka_pdp_review_1_quote', __( 'Honestly the best in the area. Crust is perfect every time.', 'lafka' ) ),
			'author' => (string) get_theme_mod( 'lafka_pdp_review_1_author', __( 'Marcus T.', 'lafka' ) ),
			'date'   => (string) get_theme_mod( 'lafka_pdp_review_1_date', __( '4 days ago', 'lafka' ) ),
		),
		array(
			'quote'  => (string) get_theme_mod( 'lafka_pdp_review_2_quote', __( 'Ordered as a treat — kids fought over the last slice.', 'lafka' ) ),
			'author' => (string) get_theme_mod( 'lafka_pdp_review_2_author', __( 'Priya A.', 'lafka' ) ),
			'date'   => (string) get_theme_mod( 'lafka_pdp_review_2_date', __( '2 weeks ago', 'lafka' ) ),
		),
	)
);

$lafka_pdp_reviews_show = (bool) apply_filters( 'lafka_pdp_reviews_visible', true );

// Bail early if absolutely nothing to show.
if ( '' === $lafka_pdp_long_desc && empty( $lafka_pdp_allergens ) && ! $lafka_pdp_reviews_show ) {
	return;
}
?>
<section class="lafka-pdp-info" aria-label="<?php esc_attr_e( 'Ingredients and reviews', 'lafka' ); ?>">
	<div class="lafka-container lafka-pdp-info__grid">

		<article class="lafka-pdp-info__card">
			<h3 class="lafka-pdp-info__card-title"><?php esc_html_e( "What's in it", 'lafka' ); ?></h3>
			<div class="lafka-pdp-info__body">
				<?php
				if ( '' !== $lafka_pdp_long_desc ) {
					echo wp_kses_post( wpautop( $lafka_pdp_long_desc ) );
				} elseif ( '' !== $lafka_pdp_short ) {
					echo wp_kses_post( wpautop( $lafka_pdp_short ) );
				} else {
					echo '<p>' . esc_html__( 'Made fresh to order.', 'lafka' ) . '</p>';
				}
				?>
			</div>
			<?php if ( ! empty( $lafka_pdp_allergens ) ) : ?>
				<h4 class="lafka-pdp-info__sublabel"><?php esc_html_e( 'Allergens', 'lafka' ); ?></h4>
				<ul class="lafka-pdp-info__allergens" role="list">
					<?php foreach ( $lafka_pdp_allergens as $lafka_pdp_allergen ) : ?>
						<li class="lafka-pdp-info__allergen-chip"><?php echo esc_html( $lafka_pdp_allergen ); ?></li>
					<?php endforeach; ?>
					<li class="lafka-pdp-info__allergen-chip lafka-pdp-info__allergen-chip--muted">
						<?php esc_html_e( 'Ask staff about dietary restrictions', 'lafka' ); ?>
					</li>
				</ul>
			<?php endif; ?>
		</article>

		<?php if ( $lafka_pdp_reviews_show && ! empty( $lafka_pdp_reviews ) ) : ?>
			<article class="lafka-pdp-info__card">
				<h3 class="lafka-pdp-info__card-title">
					<?php
					/* translators: %s — average rating, e.g. "4.8" */
					printf( esc_html__( 'Reviews · %s', 'lafka' ), esc_html( number_format_i18n( $lafka_pdp_rating_avg, 1 ) ) );
					?>
				</h3>

				<ul class="lafka-pdp-info__reviews" role="list">
					<?php foreach ( $lafka_pdp_reviews as $lafka_pdp_rev ) : ?>
						<?php
                        if ( empty( $lafka_pdp_rev['quote'] ) ) {
							continue; }
						?>
						<li class="lafka-pdp-info__review">
							<span class="lafka-pdp-info__review-stars" aria-hidden="true">★★★★★</span>
							<blockquote class="lafka-pdp-info__review-quote">
								<?php echo esc_html( $lafka_pdp_rev['quote'] ); ?>
							</blockquote>
							<p class="lafka-pdp-info__review-attribution">
								<strong><?php echo esc_html( $lafka_pdp_rev['author'] ); ?></strong>
								<?php if ( ! empty( $lafka_pdp_rev['date'] ) ) : ?>
									<span> · <?php echo esc_html( $lafka_pdp_rev['date'] ); ?></span>
								<?php endif; ?>
							</p>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $lafka_pdp_rating_count > 0 ) : ?>
					<p class="lafka-pdp-info__review-count">
						<?php
						/* translators: %s — total review count, e.g. "312" */
						printf( esc_html__( 'Based on %s customer reviews.', 'lafka' ), esc_html( number_format_i18n( $lafka_pdp_rating_count ) ) );
						?>
					</p>
				<?php endif; ?>
			</article>
		<?php endif; ?>

	</div>
</section>
