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

<div id="main" class="lafka-front-page">

	<?php
	// Section order per handoff /design_handoff_peppery_ordering/README.md
	// "Home page" (v5.60.0 rebuild):
	//  1. Hero            — warm bg, 2-col + photo
	//  2. Categories      — "What are you craving?"
	//  3. Customer favs   — top 8 products
	//  4. How it works    — 3-step explainer
	//  5. Visit us        — dark card with hours + CTAs
	//  6. Reviews         — typography-only, no card boxes
	//  7. Final CTA       — "Hungry yet?" dark rounded-xl with red glow
	get_template_part( 'partials/home-hero' );
	if ( function_exists( 'lafka_render_direct_value' ) ) {
		lafka_render_direct_value( 'home' ); // "Order direct & save vs the apps" strip
	}
	get_template_part( 'partials/home-categories' );
	get_template_part( 'partials/home-featured' );
	get_template_part( 'partials/home-how-it-works' );
	get_template_part( 'partials/home-visit' );
	get_template_part( 'partials/home-reviews' );
	get_template_part( 'partials/home-cta-closer' );
	?>

</div>

<?php
get_footer();
