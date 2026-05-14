<?php
/**
 * Sticky PDP CTA — bottom-anchored "Add — $XX.XX" button on single-product pages.
 *
 * Addresses three friction points from the conversion audit:
 *   - "Pick a size to continue" wall (no default variation)
 *   - Add-to-Cart button buried below 34 topping checkboxes on mobile
 *   - No live total as user adjusts options
 *
 * The JS in js/lafka-pdp-cta.js handles all three coordinated jobs.
 * This partial just emits the markup + localised config; everything
 * else flows from there.
 *
 * @package Lafka
 * @since   5.27.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_pdp_cta_render' ) ) {
	/**
	 * Render the sticky PDP CTA in wp_footer (only on single-product pages).
	 */
	function lafka_pdp_cta_render() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		if ( ! (bool) apply_filters( 'lafka_pdp_sticky_cta_enabled', (bool) get_theme_mod( 'lafka_pdp_sticky_cta_enabled', true ) ) ) {
			return;
		}
		?>
		<aside
			class="lafka-pdp-cta"
			data-lafka-pdp-cta
			data-state="pick"
			aria-label="<?php esc_attr_e( 'Add to cart', 'lafka' ); ?>"
		>
			<button
				type="button"
				class="lafka-pdp-cta__btn"
				data-lafka-pdp-cta-btn
				disabled
			>
				<span class="lafka-pdp-cta__label" data-lafka-pdp-cta-label>
					<?php esc_html_e( 'Select options', 'lafka' ); ?>
				</span>
				<span class="lafka-pdp-cta__price" data-lafka-pdp-cta-price aria-live="polite" hidden></span>
			</button>
		</aside>
		<?php
	}
}

add_action( 'wp_footer', 'lafka_pdp_cta_render', 5 );
