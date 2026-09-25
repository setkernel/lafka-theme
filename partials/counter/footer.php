<?php
/**
 * Counter layout: the quiet footer — one line of "{name} · {address}" and a
 * few plain links. Links: the "Footer Menu" location (tertiary, depth 1) when
 * assigned, else Full menu · Deals · Your account; the privacy policy link
 * always (when the site has one). No logo wall, no signup (classic keeps them).
 *
 * Rendered inside footer.php, which keeps the #container / #content closing
 * tags, the search dialog and wp_footer() exactly where they were.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_cf_nap   = lafka_counter_nap();
$lafka_cf_line  = implode( ' · ', array_filter( array( $lafka_cf_nap['name'], $lafka_cf_nap['address_short'] ) ) );
$lafka_cf_items = array();
if ( ! ( function_exists( 'has_nav_menu' ) && has_nav_menu( 'tertiary' ) ) ) {
	$lafka_cf_items[] = array(
		'label' => __( 'Full menu', 'lafka' ),
		'url'   => lafka_theme_menu_url(),
	);
	$lafka_cf_deals = lafka_counter_deals_url();
	if ( '' !== $lafka_cf_deals ) {
		$lafka_cf_items[] = array(
			'label' => __( 'Deals', 'lafka' ),
			'url'   => $lafka_cf_deals,
		);
	}
	$lafka_cf_account = (int) get_option( 'woocommerce_myaccount_page_id', 0 );
	if ( $lafka_cf_account ) {
		$lafka_cf_items[] = array(
			'label' => __( 'Your account', 'lafka' ),
			'url'   => get_permalink( $lafka_cf_account ),
		);
	}
}
$lafka_cf_items  = (array) apply_filters( 'lafka_counter_footer_links', $lafka_cf_items );
$lafka_cf_policy = function_exists( 'get_the_privacy_policy_link' ) ? (string) get_the_privacy_policy_link() : '';
?>
<footer id="footer" class="lafka-footer lafka-footer--counter" role="contentinfo">
	<div class="lafka-counter-footer lafka-counter-wrap">
		<?php if ( '' !== $lafka_cf_line ) : ?>
			<p class="lafka-counter-footer__line"><?php echo esc_html( $lafka_cf_line ); ?></p>
		<?php endif; ?>
		<nav class="lafka-counter-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'lafka' ); ?>">
			<?php
			if ( function_exists( 'has_nav_menu' ) && has_nav_menu( 'tertiary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'tertiary',
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
