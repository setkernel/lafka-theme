<?php
/**
 * Counter layout: the homepage.
 *
 *   hero co-stars → today's deals → the two co-star categories → every other
 *   category in WooCommerce order (per-section limits + jump index) → find us
 *
 * Section order is filterable (`lafka_counter_home_sections`); each section is
 * bracketed by `lafka_counter_before_section` / `lafka_counter_after_section`.
 * Data is cached (lafka_counter_sections()) and the page's products are primed
 * in bulk before rendering.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_home_sections = lafka_counter_sections();
$lafka_home_settings = lafka_counter_settings();

$lafka_home_ids = array();
if ( $lafka_home_sections['deals'] ) {
	$lafka_home_ids = array_merge( $lafka_home_ids, $lafka_home_sections['deals']['ids'] );
}
foreach ( array_merge( $lafka_home_sections['costars'], $lafka_home_sections['rest'] ) as $lafka_home_block ) {
	$lafka_home_ids = array_merge( $lafka_home_ids, $lafka_home_block['ids'] );
}
lafka_counter_prime( $lafka_home_ids );

$lafka_home_args = array(
	'sections' => $lafka_home_sections,
	'settings' => $lafka_home_settings,
);
?>
<div id="main" class="lafka-front-page lafka-counter-home">
	<?php
	foreach ( (array) apply_filters( 'lafka_counter_home_sections', array( 'hero', 'deals', 'costars', 'menu', 'find-us' ) ) as $lafka_home_slug ) {
		$lafka_home_slug = sanitize_key( (string) $lafka_home_slug );
		do_action( 'lafka_counter_before_section', $lafka_home_slug, $lafka_home_args );
		get_template_part( 'partials/counter/' . ( 'menu' === $lafka_home_slug ? 'menu-rest' : $lafka_home_slug ), null, $lafka_home_args );
		do_action( 'lafka_counter_after_section', $lafka_home_slug, $lafka_home_args );
	}
	?>
</div>
