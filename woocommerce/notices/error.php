<?php
/**
 * Show error messages
 *
 * Lafka override of WooCommerce core notices/error.php 8.6.0 (current in
 * WooCommerce 11.2.0). T-24: core puts role="alert" on the <ul>, which
 * replaces the list's own role, so screen readers lose "list, N items". The
 * alert role moves to a wrapping <div>; the <ul class="woocommerce-error">
 * stays a plain list and every <li> keeps its data-id attribute, which
 * WooCommerce's checkout.js reads to print the inline field errors.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $notices ) {
	return;
}

?>
<div class="lafka-notice-alert" role="alert">
	<ul class="woocommerce-error">
		<?php foreach ( $notices as $lafka_notice ) : ?>
			<li
			<?php
			foreach ( (array) ( $lafka_notice['data'] ?? array() ) as $lafka_notice_key => $lafka_notice_value ) {
				printf( ' data-%1$s="%2$s"', esc_attr( $lafka_notice_key ), esc_attr( $lafka_notice_value ) );
			}
			?>
			>
				<?php echo wp_kses_post( wc_kses_notice( $lafka_notice['notice'] ) ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
