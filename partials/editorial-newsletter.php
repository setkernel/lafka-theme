<?php
/**
 * Partial: Editorial newsletter section (brand-red background).
 *
 * Settings: lafka_editorial_home_newsletter_heading / _intro / _form_html
 *
 * The form HTML is operator-provided (Mailchimp / CF7 embed). When not
 * configured, the entire section is suppressed.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_heading   = get_theme_mod( 'lafka_editorial_home_newsletter_heading', '' );
$lafka_intro     = get_theme_mod( 'lafka_editorial_home_newsletter_intro', '' );
$lafka_form_html = get_theme_mod( 'lafka_editorial_home_newsletter_form_html', '' );

if ( ! $lafka_heading && ! $lafka_form_html ) {
	return;
}
?>
<section class="newsletter-section">
	<div class="newsletter-grid">
		<div>
			<?php if ( $lafka_heading ) : ?>
			<h2><?php echo esc_html( $lafka_heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $lafka_intro ) : ?>
			<p><?php echo esc_html( $lafka_intro ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $lafka_form_html ) : ?>
		<div class="newsletter-form-wrap">
			<?php echo wp_kses_post( $lafka_form_html ); ?>
		</div>
		<?php endif; ?>
	</div>
</section>
