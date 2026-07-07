<?php
/**
 * Product archive — handoff-spec rebuild (v5.61.0).
 *
 * Replaces the WC stock template with a clean handoff layout per
 * /design_handoff_peppery_ordering/README.md "Menu page (/menu, /menu/:cat)".
 *
 * Handles three views:
 *   - Shop page (is_shop() — flat or grouped-by-category)
 *   - Category archive (is_product_category() — flat in one category)
 *   - Tag archive (is_product_tag() — flat in one tag)
 *
 * Intentionally suppresses ALL of WC's `woocommerce_before_main_content`,
 * `woocommerce_after_main_content`, and product-loop hooks: the handoff
 * layout supplies its own wrapper, page header, loop/grid, and pagination,
 * so WC's default wrapper/breadcrumb/sidebar/loop callbacks are not run on
 * the shop/category/tag archives. None of these `do_action()` calls fire
 * here by design. (Third-party integrations that rely on those hooks will
 * therefore not run on these archives; re-introducing them would require
 * firing the actions after removing WC's default wrapper callbacks plus the
 * loop/structured-data hooks — out of scope of this template.)
 *
 * @package Lafka
 * @since   5.61.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

// GA4 view_item_list: this template deliberately suppresses
// woocommerce_before_main_content (see the docblock above), which is the only
// hook the plugin's priority-5 emit (lafka_dl_emit_view_item_list) listens on.
// Without this call the event never fires on shop/category/tag archives. Call
// the emit directly rather than re-firing the action so we don't re-introduce
// the breadcrumb/sidebar callbacks the redesign intentionally dropped. The
// emit self-guards on is_shop()/is_product_category()/is_product_tag().
if ( function_exists( 'lafka_dl_emit_view_item_list' ) ) {
	lafka_dl_emit_view_item_list();
}

$lafka_arch_is_shop    = function_exists( 'is_shop' ) ? is_shop() : false;
$lafka_arch_is_cat     = function_exists( 'is_product_category' ) ? is_product_category() : false;
$lafka_arch_is_tag     = function_exists( 'is_product_tag' ) ? is_product_tag() : false;
$lafka_arch_queried    = get_queried_object();

$lafka_arch_title = '';
$lafka_arch_lead  = '';
if ( $lafka_arch_is_cat || $lafka_arch_is_tag ) {
	$lafka_arch_title = ( $lafka_arch_queried && isset( $lafka_arch_queried->name ) ) ? (string) $lafka_arch_queried->name : '';
	$lafka_arch_lead  = ( $lafka_arch_queried && isset( $lafka_arch_queried->description ) ) ? (string) $lafka_arch_queried->description : '';
} else {
	$lafka_arch_title = (string) get_theme_mod( 'lafka_menu_archive_title', __( 'The full menu', 'lafka' ) );
	$lafka_arch_lead  = (string) get_theme_mod(
		'lafka_menu_archive_lead',
		__( 'Browse everything we make. Tap a category to jump to it or scroll through the whole menu.', 'lafka' )
	);
}

// All categories for the sticky chip strip.
$lafka_arch_terms = array();
if ( taxonomy_exists( 'product_cat' ) ) {
	$lafka_arch_term_args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		// Top-level sections only — mirrors page-menu.php so both menu surfaces
		// show the same category set, and so a product assigned to both a parent
		// and a child term is not rendered twice (the per-group query already
		// pulls in descendant products).
		'parent'     => 0,
		'orderby'    => 'count',
		'order'      => 'DESC',
	);
	if ( function_exists( 'lafka_uncategorized_excluded_ids' ) ) {
		$lafka_arch_term_args['exclude'] = lafka_uncategorized_excluded_ids();
	}
	$lafka_arch_terms_raw = get_terms( $lafka_arch_term_args );
	if ( ! is_wp_error( $lafka_arch_terms_raw ) ) {
		$lafka_arch_terms = $lafka_arch_terms_raw;
	}
}
$lafka_arch_current_slug = ( $lafka_arch_is_cat && $lafka_arch_queried && isset( $lafka_arch_queried->slug ) ) ? (string) $lafka_arch_queried->slug : 'all';
// Canonical browse target (f104): the /menu/ page via the shared resolver. Used
// below by the breadcrumb "Menu" crumb (kept in lockstep with the JSON-LD
// breadcrumb), the "All" category chip, and the empty-state reset link — all now
// point at the same /menu/ URL rather than diverging to the WC shop archive.
$lafka_arch_shop_url = lafka_theme_menu_url();
?>
<div class="lafka-menu">

	<header class="lafka-menu__header">
		<div class="lafka-container">
			<nav class="lafka-menu__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lafka' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lafka' ); ?></a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( $lafka_arch_shop_url ); ?>"><?php esc_html_e( 'Menu', 'lafka' ); ?></a>
				<?php if ( $lafka_arch_is_cat || $lafka_arch_is_tag ) : ?>
					<span aria-hidden="true">/</span>
					<span><?php echo esc_html( $lafka_arch_title ); ?></span>
				<?php endif; ?>
			</nav>
			<h1 class="lafka-menu__title"><?php echo esc_html( $lafka_arch_title ); ?></h1>
			<?php if ( '' !== $lafka_arch_lead ) : ?>
				<p class="lafka-menu__lead"><?php echo wp_kses_post( $lafka_arch_lead ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<?php
	// v5.68.0: menu controls (fulfilment toggle + search + dietary filter chips).
	// v5.74.0: wrapped in .lafka-container so controls sit within page gutter.
	?>
	<div class="lafka-container">
		<?php get_template_part( 'partials/menu-controls' ); ?>
	</div>

	<?php
	// v5.74.0: JUMP TO anchor strip — second horizontal nav for fast-scroll.
	if ( $lafka_arch_is_shop && ! empty( $lafka_arch_terms ) ) :
		?>
		<nav class="lafka-menu__toc" aria-label="<?php esc_attr_e( 'Jump to category', 'lafka' ); ?>">
			<div class="lafka-container lafka-menu__toc-inner">
				<span class="lafka-menu__toc-label"><?php esc_html_e( 'Jump to', 'lafka' ); ?></span>
				<ul class="lafka-menu__toc-list" role="list">
					<?php foreach ( $lafka_arch_terms as $lafka_arch_toc_term ) : ?>
						<li>
							<a class="lafka-menu__toc-link" href="#<?php echo esc_attr( 'lafka-menu-cat-' . $lafka_arch_toc_term->slug ); ?>">
								<?php echo esc_html( $lafka_arch_toc_term->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</nav>
		<?php
	endif;
	?>

	<?php if ( ! empty( $lafka_arch_terms ) ) : ?>
		<nav class="lafka-menu__cats" aria-label="<?php esc_attr_e( 'Categories', 'lafka' ); ?>">
			<div class="lafka-container">
				<ul class="lafka-menu__cats-list" role="list">
					<li>
						<a
							class="lafka-menu__cat-chip<?php echo 'all' === $lafka_arch_current_slug ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $lafka_arch_shop_url ); ?>"
						>
							<?php esc_html_e( 'All', 'lafka' ); ?>
						</a>
					</li>
					<?php
                    foreach ( $lafka_arch_terms as $lafka_arch_term ) :
						$lafka_arch_active = $lafka_arch_term->slug === $lafka_arch_current_slug;
						?>
						<li>
							<a
								class="lafka-menu__cat-chip<?php echo $lafka_arch_active ? ' is-active' : ''; ?>"
								href="<?php echo esc_url( get_term_link( $lafka_arch_term ) ); ?>"
							>
								<?php echo esc_html( $lafka_arch_term->name ); ?>
								<span class="lafka-menu__cat-count"><?php echo esc_html( (string) $lafka_arch_term->count ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</nav>
	<?php endif; ?>

	<div class="lafka-menu__body">
		<div class="lafka-container">

			<?php
			if ( $lafka_arch_is_shop && ! empty( $lafka_arch_terms ) ) :
				// "All" view — render items grouped by category. The per-group cap
				// is operator-configurable (Customizer mod `lafka_menu_group_limit`,
				// default 24) and filterable per category; any items beyond the cap
				// stay reachable via the "See all" link in the group header. Use a
				// paginated query so the true category total is known regardless of
				// the cap.
				$lafka_arch_group_limit_default = (int) get_theme_mod( 'lafka_menu_group_limit', 24 );
				foreach ( $lafka_arch_terms as $lafka_arch_group ) :
					$lafka_arch_group_limit = (int) apply_filters( 'lafka_menu_group_limit', $lafka_arch_group_limit_default, $lafka_arch_group );
					$lafka_arch_group_query = wc_get_products(
						array(
							'status'   => 'publish',
							'limit'    => $lafka_arch_group_limit,
							'page'     => 1,
							'paginate' => true,
							'category' => array( $lafka_arch_group->slug ),
							'orderby'  => 'menu_order',
							'order'    => 'ASC',
						)
					);
					$lafka_arch_group_products = ( is_object( $lafka_arch_group_query ) && isset( $lafka_arch_group_query->products ) ) ? $lafka_arch_group_query->products : array();
					$lafka_arch_group_total    = ( is_object( $lafka_arch_group_query ) && isset( $lafka_arch_group_query->total ) ) ? (int) $lafka_arch_group_query->total : count( $lafka_arch_group_products );
					if ( empty( $lafka_arch_group_products ) ) {
						continue;
					}
					$lafka_arch_group_id = 'lafka-menu-cat-' . $lafka_arch_group->slug;
					?>
					<section class="lafka-menu__group" id="<?php echo esc_attr( $lafka_arch_group_id ); ?>" aria-labelledby="<?php echo esc_attr( $lafka_arch_group_id . '-h' ); ?>">
						<header class="lafka-menu__group-head">
							<h2 id="<?php echo esc_attr( $lafka_arch_group_id . '-h' ); ?>" class="lafka-menu__group-title">
								<?php echo esc_html( $lafka_arch_group->name ); ?>
							</h2>
							<span class="lafka-menu__group-rule" aria-hidden="true"></span>
							<span class="lafka-menu__group-count"><?php echo esc_html( (string) $lafka_arch_group->count ); ?></span>
							<?php if ( $lafka_arch_group_total > count( $lafka_arch_group_products ) ) : ?>
								<a class="lafka-menu__group-all" href="<?php echo esc_url( get_term_link( $lafka_arch_group ) ); ?>">
									<?php
									printf(
										/* translators: %s: total number of items in this category. */
										esc_html__( 'See all %s items', 'lafka' ),
										esc_html( number_format_i18n( $lafka_arch_group_total ) )
									);
									?>
								</a>
							<?php endif; ?>
							<?php if ( '' !== $lafka_arch_group->description ) : ?>
								<p class="lafka-menu__group-blurb"><?php echo wp_kses_post( $lafka_arch_group->description ); ?></p>
							<?php endif; ?>
						</header>

						<ul class="lafka-menu__grid" role="list">
							<?php foreach ( $lafka_arch_group_products as $lafka_arch_p ) : ?>
								<?php require __DIR__ . '/loop/lafka-product-card.php'; ?>
							<?php endforeach; ?>
						</ul>
					</section>
					<?php
                endforeach;
			elseif ( woocommerce_product_loop() && have_posts() ) :
				?>
				<ul class="lafka-menu__grid" role="list">
					<?php
                    while ( have_posts() ) :
						the_post();
						global $product;
						$lafka_arch_p = $product;
						?>
						<?php require __DIR__ . '/loop/lafka-product-card.php'; ?>
					<?php endwhile; ?>
				</ul>
			<?php else : ?>
				<div class="lafka-menu__empty" data-lafka-menu-empty>
					<span class="lafka-menu__empty-icon" aria-hidden="true">🤔</span>
					<h3 class="lafka-menu__empty-title"><?php esc_html_e( 'Nothing matches', 'lafka' ); ?></h3>
					<p class="lafka-menu__empty-hint"><?php esc_html_e( 'Try clearing filters or searching for something else.', 'lafka' ); ?></p>
					<a class="lafka-menu__empty-cta" href="<?php echo esc_url( $lafka_arch_shop_url ); ?>">
						<?php esc_html_e( 'Back to all items', 'lafka' ); ?>
					</a>
				</div>
				<?php
			endif;
			?>

		</div>
	</div>

</div>

<?php
get_footer( 'shop' );
