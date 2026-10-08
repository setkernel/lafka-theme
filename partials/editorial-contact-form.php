<?php
/**
 * Partial: Editorial Contact — message form column.
 *
 * If lafka_editorial_contact_cf7_form_id > 0 and Contact Form 7 is active,
 * renders the CF7 form via shortcode.
 *
 * Falls back to a mailto: link from lafka_get_restaurant_info() if:
 *   - CF7 form ID is 0, or
 *   - Contact Form 7 is not active.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_form_id = (int) get_theme_mod( 'lafka_editorial_contact_cf7_form_id', 0 );
$lafka_info    = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_email   = ! empty( $lafka_info['email'] ) ? $lafka_info['email'] : '';
?>
<div class="contact-form">
	<h2><?php esc_html_e( 'Send us a message', 'lafka' ); ?></h2>

	<?php if ( $lafka_form_id > 0 && function_exists( 'wpcf7_contact_form' ) ) : ?>
		<?php echo do_shortcode( '[contact-form-7 id="' . absint( $lafka_form_id ) . '"]' ); ?>

	<?php elseif ( $lafka_email ) : ?>
		<p>
			<?php esc_html_e( 'Email us directly:', 'lafka' ); ?>
			<a href="mailto:<?php echo esc_attr( $lafka_email ); ?>"><?php echo esc_html( $lafka_email ); ?></a>
		</p>

	<?php else : ?>
		<?php /* Neither CF7 nor email configured — nothing to show. */ ?>

	<?php endif; ?>
</div>
