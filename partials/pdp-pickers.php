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

$attributes = $product->get_variation_attributes();
$variations = $product->is_type( 'variable' ) ? $product->get_available_variations() : array();
$prices_by_attrs = array();
foreach ( $variations as $v ) {
    $key = wp_json_encode( $v['attributes'] );
    $prices_by_attrs[ $key ] = wc_format_decimal( (string) $v['display_price'], 2 );
}
// GX M-10/M-18: single-option attributes (and operator defaults that resolve
// to a real variation) arrive preselected; pdp-summary.php computed it.
$lafka_pdp_initial = isset( $lafka_pdp_initial ) && is_array( $lafka_pdp_initial )
    ? $lafka_pdp_initial
    : ( function_exists( 'lafka_pdp_initial_selection' ) ? lafka_pdp_initial_selection( $product ) : array( 'selection' => array() ) );
?>
<div class="lafka-pdp-pickers" data-prices='<?php echo esc_attr( wp_json_encode( $prices_by_attrs ) ); ?>'>
    <?php foreach ( $attributes as $attr_name => $options ) : ?>
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
        $taxonomy   = $attr_name;
        $label      = wc_attribute_label( $attr_name, $product );
        $label      = function_exists( 'lafka_attribute_display_label' ) ? lafka_attribute_display_label( (string) $label ) : $label;
        $field_name = 'attribute_' . $attr_name;
        $match_key  = 'attribute_' . sanitize_title( (string) $attr_name );
        $preset     = (string) ( $lafka_pdp_initial['selection'][ $match_key ] ?? '' );
        $choose     = function_exists( 'lafka_pdp_choose_label' ) ? lafka_pdp_choose_label( (string) $label, (string) $attr_name, $product ) : '';
        // get_variation_attributes() lists values in database order (e.g.
        // "Medium, Large, Small"); the plugin applies the operator's order,
        // else cheapest first.
        if ( function_exists( 'lafka_sort_variation_options' ) ) {
            $options = lafka_sort_variation_options( $product, (string) $attr_name, (array) $options );
        }
        ?>
        <fieldset class="lafka-pdp-picker" data-attribute="<?php echo esc_attr( $field_name ); ?>" data-required="true" data-choose-label="<?php echo esc_attr( $choose ); ?>">
            <legend id="lafka-pdp-pick-<?php echo esc_attr( $attr_name ); ?>" class="lafka-pdp-picker__label"><?php echo esc_html( $label ); ?></legend>
            <div class="lafka-pdp-picker__chips" role="radiogroup" aria-labelledby="lafka-pdp-pick-<?php echo esc_attr( $attr_name ); ?>" aria-required="true">
                <?php foreach ( $options as $opt ) : ?>
                    <?php
                    $term       = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $opt, $taxonomy ) : null;
                    $opt_label  = $term ? $term->name : $opt;
                    // The lowest price this option can be had for (the script
                    // re-prices each chip for the other choices made).
                    $option_min = null;
                    foreach ( $variations as $v ) {
                        $v_attrs = array_change_key_case( (array) ( $v['attributes'] ?? array() ), CASE_LOWER );
                        $v_value = (string) ( $v_attrs[ $match_key ] ?? '' );
                        if ( '' === $v_value || $v_value === (string) $opt ) {
                            $option_min = null === $option_min ? (float) $v['display_price'] : min( $option_min, (float) $v['display_price'] );
                        }
                    }
                    $option_price = null !== $option_min ? wc_price( $option_min ) : '';
                    ?>
                    <label class="lafka-pdp-chip">
                        <input type="radio" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $opt ); ?>"<?php echo (string) $opt === $preset ? ' checked' : ''; ?>>
                        <span class="lafka-pdp-chip__inner">
                            <span class="lafka-pdp-chip__name"><?php echo esc_html( $opt_label ); ?></span>
                            <?php if ( $option_price ) : ?>
                                <span class="lafka-pdp-chip__price" data-lafka-chip-price><?php echo wp_kses_post( $option_price ); ?></span>
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
