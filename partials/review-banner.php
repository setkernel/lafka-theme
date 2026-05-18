<?php
/**
 * Post-purchase review banner partial (Pillar 3D, theme v6.11.0).
 *
 * Renders the dismissible "Loved your order?" banner emitted into the
 * `wp_footer` action whenever the plugin has set the `lafka_review_prompt_show`
 * cookie. The cookie is set server-side by lafka-plugin's
 * `lafka_review_banner_set_cookie` action when the current logged-in user has
 * a completed WC order within the configured window (default 7 days).
 *
 * The partial only emits markup — the JS at /js/lafka-review-banner.js wires
 * up the dismiss + CTA click handlers and pushes dataLayer events. CSS at
 * /styles/lafka-review-banner.css positions + styles it.
 *
 * Renders nothing when:
 *   - The Customizer banner toggle is OFF.
 *   - The cookie is not set (no current eligible order, or already dismissed).
 *   - The customer is mid-conversion (cart / checkout / order-received /
 *     my-account).
 *
 * Markup contract — keep stable so the CSS + JS selectors don't drift:
 *
 *   <aside class="lafka-review-banner" data-visible="true|undefined"
 *          data-dismissing="true|undefined"
 *          role="complementary" aria-live="polite"
 *          aria-labelledby="lafka-review-banner-copy">
 *     <div class="lafka-review-banner__inner">
 *       <span class="lafka-review-banner__star" aria-hidden="true">★</span>
 *       <div class="lafka-review-banner__body">
 *         <p class="lafka-review-banner__copy" id="lafka-review-banner-copy">…</p>
 *         <a class="lafka-review-banner__cta" href="…">Leave a review →</a>
 *       </div>
 *       <button class="lafka-review-banner__close" aria-label="Dismiss">×</button>
 *     </div>
 *   </aside>
 *
 * Conversion-page suppression: the partial bails when the request is on
 * /cart/, /checkout/, /order-received/, or /my-account/ because we never want
 * to distract a customer who's actively converting. The plugin still sets the
 * cookie on those pages so the banner reappears on the NEXT page in flow.
 *
 * @package Lafka_Theme
 * @since   6.11.0
 */

defined( 'ABSPATH' ) || exit;

// Customizer toggle gates the whole thing.
if ( ! function_exists( 'get_theme_mod' ) ) {
	return;
}
$lafka_review_banner_enabled = '1' === (string) get_theme_mod( 'lafka_review_banner_enabled', '0' );
if ( ! $lafka_review_banner_enabled ) {
	return;
}

// Cookie gates whether the banner actually renders — the plugin owns that
// decision so this partial trusts it.
if ( ! isset( $_COOKIE['lafka_review_prompt_show'] ) || '1' !== (string) $_COOKIE['lafka_review_prompt_show'] ) {
	return;
}

// Never distract a customer who's mid-funnel.
$lafka_review_banner_on_conversion_page = (
	( function_exists( 'is_cart' ) && is_cart() )
	|| ( function_exists( 'is_checkout' ) && is_checkout() )
	|| ( function_exists( 'is_account_page' ) && is_account_page() )
	|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) )
);
if ( $lafka_review_banner_on_conversion_page ) {
	return;
}

// Read copy from the plugin's helper functions (Customizer-backed). Falls back
// to inline defaults if the plugin isn't active.
$lafka_review_banner_copy_text = function_exists( 'lafka_review_banner_copy' )
	? (string) lafka_review_banner_copy()
	: 'Loved your order? Tap to rate us';
$lafka_review_banner_cta_text = function_exists( 'lafka_review_banner_cta_label' )
	? (string) lafka_review_banner_cta_label()
	: 'Leave a review →';
$lafka_review_banner_target_url = function_exists( 'lafka_review_target_url' )
	? (string) lafka_review_target_url()
	: '';
if ( '' === $lafka_review_banner_target_url ) {
	$lafka_review_banner_target_url = function_exists( 'home_url' ) ? home_url( '/' ) : '/';
}
?>
<aside class="lafka-review-banner"
	role="complementary"
	aria-live="polite"
	aria-labelledby="lafka-review-banner-copy"
	data-cta-url="<?php echo esc_attr( $lafka_review_banner_target_url ); ?>">
	<div class="lafka-review-banner__inner">
		<span class="lafka-review-banner__star" aria-hidden="true">&#9733;</span>
		<div class="lafka-review-banner__body">
			<p class="lafka-review-banner__copy" id="lafka-review-banner-copy">
				<?php echo esc_html( $lafka_review_banner_copy_text ); ?>
			</p>
			<a class="lafka-review-banner__cta"
				href="<?php echo esc_url( $lafka_review_banner_target_url ); ?>"
				rel="noopener noreferrer"
				target="_blank">
				<?php echo esc_html( $lafka_review_banner_cta_text ); ?>
			</a>
		</div>
		<button type="button" class="lafka-review-banner__close"
			aria-label="<?php echo esc_attr__( 'Dismiss review prompt', 'lafka' ); ?>">
			&times;
		</button>
	</div>
</aside>
