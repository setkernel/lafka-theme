<?php
/**
 * Show error messages
 *
 * Lafka override of WooCommerce core notices/error.php 8.6.0 (current in
 * WooCommerce 10.9.1). T-24: core puts role="alert" on the <ul>, which
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
		<?php foreach ( $notices as $notice ) : ?>
			<li<?php echo wc_get_notice_data_attr( $notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo wc_kses_notice( $notice['notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_kses_notice() is WooCommerce's notice sanitizer. ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
