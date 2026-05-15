<?php
/**
 * WPBakery shortcode stripper (v5.66.3).
 *
 * Per 2026-05-15 decision to "rip WPBakery from every page now" — when
 * the WPBakery plugin is inactive, its [vc_*] shortcodes render as
 * literal text in the_content. This filter unwraps known layout
 * shortcodes and drops purely-decorative ones so operator content
 * displays cleanly without WPBakery installed.
 *
 * Operator content stays in the DB unchanged — when they want to
 * re-author with a different builder (or plain HTML), they can edit
 * the page normally.
 *
 * Operators who want to keep WPBakery active can disable this filter:
 *   add_filter( 'lafka_strip_wpbakery', '__return_false' );
 *
 * @package Lafka
 * @since   5.66.3
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_strip_wpbakery_shortcodes' ) ) {
	/**
	 * Strip / unwrap WPBakery shortcodes from rendered content.
	 *
	 * Strategy:
	 *  1. Decorative shortcodes (separators, empty spaces, sliders we don't
	 *     have a plugin for) → removed entirely.
	 *  2. Layout wrappers ([vc_row], [vc_column], [vc_section],
	 *     [vc_column_text]) → unwrapped (tags removed, inner content kept).
	 *  3. Unknown [vc_*] / [foodmenu_*] shortcodes → removed (no plugin to
	 *     render them).
	 *
	 * @param string $content Post content from the_content / get_the_content.
	 * @return string Filtered content.
	 */
	function lafka_strip_wpbakery_shortcodes( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}
		if ( false === strpos( $content, '[vc_' ) && false === strpos( $content, '[foodmenu_' ) && false === strpos( $content, '[woodmart_' ) ) {
			return $content;
		}
		if ( ! (bool) apply_filters( 'lafka_strip_wpbakery', true ) ) {
			return $content;
		}

		// 1. Remove decorative / single-element shortcodes entirely
		//    (with or without parameters, self-closing variants too).
		$drop = array(
			'vc_separator',
			'vc_empty_space',
			'vc_btn',
			'vc_single_image',
			'vc_video',
			'vc_googlemaps', // legacy map embed — replaced by our pin card
			'vc_gmaps',
			'vc_widget_sidebar',
			'vc_raw_html',
			'vc_raw_js',
			'vc_facebook',
			'vc_tweetmeme',
			'vc_pinterest',
			'vc_gplus',
			'vc_progress_bar',
			'vc_pie',
			'vc_round_chart',
			'vc_line_chart',
			'vc_message',
			'vc_cta',
			'vc_icon',
			'foodmenu_categories',
			'foodmenu_item',
			'woodmart_button',
			'woodmart_title',
			'woodmart_info_box',
		);
		foreach ( $drop as $tag ) {
			$pattern = '/\[' . preg_quote( $tag, '/' ) . '(\s[^\]]*)?\](?:.*?\[\/' . preg_quote( $tag, '/' ) . '\])?/is';
			$content = (string) preg_replace( $pattern, '', $content );
		}

		// 2. Unwrap layout containers — drop open + close tags but keep inner.
		$unwrap = array(
			'vc_row',
			'vc_row_inner',
			'vc_column',
			'vc_column_inner',
			'vc_section',
			'vc_column_text',
			'vc_text_separator',
			'vc_tta_section',
			'vc_tta_tabs',
			'vc_tta_tour',
			'vc_tta_accordion',
			'vc_tabs',
			'vc_tab',
			'vc_accordion',
			'vc_accordion_tab',
		);
		foreach ( $unwrap as $tag ) {
			// Open tag with optional attributes
			$content = (string) preg_replace( '/\[' . preg_quote( $tag, '/' ) . '(\s[^\]]*)?\]/i', '', $content );
			// Close tag
			$content = (string) preg_replace( '/\[\/' . preg_quote( $tag, '/' ) . '\]/i', '', $content );
		}

		// 3. Catch-all for any [vc_*] shortcodes we didn't list — drop them.
		// (Avoids the literal "[vc_foo]..." text bleeding into rendered content.)
		$content = (string) preg_replace( '/\[\/?vc_[a-z0-9_-]+(\s[^\]]*)?\]/i', '', $content );

		// Collapse runs of empty whitespace lines left behind.
		$content = (string) preg_replace( "/(?:\s*\n){3,}/", "\n\n", $content );

		return $content;
	}
}
add_filter( 'the_content', 'lafka_strip_wpbakery_shortcodes', 5 );
