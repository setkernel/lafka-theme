<?php
/**
 * Partial: Home reviews — typography-only (v5.60.0 handoff rebuild).
 *
 * Per handoff /design_handoff_peppery_ordering/README.md "Home page > 6. Reviews":
 *   - warm brand-50 band
 *   - Center-aligned section head: inline rating row + h2
 *   - 3-up grid (1/3 cols at 0/768)
 *   - Each review: brand-700 stars + Fraunces 600 20px blockquote with curly quotes,
 *     caption-sized author + date below
 *   - NO CARD BOXES — pure typography
 *
 * @package Lafka
 * @since   5.60.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_rev_visible = (bool) get_theme_mod( 'lafka_home_reviews_visible', true );
if ( ! $lafka_rev_visible ) {
	return;
}

$lafka_rev_headline = (string) get_theme_mod( 'lafka_home_reviews_headline', __( 'What our neighbors say', 'lafka' ) );

/* Real reviews only (a fabricated review is a consumer-protection risk).
 * The quotes are the store's own approved WooCommerce product reviews, picked
 * by the plugin (lafka_get_store_reviews()); a child theme can replace the list
 * with the `lafka_home_reviews` filter. With no real review the section is not
 * printed. The rating line beside the headline is the operator's provider rating
 * (Customizer → Social Proof, e.g. the Google rating) when set, else the average
 * of the store's own review ratings. */
$lafka_reviews = function_exists( 'lafka_get_store_reviews' ) ? lafka_get_store_reviews( 3 ) : array();
$lafka_reviews = (array) apply_filters( 'lafka_home_reviews', $lafka_reviews );

// Drop any entry without real quote text.
$lafka_reviews = array_values(
	array_filter(
		$lafka_reviews,
		static function ( $lafka_rev ) {
			return is_array( $lafka_rev ) && '' !== trim( (string) ( $lafka_rev['quote'] ?? '' ) );
		}
	)
);

$lafka_rev_proof = function_exists( 'lafka_social_proof_get_data' ) ? lafka_social_proof_get_data() : null;
if ( is_array( $lafka_rev_proof ) && ! empty( $lafka_rev_proof['has_rating'] ) ) {
	$lafka_rev_avg   = (float) $lafka_rev_proof['rating'];
	$lafka_rev_count = (int) $lafka_rev_proof['count'];
} else {
	$lafka_rev_summary = function_exists( 'lafka_get_store_review_summary' ) ? lafka_get_store_review_summary() : array(
		'avg'   => 0.0,
		'count' => 0,
	);
	$lafka_rev_avg     = (float) $lafka_rev_summary['avg'];
	$lafka_rev_count   = (int) $lafka_rev_summary['count'];
}

// Aggregate star count is derived from the rounded average (0–5), so the
// star row can never disagree with the numeric score beside it.
$lafka_rev_avg_stars = (int) max( 0, min( 5, (int) round( $lafka_rev_avg ) ) );

if ( empty( $lafka_reviews ) ) {
	return;
}
?>
<section class="lafka-revs" aria-labelledby="lafka-revs-heading">
	<div class="lafka-container">

		<header class="lafka-section-head">
			<?php if ( $lafka_rev_count > 0 ) : ?>
				<p class="lafka-revs__rating">
					<span class="lafka-revs__stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $lafka_rev_avg_stars ) ); ?></span>
					<strong class="lafka-revs__avg"><?php echo esc_html( number_format_i18n( $lafka_rev_avg, 1 ) ); ?></strong>
					<span class="lafka-revs__count">
						<?php
						/* translators: %s — review count formatted */
						printf( esc_html__( '· %s reviews', 'lafka' ), esc_html( number_format_i18n( $lafka_rev_count ) ) );
						?>
					</span>
				</p>
			<?php endif; ?>
			<h2 id="lafka-revs-heading" class="lafka-section-headline"><?php echo esc_html( $lafka_rev_headline ); ?></h2>
		</header>

		<ul class="lafka-revs__grid" role="list">
			<?php
			foreach ( $lafka_reviews as $lafka_rev ) :
				// Filter-supplied entries may omit 'stars'; default to a full row.
				$lafka_rev_item_stars = isset( $lafka_rev['stars'] )
					? (int) max( 1, min( 5, absint( $lafka_rev['stars'] ) ) )
					: 5;
				?>
				<li class="lafka-revs__item">
					<span class="lafka-revs__item-stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $lafka_rev_item_stars ) ); ?></span>
					<blockquote class="lafka-revs__quote">
						<?php echo esc_html( $lafka_rev['quote'] ); ?>
					</blockquote>
					<p class="lafka-revs__attribution">
						<span class="lafka-revs__author"><?php echo esc_html( $lafka_rev['author'] ); ?></span>
						<span class="lafka-revs__date"> · <?php echo esc_html( $lafka_rev['date'] ); ?></span>
					</p>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
