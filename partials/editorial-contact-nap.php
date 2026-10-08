<?php
/**
 * Partial: Editorial Contact — NAP + hours column.
 *
 * Reads from lafka_get_restaurant_info() (W2-T1 source-of-truth).
 *
 * v6.5.0: this partial is used only by the legacy
 * page_templates/template-editorial-contact.php. The default contact
 * page renders via template-contact.php which has its own NAP block.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_info = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();

$lafka_phone_e164    = ! empty( $lafka_info['phone_e164'] ) ? $lafka_info['phone_e164'] : '';
$lafka_phone_display = lafka_theme_phone_display( $lafka_info['phone_display'] ?? '', $lafka_phone_e164 );
$lafka_email         = ! empty( $lafka_info['email'] ) ? $lafka_info['email'] : '';
$lafka_address       = ! empty( $lafka_info['address_display'] ) ? $lafka_info['address_display'] : '';
$lafka_hours         = ! empty( $lafka_info['hours'] ) ? $lafka_info['hours'] : array();

$lafka_today_name = wp_date( 'l' );
?>
<div class="contact-nap">
	<h2><?php esc_html_e( 'Find us', 'lafka' ); ?></h2>

	<?php if ( $lafka_phone_e164 ) : ?>
	<div class="contact-block">
		<div class="block-label"><?php esc_html_e( 'Phone', 'lafka' ); ?></div>
		<div class="block-value">
			<a href="tel:<?php echo esc_attr( $lafka_phone_e164 ); ?>"><?php echo esc_html( $lafka_phone_display ); ?></a>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $lafka_email ) : ?>
	<div class="contact-block">
		<div class="block-label"><?php esc_html_e( 'Email', 'lafka' ); ?></div>
		<div class="block-value">
			<a href="mailto:<?php echo esc_attr( $lafka_email ); ?>"><?php echo esc_html( $lafka_email ); ?></a>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $lafka_address ) : ?>
	<div class="contact-block">
		<div class="block-label"><?php esc_html_e( 'Address', 'lafka' ); ?></div>
		<div class="block-value"><?php echo nl2br( esc_html( $lafka_address ) ); ?></div>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $lafka_hours ) ) : ?>
	<div class="contact-block">
		<div class="block-label"><?php esc_html_e( 'Hours', 'lafka' ); ?></div>
		<div class="block-value">
			<div class="contact-hours">
				<?php
				foreach ( $lafka_hours as $lafka_day => $lafka_time ) :
					$lafka_is_today = ( strtolower( $lafka_today_name ) === strtolower( $lafka_day ) );
					?>
				<div class="<?php echo $lafka_is_today ? 'day today' : 'day'; ?>"><?php echo esc_html( $lafka_day ); ?></div>
				<div class="<?php echo $lafka_is_today ? 'time today' : 'time'; ?>"><?php echo esc_html( $lafka_time ); ?></div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>
</div>
