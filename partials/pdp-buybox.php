<?php
/**
 * PDP buy box — the add-to-cart area of the redesigned summary.
 *
 * Rendered by lafka_pdp_summary_buybox() from WooCommerce's
 * woocommerce_single_product_summary hook (priority 30, where core prints its
 * own add-to-cart). The three form variants (simple, variable, deal) are wrapped
 * in woocommerce_before_add_to_cart_form / woocommerce_after_add_to_cart_form,
 * exactly as core's per-type add-to-cart templates do, so extensions that hook
 * those actions run here too.
 *
 * Form structure mirrors WooCommerce's standard variable.php template so
 * plugins that hook into the addon/variation pipeline (e.g. lafka-plugin's
 * own product-addons system, which `reposition_display_for_variable_product()`
 * relies on woocommerce_before_variations_form firing first) get the same
 * lifecycle they expect. All standard hooks fire in the same order.
 *
 * @package Lafka\Partials
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! ( $product instanceof WC_Product ) ) {
	return;
}

$lafka_pdp_ctx           = lafka_pdp_summary_context( $product );
$lafka_is_variable       = $lafka_pdp_ctx['is_variable'];
$lafka_form_action       = $lafka_pdp_ctx['form_action'];
$lafka_pdp_cart_disabled = $lafka_pdp_ctx['cart_disabled'];
$lafka_pdp_initial       = $lafka_pdp_ctx['initial'];
?>
	<?php if ( $lafka_pdp_cart_disabled ) : ?>

		<?php
		// Store closed + add-to-cart disabled: render the plugin's closed-store
		// card in place of the buy box. The <form class="cart"> is intentionally
		// not emitted, so there is no Add-to-Cart to submit. The server also
		// rejects any replayed add through woocommerce_add_to_cart_validation and
		// the Store API gate (see Lafka_Order_Hours).
		Lafka_Order_Hours::echo_closed_store_message();
		?>

	<?php elseif ( $product->is_type( 'lafka_deal' ) ) : ?>

		<?php
		// A Deal (the plugin's lafka_deal type): the customer picks each item.
		// The plugin renders its builder from WooCommerce's own per-type
		// add-to-cart action, the same hook any theme's product page fires.
		do_action( 'woocommerce_before_add_to_cart_form' );
		do_action( 'woocommerce_lafka_deal_add_to_cart' );
		do_action( 'woocommerce_after_add_to_cart_form' );
		?>

	<?php elseif ( $lafka_is_variable ) : ?>
		<?php
		// The add button's prompt until every attribute is chosen (the script
		// keeps it current): "Choose size", from the first unchosen attribute.
		$lafka_pdp_cta_prompt = __( 'Choose your options', 'lafka' );
		foreach ( (array) $product->get_variation_attributes() as $lafka_pdp_attr_name => $lafka_pdp_attr_opts ) {
			if ( '' === (string) ( $lafka_pdp_initial['selection'][ 'attribute_' . sanitize_title( (string) $lafka_pdp_attr_name ) ] ?? '' ) && function_exists( 'lafka_pdp_choose_label' ) ) {
				$lafka_pdp_attr_label = function_exists( 'lafka_attribute_display_label' ) ? lafka_attribute_display_label( (string) wc_attribute_label( $lafka_pdp_attr_name, $product ) ) : (string) $lafka_pdp_attr_name;
				$lafka_pdp_cta_prompt = lafka_pdp_choose_label( $lafka_pdp_attr_label, (string) $lafka_pdp_attr_name, $product );
				break;
			}
		}

		// Fire BEFORE the form opens — this triggers the lafka-plugin addon
		// system's reposition_display_for_variable_product(), which moves
		// the addon display() callback from woocommerce_before_add_to_cart_button
		// to woocommerce_single_variation. Without this, no addons render
		// for variable products.
		do_action( 'woocommerce_before_add_to_cart_form' );
		do_action( 'woocommerce_before_variations_form' );
		?>
		<form class="cart variations_form"
				action="<?php echo esc_url( $lafka_form_action ); ?>"
				method="post"
				enctype="multipart/form-data"
				data-product_id="<?php echo absint( $product->get_id() ); ?>"
				data-product_variations="
				<?php
				echo esc_attr( wp_json_encode( $product->get_available_variations(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) );
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
							<span data-lafka-cta-label><?php echo esc_html( $lafka_pdp_cta_prompt ); ?></span>
						</button>
					</div>

					<div class="lafka-pdp-mobile-cta">
						<div class="lafka-pdp-mobile-cta__qty">
							<button type="button" data-lafka-qty="-1" aria-label="<?php esc_attr_e( 'Decrease', 'lafka' ); ?>">−</button>
							<span data-lafka-qty-display>1</span>
							<button type="button" data-lafka-qty="+1" aria-label="<?php esc_attr_e( 'Increase', 'lafka' ); ?>">+</button>
						</div>
						<button type="submit" class="lafka-pdp-mobile-cta__btn" data-lafka-add-to-cart disabled data-lafka-state="incomplete">
							<span data-lafka-cta-label><?php echo esc_html( $lafka_pdp_cta_prompt ); ?></span>
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
		<?php
		do_action( 'woocommerce_after_variations_form' );
		do_action( 'woocommerce_after_add_to_cart_form' );
		?>

	<?php else : /* simple / combo / etc. */ ?>

		<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>
		<form class="cart"
				action="<?php echo esc_url( $lafka_form_action ); ?>"
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
					<span data-lafka-cta-label><?php esc_html_e( 'Add to order', 'lafka' ); ?></span>
				</button>
			</div>

			<div class="lafka-pdp-mobile-cta">
				<div class="lafka-pdp-mobile-cta__qty">
					<button type="button" data-lafka-qty="-1" aria-label="<?php esc_attr_e( 'Decrease', 'lafka' ); ?>">−</button>
					<span data-lafka-qty-display>1</span>
					<button type="button" data-lafka-qty="+1" aria-label="<?php esc_attr_e( 'Increase', 'lafka' ); ?>">+</button>
				</div>
				<button type="submit" class="lafka-pdp-mobile-cta__btn" data-lafka-add-to-cart>
					<span data-lafka-cta-label><?php esc_html_e( 'Add to order', 'lafka' ); ?></span>
				</button>
			</div>

			<?php do_action( 'woocommerce_after_add_to_cart_quantity' ); ?>
			<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

			<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>">
		</form>
		<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

	<?php endif; ?>
