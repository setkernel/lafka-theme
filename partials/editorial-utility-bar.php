<?php
/**
 * Partial: Editorial utility bar (dark top strip).
 *
 * Reads NAP/hours from lafka_get_restaurant_info() (W2-T1 source-of-truth).
 * Falls back gracefully when the plugin is inactive.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_info = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();

$lafka_address       = ! empty( $lafka_info['address_display'] ) ? $lafka_info['address_display'] : '';
$lafka_phone_e164    = ! empty( $lafka_info['phone_e164'] ) ? $lafka_info['phone_e164'] : '';
$lafka_phone_display = lafka_theme_phone_display( $lafka_info['phone_display'] ?? '', $lafka_phone_e164 );
?>
<div class="editorial-utility">
	<?php if ( $lafka_address ) : ?>
	<span><?php echo esc_html( $lafka_address ); ?></span>
	<?php endif; ?>

	<div class="utility-right">
		<span class="utility-open"><?php esc_html_e( 'Open now', 'lafka' ); ?></span>
		<?php if ( $lafka_phone_e164 ) : ?>
		<a href="tel:<?php echo esc_attr( $lafka_phone_e164 ); ?>"><?php echo esc_html( $lafka_phone_display ); ?></a>
		<?php endif; ?>
	</div>
</div>
