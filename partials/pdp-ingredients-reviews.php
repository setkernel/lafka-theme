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

/*
 * Reviews — HONEST social proof only (v6.14.0, audit 2026-06-27 #conversion).
 *
 * No fabricated defaults. The rating + testimonials come from REAL data:
 *   1. the product's actual approved WooCommerce reviews + rating (automatic),
 *   2. or operator-set Customizer fields (override),
 *   3. or the `lafka_pdp_reviews` filter.
 * If none exist, the whole reviews card is omitted (the "What's in it" card
 * still shows). Defaults are 0 / '' so a fresh install never invents ratings.
 */
$lafka_pdp_rating_avg   = (float) get_theme_mod( 'lafka_pdp_rating_avg', 0 );
$lafka_pdp_rating_count = (int) get_theme_mod( 'lafka_pdp_rating_count', 0 );

// Pull the product's real WC reviews/rating when the operator hasn't overridden.
global $product;
if ( ( $lafka_pdp_rating_count <= 0 ) && $product instanceof WC_Product && wc_review_ratings_enabled() ) {
	$lafka_pdp_rating_avg   = (float) $product->get_average_rating();
	$lafka_pdp_rating_count = (int) $product->get_review_count();
}

$lafka_pdp_reviews = (array) apply_filters(
	'lafka_pdp_reviews',
	array(
		array(
			'quote'  => (string) get_theme_mod( 'lafka_pdp_review_1_quote', '' ),
			'author' => (string) get_theme_mod( 'lafka_pdp_review_1_author', '' ),
			'date'   => (string) get_theme_mod( 'lafka_pdp_review_1_date', '' ),
		),
		array(
			'quote'  => (string) get_theme_mod( 'lafka_pdp_review_2_quote', '' ),
			'author' => (string) get_theme_mod( 'lafka_pdp_review_2_author', '' ),
			'date'   => (string) get_theme_mod( 'lafka_pdp_review_2_date', '' ),
		),
	)
);

// Drop entries with no real quote text — never render a fabricated testimonial.
$lafka_pdp_reviews = array_values(
	array_filter(
		$lafka_pdp_reviews,
		static function ( $r ) {
			return is_array( $r ) && '' !== trim( (string) ( $r['quote'] ?? '' ) );
		}
	)
);

// If the operator set no testimonials, surface real WC review excerpts.
if ( empty( $lafka_pdp_reviews ) && $product instanceof WC_Product && $lafka_pdp_rating_count > 0 ) {
	$lafka_pdp_wc_comments = get_comments(
		array(
			'post_id'  => $product->get_id(),
			'status'   => 'approve',
			'type'     => 'review',
			'number'   => 3,
			'meta_key' => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small per-PDP lookup.
		)
	);
	foreach ( (array) $lafka_pdp_wc_comments as $lafka_pdp_c ) {
		$lafka_pdp_reviews[] = array(
			'quote'  => wp_trim_words( (string) $lafka_pdp_c->comment_content, 28 ),
			'author' => (string) $lafka_pdp_c->comment_author,
			'date'   => human_time_diff( strtotime( $lafka_pdp_c->comment_date_gmt ) ) . ' ' . __( 'ago', 'lafka' ),
		);
	}
}

// Reviews card shows only when there is real data.
$lafka_pdp_reviews_show = (bool) apply_filters(
	'lafka_pdp_reviews_visible',
	( $lafka_pdp_rating_count > 0 || ! empty( $lafka_pdp_reviews ) )
);

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
