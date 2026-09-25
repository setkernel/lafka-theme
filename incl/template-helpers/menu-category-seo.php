<?php
/**
 * Category landing content for the menu archives (GX3).
 *
 *   - lafka_menu_term_intro_html(): the category/tag description as a proper
 *     intro block above the grid. Operators write 80–150 words of unique copy
 *     per category; the old `<p class="lafka-menu__lead">` wrapper produced
 *     invalid nested <p> for anything longer than one paragraph.
 *   - lafka_menu_category_faq_items(): the optional per-category FAQ pairs
 *     stored by lafka-plugin (term meta). The plugin also emits the FAQPage
 *     JSON-LD; the theme only renders the visible markup
 *     (partials/menu-category-faq.php).
 *
 * @package Lafka\TemplateHelpers
 * @since   7.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_menu_term_intro_html' ) ) {
	/**
	 * Intro block for a term description, or '' when there is none.
	 *
	 * @param string $description Raw term description (may contain basic HTML).
	 * @return string Sanitised markup.
	 */
	function lafka_menu_term_intro_html( $description ) {
		$description = trim( (string) $description );
		if ( '' === $description || '' === trim( wp_strip_all_tags( $description ) ) ) {
			return '';
		}
		return '<div class="lafka-menu__intro">' . wp_kses_post( wpautop( $description ) ) . '</div>';
	}
}

if ( ! function_exists( 'lafka_menu_category_faq_items' ) ) {
	/**
	 * Filled FAQ pairs for a product category, from lafka-plugin. Empty when
	 * the plugin (or its SEO module) is absent.
	 *
	 * @param int $term_id Product category term ID.
	 * @return array<int, array{q: string, a: string}>
	 */
	function lafka_menu_category_faq_items( $term_id ) {
		$term_id = (int) $term_id;
		if ( $term_id <= 0 || ! function_exists( 'lafka_seo_get_term_faqs' ) ) {
			return array();
		}
		$items = array();
		foreach ( (array) lafka_seo_get_term_faqs( $term_id ) as $row ) {
			$q = is_array( $row ) && isset( $row['q'] ) ? trim( (string) $row['q'] ) : '';
			$a = is_array( $row ) && isset( $row['a'] ) ? trim( (string) $row['a'] ) : '';
			if ( '' === $q || '' === $a ) {
				continue;
			}
			$items[] = array(
				'q' => $q,
				'a' => $a,
			);
		}
		return $items;
	}
}
