<?php
/**
 * PDP variation + addon pickers — size + crust chips, then toppings.
 *
 * Required global: $product (WC_Product instance). Renders inside the
 * cart variations_form so the existing add-to-cart submit path still works.
 *
 * The Lafka addons hook 'woocommerce_before_add_to_cart_button' fires here
 * to render the Pizza Toppings group via lafka-plugin's existing addon
 * markup; we only restyle visually (chip CSS).
 *
 * @package Lafka\Partials
 * @since   5.16.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
	return;
}

$lafka_attributes      = $product->get_variation_attributes();
$lafka_variations      = $product->is_type( 'variable' ) ? $product->get_available_variations() : array();
$lafka_prices_by_attrs = array();
foreach ( $lafka_variations as $lafka_v ) {
	$lafka_key                           = wp_json_encode( $lafka_v['attributes'] );
	$lafka_prices_by_attrs[ $lafka_key ] = wc_format_decimal( (string) $lafka_v['display_price'], 2 );
}
// GX M-10/M-18: single-option attributes (and operator defaults that resolve
// to a real variation) arrive preselected; pdp-summary.php computed it.
$lafka_pdp_initial = isset( $lafka_pdp_initial ) && is_array( $lafka_pdp_initial )
	? $lafka_pdp_initial
	: ( function_exists( 'lafka_pdp_initial_selection' ) ? lafka_pdp_initial_selection( $product ) : array( 'selection' => array() ) );
?>
<div class="lafka-pdp-pickers" data-prices='<?php echo esc_attr( wp_json_encode( $lafka_prices_by_attrs ) ); ?>'>
	<?php foreach ( $lafka_attributes as $lafka_attr_name => $lafka_options ) : ?>
		<?php
		// $attr_name from get_variation_attributes() is the taxonomy slug
		// (e.g. "pa_size") — no "attribute_" prefix. Use it directly as the
		// taxonomy name for term-label lookups.
		//
		// The form-input `name` attribute, however, MUST be prefixed with
		// "attribute_" so WC_Form_Handler::add_to_cart_handler_variable()
		// can find $_REQUEST['attribute_pa_size'] when matching variations.
		// Get this wrong and add-to-cart silently fails with variation_id=0.
		//
		// Per-variation $v['attributes'] keys (from get_available_variations)
		// also use the "attribute_" prefix — that's why the price lookup
		// below indexes with "attribute_$attr_name".
		$lafka_taxonomy   = $lafka_attr_name;
		$lafka_label      = wc_attribute_label( $lafka_attr_name, $product );
		$lafka_label      = function_exists( 'lafka_attribute_display_label' ) ? lafka_attribute_display_label( (string) $lafka_label ) : $lafka_label;
		$lafka_field_name = 'attribute_' . $lafka_attr_name;
		$lafka_match_key  = 'attribute_' . sanitize_title( (string) $lafka_attr_name );
		$lafka_preset     = (string) ( $lafka_pdp_initial['selection'][ $lafka_match_key ] ?? '' );
		$lafka_choose     = function_exists( 'lafka_pdp_choose_label' ) ? lafka_pdp_choose_label( (string) $lafka_label, (string) $lafka_attr_name, $product ) : '';
		// get_variation_attributes() lists values in database order (e.g.
		// "Medium, Large, Small"); the plugin applies the operator's order,
		// else cheapest first.
		if ( function_exists( 'lafka_sort_variation_options' ) ) {
			$lafka_options = lafka_sort_variation_options( $product, (string) $lafka_attr_name, (array) $lafka_options );
		}
		?>
		<fieldset class="lafka-pdp-picker" data-attribute="<?php echo esc_attr( $lafka_field_name ); ?>" data-required="true" data-choose-label="<?php echo esc_attr( $lafka_choose ); ?>">
			<legend id="lafka-pdp-pick-<?php echo esc_attr( $lafka_attr_name ); ?>" class="lafka-pdp-picker__label"><?php echo esc_html( $lafka_label ); ?></legend>
			<div class="lafka-pdp-picker__chips" role="radiogroup" aria-labelledby="lafka-pdp-pick-<?php echo esc_attr( $lafka_attr_name ); ?>" aria-required="true">
				<?php foreach ( $lafka_options as $lafka_opt ) : ?>
					<?php
					$lafka_term      = taxonomy_exists( $lafka_taxonomy ) ? get_term_by( 'slug', $lafka_opt, $lafka_taxonomy ) : null;
					$lafka_opt_label = $lafka_term ? $lafka_term->name : $lafka_opt;
					// The lowest price this option can be had for (the script
					// re-prices each chip for the other choices made).
					$lafka_option_min = null;
					foreach ( $lafka_variations as $lafka_v ) {
						$lafka_v_attrs = array_change_key_case( (array) ( $lafka_v['attributes'] ?? array() ), CASE_LOWER );
						$lafka_v_value = (string) ( $lafka_v_attrs[ $lafka_match_key ] ?? '' );
						if ( '' === $lafka_v_value || $lafka_v_value === (string) $lafka_opt ) {
							$lafka_option_min = null === $lafka_option_min ? (float) $lafka_v['display_price'] : min( $lafka_option_min, (float) $lafka_v['display_price'] );
						}
					}
					$lafka_option_price = null !== $lafka_option_min ? wc_price( $lafka_option_min ) : '';
					?>
					<label class="lafka-pdp-chip">
						<input type="radio" name="<?php echo esc_attr( $lafka_field_name ); ?>" value="<?php echo esc_attr( $lafka_opt ); ?>"<?php echo (string) $lafka_opt === $lafka_preset ? ' checked' : ''; ?>>
						<span class="lafka-pdp-chip__inner">
							<span class="lafka-pdp-chip__name"><?php echo esc_html( $lafka_opt_label ); ?></span>
							<?php if ( $lafka_option_price ) : ?>
								<span class="lafka-pdp-chip__price" data-lafka-chip-price><?php echo wp_kses_post( $lafka_option_price ); ?></span>
							<?php endif; ?>
							<span class="lafka-pdp-chip__na" data-lafka-chip-na hidden><?php esc_html_e( 'Not available', 'lafka' ); ?></span>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
	<?php endforeach; ?>
</div>
<?php
// Note: standard WC variations-form hooks (woocommerce_before_variations_form,
// _before_single_variation, _single_variation, _before_add_to_cart_button, etc.)
// are fired by pdp-summary.php in the correct WC-conformant order.
// Don't add do_action() calls here — they'd fire at the wrong place in the
// form structure and break addon-plugin reposition logic.
