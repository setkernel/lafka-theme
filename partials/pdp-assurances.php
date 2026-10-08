<?php
/**
 * PDP trust line + assurances row — the foot of the redesigned summary.
 *
 * Rendered by lafka_pdp_summary_assurances() on woocommerce_single_product_summary.
 *
 * @package Lafka\Partials
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! ( $product instanceof WC_Product ) ) {
	return;
}
?>
	<div class="lafka-pdp-summary__trust">
		<?php
		if ( function_exists( 'lafka_pdp_render_prep_time' ) ) {
			lafka_pdp_render_prep_time( $product->get_id() );
		}
		?>
	</div>

	<?php
	// v5.87.0: assurances row beneath the buy box. Mirrors the handoff
	// `.assurances` block under the Add CTA — four trust signals.
	// Data sources are operator-configured (Customizer + restaurant info)
	// so the lafka-theme OSS bundle stays neutral.
	$lafka_pdp_info        = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
	$lafka_pdp_pickup_addr = isset( $lafka_pdp_info['address_short'] ) ? (string) $lafka_pdp_info['address_short'] : '';
	// SSOT: the plugin's one free-delivery accessor (0 = off, also without the
	// plugin). The free-delivery assurance is suppressed entirely when <= 0.
	$lafka_pdp_threshold = function_exists( 'lafka_get_free_delivery_threshold' )
		? (float) lafka_get_free_delivery_threshold()
		: 0.0;
	?>
	<?php // H-09: the ready time is the trust line above (one source); no second "Ready in". ?>
	<ul class="lafka-pdp-summary__assurances" role="list">
		<?php if ( $lafka_pdp_threshold > 0 ) : ?>
		<li>
			<span class="lafka-pdp-summary__assurance-icon" aria-hidden="true">🚚</span>
			<span>
			<?php
				/* translators: %s: free-delivery threshold, e.g. "$30". */
				printf( esc_html__( 'Free delivery over %s', 'lafka' ), esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $lafka_pdp_threshold ) ) : sprintf( '$%s', number_format_i18n( $lafka_pdp_threshold, 0 ) ) ) );
			?>
			</span>
		</li>
		<?php endif; ?>
		<?php if ( '' !== $lafka_pdp_pickup_addr ) : ?>
			<li>
				<span class="lafka-pdp-summary__assurance-icon" aria-hidden="true">📍</span>
				<span>
				<?php
					/* translators: %s — short pickup address, e.g. "512 Sackville Dr." */
					printf( esc_html__( 'Pickup at %s', 'lafka' ), esc_html( $lafka_pdp_pickup_addr ) );
				?>
				</span>
			</li>
		<?php endif; ?>
		<li>
			<span class="lafka-pdp-summary__assurance-icon" aria-hidden="true">✓</span>
			<span><?php esc_html_e( 'Made fresh to order', 'lafka' ); ?></span>
		</li>
	</ul>
