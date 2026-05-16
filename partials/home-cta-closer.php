<?php
/**
 * Partial: Home final CTA — "Hungry yet?" (v5.60.0 handoff rebuild).
 *
 * Per handoff /design_handoff_peppery_ordering/README.md "Home page > 7. Final CTA":
 *   - dark rounded-xl, centered
 *   - padding space-12 mobile / space-20 desktop
 *   - soft red radial glow ::after from bottom-right corner
 *   - Fraunces 800 h2 with italic brand-500 "Let's fix that."
 *   - Lead paragraph
 *   - Two CTAs: red primary + ghost-translucent phone button
 *
 * @package Lafka
 * @since   5.60.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_closer_info  = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_closer_phone = isset( $lafka_closer_info['phone_display'] ) ? (string) $lafka_closer_info['phone_display'] : '';
$lafka_closer_tel   = isset( $lafka_closer_info['phone_e164'] ) ? (string) $lafka_closer_info['phone_e164'] : $lafka_closer_phone;

$lafka_closer_headline_default = sprintf(
	/* translators: HTML allowed — second clause wrapped in an <em> for the red italic accent. */
	__( 'Hungry? %s', 'lafka' ),
	'<em class="lafka-closer__accent">' . esc_html__( "Let's fix that.", 'lafka' ) . '</em>'
);
$lafka_closer_headline = (string) get_theme_mod( 'lafka_home_closer_headline', $lafka_closer_headline_default );
$lafka_closer_lead     = (string) get_theme_mod(
	'lafka_home_closer_lead',
	__( 'Pickup or delivery. Ready in about 25 minutes.', 'lafka' )
);
$lafka_closer_cta_label = (string) get_theme_mod( 'lafka_home_closer_cta_label', __( 'Start your order', 'lafka' ) );
$lafka_closer_cta_url   = (string) get_theme_mod( 'lafka_home_closer_cta_url', home_url( '/menu/' ) );
?>
<section class="lafka-closer" aria-labelledby="lafka-closer-heading">
	<div class="lafka-container">
		<div class="lafka-closer__card">

			<h2 id="lafka-closer-heading" class="lafka-closer__headline">
				<?php
				echo wp_kses(
					$lafka_closer_headline,
					array(
						'em'     => array( 'class' => array() ),
						'span'   => array( 'class' => array() ),
						'strong' => array( 'class' => array() ),
						'br'     => array(),
					)
				);
				?>
			</h2>

			<?php if ( '' !== $lafka_closer_lead ) : ?>
				<p class="lafka-closer__lead"><?php echo esc_html( $lafka_closer_lead ); ?></p>
			<?php endif; ?>

			<div class="lafka-closer__actions">
				<a class="lafka-closer__cta lafka-closer__cta--primary" href="<?php echo esc_url( $lafka_closer_cta_url ); ?>">
					<?php echo esc_html( $lafka_closer_cta_label ); ?>
					<span class="lafka-closer__arrow" aria-hidden="true">→</span>
				</a>
				<?php if ( '' !== $lafka_closer_phone ) : ?>
					<a class="lafka-closer__cta lafka-closer__cta--ghost" href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_closer_tel ) ); ?>">
						<span aria-hidden="true">📞</span>
						<?php echo esc_html( $lafka_closer_phone ); ?>
					</a>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
