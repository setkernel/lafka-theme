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

	// Build the category list (top-level WC product_cat terms, excluded
	// "uncategorized" + operator-specified exclusions).
	$lafka_menu_terms = array();
	if ( taxonomy_exists( 'product_cat' ) ) {
		$lafka_menu_term_args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'orderby'    => 'menu_order',
		);
		if ( function_exists( 'lafka_uncategorized_excluded_ids' ) ) {
			$lafka_menu_term_args['exclude'] = lafka_uncategorized_excluded_ids();
		}
		$lafka_menu_terms_raw = get_terms( $lafka_menu_term_args );
		if ( ! is_wp_error( $lafka_menu_terms_raw ) ) {
			$lafka_menu_terms = $lafka_menu_terms_raw;
		}
	}

	// Allow operator overrides via the legacy filter for ordering / removal.
	$lafka_menu_terms = (array) apply_filters( 'lafka_menu_landing_categories', $lafka_menu_terms );

	// Canonical browse target (f104): the /menu/ page via the shared resolver,
	// so the "Back to all items" reset link matches every other menu CTA rather
	// than diverging to the WC shop archive.
	$lafka_menu_shop_url = function_exists( 'lafka_get_menu_url' ) ? lafka_get_menu_url() : home_url( '/menu/' );
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

			<nav class="lafka-menu__cats" aria-label="<?php esc_attr_e( 'Categories', 'lafka' ); ?>">
				<div class="lafka-container">
					<ul class="lafka-menu__cats-list" role="list">
						<li>
							<a class="lafka-menu__cat-chip is-active" href="#all">
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

		<div class="lafka-menu__body">
			<div class="lafka-container">

				<?php
				if ( ! empty( $lafka_menu_terms ) ) :
					// The per-group cap is operator-configurable (Customizer mod
					// `lafka_menu_group_limit`, default 24) and filterable per
					// category. The in-page chips only scroll to a section, so any
					// items beyond the cap would otherwise be unreachable here —
					// each truncated group header therefore links out to the full
					// (paginated) category archive via the "See all" link. A
					// paginated query exposes the true category total.
					$lafka_menu_group_limit_default = (int) get_theme_mod( 'lafka_menu_group_limit', 24 );
					foreach ( $lafka_menu_terms as $lafka_menu_group ) :
						$lafka_menu_group_limit = (int) apply_filters( 'lafka_menu_group_limit', $lafka_menu_group_limit_default, $lafka_menu_group );
						$lafka_menu_group_query = function_exists( 'wc_get_products' )
							? wc_get_products(
								array(
									'status'   => 'publish',
									'limit'    => $lafka_menu_group_limit,
									'page'     => 1,
									'paginate' => true,
									'category' => array( $lafka_menu_group->slug ),
									'orderby'  => 'menu_order',
									'order'    => 'ASC',
								)
							)
							: null;
						$lafka_menu_group_products = ( is_object( $lafka_menu_group_query ) && isset( $lafka_menu_group_query->products ) ) ? $lafka_menu_group_query->products : array();
						$lafka_menu_group_total    = ( is_object( $lafka_menu_group_query ) && isset( $lafka_menu_group_query->total ) ) ? (int) $lafka_menu_group_query->total : count( $lafka_menu_group_products );
						if ( empty( $lafka_menu_group_products ) ) {
							continue;
						}
						$lafka_menu_group_id = 'lafka-menu-cat-' . $lafka_menu_group->slug;
						?>
						<section class="lafka-menu__group" id="<?php echo esc_attr( $lafka_menu_group_id ); ?>" aria-labelledby="<?php echo esc_attr( $lafka_menu_group_id . '-h' ); ?>">
							<header class="lafka-menu__group-head">
								<h2 id="<?php echo esc_attr( $lafka_menu_group_id . '-h' ); ?>" class="lafka-menu__group-title">
									<?php echo esc_html( $lafka_menu_group->name ); ?>
								</h2>
								<span class="lafka-menu__group-rule" aria-hidden="true"></span>
								<span class="lafka-menu__group-count"><?php echo esc_html( (string) $lafka_menu_group->count ); ?></span>
								<?php if ( $lafka_menu_group_total > count( $lafka_menu_group_products ) ) : ?>
									<a class="lafka-menu__group-all" href="<?php echo esc_url( get_term_link( $lafka_menu_group ) ); ?>">
										<?php
										printf(
											/* translators: %s: total number of items in this category. */
											esc_html__( 'See all %s items', 'lafka' ),
											esc_html( number_format_i18n( $lafka_menu_group_total ) )
										);
										?>
									</a>
								<?php endif; ?>
								<?php if ( '' !== $lafka_menu_group->description ) : ?>
									<p class="lafka-menu__group-blurb"><?php echo wp_kses_post( $lafka_menu_group->description ); ?></p>
								<?php endif; ?>
							</header>

							<ul class="lafka-menu__grid" role="list">
								<?php foreach ( $lafka_menu_group_products as $lafka_arch_p ) : ?>
									<?php require __DIR__ . '/woocommerce/loop/lafka-product-card.php'; ?>
								<?php endforeach; ?>
							</ul>
						</section>
						<?php
					endforeach;
				else :
					?>
					<div class="lafka-menu__empty" data-lafka-menu-empty>
						<span class="lafka-menu__empty-icon" aria-hidden="true">🤔</span>
						<h3 class="lafka-menu__empty-title"><?php esc_html_e( 'Nothing matches', 'lafka' ); ?></h3>
						<p class="lafka-menu__empty-hint"><?php esc_html_e( 'Try clearing filters or searching for something else.', 'lafka' ); ?></p>
						<a class="lafka-menu__empty-cta" href="<?php echo esc_url( $lafka_menu_shop_url ); ?>">
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
endwhile;

get_footer();
