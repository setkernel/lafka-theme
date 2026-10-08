<?php
/**
 * PDP summary right-column composition.
 *
 * The column is whatever is attached to WooCommerce's
 * woocommerce_single_product_summary action: the best-seller eyebrow, title,
 * short description, price, the plugin's nutrition / social-proof / promo
 * callbacks, last-order card, quantity + Add-to-Cart (partials/pdp-buybox.php),
 * the mobile sticky CTA, trust line and assurances. The Lafka parts and their
 * priorities live in incl/woocommerce/lafka-pdp-summary.php.
 *
 * @package Lafka\Partials
 * @since   5.16.0
 * @since   7.4.0  Rendered from WooCommerce's summary hook.
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! ( $product instanceof WC_Product ) ) {
	return;
}
?>
<div class="lafka-pdp-summary">
	<?php do_action( 'woocommerce_single_product_summary' ); ?>
</div>
