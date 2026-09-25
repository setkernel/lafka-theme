<?php
/**
 * Block-editor content styles.
 *
 * WordPress 7.1 always renders the post editor canvas in an iframe, and only
 * styles enqueued on `enqueue_block_assets` are copied into it — anything on
 * `enqueue_block_editor_assets` styles the editor chrome (outer document)
 * only. The theme's editor typography therefore rides `enqueue_block_assets`,
 * gated to the admin so the front end (where the same action also fires) never
 * loads editor CSS. The Customizer-driven typography CSS
 * (lafka_add_custom_gutenberg_css()) is attached in the same callback so it
 * lands in the iframe with its stylesheet.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

add_action( 'enqueue_block_assets', 'lafka_enqueue_gutenberg_styles' );
if ( ! function_exists( 'lafka_enqueue_gutenberg_styles' ) ) {
	/**
	 * Enqueue the editor content styles (block editor + its iframed canvas only).
	 */
	function lafka_enqueue_gutenberg_styles() {
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style( 'lafka_block_editor_assets', get_template_directory_uri() . '/styles/lafka-gutenberg-styles.css', array(), lafka_asset_version( '/styles/lafka-gutenberg-styles.css' ) );

		if ( function_exists( 'lafka_add_custom_gutenberg_css' ) ) {
			lafka_add_custom_gutenberg_css();
		}

		if ( function_exists( 'lafka_typography_enqueue_google_font' ) ) {
			lafka_typography_enqueue_google_font();
		}
	}
}
