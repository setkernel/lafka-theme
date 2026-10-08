<?php
/**
 * The theme's icon set — the single source for every icon it draws.
 *
 *   lafka_icon_paths()            name => SVG children (24 px grid, stroke-drawn)
 *   lafka_icon( $name, $size )    static, decorative inline SVG (currentColor)
 *   lafka_icon_css_properties()   `--lafka-icon-<name>` data URIs for CSS masks
 *
 * Markup prints lafka_icon(); stylesheets draw pseudo-element icons with
 * `mask: var(--lafka-icon-<name>) center / 1em no-repeat` on a
 * `background-color: currentColor` box, from the custom properties attached
 * to the main stylesheet. Nothing loads an icon font.
 *
 * The drawings are original to Lafka (simple geometric strokes, some after
 * the Lucide icon conventions), distributed under the theme's GPL licence.
 * Brand marks (social networks) are simplified outlines used only to label
 * links to those networks.
 *
 * Filter: `lafka_icon_paths` (array name => SVG children).
 *
 * @package Lafka
 * @since   7.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_icon_paths' ) ) {
	/**
	 * Every icon: name => SVG children drawn on a 24 × 24 grid with a 2 px
	 * stroke, no fill.
	 *
	 * @return array<string,string>
	 */
	function lafka_icon_paths(): array {
		static $paths = null;
		if ( null === $paths ) {
			$paths = (array) apply_filters(
				'lafka_icon_paths',
				array(
					'phone'          => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
					'bag'            => '<path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
					'arrow'          => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
					'chevrons-right' => '<path d="M6 7l5 5-5 5"/><path d="M13 7l5 5-5 5"/>',
					'chevron-up'     => '<circle cx="12" cy="12" r="9"/><path d="M8 14l4-4 4 4"/>',
					'sort'           => '<path d="M8 9l4-4 4 4"/><path d="M8 15l4 4 4-4"/>',
					'clock'          => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
					'pin'            => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
					'directions'     => '<path d="M3 11l18-8-8 18-2-8-8-2z"/>',
					'close'          => '<path d="M6 6l12 12"/><path d="M18 6L6 18"/>',
					'close-circle'   => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6"/><path d="M15 9l-6 6"/>',
					'menu'           => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
					'star'           => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
					'search'         => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
					'user'           => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
					'play'           => '<circle cx="12" cy="12" r="9"/><path d="M10 8.5l5.5 3.5-5.5 3.5z"/>',
					'folder'         => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
					'tag'            => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
					'comment'        => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12z"/>',
					'alert'          => '<path d="M12 3l10 18H2z"/><path d="M12 10v5"/><path d="M12 18v.01"/>',
					'check-circle'   => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/>',
					'eye'            => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
					'mail'           => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
					'refresh'        => '<path d="M20 11a8 8 0 0 0-14.6-4.5L3 9"/><path d="M3 4v5h5"/><path d="M4 13a8 8 0 0 0 14.6 4.5L21 15"/><path d="M21 20v-5h-5"/>',
					'expand'         => '<path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/>',
					'edit'           => '<path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
					'heart-pulse'    => '<path d="M20.4 12.4L12 21l-8.4-8.6A5 5 0 0 1 12 5.5a5 5 0 0 1 8.4 6.9z"/><path d="M3 12h5l2-3 3 6 2-3h6"/>',
					'scale'          => '<path d="M12 3v18"/><path d="M7 21h10"/><path d="M5 7h14"/><path d="M5 7l-3 7a3 3 0 0 0 6 0z"/><path d="M19 7l-3 7a3 3 0 0 0 6 0z"/>',
					'facebook'       => '<path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V9H6.5v3.5H9V21h3.5v-8.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15z"/>',
					'instagram'      => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5v.01"/>',
					'tiktok'         => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.6 2.4 4.3 5 4.5"/>',
					'x'              => '<path d="M4 4l16 16"/><path d="M20 4L4 20"/>',
					'pinterest'      => '<circle cx="12" cy="12" r="9"/><path d="M11 21l2-8"/><path d="M10.5 13.5a3.5 3.5 0 1 0-1.5-2.9"/>',
					'linkedin'       => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7"/><path d="M8 7v.01"/><path d="M12 17v-4a2 2 0 0 1 4 0v4"/><path d="M12 10v7"/>',
					'whatsapp'       => '<path d="M3.5 20.5l1.3-4A8.5 8.5 0 1 1 8 19.6z"/><path d="M9 9.5a5.5 5.5 0 0 0 5.5 5.5l1-1.5-2-1-1 1a4 4 0 0 1-2-2l1-1-1-2z"/>',
					'telegram'       => '<path d="M21 4L3 11l6 2 2 6 3-4 4 3z"/><path d="M9 13l12-9"/>',
					'vk'             => '<path d="M3 7c1 6 4.5 10 9.5 10v-4c1.8.3 3.3 1.7 4 4h3.5c-.8-3-3-5-4.5-5.5 1.5-.9 3.1-2.9 3.5-4.5h-3.3c-.6 2-2 3.8-3.2 4.2V7H9.4v8C7.6 14.5 6 12 5.7 7z"/>',
				)
			);
		}

		return $paths;
	}
}

if ( ! function_exists( 'lafka_icon' ) ) {
	/**
	 * A static, decorative inline SVG (aria-hidden; label the parent control).
	 *
	 * @param string $name Icon name (lafka_icon_paths()).
	 * @param int    $size Rendered size in px.
	 * @return string SVG markup, or '' for an unknown name.
	 */
	function lafka_icon( string $name, int $size = 20 ): string {
		$paths = lafka_icon_paths();
		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		return '<svg class="lafka-icon lafka-icon--' . esc_attr( $name ) . '" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}
}

if ( ! function_exists( 'lafka_icon_css_names' ) ) {
	/**
	 * The icons stylesheets draw with CSS masks (pseudo-elements).
	 *
	 * @return list<string>
	 */
	function lafka_icon_css_names(): array {
		return (array) apply_filters(
			'lafka_icon_css_names',
			array( 'alert', 'check-circle', 'eye', 'sort', 'refresh', 'expand', 'clock', 'chevron-up', 'user', 'edit', 'chevrons-right', 'heart-pulse', 'scale', 'facebook', 'x', 'pinterest', 'linkedin', 'whatsapp', 'telegram', 'mail', 'vk' )
		);
	}
}

if ( ! function_exists( 'lafka_icon_css_properties' ) ) {
	/**
	 * `:root` custom properties holding each CSS-drawn icon as an SVG data URI
	 * (black strokes: a mask only reads alpha).
	 *
	 * @return string CSS.
	 */
	function lafka_icon_css_properties(): string {
		$paths = lafka_icon_paths();
		$css   = '';
		foreach ( lafka_icon_css_names() as $name ) {
			if ( ! isset( $paths[ $name ] ) || ! preg_match( '/^[a-z0-9-]+$/', $name ) ) {
				continue;
			}
			$svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths[ $name ] . '</svg>';
			$css .= '--lafka-icon-' . $name . ':url("data:image/svg+xml,' . rawurlencode( $svg ) . '");';
		}

		return '' === $css ? '' : ':root{' . $css . '}';
	}
}
