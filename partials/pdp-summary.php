<?php
/**
 * PDP summary right-column composition.
 *
 * Composes: best-seller eyebrow, title, short description, live price,
 * last-order card, variation+addon pickers, qty stepper + Add-to-Cart,
 * mobile-only sticky bottom CTA, trust signal.
 *
 * Form structure mirrors WooCommerce's standard variable.php template so
 * plugins that hook into the addon/variation pipeline (e.g. lafka-plugin's
 * own product-addons system, which `reposition_display_for_variable_product()`
 * relies on woocommerce_before_variations_form firing first) get the same
 * lifecycle they expect. All standard hooks fire in the same order.
 *
 * @package Lafka\Partials
 * @since   5.16.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! ( $product instanceof WC_Product ) ) {
    return;
}

$is_variable = $product->is_type( 'variable' );
$form_action = apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() );

// Store-closed gate. When the store is closed AND the operator has opted into
// lafka_order_hours_disable_add_to_cart, the add-to-cart form must be replaced
// by the closed-store card. The plugin enforces the block server-side
// (woocommerce_is_purchasable + woocommerce_add_to_cart_validation), but this
// redesigned PDP renders its own <form class="cart"> below and never fires
// woocommerce_single_product_summary, so the plugin's classic-template card
// swap can't reach it — we gate the form here and render the plugin's card
// inline instead (single source of truth for the closed-store markup).
$lafka_pdp_cart_disabled = class_exists( 'Lafka_Order_Hours' )
    && ! Lafka_Order_Hours::is_shop_open()
    && ! empty( Lafka_Order_Hours::$lafka_order_hours_options['lafka_order_hours_disable_add_to_cart'] );
?>
<div class="lafka-pdp-summary">

    <?php
    if ( function_exists( 'lafka_pdp_render_bestseller_eyebrow' ) ) {
        lafka_pdp_render_bestseller_eyebrow( $product->get_id() );
    }
    ?>

    <h1 class="lafka-pdp-summary__title"><?php echo esc_html( $product->get_name() ); ?></h1>

    <div class="lafka-pdp-summary__short">
        <?php echo wp_kses_post( $product->get_short_description() ); ?>
    </div>

    <div class="lafka-pdp-summary__price">
        <?php
        // Currency symbol/position MUST come from WC settings (wc_price()
              // honours woocommerce_currency_pos + currency code). Hardcoding
              // `$` leaks the operator's currency and breaks any non-USD shop.
              // JS replaces this textContent on size change via the formatter
              // localized in functions.php — same currency settings, same
              // output shape. 
		?>
        <span data-lafka-live-price><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
        <?php if ( $is_variable ) : ?>
            <small>
            <?php
            printf(
                esc_html__( 'starting at %s', 'lafka' ),
                wp_kses_post( wc_price( $product->get_variation_price( 'min', true ) ) )
            );
			?>
            </small>
        <?php endif; ?>
    </div>

    <?php
    /**
     * Redesigned-PDP summary integration point.
     *
     * The redesign composes its own summary and deliberately never fires
     * woocommerce_single_product_summary — doing so would re-introduce WC's
     * default title/price/excerpt/add-to-cart buy box at their stock priorities
     * (double-rendering the buy box this template rebuilds) and re-emit WC's
     * native Product JSON-LD, which is already produced in wp_head. The
     * genuinely-orphaned summary integrations (nutrition, weight, social proof,
     * sale countdown, promo tooltips, custom product popup link) are re-homed
     * onto this dedicated hook in woocommerce/single-product.php and render here,
     * just below the title/price.
     */
    do_action( 'lafka_pdp_summary', $product );
    ?>

    <?php require __DIR__ . '/pdp-last-order-card.php'; ?>

    <?php if ( $lafka_pdp_cart_disabled ) : ?>

        <?php
        // Store closed + add-to-cart disabled: render the plugin's closed-store
        // card in place of the buy box. The <form class="cart"> is intentionally
        // not emitted, so there is no Add-to-Cart to submit. The server also
        // rejects any replayed add via woocommerce_is_purchasable /
        // woocommerce_add_to_cart_validation (see Lafka_Order_Hours).
        Lafka_Order_Hours::echo_closed_store_message();
        ?>

    <?php elseif ( $is_variable ) : ?>
        <?php
        // Fire BEFORE the form opens — this triggers the lafka-plugin addon
        // system's reposition_display_for_variable_product(), which moves
        // the addon display() callback from woocommerce_before_add_to_cart_button
        // to woocommerce_single_variation. Without this, no addons render
        // for variable products.
        do_action( 'woocommerce_before_variations_form' );
        ?>
        <form class="cart variations_form"
              action="<?php echo esc_url( $form_action ); ?>"
              method="post"
              enctype="multipart/form-data"
              data-product_id="<?php echo absint( $product->get_id() ); ?>"
              data-product_variations="
              <?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_esc_json() is WC's attribute-context escape function; esc_attr fallback when not available.
				echo function_exists( 'wc_esc_json' ) ? wc_esc_json( wp_json_encode( $product->get_available_variations() ) ) : esc_attr( wp_json_encode( $product->get_available_variations() ) );
				?>
                ">

            <?php do_action( 'woocommerce_before_variations_table' ); ?>
            <?php require __DIR__ . '/pdp-pickers.php'; ?>
            <?php do_action( 'woocommerce_after_variations_table' ); ?>

            <div class="single_variation_wrap">
                <?php do_action( 'woocommerce_before_single_variation' ); ?>

                <div class="single_variation"></div>

                <div class="variations_button">
                    <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
                    <?php do_action( 'woocommerce_before_add_to_cart_quantity' ); ?>

                    <div class="lafka-pdp-summary__cart-row">
                        <div class="quantity lafka-pdp-summary__qty">
                            <button type="button" class="lafka-pdp-qty__btn" data-lafka-qty="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'lafka' ); ?>">−</button>
                            <input type="number" name="quantity" value="1" min="1" class="qty lafka-pdp-qty__input" aria-label="<?php esc_attr_e( 'Quantity', 'lafka' ); ?>">
                            <button type="button" class="lafka-pdp-qty__btn" data-lafka-qty="+1" aria-label="<?php esc_attr_e( 'Increase quantity', 'lafka' ); ?>">+</button>
                        </div>
                        <button type="submit" class="lafka-pdp-summary__cta" data-lafka-add-to-cart disabled data-lafka-state="incomplete">
                            <span data-lafka-cta-label><?php esc_html_e( 'Pick a size to continue', 'lafka' ); ?></span>
                        </button>
                    </div>

                    <div class="lafka-pdp-mobile-cta">
                        <div class="lafka-pdp-mobile-cta__qty">
                            <button type="button" data-lafka-qty="-1" aria-label="<?php esc_attr_e( 'Decrease', 'lafka' ); ?>">−</button>
                            <span data-lafka-qty-display>1</span>
                            <button type="button" data-lafka-qty="+1" aria-label="<?php esc_attr_e( 'Increase', 'lafka' ); ?>">+</button>
                        </div>
                        <button type="submit" class="lafka-pdp-mobile-cta__btn" data-lafka-add-to-cart disabled data-lafka-state="incomplete">
                            <span data-lafka-cta-label><?php esc_html_e( 'Pick a size', 'lafka' ); ?></span>
                        </button>
                    </div>

                    <?php do_action( 'woocommerce_after_add_to_cart_quantity' ); ?>
                    <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
                </div>

                <?php
                // WC core hooks two callbacks to woocommerce_single_variation:
                //   - priority 10: woocommerce_single_variation() — renders an
                //     empty wrapper div we already have above.
                //   - priority 20: woocommerce_single_variation_add_to_cart_button() —
                //     renders ANOTHER quantity input, ANOTHER submit button, and
                //     duplicate hidden inputs (add-to-cart, product_id, variation_id).
                // The duplicate submit button isn't bound to our picker-state JS,
                // so clicking it submits without our validation and WC errors out
                // with "Please choose product options for X".
                //
                // We need woocommerce_single_variation to fire so the lafka-plugin
                // addon system's repositioned display() callback runs (it's at
                // priority 15, between WC's two defaults).
                //
                // CRITICAL: Restore the callbacks IMMEDIATELY after firing.
                // Previously these remove_actions persisted for the rest of the
                // request, which broke quick-view AJAX, combo-product partials,
                // and any later product render in the same request that
                // expected WC's stock single_variation behavior.
                $lafka_wc_sv_priority_10_was_hooked = has_action( 'woocommerce_single_variation', 'woocommerce_single_variation' );
                $lafka_wc_sv_priority_20_was_hooked = has_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button' );

                if ( false !== $lafka_wc_sv_priority_10_was_hooked ) {
                    remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', $lafka_wc_sv_priority_10_was_hooked );
                }
                if ( false !== $lafka_wc_sv_priority_20_was_hooked ) {
                    remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', $lafka_wc_sv_priority_20_was_hooked );
                }

                do_action( 'woocommerce_single_variation' );

                if ( false !== $lafka_wc_sv_priority_10_was_hooked ) {
                    add_action( 'woocommerce_single_variation', 'woocommerce_single_variation', $lafka_wc_sv_priority_10_was_hooked );
                }
                if ( false !== $lafka_wc_sv_priority_20_was_hooked ) {
                    add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', $lafka_wc_sv_priority_20_was_hooked );
                }

                do_action( 'woocommerce_after_single_variation' );
                ?>
            </div>

            <input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>">
            <input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>">
            <input type="hidden" name="variation_id" class="variation_id" value="0">
        </form>
        <?php do_action( 'woocommerce_after_variations_form' ); ?>

    <?php else : /* simple / combo / etc. */ ?>

        <form class="cart"
              action="<?php echo esc_url( $form_action ); ?>"
              method="post"
              enctype="multipart/form-data">

            <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
            <?php do_action( 'woocommerce_before_add_to_cart_quantity' ); ?>

            <div class="lafka-pdp-summary__cart-row">
                <div class="quantity lafka-pdp-summary__qty">
                    <button type="button" class="lafka-pdp-qty__btn" data-lafka-qty="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'lafka' ); ?>">−</button>
                    <input type="number" name="quantity" value="1" min="1" class="qty lafka-pdp-qty__input" aria-label="<?php esc_attr_e( 'Quantity', 'lafka' ); ?>">
                    <button type="button" class="lafka-pdp-qty__btn" data-lafka-qty="+1" aria-label="<?php esc_attr_e( 'Increase quantity', 'lafka' ); ?>">+</button>
                </div>
                <button type="submit" class="lafka-pdp-summary__cta" data-lafka-add-to-cart>
                    <span data-lafka-cta-label><?php esc_html_e( 'Add to Cart', 'lafka' ); ?></span>
                </button>
            </div>

            <div class="lafka-pdp-mobile-cta">
                <div class="lafka-pdp-mobile-cta__qty">
                    <button type="button" data-lafka-qty="-1" aria-label="<?php esc_attr_e( 'Decrease', 'lafka' ); ?>">−</button>
                    <span data-lafka-qty-display>1</span>
                    <button type="button" data-lafka-qty="+1" aria-label="<?php esc_attr_e( 'Increase', 'lafka' ); ?>">+</button>
                </div>
                <button type="submit" class="lafka-pdp-mobile-cta__btn" data-lafka-add-to-cart>
                    <span data-lafka-cta-label><?php esc_html_e( 'Add to Cart', 'lafka' ); ?></span>
                </button>
            </div>

            <?php do_action( 'woocommerce_after_add_to_cart_quantity' ); ?>
            <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

            <input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>">
        </form>

    <?php endif; ?>

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
    $lafka_pdp_info       = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
    $lafka_pdp_pickup_addr = isset( $lafka_pdp_info['address_short'] ) ? (string) $lafka_pdp_info['address_short'] : '';
    $lafka_pdp_eta        = function_exists( 'lafka_service_eta_get_data' ) ? lafka_service_eta_get_data() : null;
    $lafka_pdp_pickup_eta = $lafka_pdp_eta && ! empty( $lafka_pdp_eta['pickup'] ) ? (string) $lafka_pdp_eta['pickup'] : '~25 min';
    // SSOT: read the same threshold the plugin's free-delivery rule enforces;
    // fall back to the single shared theme_mod (0 = off) when the plugin isn't
    // loaded. The free-delivery assurance is suppressed entirely when <= 0.
    $lafka_pdp_threshold  = function_exists( 'lafka_get_free_delivery_threshold' )
        ? (float) lafka_get_free_delivery_threshold()
        : (float) get_theme_mod( 'lafka_announce_bar_delivery_threshold', 0 );
    ?>
    <ul class="lafka-pdp-summary__assurances" role="list">
        <li>
            <span class="lafka-pdp-summary__assurance-icon" aria-hidden="true">⏱</span>
            <span>
            <?php
                /* translators: %s — pickup ETA, e.g. "~25 min" */
                printf( esc_html__( 'Ready in %s', 'lafka' ), esc_html( $lafka_pdp_pickup_eta ) );
            ?>
            </span>
        </li>
        <?php if ( $lafka_pdp_threshold > 0 ) : ?>
        <li>
            <span class="lafka-pdp-summary__assurance-icon" aria-hidden="true">🚚</span>
            <span>
            <?php
                /* translators: %s — formatted delivery threshold, e.g. "$30" */
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
</div>
