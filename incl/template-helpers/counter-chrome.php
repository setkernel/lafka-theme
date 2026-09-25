<?php
/**
 * GX4: shared bits of the counter chrome (header, drawer, mobile bar, footer).
 *
 *   lafka_counter_icon( $name )             static inline SVG (aria-hidden)
 *   lafka_counter_nap()                     name / phone / address from lafka_get_restaurant_info()
 *   lafka_counter_fulfilment_modes()        which of pickup / delivery the store offers
 *   lafka_counter_fulfilment_current()      the visitor's preference (else the first mode)
 *   lafka_counter_render_fulfilment()       the Pickup / Delivery radio group
 *   lafka_counter_nav_items()               default header links when no menu is assigned
 *   lafka_counter_render_nav()              the header / mobile-menu nav
 *
 * NAP and hours come ONLY from lafka-plugin's single resolver
 * lafka_get_restaurant_info(); fulfilment modes + preference from its
 * lafka_fulfilment_modes() / lafka_fulfilment_preference() when present.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_counter_icon' ) ) {
	/**
	 * A static, decorative inline SVG (24px grid, currentColor).
	 *
	 * @param string $name Icon name.
	 * @param int    $size Rendered size in px.
	 */
	function lafka_counter_icon( string $name, int $size = 20 ): string {
		$paths = array(
			'phone'      => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
			'bag'        => '<path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
			'arrow'      => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
			'clock'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'pin'        => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
			'directions' => '<path d="M3 11l18-8-8 18-2-8-8-2z"/>',
			'close'      => '<path d="M6 6l12 12"/><path d="M18 6L6 18"/>',
			'menu'       => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
			'star'       => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
		);
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}
		return '<svg class="lafka-icon lafka-icon--' . esc_attr( $name ) . '" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}
}

if ( ! function_exists( 'lafka_counter_nap' ) ) {
	/**
	 * Name, phone (display + tel), address lines and hours from the single
	 * NAP resolver. Everything is '' / [] when the operator has not set it.
	 *
	 * @return array{name:string,phone:string,tel:string,address_lines:list<string>,address_short:string,hours:array<string,string>,map_url:string}
	 */
	function lafka_counter_nap(): array {
		$info  = function_exists( 'lafka_get_restaurant_info' ) ? (array) lafka_get_restaurant_info() : array();
		$e164  = isset( $info['phone_e164'] ) ? (string) $info['phone_e164'] : '';
		$raw   = isset( $info['phone_display'] ) ? (string) $info['phone_display'] : '';
		$phone = function_exists( 'lafka_theme_phone_display' ) ? lafka_theme_phone_display( $raw, $e164 ) : ( '' !== $raw ? $raw : $e164 );
		$tel   = (string) preg_replace( '/[^0-9+]/', '', '' !== $e164 ? $e164 : $phone );
		$lines = array_values( array_filter( array_map( 'trim', explode( "\n", (string) ( $info['address_display'] ?? '' ) ) ) ) );
		$map   = (string) ( $info['map_url'] ?? '' );
		if ( '' === $map ) {
			$map = (string) ( $info['directions_url'] ?? '' );
		}
		return array(
			'name'          => (string) ( $info['name'] ?? ( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : '' ) ),
			'phone'         => $phone,
			'tel'           => $tel,
			'address_lines' => $lines,
			'address_short' => (string) ( $info['address_short'] ?? '' ),
			'hours'         => isset( $info['hours'] ) && is_array( $info['hours'] ) ? $info['hours'] : array(),
			'map_url'       => $map,
		);
	}
}

if ( ! function_exists( 'lafka_counter_fulfilment_modes' ) ) {
	/**
	 * The fulfilment modes the store offers (lafka-plugin decides from the
	 * enabled WooCommerce shipping methods); both when the plugin is absent.
	 *
	 * @return list<string> Subset of pickup, delivery.
	 */
	function lafka_counter_fulfilment_modes(): array {
		$modes = function_exists( 'lafka_fulfilment_modes' ) ? (array) lafka_fulfilment_modes() : array( 'pickup', 'delivery' );
		$modes = array_values( array_intersect( array( 'pickup', 'delivery' ), array_map( 'strval', $modes ) ) );
		return (array) apply_filters( 'lafka_counter_fulfilment_modes', $modes );
	}
}

if ( ! function_exists( 'lafka_counter_fulfilment_current' ) ) {
	/**
	 * The visitor's fulfilment preference (lafka-plugin, cookie
	 * `lafka_order_method`), else the first offered mode.
	 */
	function lafka_counter_fulfilment_current(): string {
		$modes = lafka_counter_fulfilment_modes();
		if ( function_exists( 'lafka_fulfilment_preference' ) ) {
			$pref = (string) lafka_fulfilment_preference();
		} else {
			$pref = isset( $_COOKIE['lafka_order_method'] ) ? sanitize_key( wp_unslash( $_COOKIE['lafka_order_method'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended -- read-only UI preference, sanitized with sanitize_key.
		}
		if ( in_array( $pref, $modes, true ) ) {
			return $pref;
		}
		return $modes ? $modes[0] : '';
	}
}

if ( ! function_exists( 'lafka_counter_fulfilment_label' ) ) {
	/**
	 * Visible label of a mode.
	 *
	 * @param string $mode pickup|delivery.
	 */
	function lafka_counter_fulfilment_label( string $mode ): string {
		return 'delivery' === $mode ? __( 'Delivery', 'lafka' ) : __( 'Pickup', 'lafka' );
	}
}

if ( ! function_exists( 'lafka_counter_render_fulfilment' ) ) {
	/**
	 * The Pickup / Delivery radio group (native radios in a fieldset; every
	 * instance on the page is kept in sync by js/lafka-fulfilment.js). Renders
	 * nothing when the store offers a single mode.
	 *
	 * @param string $context header|drawer — the radio name + a modifier class.
	 * @param string $legend  Visible legend ('' = screen-reader only).
	 */
	function lafka_counter_render_fulfilment( string $context, string $legend = '' ): void {
		$modes = lafka_counter_fulfilment_modes();
		if ( count( $modes ) < 2 ) {
			return;
		}
		$current = lafka_counter_fulfilment_current();
		$name    = 'lafka_fulfilment_' . sanitize_key( $context );
		?>
		<fieldset class="lafka-fulfilment lafka-fulfilment--<?php echo esc_attr( $context ); ?>" data-lafka-fulfilment-group>
			<legend class="<?php echo esc_attr( '' === $legend ? 'screen-reader-text' : 'lafka-fulfilment__legend' ); ?>"><?php echo esc_html( '' === $legend ? __( 'How do you want your order?', 'lafka' ) : $legend ); ?></legend>
			<?php foreach ( $modes as $mode ) : ?>
				<label class="lafka-fulfilment__option">
					<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $mode ); ?>" data-lafka-fulfilment-input<?php checked( $current, $mode ); ?>>
					<span class="lafka-fulfilment__label"><?php echo esc_html( lafka_counter_fulfilment_label( $mode ) ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}
}

if ( ! function_exists( 'lafka_counter_nav_location' ) ) {
	/** The nav-menu location the counter header renders. */
	function lafka_counter_nav_location(): string {
		return 'lafka-counter';
	}
}

if ( ! function_exists( 'lafka_counter_register_nav_location' ) ) {
	/** Register the counter header's WordPress menu location. */
	function lafka_counter_register_nav_location(): void {
		if ( function_exists( 'register_nav_menu' ) ) {
			register_nav_menu( lafka_counter_nav_location(), __( 'Header menu (counter layout)', 'lafka' ) );
		}
	}
}
add_action( 'after_setup_theme', 'lafka_counter_register_nav_location', 20 );

if ( ! function_exists( 'lafka_counter_nav_items' ) ) {
	/**
	 * Default header links when no menu is assigned to the location:
	 * Menu · Deals · Find us. Deals appears only when a deals category exists;
	 * Find us only when the counter home shows the find-us section.
	 *
	 * @return list<array{label:string,url:string}>
	 */
	function lafka_counter_nav_items(): array {
		$items = array(
			array(
				'label' => __( 'Menu', 'lafka' ),
				'url'   => lafka_theme_menu_url(),
			),
		);
		if ( function_exists( 'lafka_counter_resolve_sections' ) && function_exists( 'lafka_menu_top_categories' ) ) {
			$sections = lafka_counter_resolve_sections( lafka_menu_top_categories(), lafka_counter_settings() );
			if ( $sections['deals'] ) {
				$link = get_term_link( $sections['deals'] );
				if ( is_string( $link ) ) {
					$items[] = array(
						'label' => __( 'Deals', 'lafka' ),
						'url'   => $link,
					);
				}
			}
		}
		if ( function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'home', 'counter' ) && function_exists( 'lafka_counter_settings' ) && lafka_counter_settings()['show_find_us'] ) {
			$items[] = array(
				'label' => __( 'Find us', 'lafka' ),
				'url'   => home_url( '/#find-us' ),
			);
		}
		return (array) apply_filters( 'lafka_counter_nav_items', $items );
	}
}

if ( ! function_exists( 'lafka_counter_render_nav' ) ) {
	/**
	 * The counter nav: the assigned menu (depth 1) or the default items.
	 *
	 * @param string $class List class.
	 */
	function lafka_counter_render_nav( string $class ): void {
		$location = lafka_counter_nav_location();
		if ( function_exists( 'has_nav_menu' ) && has_nav_menu( $location ) ) {
			wp_nav_menu(
				array(
					'theme_location' => $location,
					'container'      => false,
					'menu_class'     => $class,
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			return;
		}
		$items = lafka_counter_nav_items();
		if ( ! $items ) {
			return;
		}
		echo '<ul class="' . esc_attr( $class ) . '">';
		foreach ( $items as $item ) {
			echo '<li class="menu-item"><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
		}
		echo '</ul>';
	}
}

if ( ! function_exists( 'lafka_counter_enqueue_assets' ) ) {
	/**
	 * Enqueue the counter stylesheet + scripts (called from
	 * lafka_enqueue_scripts_and_styles()). Nothing loads for a classic site.
	 *  - lafka-counter.css           every counter surface (tokens only)
	 *  - js/lafka-open-status.js     counter header status refresh (+ announce bar)
	 *  - js/lafka-fulfilment.js      Pickup/Delivery preference (cookie + event)
	 *  - js/lafka-size-chooser.js    2-tap size chooser + row "Add"
	 * All scripts are footer + defer, dependency-free (jQuery only to fire
	 * WooCommerce's own events when it is present).
	 */
	function lafka_counter_enqueue_assets(): void {
		if ( ! function_exists( 'lafka_any_counter_layout' ) || ! lafka_any_counter_layout() ) {
			return;
		}
		$defer = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);
		wp_enqueue_style( 'lafka-counter', get_template_directory_uri() . '/styles/lafka-counter.css', array( 'lafka-tokens' ), lafka_asset_version( '/styles/lafka-counter.css' ) );

		if ( lafka_layout_is( 'header', 'counter' ) ) {
			wp_enqueue_script( 'lafka-open-status', get_template_directory_uri() . '/js/lafka-open-status.js', array(), lafka_asset_version( '/js/lafka-open-status.js' ), $defer );
		}
		if ( lafka_layout_is( 'header', 'counter' ) || lafka_layout_is( 'drawer', 'counter' ) ) {
			wp_enqueue_script( 'lafka-fulfilment', get_template_directory_uri() . '/js/lafka-fulfilment.js', array(), lafka_asset_version( '/js/lafka-fulfilment.js' ), $defer );
			if ( function_exists( 'lafka_localize_fulfilment_cfg' ) ) {
				lafka_localize_fulfilment_cfg( 'lafka-fulfilment' );
			}
		}
		if ( lafka_layout_is( 'home', 'counter' ) || lafka_layout_is( 'menu', 'counter' ) || lafka_layout_is( 'drawer', 'counter' ) ) {
			wp_enqueue_script( 'lafka-size-chooser', get_template_directory_uri() . '/js/lafka-size-chooser.js', array(), lafka_asset_version( '/js/lafka-size-chooser.js' ), $defer );
			wp_localize_script(
				'lafka-size-chooser',
				'lafkaCounter',
				array(
					'addUrl'  => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'add_to_cart' ) : '',
					'i18n'    => array(
						/* translators: 1: product name, 2: option (e.g. "Medium") */
						'added'       => __( 'Added %1$s, %2$s', 'lafka' ),
						/* translators: %s: product name */
						'addedSimple' => __( 'Added %s', 'lafka' ),
						'unavailable' => __( 'Not available', 'lafka' ),
						/* translators: 1: option (e.g. "Medium"), 2: price */
						'option'      => __( '%1$s, %2$s, add to order', 'lafka' ),
						'error'       => __( 'Could not add that. Opening the product page…', 'lafka' ),
					),
				)
			);
		}
	}
}

if ( ! function_exists( 'lafka_counter_cart_count' ) ) {
	/** Items in the cart (0 without WooCommerce). */
	function lafka_counter_cart_count(): int {
		return ( function_exists( 'WC' ) && WC() && isset( WC()->cart ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
	}
}
