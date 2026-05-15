<?php
/**
 * Auto-apply Lafka Contact template to pages with the "contact" or
 * "contact-us" slug — without the operator having to set Page Attributes.
 *
 * Operators who want to opt out of this auto-loading can drop:
 *   add_filter( 'lafka_auto_apply_contact_template', '__return_false' );
 * into a child theme functions.php.
 *
 * @package Lafka
 * @since   5.66.2
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_auto_contact_template' ) ) {
	/**
	 * Hook into template_include to swap in template-contact.php for known
	 * contact page slugs. Runs late enough that page-builder content (the
	 * legacy WPBakery embed on /contact-us/) is bypassed entirely.
	 *
	 * @param string $template Resolved template path.
	 * @return string Filtered template path.
	 */
	function lafka_auto_contact_template( $template ) {
		if ( ! is_page() ) {
			return $template;
		}
		if ( ! (bool) apply_filters( 'lafka_auto_apply_contact_template', true ) ) {
			return $template;
		}

		$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
		$contact_slugs = (array) apply_filters(
			'lafka_contact_page_slugs',
			array( 'contact', 'contact-us', 'contacts' )
		);

		if ( ! in_array( strtolower( $slug ), array_map( 'strtolower', $contact_slugs ), true ) ) {
			return $template;
		}

		// If the page has explicitly assigned a different template, respect it.
		$assigned = (string) get_post_meta( get_queried_object_id(), '_wp_page_template', true );
		if ( '' !== $assigned && 'default' !== $assigned && 'template-contact.php' !== $assigned ) {
			return $template;
		}

		$candidate = get_template_directory() . '/template-contact.php';
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
		return $template;
	}
}
add_filter( 'template_include', 'lafka_auto_contact_template', 99 );
