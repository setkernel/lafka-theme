<?php
/**
 * Partial: Mobile slide-out nav drawer (v5.56.0).
 *
 * Left-side slide-in drawer triggered by the .lafka-header__menu-toggle
 * button. Per handoff spec at /design_handoff_peppery_ordering/README.md
 * "Header > Mobile slide-out nav":
 *
 *   - width: min(86vw, 360px)
 *   - dark scrim with 4px blur
 *   - lists primary nav + every category from product_cat
 *   - bottom-anchored phone CTA
 *   - closes on: × button, scrim click, ESC key, route change
 *   - body scroll lock while open
 *
 * Rendered via wp_footer (see incl/mobile-nav-loader.php) so it sits
 * outside the header DOM and uses position: fixed for the slide-in.
 *
 * @package Lafka
 * @since   5.56.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_mn_info  = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_mn_phone = isset( $lafka_mn_info['phone_display'] ) ? (string) $lafka_mn_info['phone_display'] : '';
$lafka_mn_tel   = isset( $lafka_mn_info['phone_e164'] ) ? (string) $lafka_mn_info['phone_e164'] : $lafka_mn_phone;

$lafka_mn_terms = array();
if ( taxonomy_exists( 'product_cat' ) ) {
	$lafka_mn_args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 24,
	);
	if ( function_exists( 'lafka_uncategorized_excluded_ids' ) ) {
		$lafka_mn_args['exclude'] = lafka_uncategorized_excluded_ids();
	}
	$lafka_mn_raw = get_terms( $lafka_mn_args );
	if ( ! is_wp_error( $lafka_mn_raw ) ) {
		$lafka_mn_terms = $lafka_mn_raw;
	}
}
?>
<div
	id="lafka-mobile-nav"
	class="lafka-mobile-nav"
	aria-hidden="true"
	role="dialog"
	aria-modal="true"
	aria-label="<?php esc_attr_e( 'Main menu', 'lafka' ); ?>"
	data-lafka-mobile-nav
>
	<div class="lafka-mobile-nav__scrim" data-lafka-mobile-nav-close></div>

	<aside class="lafka-mobile-nav__panel" tabindex="-1">
		<header class="lafka-mobile-nav__header">
			<a class="lafka-mobile-nav__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php
				$lafka_logo_id = function_exists( 'get_theme_mod' ) ? get_theme_mod( 'lafka_theme_logo', 0 ) : 0;
				if ( $lafka_logo_id ) {
					echo wp_get_attachment_image(
						$lafka_logo_id,
						'medium',
						false,
						array(
							'class' => 'lafka-mobile-nav__logo',
							'alt'   => esc_attr( get_bloginfo( 'name' ) ),
						)
					);
				}
				?>
				<span class="lafka-mobile-nav__wordmark"><?php bloginfo( 'name' ); ?></span>
			</a>
			<button
				type="button"
				class="lafka-mobile-nav__close"
				aria-label="<?php esc_attr_e( 'Close menu', 'lafka' ); ?>"
				data-lafka-mobile-nav-close
			>×</button>
		</header>

		<div class="lafka-mobile-nav__body">

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<section class="lafka-mobile-nav__section">
					<h2 class="lafka-mobile-nav__section-title"><?php esc_html_e( 'Menu', 'lafka' ); ?></h2>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => 'nav',
							'container_class' => 'lafka-mobile-nav__nav',
							'menu_class'     => 'lafka-mobile-nav__list',
							'depth'          => 1,
							'fallback_cb'    => '',
						)
					);
					?>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $lafka_mn_terms ) ) : ?>
				<section class="lafka-mobile-nav__section">
					<h2 class="lafka-mobile-nav__section-title"><?php esc_html_e( 'Categories', 'lafka' ); ?></h2>
					<ul class="lafka-mobile-nav__list">
						<?php foreach ( $lafka_mn_terms as $lafka_mn_term ) : ?>
							<li>
								<a href="<?php echo esc_url( get_term_link( $lafka_mn_term ) ); ?>">
									<?php echo esc_html( $lafka_mn_term->name ); ?>
									<span class="lafka-mobile-nav__count"><?php echo esc_html( (string) $lafka_mn_term->count ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

		</div>

		<?php if ( '' !== $lafka_mn_phone ) : ?>
			<footer class="lafka-mobile-nav__footer">
				<a
					class="lafka-mobile-nav__phone"
					href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_mn_tel ) ); ?>"
				>
					<span class="lafka-mobile-nav__phone-icon" aria-hidden="true">📞</span>
					<span class="lafka-mobile-nav__phone-label">
						<span class="lafka-mobile-nav__phone-hint"><?php esc_html_e( 'Call to order', 'lafka' ); ?></span>
						<span class="lafka-mobile-nav__phone-number"><?php echo esc_html( $lafka_mn_phone ); ?></span>
					</span>
				</a>
			</footer>
		<?php endif; ?>

	</aside>
</div>
