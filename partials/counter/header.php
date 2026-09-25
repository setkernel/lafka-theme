<?php
/**
 * Counter layout: header inner (rendered INSIDE header.php's
 * <header id="header" class="lafka-header">, which stays the single banner).
 *
 *   logo + name · open/closed status (order gate aware) · [nav] ·
 *   Pickup/Delivery · phone · Cart · Order online · checkered motif band
 *
 * ≥1024 one row (+ an optional slim nav row). 600–1023: the fulfilment toggle
 * and phone move to the drawer / mobile bar. <600: logo + name + status + Cart
 * (phone + order CTA live in the sticky mobile bar).
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_ch_nap    = lafka_counter_nap();
$lafka_ch_status = function_exists( 'lafka_counter_open_status' ) ? lafka_counter_open_status() : null;
$lafka_ch_logo   = function_exists( 'lafka_get_logo_id' ) ? (int) lafka_get_logo_id() : 0;
$lafka_ch_nav_on = (bool) get_theme_mod( 'lafka_counter_header_nav', true );
$lafka_ch_hours  = function_exists( 'lafka_open_status_hours_for_client' ) ? lafka_open_status_hours_for_client() : array();
$lafka_ch_short  = lafka_counter_brand_short( $lafka_ch_nap['name'] );
?>
<div class="lafka-counter-header">
	<div class="lafka-counter-header__bar lafka-counter-wrap">
		<a class="lafka-counter-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			if ( $lafka_ch_logo ) {
				echo wp_get_attachment_image(
					$lafka_ch_logo,
					'thumbnail',
					false,
					array(
						'class'   => 'lafka-counter-header__logo',
						'alt'     => '',
						'loading' => 'eager',
						'width'   => '64',
						'height'  => '64',
					)
				);
			}
			?>
			<?php if ( '' !== $lafka_ch_short ) : ?>
				<span class="lafka-counter-header__name lafka-counter-header__name--has-short"><span class="lafka-counter-header__name-full"><?php echo esc_html( $lafka_ch_nap['name'] ); ?></span><span class="lafka-counter-header__name-short" aria-hidden="true"><?php echo esc_html( $lafka_ch_short ); ?></span></span>
			<?php else : ?>
				<span class="lafka-counter-header__name"><?php echo esc_html( $lafka_ch_nap['name'] ); ?></span>
			<?php endif; ?>
		</a>

		<?php if ( $lafka_ch_status ) : ?>
			<p
				class="lafka-counter-status <?php echo esc_attr( $lafka_ch_status['is_open'] ? 'is-open' : 'is-closed' ); ?>"
				data-lafka-open-status
				data-lafka-gate="<?php echo esc_attr( $lafka_ch_status['gate'] ); ?>"
				data-lafka-hours="<?php echo esc_attr( (string) wp_json_encode( $lafka_ch_hours ) ); ?>"
			>
				<span class="lafka-counter-status__dot" aria-hidden="true"></span>
				<span class="lafka-counter-status__text" data-lafka-open-status-text><strong><?php echo esc_html( $lafka_ch_status['strong'] ); ?></strong><?php echo '' !== $lafka_ch_status['rest'] ? ' · ' . esc_html( $lafka_ch_status['rest'] ) : ''; ?></span>
			</p>
		<?php endif; ?>

		<?php lafka_counter_render_fulfilment( 'header' ); ?>

		<div class="lafka-counter-header__actions">
			<?php if ( '' !== $lafka_ch_nap['phone'] ) : ?>
				<a class="lafka-counter-header__phone" href="<?php echo esc_attr( 'tel:' . $lafka_ch_nap['tel'] ); ?>" data-lafka-channel="phone">
					<?php echo lafka_counter_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span class="lafka-counter-header__phone-number"><?php echo esc_html( $lafka_ch_nap['phone'] ); ?></span>
				</a>
			<?php endif; ?>

			<button type="button" class="lafka-counter-header__menu" aria-controls="lafka-mobile-nav" aria-expanded="false" data-lafka-menu-toggle>
				<?php echo lafka_counter_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span class="lafka-counter-header__menu-label"><?php esc_html_e( 'Menu', 'lafka' ); ?></span>
			</button>

			<?php echo lafka_counter_header_cart_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* (also a cart fragment, so the name + count stay current). ?>

			<a class="lafka-counter-header__order lafka-counter-btn lafka-counter-btn--primary" href="<?php echo esc_url( lafka_theme_menu_url() ); ?>">
				<?php echo esc_html( (string) apply_filters( 'lafka_header_cta_label', __( 'Order online', 'lafka' ) ) ); ?>
			</a>
		</div>
	</div>

	<?php if ( $lafka_ch_nav_on ) : ?>
		<nav class="lafka-counter-nav" aria-label="<?php esc_attr_e( 'Main', 'lafka' ); ?>">
			<div class="lafka-counter-wrap">
				<?php lafka_counter_render_nav( 'lafka-counter-nav__list' ); ?>
			</div>
		</nav>
	<?php endif; ?>

	<div class="lafka-motif-band" aria-hidden="true"></div>
</div>
