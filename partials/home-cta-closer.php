<?php
/**
 * Partial: Home CTA closer (v5.49.0)
 *
 * Full-bleed brand-yellow band that catches scrollers who didn't tap a
 * CTA earlier. Three elements: Fraunces "Hungry yet?" headline, a giant
 * pill CTA, and the operator's phone link. Visually unmissable.
 *
 * Customizer reads:
 *  - lafka_home_closer_visible  (boolean, default true)
 *  - lafka_home_closer_eyebrow
 *  - lafka_home_closer_headline
 *  - lafka_home_closer_subhead
 *  - lafka_home_closer_cta_label / url
 *
 * Phone source: lafka_contact_phone (Customizer) → fallback to
 * legacy top_bar_message_phone theme option.
 *
 * @package Lafka
 * @since   5.49.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! (bool) get_theme_mod( 'lafka_home_closer_visible', true ) ) {
	return;
}

$lafka_closer_eyebrow  = (string) get_theme_mod( 'lafka_home_closer_eyebrow', __( "Don't wait", 'lafka' ) );
$lafka_closer_headline = (string) get_theme_mod( 'lafka_home_closer_headline', __( 'Hungry yet?', 'lafka' ) );
$lafka_closer_subhead  = (string) get_theme_mod( 'lafka_home_closer_subhead', __( 'Order online for pickup or delivery — ready when you are.', 'lafka' ) );

$lafka_closer_cta_label = (string) get_theme_mod( 'lafka_home_closer_cta_label', __( 'Order Now', 'lafka' ) );
$lafka_closer_cta_url   = (string) get_theme_mod( 'lafka_home_closer_cta_url', function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );

$lafka_closer_phone = (string) get_theme_mod( 'lafka_contact_phone', '' );
if ( '' === $lafka_closer_phone && function_exists( 'lafka_get_option' ) ) {
	$lafka_closer_phone = (string) lafka_get_option( 'top_bar_message_phone' );
}
$lafka_closer_phone_link = preg_replace( '/[^0-9+]/', '', $lafka_closer_phone );
?>
<section class="lafka-home-closer" aria-labelledby="lafka-home-closer-heading">
	<div class="lafka-container lafka-home-closer__inner">
		<header class="lafka-section-head">

			<?php if ( '' !== $lafka_closer_eyebrow ) : ?>
				<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_closer_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 id="lafka-home-closer-heading" class="lafka-section-headline lafka-section-headline--display"><?php echo esc_html( $lafka_closer_headline ); ?></h2>

			<?php if ( '' !== $lafka_closer_subhead ) : ?>
				<p class="lafka-section-subhead"><?php echo esc_html( $lafka_closer_subhead ); ?></p>
			<?php endif; ?>

		</header>

		<div class="lafka-home-closer__actions">
			<?php if ( '' !== $lafka_closer_cta_url ) : ?>
				<a class="lafka-btn lafka-btn--primary lafka-btn--lg" href="<?php echo esc_url( $lafka_closer_cta_url ); ?>">
					<?php echo esc_html( $lafka_closer_cta_label ); ?>
				</a>
			<?php endif; ?>

			<?php if ( '' !== $lafka_closer_phone ) : ?>
				<a class="lafka-btn lafka-btn--ghost-light lafka-btn--lg" href="tel:<?php echo esc_attr( $lafka_closer_phone_link ); ?>">
					<?php
					/* translators: %s — phone number. */
					printf( esc_html__( 'Or call %s', 'lafka' ), esc_html( $lafka_closer_phone ) );
					?>
				</a>
			<?php endif; ?>
		</div>

	</div>
</section>
