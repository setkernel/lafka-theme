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
 * sit inline. The strip refreshes its status label every 60s via the
 * lafka-announce-bar.js companion — see /js/lafka-announce-bar.js.
 *
 * Operator data flows:
 *   - Hours       → lafka_open_status_get_hours_map() (plugin restaurant-info)
 *   - City        → restaurant info → 'city'
 *   - Phone       → restaurant info → 'phone_display' / 'phone_e164'
 *   - Free over X → SSOT lafka_get_free_delivery_threshold() (plugin) when
 *                   available, else theme_mod 'lafka_announce_bar_delivery_threshold'
 *                   (0 = off). Promise is suppressed when the resolved value <= 0,
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

$lafka_ann_status = function_exists( 'lafka_open_status' ) ? lafka_open_status() : null;

$lafka_ann_info  = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_ann_city  = isset( $lafka_ann_info['city'] ) ? (string) $lafka_ann_info['city'] : '';
$lafka_ann_phone = isset( $lafka_ann_info['phone_display'] ) ? (string) $lafka_ann_info['phone_display'] : '';
$lafka_ann_tel   = isset( $lafka_ann_info['phone_e164'] ) ? (string) $lafka_ann_info['phone_e164'] : $lafka_ann_phone;

// Bail when there's truly nothing to say.
if ( ! $lafka_ann_status && '' === $lafka_ann_phone && '' === $lafka_ann_city ) {
	return;
}

// SSOT: read the same threshold the plugin's free-delivery rule enforces; fall
// back to the single shared theme_mod (0 = off) when the plugin isn't loaded.
$lafka_ann_threshold = function_exists( 'lafka_get_free_delivery_threshold' )
	? (float) lafka_get_free_delivery_threshold()
	: (float) get_theme_mod( 'lafka_announce_bar_delivery_threshold', 0 );
$lafka_ann_show_delivery = (bool) get_theme_mod( 'lafka_announce_bar_show_delivery', true );

$lafka_ann_hours_json = function_exists( 'lafka_open_status_hours_for_client' ) ? lafka_open_status_hours_for_client() : array();

$lafka_ann_classes = array( 'lafka-announce-bar' );
if ( $lafka_ann_status && ! empty( $lafka_ann_status['is_open'] ) ) {
	$lafka_ann_classes[] = 'lafka-announce-bar--open';
} else {
	$lafka_ann_classes[] = 'lafka-announce-bar--closed';
}

$lafka_ann_threshold_label = function_exists( 'wc_price' )
	? wp_strip_all_tags( wc_price( $lafka_ann_threshold ) )
	: sprintf( '$%s', number_format_i18n( $lafka_ann_threshold, 0 ) );
?>
<aside
	class="<?php echo esc_attr( implode( ' ', $lafka_ann_classes ) ); ?>"
	role="region"
	aria-label="<?php esc_attr_e( 'Service status and contact', 'lafka' ); ?>"
	data-lafka-announce-bar
	data-lafka-hours="<?php echo esc_attr( wp_json_encode( $lafka_ann_hours_json ) ); ?>"
>
	<div class="lafka-container lafka-announce-bar__inner">

		<?php if ( $lafka_ann_status ) : ?>
			<span class="lafka-announce-bar__status" data-lafka-status>
				<span
					class="lafka-announce-bar__dot"
					aria-hidden="true"
					style="<?php echo esc_attr( '--lafka-dot: ' . $lafka_ann_status['dot_color'] ); ?>"
					data-lafka-status-dot
				></span>
				<span class="lafka-announce-bar__status-label" data-lafka-status-label><?php echo esc_html( $lafka_ann_status['label'] ); ?></span>
			</span>
		<?php endif; ?>

		<?php if ( $lafka_ann_show_delivery && '' !== $lafka_ann_city ) : ?>
			<span class="lafka-announce-bar__delivery">
				<span class="lafka-announce-bar__icon" aria-hidden="true">🚚</span>
				<?php
				if ( $lafka_ann_threshold > 0 ) {
					/* translators: 1: city name; 2: formatted threshold (e.g. "$30") */
					printf(
						esc_html__( 'Delivery in %1$s · Free over %2$s', 'lafka' ),
						esc_html( $lafka_ann_city ),
						esc_html( $lafka_ann_threshold_label )
					);
				} else {
					/* translators: %s: city name */
					printf(
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
				href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_ann_tel ) ); ?>"
				rel="nofollow"
			>
				<?php echo esc_html( $lafka_ann_phone ); ?>
			</a>
		<?php endif; ?>

	</div>
</aside>
