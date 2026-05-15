<?php
/**
 * Lafka front-page template
 *
 * Static-front-page template. Renders the Phase B native home page —
 * hero, category quick-pick, featured products, trust strip — without
 * any page-builder dependency. Reads all content from Customizer panels
 * + WC settings with sensible defaults so the OSS bundle ships ready.
 *
 * The operator's static page assigned to "Front page" still exists in
 * the database; this template just doesn't render its post_content.
 * To revert to page.php rendering, remove this file.
 *
 * @package Lafka
 * @since   5.46.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Body content wrapper: skip the legacy .lafka_title_holder + .inner
// container that page.php uses. Our partials manage their own
// containers + breakpoints via the design system.
?>

<main id="main" class="lafka-front-page" role="main">

	<?php
	get_template_part( 'partials/home-hero' );
	get_template_part( 'partials/home-trust-strip' );
	get_template_part( 'partials/home-categories' );
	get_template_part( 'partials/home-featured' );
	?>

</main>

<?php
get_footer();
