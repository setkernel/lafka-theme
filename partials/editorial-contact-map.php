<?php
/**
 * Partial: Editorial Contact — map column.
 *
 * Map embed URL from Customizer: lafka_editorial_contact_map_embed_url.
 * Lazy-loaded iframe for performance. Renders nothing if no URL is configured.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_map_url = get_theme_mod( 'lafka_editorial_contact_map_embed_url', '' );

if ( ! $lafka_map_url ) {
	return;
}
?>
<div class="contact-map-frame">
	<iframe
		src="<?php echo esc_url( $lafka_map_url ); ?>"
		loading="lazy"
		referrerpolicy="no-referrer-when-downgrade"
		title="<?php esc_attr_e( 'Location map', 'lafka' ); ?>"
		allowfullscreen
	></iframe>
</div>
