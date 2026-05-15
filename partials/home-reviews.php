<?php
/**
 * Partial: Home reviews wall (v5.49.0)
 *
 * 3 static review cards with Fraunces-italic pull-quotes + reviewer
 * name + star rating + source. A Customizer repeater feeds them; the
 * partial returns early if the operator hasn't entered any reviews
 * (no "empty state" — better to hide than show fake testimonials).
 *
 * Customizer reads:
 *  - lafka_home_reviews_visible (boolean — default true)
 *  - lafka_home_reviews_eyebrow
 *  - lafka_home_reviews_headline
 *  - lafka_home_reviews_rating       (e.g. "4.9")
 *  - lafka_home_reviews_count        (e.g. "230")
 *  - lafka_home_reviews_source       (e.g. "Google")
 *  - lafka_home_reviews_1_quote/name/source/stars
 *  - lafka_home_reviews_2_*  (same shape)
 *  - lafka_home_reviews_3_*  (same shape)
 *
 * @package Lafka
 * @since   5.49.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! (bool) get_theme_mod( 'lafka_home_reviews_visible', true ) ) {
	return;
}

$lafka_reviews_eyebrow  = (string) get_theme_mod( 'lafka_home_reviews_eyebrow', __( 'Loved locally', 'lafka' ) );
$lafka_reviews_headline = (string) get_theme_mod( 'lafka_home_reviews_headline', __( 'What our neighbors say', 'lafka' ) );
$lafka_reviews_rating   = (string) get_theme_mod( 'lafka_home_reviews_rating', '' );
$lafka_reviews_count    = (string) get_theme_mod( 'lafka_home_reviews_count', '' );
$lafka_reviews_source   = (string) get_theme_mod( 'lafka_home_reviews_source', 'Google' );

// Compose the review cards. Each defaults to empty so unset reviews
// drop out cleanly.
$lafka_review_items = array();
for ( $lafka_review_i = 1; $lafka_review_i <= 3; $lafka_review_i++ ) {
	$lafka_quote  = trim( (string) get_theme_mod( "lafka_home_reviews_{$lafka_review_i}_quote", '' ) );
	$lafka_name   = trim( (string) get_theme_mod( "lafka_home_reviews_{$lafka_review_i}_name", '' ) );
	$lafka_source = trim( (string) get_theme_mod( "lafka_home_reviews_{$lafka_review_i}_source", $lafka_reviews_source ) );
	$lafka_stars  = (int) get_theme_mod( "lafka_home_reviews_{$lafka_review_i}_stars", 5 );

	if ( '' === $lafka_quote ) {
		continue;
	}
	$lafka_review_items[] = array(
		'quote'  => $lafka_quote,
		'name'   => $lafka_name,
		'source' => $lafka_source,
		'stars'  => max( 1, min( 5, $lafka_stars ) ),
	);
}

if ( empty( $lafka_review_items ) ) {
	return;
}
?>
<section class="lafka-home-reviews" aria-labelledby="lafka-home-reviews-heading">
	<div class="lafka-home-reviews__inner">

		<header class="lafka-home-reviews__head">
			<?php if ( '' !== $lafka_reviews_eyebrow ) : ?>
				<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_reviews_eyebrow ); ?></p>
			<?php endif; ?>
			<h2 id="lafka-home-reviews-heading" class="lafka-home-reviews__headline"><?php echo esc_html( $lafka_reviews_headline ); ?></h2>

			<?php if ( '' !== $lafka_reviews_rating ) : ?>
				<p class="lafka-home-reviews__aggregate">
					<span class="lafka-home-reviews__aggregate-stars" aria-hidden="true">★ ★ ★ ★ ★</span>
					<strong><?php echo esc_html( $lafka_reviews_rating ); ?></strong>
					<?php if ( '' !== $lafka_reviews_count ) : ?>
						<span class="lafka-home-reviews__aggregate-count">
							<?php printf( esc_html__( 'from %1$s reviews on %2$s', 'lafka' ), esc_html( $lafka_reviews_count ), esc_html( $lafka_reviews_source ) ); ?>
						</span>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</header>

		<ul class="lafka-home-reviews__grid" role="list">
			<?php foreach ( $lafka_review_items as $lafka_review ) : ?>
				<li class="lafka-home-reviews__card">
					<span class="lafka-home-reviews__stars" aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $lafka_review['stars'], 'lafka' ), $lafka_review['stars'] ) ); ?>">
						<?php echo esc_html( str_repeat( '★ ', $lafka_review['stars'] ) ); ?>
					</span>
					<blockquote class="lafka-home-reviews__quote">
						<?php echo esc_html( $lafka_review['quote'] ); ?>
					</blockquote>
					<footer class="lafka-home-reviews__attribution">
						<?php if ( '' !== $lafka_review['name'] ) : ?>
							<strong><?php echo esc_html( $lafka_review['name'] ); ?></strong>
						<?php endif; ?>
						<?php if ( '' !== $lafka_review['source'] ) : ?>
							<span class="lafka-home-reviews__source"><?php echo esc_html( $lafka_review['source'] ); ?></span>
						<?php endif; ?>
					</footer>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
