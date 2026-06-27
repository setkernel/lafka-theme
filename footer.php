<?php
/**
 * Footer — handoff-spec rebuild (v5.58.0).
 *
 * Replaces the legacy theAlThemist footer. Legacy file preserved as
 * footer-legacy.php for emergency rollback.
 *
 * Per handoff spec /design_handoff_peppery_ordering/README.md "Footer":
 *   - dark --lafka-color-text-primary bg, white text
 *   - 4-col grid (≥768): 1.6 / 1 / 1 / 1
 *       col 1: brand + about + email signup + social
 *       col 2: Order column (menu, cart, account)
 *       col 3: Visit column (address + hours)
 *       col 4: Reach us column (phone + email + contact)
 *   - bottom bar: copyright + tagline, border-top 12% white
 *
 * Email signup is a hookable surface — operators can swap in mc4wp /
 * Klaviyo / etc by filtering `lafka_footer_signup_html`. The built-in default
 * form is only emitted when a `lafka_footer_subscribe` AJAX handler is actually
 * registered, so a fresh install never ships a form that dead-ends on submit
 * (audit 2026-06-27 #4).
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_ft_info  = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_ft_name  = isset( $lafka_ft_info['name'] ) ? (string) $lafka_ft_info['name'] : (string) get_bloginfo( 'name' );
$lafka_ft_addr  = isset( $lafka_ft_info['address_display'] ) ? (string) $lafka_ft_info['address_display'] : '';
$lafka_ft_phone = isset( $lafka_ft_info['phone_display'] ) ? (string) $lafka_ft_info['phone_display'] : '';
$lafka_ft_tel   = isset( $lafka_ft_info['phone_e164'] ) ? (string) $lafka_ft_info['phone_e164'] : $lafka_ft_phone;
$lafka_ft_email = isset( $lafka_ft_info['email'] ) ? (string) $lafka_ft_info['email'] : (string) get_bloginfo( 'admin_email' );
$lafka_ft_hours = isset( $lafka_ft_info['hours'] ) && is_array( $lafka_ft_info['hours'] ) ? $lafka_ft_info['hours'] : array();
$lafka_ft_logo  = function_exists( 'lafka_get_option' ) ? lafka_get_option( 'theme_logo' ) : 0;

$lafka_ft_about = (string) get_theme_mod(
	'lafka_footer_about',
	__( 'Fresh-baked pizza, poutine, donair and more — made to order from scratch in our kitchen.', 'lafka' )
);

// Only ship the built-in form when an integration has actually registered a
// subscribe handler — otherwise it would dead-end on submit. Providers that
// filter `lafka_footer_signup_html` supply their own markup regardless.
$lafka_ft_signup_default_html = '';
if ( has_action( 'wp_ajax_nopriv_lafka_footer_subscribe' ) ) {
	$lafka_ft_signup_default_html = '
<form class="lafka-footer__signup" action="' . esc_url( admin_url( 'admin-ajax.php?action=lafka_footer_subscribe' ) ) . '" method="post" data-lafka-footer-signup>
	<label class="lafka-footer__signup-label" for="lafka-footer-signup-email">
		<span class="lafka-footer__signup-headline">' . esc_html__( 'Get our latest deals', 'lafka' ) . '</span>
		<span class="lafka-footer__signup-hint">' . esc_html__( 'Monthly deals and new-menu drops. Unsubscribe anytime.', 'lafka' ) . '</span>
	</label>
	<div class="lafka-footer__signup-row">
		<input type="email" id="lafka-footer-signup-email" name="email" class="lafka-footer__signup-input" placeholder="' . esc_attr__( 'your@email.com', 'lafka' ) . '" autocomplete="email" required>
		<button type="submit" class="lafka-footer__signup-button">' . esc_html__( 'Subscribe', 'lafka' ) . '</button>
	</div>
	<p class="lafka-footer__signup-success" data-lafka-footer-signup-success hidden>
		<span aria-hidden="true">✓</span> ' . esc_html__( 'Thanks — you are subscribed.', 'lafka' ) . '
	</p>
</form>';
}

$lafka_ft_signup_html = (string) apply_filters( 'lafka_footer_signup_html', $lafka_ft_signup_default_html );

$lafka_ft_social = array(
	'facebook' => (string) get_theme_mod( 'lafka_social_facebook', '' ),
	'instagram' => (string) get_theme_mod( 'lafka_social_instagram', '' ),
	'tiktok'    => (string) get_theme_mod( 'lafka_social_tiktok', '' ),
);
$lafka_ft_social = array_filter( $lafka_ft_social );

$lafka_ft_year = function_exists( 'wp_date' ) ? wp_date( 'Y' ) : date_i18n( 'Y' );
?>
		</div><!-- #container -->
	</main><!-- #content -->

	<footer id="footer" class="lafka-footer" role="contentinfo">
		<div class="lafka-container lafka-footer__inner">

			<div class="lafka-footer__col lafka-footer__col--brand">
				<a class="lafka-footer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php
					if ( $lafka_ft_logo ) {
						echo wp_get_attachment_image(
							$lafka_ft_logo,
							'medium',
							false,
							array(
								'class' => 'lafka-footer__logo',
								'alt'   => esc_attr( $lafka_ft_name ),
							)
						);
					}
					?>
					<span class="lafka-footer__brand-name"><?php echo esc_html( $lafka_ft_name ); ?></span>
				</a>

				<p class="lafka-footer__about"><?php echo esc_html( $lafka_ft_about ); ?></p>

				<?php
				/* Email signup — filterable. Default markup escaped above; filter consumers responsible. */
				echo $lafka_ft_signup_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

				<?php if ( ! empty( $lafka_ft_social ) ) : ?>
					<ul class="lafka-footer__social">
						<?php foreach ( $lafka_ft_social as $lafka_ft_net => $lafka_ft_url ) : ?>
							<li>
								<a class="lafka-footer__social-link lafka-footer__social-link--<?php echo esc_attr( $lafka_ft_net ); ?>" href="<?php echo esc_url( $lafka_ft_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $lafka_ft_net ) ); ?>">
									<i class="fa fa-<?php echo esc_attr( $lafka_ft_net ); ?>" aria-hidden="true"></i>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="lafka-footer__col lafka-footer__col--order">
				<h2 class="lafka-footer__col-title"><?php esc_html_e( 'Order', 'lafka' ); ?></h2>
				<ul class="lafka-footer__links">
					<li><a href="<?php echo esc_url( home_url( '/menu/' ) ); ?>"><?php esc_html_e( 'Full menu', 'lafka' ); ?></a></li>
					<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
						<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Your cart', 'lafka' ); ?></a></li>
					<?php endif; ?>
					<?php if ( function_exists( 'wc_get_checkout_url' ) ) : ?>
						<li><a href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'lafka' ); ?></a></li>
					<?php endif; ?>
					<?php if ( function_exists( 'get_option' ) && get_option( 'woocommerce_myaccount_page_id' ) ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>"><?php esc_html_e( 'My account', 'lafka' ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="lafka-footer__col lafka-footer__col--visit">
				<h2 class="lafka-footer__col-title"><?php esc_html_e( 'Visit us', 'lafka' ); ?></h2>
				<?php if ( '' !== $lafka_ft_addr ) : ?>
					<address class="lafka-footer__address">
						<?php echo nl2br( esc_html( $lafka_ft_addr ) ); ?>
					</address>
				<?php endif; ?>

				<?php if ( ! empty( $lafka_ft_hours ) ) : ?>
					<dl class="lafka-footer__hours">
						<?php foreach ( $lafka_ft_hours as $lafka_ft_day => $lafka_ft_range ) : ?>
							<div class="lafka-footer__hours-row">
								<dt><?php echo esc_html( $lafka_ft_day ); ?></dt>
								<dd><?php echo esc_html( $lafka_ft_range ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>

			<div class="lafka-footer__col lafka-footer__col--reach">
				<h2 class="lafka-footer__col-title"><?php esc_html_e( 'Reach us', 'lafka' ); ?></h2>
				<ul class="lafka-footer__contact">
					<?php if ( '' !== $lafka_ft_phone ) : ?>
						<li>
							<a class="lafka-footer__phone" href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_ft_tel ) ); ?>">
								<span aria-hidden="true">📞</span>
								<?php echo esc_html( $lafka_ft_phone ); ?>
							</a>
						</li>
					<?php endif; ?>
					<?php if ( '' !== $lafka_ft_email ) : ?>
						<li>
							<a class="lafka-footer__email" href="mailto:<?php echo esc_attr( $lafka_ft_email ); ?>">
								<span aria-hidden="true">✉</span>
								<?php echo esc_html( $lafka_ft_email ); ?>
							</a>
						</li>
					<?php endif; ?>
					<?php if ( function_exists( 'get_page_by_path' ) ) : ?>
						<?php $lafka_contact_page = get_page_by_path( 'contact' ); ?>
						<?php if ( $lafka_contact_page ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $lafka_contact_page->ID ) ); ?>"><?php esc_html_e( 'Contact page', 'lafka' ); ?> →</a></li>
						<?php endif; ?>
					<?php endif; ?>
				</ul>
			</div>

		</div>

		<div class="lafka-footer__bottom">
			<div class="lafka-container lafka-footer__bottom-inner">
				<p class="lafka-footer__copyright">
					<?php
					/* translators: 1: year, 2: business name */
					printf( esc_html__( '© %1$s %2$s. All rights reserved.', 'lafka' ), esc_html( $lafka_ft_year ), esc_html( $lafka_ft_name ) );
					?>
				</p>
				<p class="lafka-footer__tagline">
					<?php esc_html_e( 'Made fresh. Served fast. Eaten happily.', 'lafka' ); ?>
				</p>
			</div>
		</div>
	</footer>

	<?php
	// Header search overlay — opened by the [data-lafka-search-toggle] icon in
	// header.php. Native <dialog>: showModal() handles Escape + focus trapping;
	// the close button is a method="dialog" form. (Audit 2026-06-27 #3.)
	if ( function_exists( 'lafka_get_option' ) && lafka_get_option( 'show_searchform' ) ) :
		?>
		<dialog id="lafka-search-dialog" class="lafka-search-dialog" aria-label="<?php esc_attr_e( 'Search', 'lafka' ); ?>">
			<div class="lafka-search-dialog__panel">
				<form role="search" method="get" class="lafka-search-dialog__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="lafka-search-field"><?php esc_html_e( 'Search for:', 'lafka' ); ?></label>
					<input type="search" id="lafka-search-field" class="lafka-search-dialog__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search the menu…', 'lafka' ); ?>" autocomplete="off">
					<button type="submit" class="lafka-search-dialog__submit"><?php esc_html_e( 'Search', 'lafka' ); ?></button>
				</form>
				<form method="dialog" class="lafka-search-dialog__close-form">
					<button type="submit" class="lafka-search-dialog__close" aria-label="<?php esc_attr_e( 'Close search', 'lafka' ); ?>">&times;</button>
				</form>
			</div>
		</dialog>
		<?php
	endif;
	?>

	<?php wp_footer(); ?>
</body>
</html>
