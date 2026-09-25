<?php
/**
 * Counter layout: the quiet footer — one line of "{name} · {address}" and a
 * few plain links on one row. Links: the counter's own "Footer menu (counter
 * layout)" location when assigned (depth 1, capped by
 * lafka_counter_footer_max_links), else Menu · Deals · Find us
 * (lafka_counter_footer_items()); the privacy policy link always (when the
 * site has one). The legacy tertiary "Footer Menu" is never printed here — on
 * migrated stores it holds the old link wall. No logo wall, no signup
 * (classic keeps them).
 *
 * Rendered inside footer.php, which keeps the #container / #content closing
 * tags, the search dialog and wp_footer() exactly where they were.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_cf_nap      = lafka_counter_nap();
// H-24: the full street + city/region/postcode line (the address display's
// first two lines, not the short form), then the phone.
$lafka_cf_address  = $lafka_cf_nap['address_lines'] ? implode( ', ', array_slice( $lafka_cf_nap['address_lines'], 0, 2 ) ) : $lafka_cf_nap['address_short'];
$lafka_cf_line     = implode( ' · ', array_filter( array( $lafka_cf_nap['name'], $lafka_cf_address ) ) );
$lafka_cf_location = lafka_counter_footer_location();
$lafka_cf_menu     = function_exists( 'has_nav_menu' ) && has_nav_menu( $lafka_cf_location );
$lafka_cf_items    = $lafka_cf_menu ? array() : lafka_counter_footer_items();
$lafka_cf_policy   = function_exists( 'get_the_privacy_policy_link' ) ? (string) get_the_privacy_policy_link() : '';
?>
<footer id="footer" class="lafka-footer lafka-footer--counter" role="contentinfo">
	<div class="lafka-counter-footer lafka-counter-wrap">
		<?php if ( '' !== $lafka_cf_line || '' !== $lafka_cf_nap['phone'] ) : ?>
			<p class="lafka-counter-footer__line">
				<?php echo esc_html( $lafka_cf_line ); ?>
				<?php if ( '' !== $lafka_cf_nap['phone'] ) : ?>
					<?php echo '' !== $lafka_cf_line ? ' · ' : ''; ?><a class="lafka-counter-footer__phone" href="<?php echo esc_attr( 'tel:' . $lafka_cf_nap['tel'] ); ?>" data-lafka-channel="phone"><?php echo esc_html( $lafka_cf_nap['phone'] ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<nav class="lafka-counter-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'lafka' ); ?>">
			<?php
			if ( $lafka_cf_menu ) {
				wp_nav_menu(
					array(
						'theme_location' => $lafka_cf_location,
						'container'      => false,
						'menu_class'     => 'lafka-counter-footer__list',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			}
			?>
			<?php if ( $lafka_cf_items || '' !== $lafka_cf_policy ) : ?>
				<ul class="lafka-counter-footer__list">
					<?php foreach ( $lafka_cf_items as $lafka_cf_item ) : ?>
						<li><a href="<?php echo esc_url( $lafka_cf_item['url'] ); ?>"><?php echo esc_html( $lafka_cf_item['label'] ); ?></a></li>
					<?php endforeach; ?>
					<?php if ( '' !== $lafka_cf_policy ) : ?>
						<li><?php echo wp_kses_post( $lafka_cf_policy ); ?></li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
		</nav>
	</div>
</footer>
