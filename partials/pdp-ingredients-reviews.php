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
 *   - Reviews and rating: this product's real approved WooCommerce reviews
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
		'wheat'     => __( 'Wheat', 'lafka' ),
		'gluten'    => __( 'Gluten', 'lafka' ),
		'milk'      => __( 'Milk', 'lafka' ),
		'dairy'     => __( 'Dairy', 'lafka' ),
		'eggs'      => __( 'Egg', 'lafka' ),
		'egg'       => __( 'Egg', 'lafka' ),
		'soy'       => __( 'Soy', 'lafka' ),
		'peanuts'   => __( 'Peanuts', 'lafka' ),
		'nuts'      => __( 'May contain nuts', 'lafka' ),
		'fish'      => __( 'Fish', 'lafka' ),
		'shellfish' => __( 'Shellfish', 'lafka' ),
	)
);

$lafka_pdp_tags = wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'slugs' ) );
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
 * Reviews: this product's REAL approved WooCommerce reviews and rating only.
 * Nothing is typed in by hand, so a fresh install never shows an invented
 * rating or testimonial. With no review the whole reviews card is omitted (the
 * "What's in it" card still shows). The `lafka_pdp_reviews` filter lets a child
 * theme adjust the list.
 */
$lafka_pdp_rating_avg   = 0.0;
$lafka_pdp_rating_count = 0;
if ( wc_review_ratings_enabled() ) {
	$lafka_pdp_rating_avg   = (float) $product->get_average_rating();
	$lafka_pdp_rating_count = (int) $product->get_review_count();
}

$lafka_pdp_reviews = array();
if ( $lafka_pdp_rating_count > 0 ) {
	$lafka_pdp_wc_comments = get_comments(
		array(
			'post_id'  => $product->get_id(),
			'status'   => 'approve',
			'type'     => 'review',
			'number'   => 2,
			'meta_key' => 'rating',
		)
	);
	foreach ( (array) $lafka_pdp_wc_comments as $lafka_pdp_c ) {
		$lafka_pdp_quote = trim( wp_trim_words( wp_strip_all_tags( (string) $lafka_pdp_c->comment_content ), 28 ) );
		if ( '' === $lafka_pdp_quote ) {
			continue;
		}
		$lafka_pdp_reviews[] = array(
			'quote'  => $lafka_pdp_quote,
			'author' => function_exists( 'lafka_store_reviews_short_name' ) ? lafka_store_reviews_short_name( (string) $lafka_pdp_c->comment_author ) : '',
			'date'   => human_time_diff( strtotime( $lafka_pdp_c->comment_date_gmt ) ) . ' ' . __( 'ago', 'lafka' ),
			'stars'  => (int) max( 1, min( 5, (int) get_comment_meta( (int) $lafka_pdp_c->comment_ID, 'rating', true ) ) ),
		);
	}
}
$lafka_pdp_reviews = (array) apply_filters( 'lafka_pdp_reviews', $lafka_pdp_reviews );

// Drop entries with no real quote text.
$lafka_pdp_reviews = array_values(
	array_filter(
		$lafka_pdp_reviews,
		static function ( $r ) {
			return is_array( $r ) && '' !== trim( (string) ( $r['quote'] ?? '' ) );
		}
	)
);

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
<section class="lafka-pdp-info" aria-label="<?php echo esc_attr( $lafka_pdp_reviews_show && ! empty( $lafka_pdp_reviews ) ? __( 'Ingredients and reviews', 'lafka' ) : __( 'Ingredients', 'lafka' ) ); ?>">
	<div class="lafka-container lafka-pdp-info__grid">

		<article class="lafka-pdp-info__card">
			<h2 class="lafka-pdp-info__card-title"><?php esc_html_e( "What's in it", 'lafka' ); ?></h2>
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
				<h3 class="lafka-pdp-info__sublabel"><?php esc_html_e( 'Allergens', 'lafka' ); ?></h3>
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
				<h2 class="lafka-pdp-info__card-title">
					<?php
					/* translators: %s — average rating, e.g. "4.8" */
					printf( esc_html__( 'Reviews · %s', 'lafka' ), esc_html( number_format_i18n( $lafka_pdp_rating_avg, 1 ) ) );
					?>
				</h2>

				<ul class="lafka-pdp-info__reviews" role="list">
					<?php foreach ( $lafka_pdp_reviews as $lafka_pdp_rev ) : ?>
						<?php
						if ( empty( $lafka_pdp_rev['quote'] ) ) {
							continue; }
						?>
						<li class="lafka-pdp-info__review">
							<span class="lafka-pdp-info__review-stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', isset( $lafka_pdp_rev['stars'] ) ? (int) max( 1, min( 5, (int) $lafka_pdp_rev['stars'] ) ) : 5 ) ); ?></span>
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
