<?php
/**
 * Partial: site-wide announce bar (v5.54.0)
 *
 * Dark full-bleed strip at the very top of every page. Renders three
 * inline items separated by middots:
 *   1. Live open/closed status (dot + label) from lafka_open_status()
 *   2. Delivery info ("🚚 Delivery in {city} · Free over ${threshold}")
 *   3. Click-to-call phone link (yellow)
 *
 * Items 2 + 3 are hidden below 560px (CSS). On larger viewports all three
 * sit inline. The shared live status script (js/lafka-open-status.js) keeps
 * the label current on a cached page.
 *
 * Operator data flows:
 *   - Hours       → lafka_open_status() → the plugin's Lafka_Order_Hours::status()
 *   - City        → restaurant info → 'city'
 *   - Phone       → restaurant info → 'phone_display' / 'phone_e164'
 *   - Free over X → SSOT lafka_free_delivery_threshold() (the plugin's one
 *                   resolver). Promise is suppressed when the resolved value <= 0,
 *                   so it can never diverge from what the shipping rule enforces.
 *   - Visible     → Customizer key 'lafka_announce_bar_enabled' (default true)
 *
 * Auto-hides entirely when no hours AND no phone configured.
 *
 * @package Lafka
 * @since   5.54.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! (bool) get_theme_mod( 'lafka_announce_bar_enabled', true ) ) {
	return;
}

// GX4: the counter header carries status + phone itself (and the approved
// design has no strip above it), so the classic announce bar stays off under
// a counter header unless a site opts back in.
if ( function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'header', 'counter' ) && ! apply_filters( 'lafka_counter_show_announce_bar', false ) ) {
	return;
}

$lafka_ann_status = function_exists( 'lafka_open_status' ) ? lafka_open_status() : null;

$lafka_ann_info  = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_ann_city  = isset( $lafka_ann_info['city'] ) ? (string) $lafka_ann_info['city'] : '';
$lafka_ann_phone = isset( $lafka_ann_info['phone_display'] ) ? (string) $lafka_ann_info['phone_display'] : '';
$lafka_ann_tel   = (string) ( $lafka_ann_info['phone_tel'] ?? '' );

// Bail when there's truly nothing to say.
if ( ! $lafka_ann_status && '' === $lafka_ann_phone && '' === $lafka_ann_city ) {
	return;
}

// SSOT: the threshold the plugin's free-delivery rule enforces (0 = off).
$lafka_ann_threshold     = lafka_free_delivery_threshold();
$lafka_ann_show_delivery = (bool) get_theme_mod( 'lafka_announce_bar_show_delivery', true );

$lafka_ann_classes = array( 'lafka-announce-bar' );
if ( $lafka_ann_status && ! empty( $lafka_ann_status['is_open'] ) ) {
	$lafka_ann_classes[] = 'lafka-announce-bar--open';
} else {
	$lafka_ann_classes[] = 'lafka-announce-bar--closed';
}

$lafka_ann_threshold_label = lafka_free_delivery_amount_text( $lafka_ann_threshold );
?>
<aside
	class="<?php echo esc_attr( implode( ' ', $lafka_ann_classes ) ); ?>"
	role="region"
	aria-label="<?php esc_attr_e( 'Service status and contact', 'lafka' ); ?>"
	data-lafka-announce-bar
>
	<div class="lafka-container lafka-announce-bar__inner">

		<?php if ( $lafka_ann_status ) : ?>
			<span class="lafka-announce-bar__status" data-lafka-status>
				<span class="lafka-announce-bar__dot" aria-hidden="true"></span>
				<span class="lafka-announce-bar__status-label" data-lafka-status-label><?php echo esc_html( $lafka_ann_status['label'] ); ?></span>
			</span>
		<?php endif; ?>

		<?php if ( $lafka_ann_show_delivery && '' !== $lafka_ann_city ) : ?>
			<span class="lafka-announce-bar__delivery">
				<span class="lafka-announce-bar__icon" aria-hidden="true">🚚</span>
				<?php
				if ( $lafka_ann_threshold > 0 ) {
					printf(
						/* translators: 1: city name; 2: formatted threshold (e.g. "$30") */
						esc_html__( 'Delivery in %1$s · Free over %2$s', 'lafka' ),
						esc_html( $lafka_ann_city ),
						esc_html( $lafka_ann_threshold_label )
					);
				} else {
					printf(
						/* translators: %s: city name */
						esc_html__( 'Delivery in %s', 'lafka' ),
						esc_html( $lafka_ann_city )
					);
				}
				?>
			</span>
		<?php endif; ?>

		<?php if ( '' !== $lafka_ann_phone ) : ?>
			<a
				class="lafka-announce-bar__phone"
				href="<?php echo esc_attr( 'tel:' . $lafka_ann_tel ); ?>"
				rel="nofollow"
			>
				<?php echo esc_html( $lafka_ann_phone ); ?>
			</a>
		<?php endif; ?>

	</div>
</aside>
