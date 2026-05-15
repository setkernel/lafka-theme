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

$lafka_rev_avg   = (float) get_theme_mod( 'lafka_home_reviews_avg', 4.8 );
$lafka_rev_count = (int) get_theme_mod( 'lafka_home_reviews_count', 500 );
$lafka_rev_headline = (string) get_theme_mod( 'lafka_home_reviews_headline', __( 'People keep coming back.', 'lafka' ) );

/* v5.68.0: defaults are restaurant-agnostic per OSS-bundle policy.
 * Operators replace via Customizer per-review fields, or via the
 * `lafka_home_reviews` filter (intended path for child themes). */
$lafka_reviews = (array) apply_filters(
	'lafka_home_reviews',
	array(
		array(
			'quote'  => (string) get_theme_mod( 'lafka_home_review_1_quote', __( 'Hot, fresh, and delivered fast. The dough is perfect and the toppings are always fresh.', 'lafka' ) ),
			'author' => (string) get_theme_mod( 'lafka_home_review_1_author', __( 'Regular customer', 'lafka' ) ),
			'date'   => (string) get_theme_mod( 'lafka_home_review_1_date', __( 'Recently', 'lafka' ) ),
		),
		array(
			'quote'  => (string) get_theme_mod( 'lafka_home_review_2_quote', __( 'Order showed up fast and hot. The poutine is the real deal.', 'lafka' ) ),
			'author' => (string) get_theme_mod( 'lafka_home_review_2_author', __( 'Local diner', 'lafka' ) ),
			'date'   => (string) get_theme_mod( 'lafka_home_review_2_date', __( 'Recently', 'lafka' ) ),
		),
		array(
			'quote'  => (string) get_theme_mod( 'lafka_home_review_3_quote', __( 'Family favourite for years. Friendly staff, great prices, never a bad meal.', 'lafka' ) ),
			'author' => (string) get_theme_mod( 'lafka_home_review_3_author', __( 'Long-time customer', 'lafka' ) ),
			'date'   => (string) get_theme_mod( 'lafka_home_review_3_date', __( 'Recently', 'lafka' ) ),
		),
	)
);

if ( empty( $lafka_reviews ) ) {
	return;
}
?>
<section class="lafka-revs" aria-labelledby="lafka-revs-heading">
	<div class="lafka-container">

		<header class="lafka-section-head">
			<p class="lafka-revs__rating">
				<span class="lafka-revs__stars" aria-hidden="true">★★★★★</span>
				<strong class="lafka-revs__avg"><?php echo esc_html( number_format_i18n( $lafka_rev_avg, 1 ) ); ?></strong>
				<span class="lafka-revs__count">
					<?php
					/* translators: %s — review count formatted */
					printf( esc_html__( '· %s reviews', 'lafka' ), esc_html( number_format_i18n( $lafka_rev_count ) ) );
					?>
				</span>
			</p>
			<h2 id="lafka-revs-heading" class="lafka-section-headline"><?php echo esc_html( $lafka_rev_headline ); ?></h2>
		</header>

		<ul class="lafka-revs__grid" role="list">
			<?php foreach ( $lafka_reviews as $lafka_rev ) : ?>
				<li class="lafka-revs__item">
					<span class="lafka-revs__item-stars" aria-hidden="true">★★★★★</span>
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
