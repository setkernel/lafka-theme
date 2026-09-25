<?php
/**
 * Partial: the grouped "All" menu — one section per top-level category.
 *
 * Shared by page-menu.php (the /menu/ page) and the WooCommerce shop view in
 * woocommerce/archive-product.php so both surfaces list the same categories in
 * the same (WooCommerce) order, render the same rows and carry the same
 * client-side empty state.
 *
 *   - Every item renders by default. `lafka_menu_group_limit` (Customizer →
 *     Lafka — Menu Landing → Behaviour; 0 = all) caps a group, and the capped
 *     group header then links to its full, paginated category archive.
 *   - A category with subcategories renders a subheading per child term, in
 *     WooCommerce order (`lafka_menu_subheads`, default on). A product filed in
 *     several children appears once, under the first.
 *   - The empty state is always present (hidden) so the search box and the
 *     dietary chips can tell the customer nothing matched, with a reset.
 *
 * $args:
 *   terms      WP_Term[] top-level categories, in display order (required)
 *   reset_url  string    "Back to all items" target
 *
 * @package Lafka
 * @since   7.3.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_mg_terms     = isset( $args['terms'] ) && is_array( $args['terms'] ) ? $args['terms'] : array();
$lafka_mg_reset_url = isset( $args['reset_url'] ) ? (string) $args['reset_url'] : '';
$lafka_mg_default   = max( 0, (int) get_theme_mod( 'lafka_menu_group_limit', 0 ) );
$lafka_mg_subheads  = (bool) apply_filters( 'lafka_menu_subheads', (bool) get_theme_mod( 'lafka_menu_subheads', true ) );
$lafka_mg_counter   = function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'menu', 'counter' );
$lafka_mg_order     = array(
	'menu_order' => 'ASC',
	'title'      => 'ASC',
);
$lafka_mg_rendered  = 0;

foreach ( $lafka_mg_terms as $lafka_mg_term ) :
	if ( ! is_object( $lafka_mg_term ) || ! isset( $lafka_mg_term->slug ) ) {
		continue;
	}
	$lafka_mg_limit = (int) apply_filters( 'lafka_menu_group_limit', $lafka_mg_default, $lafka_mg_term );
	$lafka_mg_query = function_exists( 'wc_get_products' )
		? wc_get_products(
			array(
				'status'     => 'publish',
				'visibility' => 'catalog',
				'limit'      => $lafka_mg_limit > 0 ? $lafka_mg_limit : -1,
				'page'       => 1,
				'paginate'   => true,
				'category'   => array( (string) $lafka_mg_term->slug ),
				// Title breaks menu_order ties (WooCommerce's own default catalog
				// order), so equal-order items never shuffle between requests.
				'orderby'    => $lafka_mg_order,
			)
		)
		: null;
	$lafka_mg_products = ( is_object( $lafka_mg_query ) && isset( $lafka_mg_query->products ) ) ? (array) $lafka_mg_query->products : array();
	$lafka_mg_total    = ( is_object( $lafka_mg_query ) && isset( $lafka_mg_query->total ) ) ? (int) $lafka_mg_query->total : count( $lafka_mg_products );
	if ( empty( $lafka_mg_products ) ) {
		continue;
	}
	++$lafka_mg_rendered;

	// Subsections: child terms in WooCommerce order; items not filed in any
	// child stay at the top of the group without a subheading.
	$lafka_mg_sections = array(
		array(
			'term'     => null,
			'products' => $lafka_mg_products,
		),
	);
	if ( $lafka_mg_subheads && isset( $lafka_mg_term->term_id ) ) {
		$lafka_mg_children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => (int) $lafka_mg_term->term_id,
				'orderby'    => 'menu_order',
			)
		);
		if ( is_array( $lafka_mg_children ) && ! empty( $lafka_mg_children ) ) {
			$lafka_mg_by_id = array();
			foreach ( $lafka_mg_products as $lafka_mg_p ) {
				$lafka_mg_by_id[ (int) $lafka_mg_p->get_id() ] = $lafka_mg_p;
			}
			$lafka_mg_used  = array();
			$lafka_mg_child_sections = array();
			foreach ( $lafka_mg_children as $lafka_mg_child ) {
				$lafka_mg_child_ids = (array) wc_get_products(
					array(
						'status'     => 'publish',
						'visibility' => 'catalog',
						'limit'      => -1,
						'category'   => array( (string) $lafka_mg_child->slug ),
						'orderby'    => $lafka_mg_order,
						'return'     => 'ids',
					)
				);
				$lafka_mg_pick = array();
				foreach ( $lafka_mg_child_ids as $lafka_mg_cid ) {
					$lafka_mg_cid = (int) $lafka_mg_cid;
					if ( isset( $lafka_mg_by_id[ $lafka_mg_cid ] ) && ! isset( $lafka_mg_used[ $lafka_mg_cid ] ) ) {
						$lafka_mg_pick[]                = $lafka_mg_by_id[ $lafka_mg_cid ];
						$lafka_mg_used[ $lafka_mg_cid ] = true;
					}
				}
				if ( $lafka_mg_pick ) {
					$lafka_mg_child_sections[] = array(
						'term'     => $lafka_mg_child,
						'products' => $lafka_mg_pick,
					);
				}
			}
			if ( $lafka_mg_child_sections ) {
				$lafka_mg_loose = array_values(
					array_filter(
						$lafka_mg_products,
						static fn( $p ) => ! isset( $lafka_mg_used[ (int) $p->get_id() ] )
					)
				);
				$lafka_mg_sections = array_merge(
					$lafka_mg_loose ? array(
						array(
							'term'     => null,
							'products' => $lafka_mg_loose,
						),
					) : array(),
					$lafka_mg_child_sections
				);
			}
		}
	}

	$lafka_mg_id = 'lafka-menu-cat-' . $lafka_mg_term->slug;
	?>
	<section class="lafka-menu__group" id="<?php echo esc_attr( $lafka_mg_id ); ?>" aria-labelledby="<?php echo esc_attr( $lafka_mg_id . '-h' ); ?>">
		<header class="lafka-menu__group-head">
			<h2 id="<?php echo esc_attr( $lafka_mg_id . '-h' ); ?>" class="lafka-menu__group-title">
				<?php echo esc_html( $lafka_mg_term->name ); ?>
			</h2>
			<span class="lafka-menu__group-rule" aria-hidden="true"></span>
			<span class="lafka-menu__group-count"><?php echo esc_html( number_format_i18n( $lafka_mg_total ) ); ?></span>
			<?php if ( $lafka_mg_total > count( $lafka_mg_products ) ) : ?>
				<a class="lafka-menu__group-all" href="<?php echo esc_url( get_term_link( $lafka_mg_term ) ); ?>">
					<?php
					printf(
						/* translators: %s: total number of items in this category. */
						esc_html__( 'See all %s items', 'lafka' ),
						esc_html( number_format_i18n( $lafka_mg_total ) )
					);
					?>
				</a>
			<?php endif; ?>
			<?php if ( $lafka_mg_counter && function_exists( 'lafka_category_tagline' ) ) : ?>
				<?php $lafka_mg_tagline = lafka_category_tagline( $lafka_mg_term ); // GX4: the short line, as on the counter home. ?>
				<?php if ( '' !== $lafka_mg_tagline ) : ?>
					<p class="lafka-menu__group-blurb"><?php echo esc_html( $lafka_mg_tagline ); ?></p>
				<?php endif; ?>
			<?php elseif ( '' !== (string) $lafka_mg_term->description ) : ?>
				<p class="lafka-menu__group-blurb"><?php echo wp_kses_post( $lafka_mg_term->description ); ?></p>
			<?php endif; ?>
			<?php
			if ( function_exists( 'lafka_age_notice_html' ) ) {
				echo lafka_age_notice_html( array( $lafka_mg_term ), 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* in lafka_age_notice_html().
			}
			?>
		</header>

		<?php foreach ( $lafka_mg_sections as $lafka_mg_section ) : ?>
			<?php if ( null !== $lafka_mg_section['term'] ) : ?>
				<div class="lafka-menu__sub" data-lafka-menu-sub>
					<h3 class="lafka-menu__subhead" id="<?php echo esc_attr( 'lafka-menu-cat-' . $lafka_mg_section['term']->slug ); ?>">
						<?php echo esc_html( $lafka_mg_section['term']->name ); ?>
					</h3>
			<?php endif; ?>
			<ul class="lafka-menu__grid" role="list">
				<?php foreach ( $lafka_mg_section['products'] as $lafka_arch_p ) : ?>
					<?php require get_template_directory() . '/woocommerce/loop/lafka-product-card.php'; ?>
				<?php endforeach; ?>
			</ul>
			<?php if ( null !== $lafka_mg_section['term'] ) : ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</section>
	<?php
endforeach;

// Client-side empty state (search / dietary chips), or the server one when no
// category rendered at all.
?>
<div class="lafka-menu__empty" data-lafka-menu-empty<?php echo $lafka_mg_rendered > 0 ? ' hidden' : ''; ?>>
	<span class="lafka-menu__empty-icon" aria-hidden="true">🤔</span>
	<h2 class="lafka-menu__empty-title"><?php esc_html_e( 'Nothing matches', 'lafka' ); ?></h2>
	<p class="lafka-menu__empty-hint" data-lafka-menu-empty-hint><?php esc_html_e( 'Try another word, or clear the search and filters to see the whole menu.', 'lafka' ); ?></p>
	<?php if ( $lafka_mg_rendered > 0 ) : ?>
		<button type="button" class="lafka-menu__empty-cta" data-lafka-menu-reset><?php esc_html_e( 'Clear search and filters', 'lafka' ); ?></button>
	<?php elseif ( '' !== $lafka_mg_reset_url ) : ?>
		<a class="lafka-menu__empty-cta" href="<?php echo esc_url( $lafka_mg_reset_url ); ?>"><?php esc_html_e( 'Back to all items', 'lafka' ); ?></a>
	<?php endif; ?>
</div>
<p class="screen-reader-text" data-lafka-menu-status aria-live="polite"></p>
