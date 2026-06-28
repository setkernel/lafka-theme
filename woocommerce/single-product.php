<?php
/**
 * Single product template — overrides woocommerce/templates/single-product.php (WC 10.7).
 *
 * When the redesign feature flag is OFF, falls through to the parent theme's
 * legacy template via locate_template().
 *
 * @package Lafka\WooCommerce
 * @since   5.16.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_pdp_redesign_enabled' ) || ! lafka_pdp_redesign_enabled() ) {
    // Redesign OFF — render WooCommerce's default single-product flow inline.
    // (Cannot delegate to the parent theme: lafka-theme has no
    // woocommerce/single-product.php override, and locate_template() with
    // $load=false simply returns the path with no side effect, which left
    // product pages blank when the flag was disabled.)
    get_header( 'shop' );
    do_action( 'woocommerce_before_main_content' );

    while ( have_posts() ) {
        the_post();
        wc_get_template_part( 'content', 'single-product' );
    }

    do_action( 'woocommerce_after_main_content' );
    do_action( 'woocommerce_sidebar' );
    get_footer( 'shop' );
    return;
}

/*
 * Re-home the summary integrations that rode woocommerce_single_product_summary.
 *
 * The redesigned PDP composes its own summary (partials/pdp-summary.php) and
 * never fires that WC hook: firing it would re-introduce WC's default
 * title/price/excerpt/add-to-cart buy box at their stock priorities — double-
 * rendering the buy box this redesign rebuilds — and re-emit WC's native
 * Product JSON-LD, which is already present in wp_head. pdp-summary.php instead
 * fires a dedicated lafka_pdp_summary action below the title/price; wire the
 * callbacks that were silently orphaned by the redesign onto it here, once per
 * request, preserving their original relative priority order. Each is guarded so
 * the theme degrades cleanly when the plugin is inactive (OSS bundle).
 */
if ( ! defined( 'LAFKA_PDP_SUMMARY_WIRED' ) ) {
    define( 'LAFKA_PDP_SUMMARY_WIRED', true );

    // Social-proof block (theme): rating + order-count under the title.
    if ( function_exists( 'lafka_social_proof_render_pdp' ) ) {
        add_action( 'lafka_pdp_summary', 'lafka_social_proof_render_pdp', 6 );
    }

    // Nutrition + weight (plugin): methods on the shared display singleton.
    if ( isset( $GLOBALS['Lafka_Nutrition_Display'] ) && is_object( $GLOBALS['Lafka_Nutrition_Display'] ) ) {
        add_action( 'lafka_pdp_summary', array( $GLOBALS['Lafka_Nutrition_Display'], 'display_weight' ), 7 );
        add_action( 'lafka_pdp_summary', array( $GLOBALS['Lafka_Nutrition_Display'], 'display_nutrition' ), 8 );
    }

    // Sale countdown (theme).
    if ( function_exists( 'lafka_product_sale_countdown' ) ) {
        add_action( 'lafka_pdp_summary', 'lafka_product_sale_countdown', 9 );
    }

    // Promo info tooltips (plugin): above-price / below-price / below-add-to-cart zones.
    if ( function_exists( 'lafka_output_info_tooltips' ) ) {
        add_action(
            'lafka_pdp_summary',
            function () {
                lafka_output_info_tooltips( 'above-price' );
            },
            9
        );
        add_action(
            'lafka_pdp_summary',
            function () {
                lafka_output_info_tooltips( 'below-price' );
            },
            11
        );
        add_action(
            'lafka_pdp_summary',
            function () {
                lafka_output_info_tooltips( 'below-add-to-cart' );
            },
            39
        );
    }

    // Custom product popup link (plugin).
    if ( function_exists( 'lafka_show_custom_product_popup_link' ) ) {
        add_action( 'lafka_pdp_summary', 'lafka_show_custom_product_popup_link', 12 );
    }
}

get_header( 'shop' );
?>
<div class="lafka-pdp">
    <div class="lafka-pdp__main">
        <?php
        while ( have_posts() ) :
			the_post();

			// GA4 view_item: the redesigned PDP never fires
			// woocommerce_before_single_product_summary, so the plugin's
			// priority-5 emit (lafka_dl_emit_view_item) would otherwise never
			// run on a product page. Call the emit directly rather than firing
			// the action — woocommerce_show_product_images is also attached to
			// that hook (priority 20) and would duplicate the gallery output.
			// The emit self-guards on is_product(), so this is a no-op off-PDP.
			if ( function_exists( 'lafka_dl_emit_view_item' ) ) {
				lafka_dl_emit_view_item();
			}

			// Fire woocommerce_before_single_product so WC core's
			// woocommerce_output_all_notices (priority 10) prints PDP notices —
			// add-to-cart validation errors ('Please choose product options',
			// sold-individually / out-of-stock limits), coupon/login-required
			// messages, and the 'redirect to product after add' success notice.
			// The redesign otherwise never fires this hook and silently swallows
			// every one. Kept inside .lafka-pdp__main (above the breadcrumb) so
			// notices render within the styled wrapper, not unstyled above the
			// layout. Side-effect-safe: WC core carries only
			// woocommerce_output_all_notices here, and the plugin's gallery emit
			// hooks the distinct woocommerce_before_single_product_summary.
			do_action( 'woocommerce_before_single_product' );
			?>

            <nav class="lafka-pdp__breadcrumb"><?php woocommerce_breadcrumb(); ?></nav>

            <div class="lafka-pdp__hero">
                <div class="lafka-pdp__gallery">
                    <?php woocommerce_show_product_images(); ?>
                </div>
                <?php require get_template_directory() . '/partials/pdp-summary.php'; ?>
            </div>

            <?php require get_template_directory() . '/partials/pdp-make-it-a-meal.php'; ?>
            <?php // v5.91.0: ingredients + reviews 2-card grid (handoff). Replaces the WC tabs. ?>
            <?php require get_template_directory() . '/partials/pdp-ingredients-reviews.php'; ?>

            <?php woocommerce_output_related_products(); ?>

        <?php endwhile; ?>
    </div>

    <?php /* Cart drawer now renders globally via wp_footer — see functions.php (v5.57.0). */ ?>
</div>
<?php
// Fire woocommerce_after_single_product so third-party integrations hooked here
// run on the redesigned PDP — the redesign otherwise never fires it.
do_action( 'woocommerce_after_single_product' );
get_footer( 'shop' );
