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

$id_suffix = wp_unique_id();

$per_page_requets = '';
if ( array_key_exists( 'per_page', $_GET ) ) {
	$per_page_requets = esc_attr( $_GET['per_page'] );
}

?>
<form class="woocommerce-ordering" method="get">
	<?php if ( lafka_get_option( 'show_products_limit' ) ) : ?>
		<?php $products_per_page_from_options = intval( lafka_get_option( 'products_per_page' ) ); ?>
		<?php if ( $products_per_page_from_options > 0 ) : ?>
			<div class="limit">
				<b><?php esc_html_e( 'Show', 'lafka' ); ?>:</b>
				<select class="per_page" name="per_page">
					<?php
					$per_page_options = array( $products_per_page_from_options => $products_per_page_from_options );

					$temp = $products_per_page_from_options;
					for ( $i = 1;$i <= 3;$i++ ) {
						$temp                      = $temp * 2;
						$per_page_options[ $temp ] = $temp;
					}

					$per_page_options['-1'] = esc_html__( 'Show All', 'lafka' );

					foreach ( $per_page_options as $id => $name ) {
						echo '<option value="' . esc_attr( $id ) . '" ' . selected( $per_page_requets, $id, false ) . '>' . esc_attr( $name ) . '</option>';
					}
					?>
				</select>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	<div class="sort">
		<?php if ( ! empty( $use_label ) ) : ?>
			<label for="woocommerce-orderby-<?php echo esc_attr( $id_suffix ); ?>"><?php esc_html_e( 'Sort By', 'lafka' ); ?></label>
		<?php else : ?>
			<b><?php esc_html_e( 'Sort By', 'lafka' ); ?>:</b>
		<?php endif; ?>
		<select
			name="orderby"
			class="orderby"
			<?php if ( ! empty( $use_label ) ) : ?>
				id="woocommerce-orderby-<?php echo esc_attr( $id_suffix ); ?>"
			<?php else : ?>
				aria-label="<?php esc_attr_e( 'Shop order', 'lafka' ); ?>"
			<?php endif; ?>
		>
			<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="hidden" name="paged" value="1" />
	</div>
	<?php wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'per_page', 'product-page' ) ); ?>
</form>
