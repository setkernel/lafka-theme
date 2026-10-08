<?php
/**
 * Show options for ordering
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/orderby.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lafka_id_suffix = wp_unique_id();

$lafka_per_page_requets = esc_attr( (string) lafka_query_arg( 'per_page' ) );

?>
<form class="woocommerce-ordering" method="get">
	<?php if ( get_theme_mod( 'lafka_show_products_limit', true ) ) : ?>
		<?php $lafka_products_per_page_from_options = intval( get_theme_mod( 'lafka_products_per_page', 12 ) ); ?>
		<?php if ( $lafka_products_per_page_from_options > 0 ) : ?>
			<div class="limit">
				<b><?php esc_html_e( 'Show', 'lafka' ); ?>:</b>
				<select class="per_page" name="per_page">
					<?php
					$lafka_per_page_options = array( $lafka_products_per_page_from_options => $lafka_products_per_page_from_options );

					$lafka_temp = $lafka_products_per_page_from_options;
					for ( $lafka_i = 1;$lafka_i <= 3;$lafka_i++ ) {
						$lafka_temp                            = $lafka_temp * 2;
						$lafka_per_page_options[ $lafka_temp ] = $lafka_temp;
					}

					$lafka_per_page_options['-1'] = esc_html__( 'Show All', 'lafka' );

					foreach ( $lafka_per_page_options as $lafka_id => $lafka_name ) {
						echo '<option value="' . esc_attr( $lafka_id ) . '" ' . selected( $lafka_per_page_requets, $lafka_id, false ) . '>' . esc_attr( $lafka_name ) . '</option>';
					}
					?>
				</select>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	<div class="sort">
		<?php if ( ! empty( $use_label ) ) : ?>
			<label for="woocommerce-orderby-<?php echo esc_attr( $lafka_id_suffix ); ?>"><?php esc_html_e( 'Sort By', 'lafka' ); ?></label>
		<?php else : ?>
			<b><?php esc_html_e( 'Sort By', 'lafka' ); ?>:</b>
		<?php endif; ?>
		<select
			name="orderby"
			class="orderby"
			<?php if ( ! empty( $use_label ) ) : ?>
				id="woocommerce-orderby-<?php echo esc_attr( $lafka_id_suffix ); ?>"
			<?php else : ?>
				aria-label="<?php esc_attr_e( 'Shop order', 'lafka' ); ?>"
			<?php endif; ?>
		>
			<?php foreach ( $catalog_orderby_options as $lafka_id => $lafka_name ) : ?>
				<option value="<?php echo esc_attr( $lafka_id ); ?>" <?php selected( $orderby, $lafka_id ); ?>><?php echo esc_html( $lafka_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="hidden" name="paged" value="1" />
	</div>
	<?php wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'per_page', 'product-page' ) ); ?>
</form>
