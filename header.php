<?php
/**
 * Header — handoff-spec rebuild (v5.55.0).
 *
 * Replaces the legacy 364-line header from theAlThemist. The legacy file
 * is preserved as header-legacy.php for emergency rollback and historical
 * reference. The new header is a clean handoff implementation of
 * /design_handoff_peppery_ordering/README.md "Header".
 *
 * Layout (single row, 64px mobile / 72px desktop):
 *  [hamburger (mobile)]  [logo]  [primary nav]  [cart icon]  [Order now CTA]
 *
 * What we kept from legacy:
 *  - doctype + html + head + wp_head() (WP plumbing)
 *  - LCP image preload for PDPs
 *  - Skip-link, preloader gate, cart_add_sound gate (operator options)
 *  - wp_body_open() — fires the v5.54.0 announce bar + promo bar
 *  - <main id="content"> + <div id="container"> open (closed by footer.php)
 *
 * What we dropped (deliberate, per 2026-05-15 WPBakery-rip + handoff rebuild):
 *  - "Top header" (#header_top) with language selector + secondary menus
 *  - .lafka-top-bar-message strip
 *  - RevSlider before-header rendering
 *  - YITH wishlist counter in header
 *  - Account dropdown (account icon links straight to /my-account/)
 *  - WCMP / WC-Vendors header dashboard buttons
 *  - $lafka_is_blank toggle (irrelevant for the ordering flow)
 *  - LafkaMobileMenuWalker (mobile drawer uses default markup + CSS)
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>" />
	<?php
	// P6-PERF-1: Preload the LCP image for the current page template.
	$lafka_lcp_image = apply_filters( 'lafka_lcp_image_url', '' );
	if ( $lafka_lcp_image ) {
		printf(
			'<link rel="preload" as="image" fetchpriority="high" href="%s">' . "\n",
			esc_url( $lafka_lcp_image )
		);
	}

	// LCP optimization for PDP: preload the main product image.
	if ( function_exists( 'is_product' ) && is_product() ) {
		global $post;
		$lafka_thumb_id = $post ? get_post_thumbnail_id( $post->ID ) : 0;
		if ( $lafka_thumb_id ) {
			$lafka_pdp_src = wp_get_attachment_image_url( $lafka_thumb_id, 'woocommerce_single' );
			if ( $lafka_pdp_src ) {
				printf(
					'<link rel="preload" as="image" fetchpriority="high" href="%s">' . "\n",
					esc_url( $lafka_pdp_src )
				);
			}
		}
	}
	?>

	<?php
	// v5.99.0: preload Fraunces — the display font used on every h1/h2
	// across the site. Without preload, Fraunces requests serialize
	// behind CSS parse + first-paint, which is the dominant LCP+CLS
	// contributor on home (CLS 0.83 → 0.05 once font swap is eliminated).
	// font-display: optional (v5.82) prevents FOUT but the font still
	// needs to start downloading early enough to land within ~100ms.
	?>
	<link rel="preload" href="<?php echo esc_url( get_template_directory_uri() . '/assets/fonts/fraunces/Fraunces-600.woff2' ); ?>" as="font" type="font/woff2" crossorigin="anonymous">
	<link rel="preload" href="<?php echo esc_url( get_template_directory_uri() . '/assets/fonts/fraunces/Fraunces-800.woff2' ); ?>" as="font" type="font/woff2" crossorigin="anonymous">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php
	wp_body_open();
	// ↑ Fires the announce bar (v5.54.0) + promo bar (v5.51.0) + any other
	//   wp_body_open hooks. Renders at the very top of <body>, above #header.
	?>

	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'lafka' ); ?></a>

	<?php if ( function_exists( 'lafka_get_option' ) && lafka_get_option( 'show_preloader' ) ) : ?>
		<div class="mask" aria-hidden="true">
			<div id="spinner">
				<div class="double-bounce1"></div>
				<div class="double-bounce2"></div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( function_exists( 'lafka_get_option' ) && lafka_get_option( 'add_to_cart_sound' ) ) : ?>
		<?php // preload="none" — 352 KB wav stays uncached until add-to-cart fires. ?>
		<audio id="cart_add_sound" controls preload="none" hidden>
			<source src="<?php echo esc_url( LAFKA_IMAGES_PATH . 'cart_add.wav' ); ?>" type="audio/wav">
		</audio>
	<?php endif; ?>

	<header id="header" class="lafka-header" role="banner">
		<div class="lafka-header__inner lafka-container">

			<button
				type="button"
				class="lafka-header__menu-toggle"
				aria-label="<?php esc_attr_e( 'Open menu', 'lafka' ); ?>"
				aria-controls="lafka-mobile-nav"
				aria-expanded="false"
				data-lafka-menu-toggle
			>
				<span class="lafka-header__menu-icon" aria-hidden="true">☰</span>
			</button>

			<a class="lafka-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php
				/*
				 * v6.3.0: prefer WP-standard custom-logo (Appearance →
				 * Customize → Site Identity → Logo). Fall back to legacy
				 * lafka_get_option('theme_logo') so operators with data
				 * stored in the old framework still see their logo until
				 * they re-upload via the standard UI. Final fallback is
				 * the WP Site Icon.
				 */
				$lafka_custom_logo_id = function_exists( 'get_theme_mod' ) ? (int) get_theme_mod( 'custom_logo', 0 ) : 0;
				$lafka_legacy_main    = function_exists( 'lafka_get_option' ) ? (int) lafka_get_option( 'theme_logo' ) : 0;
				$lafka_legacy_mobile  = function_exists( 'lafka_get_option' ) ? (int) lafka_get_option( 'mobile_theme_logo' ) : 0;
				$lafka_logo_id        = $lafka_custom_logo_id ?: $lafka_legacy_main ?: $lafka_legacy_mobile;
				if ( $lafka_logo_id ) {
					echo wp_get_attachment_image(
						$lafka_logo_id,
						'medium',
						false,
						array(
							'class'   => 'lafka-header__logo-img',
							'alt'     => esc_attr( get_bloginfo( 'name' ) ),
							'loading' => 'eager',
						)
					);
				} else {
					// Site icon as last-resort fallback.
					$lafka_site_icon = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 96 ) : '';
					if ( $lafka_site_icon ) {
						printf(
							'<img class="lafka-header__logo-img" src="%s" alt="%s" loading="eager">',
							esc_url( $lafka_site_icon ),
							esc_attr( get_bloginfo( 'name' ) )
						);
					}
				}
				?>
				<span class="lafka-header__wordmark"><?php bloginfo( 'name' ); ?></span>
			</a>

			<?php
			if ( has_nav_menu( 'primary' ) ) :
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => 'nav',
						'container_id'   => 'lafka-primary-nav',
						'container_class' => 'lafka-header__nav',
						'menu_class'     => 'lafka-header__nav-list',
						'depth'          => 1,
						'fallback_cb'    => '',
					)
				);
			endif;
			?>

			<div class="lafka-header__actions">

				<?php if ( function_exists( 'lafka_get_option' ) && lafka_get_option( 'show_searchform' ) ) : ?>
					<a class="lafka-header__icon-btn lafka-header__search" href="#search" aria-label="<?php esc_attr_e( 'Search', 'lafka' ); ?>" data-lafka-search-toggle>
						<i class="fa fa-search" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ( function_exists( 'is_user_logged_in' ) && function_exists( 'lafka_should_show_account_icon' ) && lafka_should_show_account_icon() ) : ?>
					<a class="lafka-header__icon-btn lafka-header__account" href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>" aria-label="<?php esc_attr_e( 'My account', 'lafka' ); ?>">
						<i class="fa fa-user" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php if ( defined( 'LAFKA_IS_WOOCOMMERCE' ) && LAFKA_IS_WOOCOMMERCE && function_exists( 'WC' ) && WC()->cart ) : ?>
					<?php
					// v5.82.0: a11y — WCAG 2.5.3 (Label in Name). The visible
					// text on this link is the count ("0", "3", etc.); the
					// aria-label has to contain that visible text so screen
					// readers and voice-input users (who say "click view
					// cart 0 items") get a match. Old aria-label was just
					// "View cart" which didn't include the count.
					$lafka_cart_count = (int) WC()->cart->get_cart_contents_count();
					/* translators: %d: number of items in the cart */
					$lafka_cart_aria  = sprintf( _n( 'View cart, %d item', 'View cart, %d items', $lafka_cart_count, 'lafka' ), $lafka_cart_count );
					?>
					<a
						class="lafka-header__cart"
						href="<?php echo esc_url( wc_get_cart_url() ); ?>"
						aria-label="<?php echo esc_attr( $lafka_cart_aria ); ?>"
						data-lafka-cart-open
					>
						<i class="fa fa-shopping-bag" aria-hidden="true"></i>
						<span class="lafka-header__cart-count" data-lafka-cart-count aria-hidden="true">
							<?php echo esc_html( (string) $lafka_cart_count ); ?>
						</span>
					</a>
				<?php endif; ?>

				<a class="lafka-header__cta" href="<?php echo esc_url( apply_filters( 'lafka_header_cta_url', home_url( '/menu/' ) ) ); ?>">
					<span class="lafka-header__cta-label"><?php echo esc_html( apply_filters( 'lafka_header_cta_label', __( 'Order now', 'lafka' ) ) ); ?></span>
					<span class="lafka-header__cta-arrow" aria-hidden="true">→</span>
				</a>

			</div>

		</div>
	</header>

	<main id="content" tabindex="-1">
		<div id="container">
