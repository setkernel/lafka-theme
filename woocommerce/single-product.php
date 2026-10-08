<?php
/**
 * Single product template — overrides woocommerce/templates/single-product.php.
 *
 * When the PDP-redesign flag is OFF (or the plugin is inactive), renders
 * WooCommerce's default single-product flow inline (identical to core 1.6.4).
 *
 * @package Lafka\WooCommerce
 * @since   5.16.0
 * @version 1.6.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_pdp_redesign_enabled' ) || ! lafka_pdp_redesign_enabled() ) {
	// Redesign OFF — render WooCommerce's default single-product flow inline.
	get_header( 'shop' );
	do_action( 'woocommerce_before_main_content' );

	while ( have_posts() ) {
		the_post();
		wc_get_template_part( 'content', 'single-product' );
	}

	do_action( 'woocommerce_after_main_content' );
	do_action( 'woocommerce_sidebar' );
	get_footer( 'shop' );
	return;
}

/*
 * The redesigned page composes its own layout on WooCommerce's hooks: the
 * Lafka parts are callbacks on woocommerce_single_product_summary and
 * woocommerce_after_single_product_summary (incl/woocommerce/lafka-pdp-summary.php),
 * and every product-page action core fires fires here too, so extensions that
 * hook them (express pay, gift cards, bookings, SEO, analytics) run on it.
 */
if ( function_exists( 'lafka_pdp_compose_summary' ) ) {
	lafka_pdp_compose_summary();
}

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );

global $product;
while ( have_posts() ) :
	the_post();
	?>
<div class="lafka-pdp">
	<?php
	// Core's per-product wrapper id and classes (type, stock, categories) for
	// extensions' selectors — minus the bare `product` class (legacy `div.product` rules style the classic
	// page, not this design) and the classic card's theme classes.
	$lafka_pdp_classes = lafka_wc_core_product_classes( 'lafka-pdp__main', $product, array( 'product' ) );
	?>
	<div id="product-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $lafka_pdp_classes ) ); ?>">
		<?php
		// Product notices (add-to-cart validation errors, the 'redirect to product
		// after add' success notice) print from woocommerce_output_all_notices on
		// this action. Kept inside the styled wrapper, above the breadcrumb.
		do_action( 'woocommerce_before_single_product' );
		?>

		<nav class="lafka-pdp__breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'lafka' ); ?>"><?php woocommerce_breadcrumb(); ?></nav>

		<div class="lafka-pdp__hero">
			<div class="lafka-pdp__gallery">
				<?php
				// Core's gallery (priority 20) plus anything attached here, e.g. the
				// GA4 view_item event (lafka-plugin, priority 5).
				do_action( 'woocommerce_before_single_product_summary' );
				?>
			</div>
			<?php require get_template_directory() . '/partials/pdp-summary.php'; ?>
		</div>

		<?php
		// "Make it a meal", the ingredients + reviews cards, upsells and related
		// products — each a callback on this action.
		do_action( 'woocommerce_after_single_product_summary' );
		?>
	</div>

	<?php /* Cart drawer now renders globally via wp_footer — see functions.php (v5.57.0). */ ?>
</div>
<?php endwhile; ?>
<?php
// Third-party integrations hooked on woocommerce_after_single_product, then
// core's closing main-content actions.
do_action( 'woocommerce_after_single_product' );
do_action( 'woocommerce_after_main_content' );
do_action( 'woocommerce_sidebar' );
get_footer( 'shop' );
