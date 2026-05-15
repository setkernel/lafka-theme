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
	// Section order follows UX spec from v5.49.0 design pass:
	//  1. Hero — convert in 2 seconds
	//  2. Trust strip — dark band, breaks monotone, addresses "are you open?"
	//  3. Categories — primary menu funnel
	//  4. Featured products — social proof + direct add
	//  5. Story — local-brand differentiation
	//  6. Reviews — social proof (only renders if operator entered any)
	//  7. CTA closer — yellow band, catches scrollers who didn't tap above
	get_template_part( 'partials/home-hero' );
	get_template_part( 'partials/home-trust-strip' );
	get_template_part( 'partials/home-categories' );
	get_template_part( 'partials/home-featured' );
	get_template_part( 'partials/home-story' );
	get_template_part( 'partials/home-reviews' );
	get_template_part( 'partials/home-cta-closer' );
	?>

</main>

<?php
get_footer();
