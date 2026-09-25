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

if ( ! function_exists( 'lafka_counter_brand_short' ) ) {
	/**
	 * Optional short brand name for the phone-width header (e.g. "Peppery"),
	 * shown <600 in place of the full name so the logo + name stay one line.
	 * Customizer → Lafka — Layouts → "Short name on phones"; filter
	 * lafka_counter_brand_short. '' (the default) keeps the full name, which
	 * the header then sizes to one line (critical-counter.css).
	 *
	 * @param string $full The full business name.
	 * @return string '' when unset or identical to the full name.
	 */
	function lafka_counter_brand_short( string $full ): string {
		$short = trim( (string) get_theme_mod( 'lafka_counter_brand_short', '' ) );
		if ( '' === $short ) {
			$short = lafka_counter_brand_short_auto( $full );
		}
		$short = trim( wp_strip_all_tags( (string) apply_filters( 'lafka_counter_brand_short', $short, $full ) ) );
		return $short === trim( $full ) ? '' : $short;
	}
}

if ( ! function_exists( 'lafka_counter_brand_short_auto' ) ) {
	/**
	 * H-01: a long name with no operator short name falls back to its first
	 * part — "Harbour Pizza & Poutine" → "Harbour Pizza" — on narrow headers.
	 * Names of 18 characters or fewer, or without a joiner, stay whole ('').
	 *
	 * @param string $full Full brand name.
	 */
	function lafka_counter_brand_short_auto( string $full ): string {
		$full = trim( html_entity_decode( wp_strip_all_tags( $full ), ENT_QUOTES, 'UTF-8' ) );
		if ( mb_strlen( $full ) <= 18 ) {
			return '';
		}
		$parts = preg_split( '/\s+(?:&|\+|and|\||-|–|—)\s+/u', $full, 2 );
		$first = is_array( $parts ) ? trim( (string) $parts[0] ) : '';
		return ( '' !== $first && $first !== $full && mb_strlen( $first ) >= 3 ) ? $first : '';
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

if ( ! function_exists( 'lafka_counter_footer_location' ) ) {
	/** The counter footer's own menu location (never the legacy tertiary one). */
	function lafka_counter_footer_location(): string {
		return 'lafka-counter-footer';
	}
}

if ( ! function_exists( 'lafka_counter_register_nav_location' ) ) {
	/** Register the counter header's and footer's WordPress menu locations. */
	function lafka_counter_register_nav_location(): void {
		if ( function_exists( 'register_nav_menu' ) ) {
			register_nav_menu( lafka_counter_nav_location(), __( 'Header menu (counter layout)', 'lafka' ) );
			register_nav_menu( lafka_counter_footer_location(), __( 'Footer menu (counter layout)', 'lafka' ) );
		}
	}
}
add_action( 'after_setup_theme', 'lafka_counter_register_nav_location', 20 );

if ( ! function_exists( 'lafka_counter_footer_items' ) ) {
	/**
	 * Default footer links when no menu is assigned to the counter footer
	 * location: Menu · Deals · Find us (Deals / Find us only when they exist,
	 * as in the header). The privacy link is printed separately, always.
	 * Filter: lafka_counter_footer_links.
	 *
	 * @return list<array{label:string,url:string}>
	 */
	function lafka_counter_footer_items(): array {
		$items = array(
			array(
				'label' => __( 'Menu', 'lafka' ),
				'url'   => lafka_theme_menu_url(),
			),
		);
		$deals = lafka_counter_deals_url();
		if ( '' !== $deals ) {
			$items[] = array(
				'label' => __( 'Deals', 'lafka' ),
				'url'   => $deals,
			);
		}
		if ( function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'home', 'counter' ) && function_exists( 'lafka_counter_settings' ) && lafka_counter_settings()['show_find_us'] ) {
			$items[] = array(
				'label' => __( 'Find us', 'lafka' ),
				'url'   => home_url( '/#find-us' ),
			);
		}
		return (array) apply_filters( 'lafka_counter_footer_links', $items );
	}
}

if ( ! function_exists( 'lafka_counter_footer_cap_links' ) ) {
	/**
	 * wp_nav_menu_objects: the quiet footer never becomes a link wall — an
	 * assigned counter-footer menu shows its first N top-level items
	 * (filter lafka_counter_footer_max_links, default 6).
	 *
	 * @param array  $items Menu items.
	 * @param object $args  wp_nav_menu() args.
	 * @return array
	 */
	function lafka_counter_footer_cap_links( $items, $args = null ) {
		if ( ! is_array( $items ) || ! is_object( $args ) || ( $args->theme_location ?? '' ) !== lafka_counter_footer_location() ) {
			return $items;
		}
		$max = max( 1, (int) apply_filters( 'lafka_counter_footer_max_links', 6 ) );
		return array_slice( $items, 0, $max );
	}
}
add_filter( 'wp_nav_menu_objects', 'lafka_counter_footer_cap_links', 10, 2 );

if ( ! function_exists( 'lafka_counter_deals_url' ) ) {
	/** The deals category's archive URL, or '' when the store has none. */
	function lafka_counter_deals_url(): string {
		if ( ! function_exists( 'lafka_counter_resolve_sections' ) || ! function_exists( 'lafka_menu_top_categories' ) ) {
			return '';
		}
		$sections = lafka_counter_resolve_sections( lafka_menu_top_categories(), lafka_counter_settings() );
		if ( ! $sections['deals'] ) {
			return '';
		}
		$link = get_term_link( $sections['deals'] );
		return is_string( $link ) ? $link : '';
	}
}

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
		$deals = lafka_counter_deals_url();
		if ( '' !== $deals ) {
			$items[] = array(
				'label' => __( 'Deals', 'lafka' ),
				'url'   => $deals,
			);
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
		$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
		echo '<ul class="' . esc_attr( $class ) . '">';
		foreach ( $items as $item ) {
			$current = lafka_counter_nav_is_current( (string) $item['url'], $request );
			echo '<li class="menu-item' . ( $current ? ' current-menu-item' : '' ) . '"><a href="' . esc_url( $item['url'] ) . '"' . ( $current ? ' aria-current="page"' : '' ) . '>' . esc_html( $item['label'] ) . '</a></li>';
		}
		echo '</ul>';
	}
}

if ( ! function_exists( 'lafka_counter_nav_is_current' ) ) {
	/**
	 * PURE: whether a nav link points at the page being viewed (H-23). In-page
	 * anchors ("/#deals") are never "the current page".
	 *
	 * @param string $url     Link URL.
	 * @param string $request Request URI (path + query).
	 */
	function lafka_counter_nav_is_current( string $url, string $request ): bool {
		if ( '' === $url || false !== strpos( $url, '#' ) ) {
			return false;
		}
		$link = (string) wp_parse_url( $url, PHP_URL_PATH );
		$here = (string) wp_parse_url( $request, PHP_URL_PATH );
		return '' !== $here && rtrim( $link, '/' ) === rtrim( $here, '/' ) && '' !== rtrim( $link, '/' );
	}
}

if ( ! function_exists( 'lafka_localize_fulfilment_cfg' ) ) {
	/**
	 * Attach the brand-neutral fulfilment storage contract (window.lafkaCfg) to
	 * a script handle. Always loaded here (GX4) so the counter's
	 * js/lafka-fulfilment.js gets it on every page; the same guarded definition
	 * in partials/menu-controls.php and woocommerce/cart/cart.php is then a
	 * no-op. Filter: lafka_fulfilment_js_config.
	 *
	 * @param string $handle Registered script handle to localize.
	 */
	function lafka_localize_fulfilment_cfg( $handle ) {
		static $done = array();
		if ( isset( $done[ $handle ] ) || ! function_exists( 'wp_localize_script' ) ) {
			return;
		}
		$done[ $handle ] = true;
		wp_localize_script(
			$handle,
			'lafkaCfg',
			apply_filters(
				'lafka_fulfilment_js_config',
				array(
					'fulfilmentKey'       => 'lafka.fulfilment',
					'fulfilmentDefault'   => 'pickup',
					// Pre-rename key, read once for migration only (menu/cart controllers).
					'fulfilmentLegacyKey' => 'peppery.fulfilment',
				)
			)
		);
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
			wp_localize_script(
				'lafka-open-status',
				'lafkaOpenStatusL10n',
				array(
					'openNow'       => __( 'Open now', 'lafka' ),
					'closed'        => __( 'Closed', 'lafka' ),
					/* translators: %s: closing time, e.g. "11 pm" */
					'until'         => __( 'until %s', 'lafka' ),
					/* translators: %s: opening time today */
					'opensToday'    => __( 'opens today at %s', 'lafka' ),
					/* translators: %s: opening time tomorrow */
					'opensTomorrow' => __( 'opens tomorrow at %s', 'lafka' ),
					/* translators: 1: weekday, 2: opening time */
					'opensOn'       => __( 'opens %1$s at %2$s', 'lafka' ),
					/* translators: %s: hour (and minutes), e.g. "11" or "11:30" */
					'am'            => __( '%s am', 'lafka' ),
					/* translators: %s: hour (and minutes), e.g. "11" or "11:30" */
					'pm'            => __( '%s pm', 'lafka' ),
					'noon'          => __( 'noon', 'lafka' ),
					'midnight'      => __( 'midnight', 'lafka' ),
					// Store UTC offset in minutes, so the refresh reads the store's clock.
					'offset'        => function_exists( 'wp_timezone' ) ? (int) ( wp_timezone()->getOffset( new DateTime( 'now', wp_timezone() ) ) / 60 ) : 0,
					'days'          => array( __( 'Sunday', 'lafka' ), __( 'Monday', 'lafka' ), __( 'Tuesday', 'lafka' ), __( 'Wednesday', 'lafka' ), __( 'Thursday', 'lafka' ), __( 'Friday', 'lafka' ), __( 'Saturday', 'lafka' ) ),
				)
			);
		}
		if ( lafka_layout_is( 'header', 'counter' ) || lafka_layout_is( 'drawer', 'counter' ) ) {
			wp_enqueue_script( 'lafka-fulfilment', get_template_directory_uri() . '/js/lafka-fulfilment.js', array(), lafka_asset_version( '/js/lafka-fulfilment.js' ), $defer );
			lafka_localize_fulfilment_cfg( 'lafka-fulfilment' );
		}
		if ( lafka_layout_is( 'home', 'counter' ) || lafka_layout_is( 'menu', 'counter' ) || lafka_layout_is( 'drawer', 'counter' ) ) {
			// The single add path (window.lafkaQuickAdd.add) — same handle and
			// args as the archive quick-add enqueue, so it never loads twice.
			wp_enqueue_script( 'lafka-archive-quickadd', get_template_directory_uri() . '/js/lafka-archive-quickadd.js', array( 'jquery' ), lafka_asset_version( '/js/lafka-archive-quickadd.js' ), $defer );
			wp_enqueue_script( 'lafka-size-chooser', get_template_directory_uri() . '/js/lafka-size-chooser.js', array( 'lafka-archive-quickadd' ), lafka_asset_version( '/js/lafka-size-chooser.js' ), $defer );
			wp_localize_script(
				'lafka-size-chooser',
				'lafkaCounter',
				array(
					'i18n' => array(
						/* translators: 1: product name, 2: option (e.g. "Medium") */
						'added'       => __( 'Added %1$s, %2$s', 'lafka' ),
						/* translators: %s: product name */
						'addedSimple' => __( 'Added %s', 'lafka' ),
						'unavailable' => __( 'Not available', 'lafka' ),
						'addToOrder'  => __( 'Add to order', 'lafka' ),
						/* translators: 1: option (e.g. "Medium"), 2: price */
						'option'      => __( '%1$s, %2$s, add to order', 'lafka' ),
						'error'       => __( 'Could not add that. Opening the product page…', 'lafka' ),
						/* translators: %s: attribute name in lower case, e.g. "size", "pieces". */
						'chooseOne'   => __( 'Choose %s', 'lafka' ),
						'chooseMany'  => __( 'Choose your options', 'lafka' ),
					),
				)
			);
		}
	}
}

if ( ! function_exists( 'lafka_counter_add_mode' ) ) {
	/**
	 * How a product can be added from a listing: `chooser` (variable, 2-tap
	 * size chooser), `direct` (one tap) or '' (link to the product page —
	 * required add-ons, "any" attributes, unavailable, or quick-add turned off).
	 * Registers the chooser payload when needed.
	 *
	 * @param WC_Product $product Product.
	 */
	function lafka_counter_add_mode( $product ): string {
		$payload = lafka_chooser_payload( $product );
		$quick   = (bool) apply_filters( 'lafka_archive_quickadd_enabled', (bool) get_theme_mod( 'lafka_archive_quickadd_enabled', true ), $product );
		if ( ! $payload['addable'] || ! $quick ) {
			return '';
		}
		if ( 'chooser' === $payload['mode'] ) {
			lafka_chooser_register( $product );
			return 'chooser';
		}
		return 'direct';
	}
}

if ( ! function_exists( 'lafka_counter_add_action' ) ) {
	/**
	 * The worded add control of a listing: a real <button> for chooser / direct
	 * adds, else a "Choose" link to the product page. The product name follows
	 * the visible word for screen readers ("Add Loaded Fries").
	 *
	 * @param WC_Product $product Product.
	 * @param string     $label   Visible word(s), e.g. "Add" / "Add to order".
	 * @param string     $class   Extra classes.
	 */
	function lafka_counter_add_action( $product, string $label, string $class = '' ): string {
		$name = wp_strip_all_tags( (string) $product->get_name() );
		$url  = (string) $product->get_permalink();
		$mode = lafka_counter_add_mode( $product );
		if ( '' !== $mode ) {
			// A one-variation variable product adds that variation directly.
			$payload = 'direct' === $mode ? lafka_chooser_payload( $product ) : array();
			$add_id  = ! empty( $payload['variation_id'] ) ? (int) $payload['variation_id'] : (int) $product->get_id();
			return '<button type="button" class="' . esc_attr( trim( 'lafka-counter-btn ' . $class ) ) . '"'
				. ' data-lafka-add="' . esc_attr( (string) $add_id ) . '"'
				. ' data-lafka-add-mode="' . esc_attr( $mode ) . '"'
				. ' data-lafka-add-url="' . esc_url( $url ) . '">'
				. esc_html( $label ) . '<span class="screen-reader-text"> ' . esc_html( $name ) . '</span></button>';
		}
		$choose = (string) apply_filters( 'lafka_counter_choose_label', __( 'Choose', 'lafka' ), $product );
		return '<a class="' . esc_attr( trim( 'lafka-counter-btn lafka-counter-btn--choose ' . $class ) ) . '" href="' . esc_url( $url ) . '">'
			. esc_html( $choose ) . '<span class="screen-reader-text"> '
			/* translators: %s: product name */
			. esc_html( sprintf( __( 'options for %s', 'lafka' ), $name ) ) . '</span></a>';
	}
}

if ( ! function_exists( 'lafka_counter_price_text' ) ) {
	/**
	 * A product's short price line: "$8.50" or "from $10.99".
	 *
	 * @param WC_Product $product Product.
	 */
	function lafka_counter_price_text( $product ): string {
		$prices = lafka_price_columns( $product );
		$price  = lafka_price_plain( (float) $prices['price'] );
		/* translators: %s: lowest price */
		return 'single' === $prices['type'] ? $price : sprintf( __( 'from %s', 'lafka' ), $price );
	}
}

if ( ! function_exists( 'lafka_counter_lcp_image_url' ) ) {
	/**
	 * The counter home's LCP is the front hero dish — an eager
	 * fetchpriority=high <img> with srcset the preload scanner finds on its own.
	 * Suppress the classic hero preload so the browser never fetches a
	 * different (unused) image first.
	 *
	 * @param string $url Preload URL.
	 */
	function lafka_counter_lcp_image_url( $url ) {
		if ( function_exists( 'is_front_page' ) && is_front_page() && function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'home', 'counter' ) ) {
			return '';
		}
		return $url;
	}
}
add_filter( 'lafka_lcp_image_url', 'lafka_counter_lcp_image_url', 20 );

if ( ! function_exists( 'lafka_counter_cart_count' ) ) {
	/** Items in the cart (0 without WooCommerce). */
	function lafka_counter_cart_count(): int {
		return ( function_exists( 'WC' ) && WC() && isset( WC()->cart ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
	}
}

if ( ! function_exists( 'lafka_counter_cart_subtotal_text' ) ) {
	/** The cart subtotal as plain text ("$42.94"), in WC's display tax mode. */
	function lafka_counter_cart_subtotal_text(): string {
		if ( ! function_exists( 'WC' ) || ! WC() || empty( WC()->cart ) || ! method_exists( WC()->cart, 'get_cart_subtotal' ) ) {
			return '';
		}
		return trim( html_entity_decode( wp_strip_all_tags( (string) WC()->cart->get_cart_subtotal() ), ENT_QUOTES, 'UTF-8' ) );
	}
}

if ( ! function_exists( 'lafka_counter_bar_active' ) ) {
	/**
	 * Whether the sticky mobile bar renders on this request: counter header,
	 * Customizer toggle on, and not the cart / checkout / a product page.
	 */
	function lafka_counter_bar_active(): bool {
		if ( ! function_exists( 'lafka_layout_is' ) || ! lafka_layout_is( 'header', 'counter' ) ) {
			return false;
		}
		if ( ! (bool) get_theme_mod( 'lafka_counter_mobile_bar', true ) ) {
			return false;
		}
		foreach ( array( 'is_cart', 'is_checkout', 'is_product' ) as $conditional ) {
			if ( function_exists( $conditional ) && $conditional() ) {
				return false;
			}
		}
		return true;
	}
}

if ( ! function_exists( 'lafka_counter_bar_order_html' ) ) {
	/**
	 * The mobile bar's order control — also the `a.lafka-counter-bar__order`
	 * cart fragment. Empty cart: "Order online →" to the menu. Otherwise
	 * "View order · 3 · $42.94", which opens the drawer.
	 */
	function lafka_counter_bar_order_html(): string {
		$count = lafka_counter_cart_count();
		$arrow = lafka_counter_icon( 'arrow' );
		if ( 0 === $count ) {
			return '<a class="lafka-counter-bar__order lafka-counter-btn lafka-counter-btn--primary" href="' . esc_url( lafka_theme_menu_url() ) . '">'
				. '<span>' . esc_html__( 'Order online', 'lafka' ) . '</span>' . $arrow . '</a>';
		}
		$subtotal = lafka_counter_cart_subtotal_text();
		$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : lafka_theme_menu_url();
		/* translators: 1: number of items, 2: subtotal */
		$label = sprintf( _n( 'View order, %1$d item, %2$s', 'View order, %1$d items, %2$s', $count, 'lafka' ), $count, $subtotal );
		return '<a class="lafka-counter-bar__order lafka-counter-btn lafka-counter-btn--primary" href="' . esc_url( $cart_url ) . '" data-lafka-cart-open aria-label="' . esc_attr( $label ) . '">'
			. '<span>' . esc_html__( 'View order', 'lafka' ) . '</span>'
			. '<span class="lafka-counter-bar__count" aria-hidden="true">' . esc_html( (string) $count ) . '</span>'
			. ( '' !== $subtotal ? '<span aria-hidden="true">' . esc_html( $subtotal ) . '</span>' : '' )
			. '</a>';
	}
}

if ( ! function_exists( 'lafka_counter_render_mobile_bar' ) ) {
	/** wp_footer: the sticky mobile bar (replaces the classic sticky cart bar). */
	function lafka_counter_render_mobile_bar(): void {
		if ( is_admin() || ! lafka_counter_bar_active() ) {
			return;
		}
		get_template_part( 'partials/counter/mobile-bar' );
	}
}
add_action( 'wp_footer', 'lafka_counter_render_mobile_bar', 5 );

if ( ! function_exists( 'lafka_counter_bar_body_class' ) ) {
	/**
	 * Reserve room for the bar so it never covers content.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	function lafka_counter_bar_body_class( $classes ) {
		if ( lafka_counter_bar_active() ) {
			$classes[] = 'lafka-has-counter-bar';
		}
		return $classes;
	}
}
add_filter( 'body_class', 'lafka_counter_bar_body_class' );

if ( ! function_exists( 'lafka_counter_cart_fragments' ) ) {
	/**
	 * WooCommerce cart fragments for the counter chrome (mobile bar order
	 * control; the drawer's checkout-total label once the drawer is counter).
	 *
	 * @param array<string,string> $fragments Fragments.
	 * @return array<string,string>
	 */
	function lafka_counter_cart_fragments( $fragments ) {
		if ( ! function_exists( 'lafka_layout_is' ) ) {
			return $fragments;
		}
		$fragments = (array) $fragments;
		if ( lafka_layout_is( 'header', 'counter' ) ) {
			$fragments['a.lafka-counter-bar__order']   = lafka_counter_bar_order_html();
			$fragments['a.lafka-counter-header__cart'] = lafka_counter_header_cart_html();
		}
		if ( lafka_layout_is( 'drawer', 'counter' ) ) {
			$fragments['span.lafka-drawer__checkout-total'] = lafka_counter_drawer_checkout_label();
			$fragments['span.lafka-drawer__summary']        = lafka_counter_drawer_summary();
		}
		return $fragments;
	}
}
add_filter( 'woocommerce_add_to_cart_fragments', 'lafka_counter_cart_fragments' );

if ( ! function_exists( 'lafka_counter_header_cart_html' ) ) {
	/**
	 * The header's worded Cart link: "Cart" + a count badge (hidden at 0),
	 * named "Cart, 3 items" for screen readers; opens the drawer. Also a cart
	 * fragment, so the name, count and empty state stay current.
	 */
	function lafka_counter_header_cart_html(): string {
		if ( ! function_exists( 'wc_get_cart_url' ) ) {
			return '';
		}
		$count = lafka_counter_cart_count();
		/* translators: %d: number of items in the cart */
		$label = sprintf( _n( 'Cart, %d item', 'Cart, %d items', $count, 'lafka' ), $count );
		return '<a class="lafka-counter-header__cart" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="' . esc_attr( $label ) . '" data-lafka-cart-open>'
			. lafka_counter_icon( 'bag' )
			. '<span class="lafka-counter-header__cart-label">' . esc_html__( 'Cart', 'lafka' ) . '</span>'
			. '<span class="lafka-counter-header__count' . ( 0 === $count ? ' is-empty' : '' ) . '" data-lafka-cart-count aria-hidden="true">' . esc_html( (string) $count ) . '</span>'
			. '</a>';
	}
}

if ( ! function_exists( 'lafka_counter_drawer_summary' ) ) {
	/** "3 items" under the drawer title (also a cart fragment). */
	function lafka_counter_drawer_summary(): string {
		$count = lafka_counter_cart_count();
		/* translators: %d: number of items in the order */
		return '<span class="lafka-drawer__summary">' . esc_html( sprintf( _n( '%d item', '%d items', $count, 'lafka' ), $count ) ) . '</span>';
	}
}

if ( ! function_exists( 'lafka_counter_drawer_checkout_label' ) ) {
	/** "Go to checkout — $42.94" (also a cart fragment). */
	function lafka_counter_drawer_checkout_label(): string {
		$subtotal = lafka_counter_cart_count() > 0 ? lafka_counter_cart_subtotal_text() : '';
		$label    = '' !== $subtotal
			/* translators: %s: order subtotal */
			? sprintf( __( 'Go to checkout — %s', 'lafka' ), $subtotal )
			: __( 'Go to checkout', 'lafka' );
		return '<span class="lafka-drawer__checkout-total">' . esc_html( $label ) . '</span>';
	}
}

if ( ! function_exists( 'lafka_counter_upsell_heading' ) ) {
	/**
	 * Counter wording for lafka-plugin's drawer upsell (gx4p filters).
	 *
	 * @param string $heading Plugin heading.
	 */
	function lafka_counter_upsell_heading( $heading ) {
		return function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'drawer', 'counter' ) ? __( 'Add a little extra?', 'lafka' ) : $heading;
	}
}
add_filter( 'lafka_cart_drawer_upsell_heading', 'lafka_counter_upsell_heading' );

if ( ! function_exists( 'lafka_counter_upsell_row_note' ) ) {
	/**
	 * A short note under each upsell (the product's short description, up to
	 * 40 characters, never cut mid-word), else nothing.
	 *
	 * @param string     $note    Plugin note ('' by default).
	 * @param WC_Product $product Upsell product.
	 */
	function lafka_counter_upsell_row_note( $note, $product = null ) {
		if ( '' !== (string) $note || ! is_object( $product ) || ! function_exists( 'lafka_layout_is' ) || ! lafka_layout_is( 'drawer', 'counter' ) ) {
			return $note;
		}
		$text = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $product->get_short_description() ) ) );
		if ( mb_strlen( $text ) > 40 ) {
			$cut   = mb_substr( $text, 0, 40 );
			$space = mb_strrpos( $cut, ' ' );
			$text  = rtrim( false !== $space ? mb_substr( $cut, 0, $space ) : $cut, ' ,;:.' ) . '…';
		}
		return $text;
	}
}
add_filter( 'lafka_cart_drawer_upsell_row_note', 'lafka_counter_upsell_row_note', 10, 2 );

if ( ! function_exists( 'lafka_counter_upsell_button_label' ) ) {
	/**
	 * Worded "Add" on the drawer upsell buttons.
	 *
	 * @param string $label Plugin label.
	 */
	function lafka_counter_upsell_button_label( $label ) {
		return function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'drawer', 'counter' ) ? __( 'Add', 'lafka' ) : $label;
	}
}
add_filter( 'lafka_cart_drawer_upsell_add_label', 'lafka_counter_upsell_button_label' );

if ( ! function_exists( 'lafka_counter_disable_emoji_enabled' ) ) {
	/**
	 * H-27: skip WordPress's emoji script + s.w.org images on counter
	 * storefronts (Customizer → Layouts → "Use the visitor's own emoji",
	 * default on). Filter `lafka_disable_wp_emoji`.
	 */
	function lafka_counter_disable_emoji_enabled(): bool {
		$on = (bool) get_theme_mod( 'lafka_disable_wp_emoji', true )
			&& function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'header', 'counter' );
		return (bool) apply_filters( 'lafka_disable_wp_emoji', $on );
	}
}

if ( ! function_exists( 'lafka_counter_disable_emoji' ) ) {
	/** template_redirect: unhook the front-end emoji detection script + styles. */
	function lafka_counter_disable_emoji(): void {
		if ( is_admin() || ! lafka_counter_disable_emoji_enabled() ) {
			return;
		}
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}
}
add_action( 'template_redirect', 'lafka_counter_disable_emoji', 0 );

// H-31 / GX T-01: lafka_preloader_enabled() lives in incl/system/lafka-preloader.php
// (off by default, never under any counter layout, filter `lafka_preloader_enabled`).
