<?php
/**
 * Last-order card partial — delegates to lafka_pdp_render_last_order_card()
 * (defined in lafka-plugin/incl/woocommerce/lafka-last-order-card.php, W4-T4).
 *
 * Lives in the parent theme so the visual surface is theme-side, but the
 * data + render logic stays in the plugin where it belongs.
 *
 * @package Lafka\Partials
 * @since   5.16.0
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'lafka_pdp_render_last_order_card' ) ) {
    lafka_pdp_render_last_order_card();
}
