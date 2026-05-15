<?php
/**
 * Partial: Home "How it works" — 3-step explainer (v5.60.0 handoff rebuild).
 *
 * Per handoff /design_handoff_peppery_ordering/README.md "Home page > 4. How it works":
 *   - warm brand-50 band
 *   - 3-step grid
 *   - Each step: 48×48 red pill with Fraunces number + red shadow
 *   - h3 22px Fraunces 700, p body-sm secondary
 *
 * @package Lafka
 * @since   5.60.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_how_eyebrow  = (string) get_theme_mod( 'lafka_home_how_eyebrow', __( 'How it works', 'lafka' ) );
$lafka_how_headline = (string) get_theme_mod( 'lafka_home_how_headline', __( 'Hot food in three steps.', 'lafka' ) );

$lafka_how_steps = (array) apply_filters(
	'lafka_home_how_it_works_steps',
	array(
		array(
			'title' => (string) get_theme_mod( 'lafka_home_how_1_title', __( 'Order online', 'lafka' ) ),
			'body'  => (string) get_theme_mod( 'lafka_home_how_1_body', __( 'Browse the menu, customize your size and toppings, and lock in pickup or delivery.', 'lafka' ) ),
		),
		array(
			'title' => (string) get_theme_mod( 'lafka_home_how_2_title', __( 'We make it fresh', 'lafka' ) ),
			'body'  => (string) get_theme_mod( 'lafka_home_how_2_body', __( 'Hand-stretched dough, real cheese, never sitting under a heat lamp.', 'lafka' ) ),
		),
		array(
			'title' => (string) get_theme_mod( 'lafka_home_how_3_title', __( 'Pickup or delivered', 'lafka' ) ),
			'body'  => (string) get_theme_mod( 'lafka_home_how_3_body', __( 'Grab it on your way home, or let us bring it. Free delivery on orders over $30.', 'lafka' ) ),
		),
	)
);

if ( empty( $lafka_how_steps ) ) {
	return;
}
?>
<section class="lafka-how" aria-labelledby="lafka-how-heading">
	<div class="lafka-container">

		<header class="lafka-section-head">
			<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_how_eyebrow ); ?></p>
			<h2 id="lafka-how-heading" class="lafka-section-headline"><?php echo esc_html( $lafka_how_headline ); ?></h2>
		</header>

		<ol class="lafka-how__grid">
			<?php foreach ( $lafka_how_steps as $lafka_how_idx => $lafka_how_step ) : ?>
				<li class="lafka-how__step">
					<span class="lafka-how__num" aria-hidden="true"><?php echo esc_html( (string) ( $lafka_how_idx + 1 ) ); ?></span>
					<h3 class="lafka-how__title"><?php echo esc_html( $lafka_how_step['title'] ); ?></h3>
					<p class="lafka-how__body"><?php echo esc_html( $lafka_how_step['body'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>

	</div>
</section>
