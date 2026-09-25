<?php
/**
 * Product archive — handoff-spec rebuild (v5.61.0).
 *
 * Replaces the WC stock template with a clean handoff layout per
 * /design_handoff_peppery_ordering/README.md "Menu page (/menu, /menu/:cat)".
 *
 * Handles four views:
 *   - Product search (is_search() — WooCommerce also reports is_shop() for a
 *     `post_type=product` search, so search is checked FIRST): the query is
 *     echoed and prefilled, matching items render as menu rows with a count,
 *     and a no-result search gets a real empty state.
 *   - Shop page (is_shop() — grouped by category via partials/menu-groups.php,
 *     the same partial /menu/ renders; usually redirected to /menu/, see
 *     lafka_shop_to_menu_redirect()).
 *   - Category archive (is_product_category() — flat, paginated, with its
 *     subcategories linked).
 *   - Tag archive (is_product_tag() — flat, paginated).
 *
 * Intentionally suppresses ALL of WC's `woocommerce_before_main_content`,
 * `woocommerce_after_main_content`, and product-loop hooks: the handoff
 * layout supplies its own wrapper, page header, loop/grid, and pagination,
 * so WC's default wrapper/breadcrumb/sidebar/loop callbacks are not run on
 * the shop/category/tag archives. None of these `do_action()` calls fire
 * here by design. (Third-party integrations that rely on those hooks will
 * therefore not run on these archives; re-introducing them would require
 * firing the actions after removing WC's default wrapper callbacks plus the
 * loop/structured-data hooks — out of scope of this template.) The one
 * core behaviour kept is the store notice output WooCommerce hangs on
 * woocommerce_before_shop_loop, printed at the top of the body below.
 *
 * Reviewed against WooCommerce core archive-product.php 8.6.0.
 *
 * @package Lafka
 * @since   5.61.0
 * @version 8.6.0
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

$lafka_arch_is_search = function_exists( 'is_search' ) && is_search();
$lafka_arch_is_shop   = ! $lafka_arch_is_search && function_exists( 'is_shop' ) && is_shop();
$lafka_arch_is_cat    = function_exists( 'is_product_category' ) ? is_product_category() : false;
$lafka_arch_is_tag    = function_exists( 'is_product_tag' ) ? is_product_tag() : false;
$lafka_arch_queried   = get_queried_object();
$lafka_arch_query_s   = $lafka_arch_is_search && function_exists( 'get_search_query' ) ? (string) get_search_query() : '';
$lafka_arch_found     = ( $lafka_arch_is_search && isset( $GLOBALS['wp_query'] ) && is_object( $GLOBALS['wp_query'] ) && isset( $GLOBALS['wp_query']->found_posts ) ) ? (int) $GLOBALS['wp_query']->found_posts : 0;

$lafka_arch_title = '';
$lafka_arch_lead  = '';
$lafka_arch_intro = '';
if ( $lafka_arch_is_search ) {
	$lafka_arch_title = '' !== $lafka_arch_query_s
		/* translators: %s: the customer's search words. */
		? sprintf( __( 'Results for “%s”', 'lafka' ), $lafka_arch_query_s )
		: __( 'Search the menu', 'lafka' );
	$lafka_arch_lead = $lafka_arch_found > 0
		/* translators: %s: number of matching menu items. */
		? sprintf( _n( '%s item on the menu matches.', '%s items on the menu match.', $lafka_arch_found, 'lafka' ), number_format_i18n( $lafka_arch_found ) )
		: '';
} elseif ( $lafka_arch_is_cat || $lafka_arch_is_tag ) {
	$lafka_arch_title = ( $lafka_arch_queried && isset( $lafka_arch_queried->name ) ) ? (string) $lafka_arch_queried->name : '';
	// GX3: the term description is the category's landing copy (often several
	// paragraphs) — rendered as an intro block, not squeezed into the one-line
	// <p> lead the shop view uses.
	$lafka_arch_desc  = ( $lafka_arch_queried && isset( $lafka_arch_queried->description ) ) ? (string) $lafka_arch_queried->description : '';
	$lafka_arch_intro = function_exists( 'lafka_menu_term_intro_html' ) ? lafka_menu_term_intro_html( $lafka_arch_desc ) : '';
} else {
	$lafka_arch_title = (string) get_theme_mod( 'lafka_menu_archive_title', __( 'The full menu', 'lafka' ) );
	if ( '' === $lafka_arch_title ) {
		$lafka_arch_title = __( 'The full menu', 'lafka' );
	}
	$lafka_arch_lead = (string) get_theme_mod(
		'lafka_menu_archive_lead',
		__( 'Browse everything we make. Tap a category to jump to it or scroll through the whole menu.', 'lafka' )
	);
}

// Top-level categories in WooCommerce order — the SAME list /menu/ and the
// counter homepage use (lafka_menu_top_categories()).
$lafka_arch_terms = ( taxonomy_exists( 'product_cat' ) && function_exists( 'lafka_menu_top_categories' ) ) ? lafka_menu_top_categories() : array();

// Breadcrumb trail below "Menu": the category's ancestors (top first), so a
// subcategory reads Home / Menu / Pizza / Vegan pizzas like the PDP and the
// JSON-LD BreadcrumbList.
$lafka_arch_ancestors = array();
if ( $lafka_arch_is_cat && $lafka_arch_queried && isset( $lafka_arch_queried->term_id ) && function_exists( 'get_ancestors' ) ) {
	foreach ( array_reverse( (array) get_ancestors( (int) $lafka_arch_queried->term_id, 'product_cat', 'taxonomy' ) ) as $lafka_arch_anc_id ) {
		$lafka_arch_anc = function_exists( 'get_term' ) ? get_term( (int) $lafka_arch_anc_id, 'product_cat' ) : null;
		if ( is_object( $lafka_arch_anc ) && ! is_wp_error( $lafka_arch_anc ) ) {
			$lafka_arch_ancestors[] = $lafka_arch_anc;
		}
	}
}

// The active chip is the top-level section the archive belongs to.
$lafka_arch_current_slug = 'all';
if ( $lafka_arch_is_cat && $lafka_arch_queried && isset( $lafka_arch_queried->slug ) ) {
	$lafka_arch_current_slug = $lafka_arch_ancestors ? (string) $lafka_arch_ancestors[0]->slug : (string) $lafka_arch_queried->slug;
} elseif ( $lafka_arch_is_search || $lafka_arch_is_tag ) {
	$lafka_arch_current_slug = '';
}

// Subcategories of the viewed category (a parent category links its children).
$lafka_arch_children = array();
if ( $lafka_arch_is_cat && $lafka_arch_queried && isset( $lafka_arch_queried->term_id ) ) {
	$lafka_arch_children_raw = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => (int) $lafka_arch_queried->term_id,
			'orderby'    => 'menu_order',
		)
	);
	if ( is_array( $lafka_arch_children_raw ) ) {
		$lafka_arch_children = $lafka_arch_children_raw;
	}
}

// Canonical browse target (f104): the /menu/ page via the shared resolver. Used
// below by the breadcrumb "Menu" crumb (kept in lockstep with the JSON-LD
// breadcrumb), the "All" category chip, and the empty-state reset link — all now
// point at the same /menu/ URL rather than diverging to the WC shop archive.
$lafka_arch_shop_url = lafka_theme_menu_url();
?>
<div class="lafka-menu<?php echo $lafka_arch_is_search ? ' lafka-menu--search' : ''; ?>">

	<header class="lafka-menu__header">
		<div class="lafka-container">
			<nav class="lafka-menu__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lafka' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'lafka' ); ?></a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( $lafka_arch_shop_url ); ?>"><?php esc_html_e( 'Menu', 'lafka' ); ?></a>
				<?php foreach ( $lafka_arch_ancestors as $lafka_arch_anc ) : ?>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( get_term_link( $lafka_arch_anc ) ); ?>"><?php echo esc_html( $lafka_arch_anc->name ); ?></a>
				<?php endforeach; ?>
				<?php if ( $lafka_arch_is_cat || $lafka_arch_is_tag ) : ?>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php echo esc_html( $lafka_arch_title ); ?></span>
				<?php elseif ( $lafka_arch_is_search ) : ?>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php esc_html_e( 'Search results', 'lafka' ); ?></span>
				<?php endif; ?>
			</nav>
			<h1 class="lafka-menu__title"><?php echo esc_html( $lafka_arch_title ); ?></h1>
			<?php if ( '' !== $lafka_arch_lead ) : ?>
				<p class="lafka-menu__lead"><?php echo wp_kses_post( $lafka_arch_lead ); ?></p>
			<?php endif; ?>
			<?php
			if ( '' !== $lafka_arch_intro ) {
				echo $lafka_arch_intro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lafka_menu_term_intro_html() returns wp_kses_post()-sanitised markup.
			}
			if ( $lafka_arch_is_cat && function_exists( 'lafka_age_notice_html' ) && $lafka_arch_queried ) {
				echo lafka_age_notice_html( array_merge( $lafka_arch_ancestors, array( $lafka_arch_queried ) ), 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* in lafka_age_notice_html().
			}
			?>
		</div>
	</header>

	<?php
	// v5.68.0: menu controls (fulfilment toggle + search + dietary filter chips).
	// v5.74.0: wrapped in .lafka-container so controls sit within page gutter.
	// On a search results page the search box is a real GET form, prefilled.
	?>
	<div class="lafka-container">
		<?php
		get_template_part(
			'partials/menu-controls',
			null,
			array(
				'search_query' => $lafka_arch_query_s,
				'search_mode'  => $lafka_arch_is_search ? 'server' : 'live',
			)
		);
		?>
	</div>

	<?php if ( ! empty( $lafka_arch_terms ) ) : ?>
		<nav class="lafka-menu__cats" aria-label="<?php esc_attr_e( 'Categories', 'lafka' ); ?>">
			<div class="lafka-container">
				<ul class="lafka-menu__cats-list" role="list">
					<li>
						<a
							class="lafka-menu__cat-chip<?php echo 'all' === $lafka_arch_current_slug ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $lafka_arch_shop_url ); ?>"
							<?php echo 'all' === $lafka_arch_current_slug ? 'aria-current="page"' : ''; ?>
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
								<?php echo $lafka_arch_active ? 'aria-current="page"' : ''; ?>
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

	<div class="lafka-menu__body" id="lafka-menu-all">
		<div class="lafka-container">

			<?php
			// Store notices (e.g. "added to cart" after a non-AJAX add) — core
			// prints these from woocommerce_before_shop_loop, which never fires here.
			if ( function_exists( 'woocommerce_output_all_notices' ) ) {
				woocommerce_output_all_notices();
			}

			if ( ! empty( $lafka_arch_children ) ) :
				?>
				<nav class="lafka-menu__subcats" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: category name. */ __( 'More in %s', 'lafka' ), $lafka_arch_title ) ); ?>">
					<ul class="lafka-menu__subcats-list" role="list">
						<?php foreach ( $lafka_arch_children as $lafka_arch_child ) : ?>
							<li><a class="lafka-menu__subcat" href="<?php echo esc_url( get_term_link( $lafka_arch_child ) ); ?>"><?php echo esc_html( $lafka_arch_child->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
				<?php
			endif;

			if ( $lafka_arch_is_shop && ! empty( $lafka_arch_terms ) ) :
				// "All" view — the same grouped partial as /menu/.
				get_template_part(
					'partials/menu-groups',
					null,
					array(
						'terms'     => $lafka_arch_terms,
						'reset_url' => $lafka_arch_shop_url,
					)
				);
			elseif ( woocommerce_product_loop() && have_posts() ) :
				?>
				<ul class="lafka-menu__grid" role="list">
					<?php
					$lafka_card_heading_level = 2; // Rows sit directly under the page h1 (T-19).
					while ( have_posts() ) :
						the_post();
						global $product;
						$lafka_arch_p = $product;
						?>
						<?php require __DIR__ . '/loop/lafka-product-card.php'; ?>
					<?php endwhile; ?>
				</ul>
				<div class="lafka-menu__empty" data-lafka-menu-empty hidden>
					<span class="lafka-menu__empty-icon" aria-hidden="true">🤔</span>
					<h2 class="lafka-menu__empty-title"><?php esc_html_e( 'Nothing matches', 'lafka' ); ?></h2>
					<p class="lafka-menu__empty-hint"><?php esc_html_e( 'Try another word, or clear the search and filters.', 'lafka' ); ?></p>
					<button type="button" class="lafka-menu__empty-cta" data-lafka-menu-reset><?php esc_html_e( 'Clear search and filters', 'lafka' ); ?></button>
				</div>
				<p class="screen-reader-text" data-lafka-menu-status aria-live="polite"></p>
				<?php
				if ( function_exists( 'lafka_menu_pagination_html' ) ) {
					echo lafka_menu_pagination_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() markup built from escaped URLs.
				}
				// GX3: optional per-category FAQ (lafka-plugin term meta; the
				// plugin emits the FAQPage JSON-LD). Renders nothing when empty.
				if ( $lafka_arch_is_cat && $lafka_arch_queried && isset( $lafka_arch_queried->term_id ) ) {
					get_template_part( 'partials/menu-category-faq', null, array( 'term_id' => (int) $lafka_arch_queried->term_id ) );
				}
				?>
			<?php else : ?>
				<div class="lafka-menu__empty" data-lafka-menu-empty>
					<span class="lafka-menu__empty-icon" aria-hidden="true">🤔</span>
					<?php if ( $lafka_arch_is_search && '' !== $lafka_arch_query_s ) : ?>
						<h2 class="lafka-menu__empty-title">
							<?php
							/* translators: %s: the customer's search words. */
							echo esc_html( sprintf( __( 'Nothing on the menu matches “%s”', 'lafka' ), $lafka_arch_query_s ) );
							?>
						</h2>
						<p class="lafka-menu__empty-hint"><?php esc_html_e( 'Check the spelling, try a shorter word (like “pizza” or “wings”), or browse a category above.', 'lafka' ); ?></p>
					<?php else : ?>
						<h2 class="lafka-menu__empty-title"><?php esc_html_e( 'Nothing matches', 'lafka' ); ?></h2>
						<p class="lafka-menu__empty-hint"><?php esc_html_e( 'Try clearing filters or searching for something else.', 'lafka' ); ?></p>
					<?php endif; ?>
					<a class="lafka-menu__empty-cta" href="<?php echo esc_url( $lafka_arch_shop_url ); ?>">
						<?php esc_html_e( 'See the full menu', 'lafka' ); ?>
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
