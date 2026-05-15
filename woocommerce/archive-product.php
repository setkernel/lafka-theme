<?php
/**
 * Product archive — handoff-spec rebuild (v5.61.0).
 *
 * Replaces the WC stock template with a clean handoff layout per
 * /design_handoff_peppery_ordering/README.md "Menu page (/menu, /menu/:cat)".
 *
 * Legacy template preserved as archive-product-legacy.php.
 *
 * Handles three views:
 *   - Shop page (is_shop() — flat or grouped-by-category)
 *   - Category archive (is_product_category() — flat in one category)
 *   - Tag archive (is_product_tag() — flat in one tag)
 *
 * Suppresses WC's `woocommerce_before_main_content` / `_after` / loop
 * hooks because the handoff layout supplies its own page header, ordering,
 * and pagination — but keeps `woocommerce_after_main_content` for trailing
 * structured data + integrations that hook there.
 *
 * @package Lafka
 * @since   5.61.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

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
$lafka_arch_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/menu/' );
?>
<main id="main" class="lafka-menu" role="main">

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
				// "All" view — render items grouped by category.
				foreach ( $lafka_arch_terms as $lafka_arch_group ) :
					$lafka_arch_group_products = wc_get_products(
						array(
							'status'   => 'publish',
							'limit'    => 24,
							'category' => array( $lafka_arch_group->slug ),
							'orderby'  => 'menu_order',
							'order'    => 'ASC',
						)
					);
					if ( empty( $lafka_arch_group_products ) ) {
						continue;
					}
					$lafka_arch_group_id = 'lafka-menu-cat-' . $lafka_arch_group->slug;
					?>
					<section class="lafka-menu__group" id="<?php echo esc_attr( $lafka_arch_group_id ); ?>" aria-labelledby="<?php echo esc_attr( $lafka_arch_group_id . '-h' ); ?>">
						<header class="lafka-menu__group-head">
							<h2 id="<?php echo esc_attr( $lafka_arch_group_id . '-h' ); ?>" class="lafka-menu__group-title">
								<?php echo esc_html( $lafka_arch_group->name ); ?>
								<span class="lafka-menu__group-count"><?php echo esc_html( (string) $lafka_arch_group->count ); ?></span>
							</h2>
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
				<div class="lafka-menu__empty">
					<span class="lafka-menu__empty-icon" aria-hidden="true">🤔</span>
					<h3 class="lafka-menu__empty-title"><?php esc_html_e( 'Nothing here yet', 'lafka' ); ?></h3>
					<p class="lafka-menu__empty-hint"><?php esc_html_e( "We couldn't find any items in this category.", 'lafka' ); ?></p>
					<a class="lafka-menu__empty-cta" href="<?php echo esc_url( $lafka_arch_shop_url ); ?>">
						<?php esc_html_e( 'Back to all items', 'lafka' ); ?>
					</a>
				</div>
				<?php
			endif;
			?>

		</div>
	</div>

</main>

<?php
get_footer( 'shop' );
