<?php
/**
 * page-menu.php — full menu listing template for the /menu/ slug (v5.86.0).
 *
 * WP template hierarchy: any Page with slug "menu" is automatically rendered
 * by this file. Was previously a content-filter that injected just a
 * category-tile grid; that legacy mode is preserved in page-menu-helpers.php
 * for operators with a custom Customizer config but doesn't ship by default
 * anymore.
 *
 * Emits the full handoff `/#/menu` layout:
 *   1. Page-head — crumbs + h1 + lead
 *   2. Menu controls (partials/menu-controls.php) — fulfilment toggle + search + dietary chips
 *   3. Sticky category chip strip
 *   4. JUMP TO TOC strip
 *   5. Per-category sections with product cards
 *
 * Mirrors woocommerce/archive-product.php's "all" view but runs as a page
 * template (not a WC archive), so it works at any URL (operator can rename
 * the page or change the slug).
 *
 * @package Lafka
 * @since   5.86.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lafka_menu_title = (string) get_theme_mod(
		'lafka_menu_archive_title',
		get_the_title()
	);
	if ( '' === $lafka_menu_title ) {
		$lafka_menu_title = __( 'The full menu', 'lafka' );
	}

	$lafka_menu_lead = (string) get_theme_mod(
		'lafka_menu_archive_lead',
		__( 'Browse everything we make. Tap a category to jump to it or scroll through the whole menu.', 'lafka' )
	);

	// Build the category list: top-level, non-empty product_cat terms in
	// WooCommerce order, minus the excluded "uncategorized" ids, through the
	// `lafka_menu_landing_categories` filter — the shared GX4 helper, so the
	// /menu/ page and the counter homepage always list the same categories.
	$lafka_menu_terms = taxonomy_exists( 'product_cat' ) && function_exists( 'lafka_menu_top_categories' )
		? lafka_menu_top_categories()
		: array();

	// Canonical browse target (f104): the /menu/ page via the shared resolver,
	// so the "Back to all items" reset link matches every other menu CTA rather
	// than diverging to the WC shop archive.
	$lafka_menu_shop_url = lafka_theme_menu_url();
	?>
	<div class="lafka-menu">

		<header class="lafka-menu__header">
			<div class="lafka-container">
				<nav class="lafka-menu__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lafka' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lafka' ); ?></a>
					<span aria-hidden="true">/</span>
					<span><?php echo esc_html( $lafka_menu_title ); ?></span>
				</nav>
				<h1 class="lafka-menu__title"><?php echo esc_html( $lafka_menu_title ); ?></h1>
				<?php if ( '' !== $lafka_menu_lead ) : ?>
					<p class="lafka-menu__lead"><?php echo wp_kses_post( $lafka_menu_lead ); ?></p>
				<?php endif; ?>
			</div>
		</header>

		<div class="lafka-container">
			<?php
			if ( function_exists( 'lafka_render_active_promos' ) ) {
				lafka_render_active_promos( 'menu' );
			}
			get_template_part( 'partials/menu-controls' );
			?>
		</div>

		<?php if ( ! empty( $lafka_menu_terms ) ) : ?>
			<?php
			/*
			 * V3 (audit): the filter-pill strip below (.lafka-menu__cats) is the
			 * single canonical category nav on /menu/. The "Jump to" anchor strip
			 * is a duplicate of it — same in-page category anchors — so it no
			 * longer renders by default. Operators who want the extra in-page TOC
			 * back can re-enable it with:
			 *   add_filter( 'lafka_menu_show_jump_links', '__return_true' );
			 */
			if ( (bool) apply_filters( 'lafka_menu_show_jump_links', false ) ) :
				?>
				<nav class="lafka-menu__toc" aria-label="<?php esc_attr_e( 'Jump to category', 'lafka' ); ?>">
					<div class="lafka-container lafka-menu__toc-inner">
						<span class="lafka-menu__toc-label"><?php esc_html_e( 'Jump to', 'lafka' ); ?></span>
						<ul class="lafka-menu__toc-list" role="list">
							<?php foreach ( $lafka_menu_terms as $lafka_menu_toc_term ) : ?>
								<li>
									<a class="lafka-menu__toc-link" href="#<?php echo esc_attr( 'lafka-menu-cat-' . $lafka_menu_toc_term->slug ); ?>">
										<?php echo esc_html( $lafka_menu_toc_term->name ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</nav>
				<?php
			endif;
			?>

			<nav class="lafka-menu__cats" aria-label="<?php esc_attr_e( 'Categories', 'lafka' ); ?>">
				<div class="lafka-container">
					<ul class="lafka-menu__cats-list" role="list">
						<li>
							<a class="lafka-menu__cat-chip is-active" href="#lafka-menu-all" aria-current="true">
								<?php esc_html_e( 'All', 'lafka' ); ?>
							</a>
						</li>
						<?php foreach ( $lafka_menu_terms as $lafka_menu_term ) : ?>
							<li>
								<a class="lafka-menu__cat-chip" href="<?php echo esc_attr( '#lafka-menu-cat-' . $lafka_menu_term->slug ); ?>">
									<?php echo esc_html( $lafka_menu_term->name ); ?>
									<span class="lafka-menu__cat-count"><?php echo esc_html( (string) $lafka_menu_term->count ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
		<?php endif; ?>

		<div class="lafka-menu__body" id="lafka-menu-all">
			<div class="lafka-container">

				<?php
				// The grouped menu (shared with the shop view of archive-product.php):
				// one section per category, subheadings for subcategories, and the
				// search / filter empty state.
				get_template_part(
					'partials/menu-groups',
					null,
					array(
						'terms'     => $lafka_menu_terms,
						'reset_url' => $lafka_menu_shop_url,
					)
				);
				?>

			</div>
		</div>

	</div>
	<?php
endwhile;

get_footer();
