<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked wc_print_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form();

	return;
}

$lafka_product_classes = array( 'box', 'box-common', 'fixed', 'lafka-single-product' );
if ( lafka_is_product_eligible_for_variation_in_listings( $product ) ) {
	$lafka_product_classes[] = 'lafka-variations-list-in-catalog';
}
if ( lafka_get_option( 'hide_product_price_on_zero' ) && $product->get_price() == 0 ) {
	$lafka_product_classes[] = 'lafka-hide-zero-price';
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( implode( ' ', $lafka_product_classes ), $product ); ?>>
	<div class="content_holder">
		<?php
		// WooCommerce single product gallery type
		$lafka_single_product_gallery_classes = lafka_get_gallery_type_classes();
		?>
		<div class="lafka-product-summary-wrapper
		<?php
		if ( ! empty( $lafka_single_product_gallery_classes ) ) {
			echo ' ' . implode( ' ', $lafka_single_product_gallery_classes );}
		?>
		">
			<?php if ( lafka_get_option( 'show_breadcrumb' ) ) : ?>
				<?php woocommerce_breadcrumb(); ?>
			<?php endif; ?>
			<?php
			/**
			 * Hook: woocommerce_before_single_product_summary.
			 *
			 * @hooked woocommerce_show_product_sale_flash - 10 (removed by Althemist)
			 * @hooked woocommerce_show_product_images - 20
			 */
			do_action( 'woocommerce_before_single_product_summary' );
			?>
			<?php
			$lafka_has_product_addon = false;
			if ( class_exists( 'WC_Product_Addons_Helper' ) && lafka_get_option( 'product_addons' ) === 'enabled' ) {
				$lafka_has_product_addon = count( WC_Product_Addons_Helper::get_product_addons( get_the_ID() ) );
			}
			?>
			<div class="summary entry-summary
			<?php
			if ( $lafka_has_product_addon ) {
				echo ' lafka-product-has-addons';}
			?>
			">

				<?php
				/**
				 * Hook: woocommerce_single_product_summary.
				 *
				 * @hooked woocommerce_show_product_sale_flash - 1 (added by Althemist)
				 * @hooked woocommerce_template_single_title - 5
				 * @hooked woocommerce_template_single_excerpt - 6
				 * @hooked woocommerce_template_single_rating - 8 (moved by Althemist)
				 * @hooked lafka_product_sale_countdown - 9 (moved by Althemist)
				 * @hooked woocommerce_template_single_price - 10
				 * @hooked woocommerce_template_single_add_to_cart - 30
				 * @hooked woocommerce_template_single_meta - 40
				 * @hooked woocommerce_template_single_sharing - 50
				 * @hooked WC_Structured_Data::generate_product_data() - 60
				 */
				do_action( 'woocommerce_single_product_summary' );
				?>

			</div><!-- .summary -->
			<div class="clear"></div>
		</div><!-- .lafka-product-summary-wrapper -->
		<?php
		/**
		 * woocommerce_after_single_product_summary hook.
		 *
		 * @hooked woocommerce_output_product_data_tabs - 10
		 * @hooked woocommerce_upsell_display - 15
		 * @hooked woocommerce_output_related_products - 20
		 */
		do_action( 'woocommerce_after_single_product_summary' );
		?>

	</div><!-- closing div of content-holder -->
	<?php
	if ( lafka_get_option( 'show_sidebar_product' ) ) {
		do_action( 'woocommerce_sidebar' );
		echo '<div class="clear"></div>';
	}
	?>
</div><!-- #product-<?php the_ID(); ?> -->

<?php do_action( 'woocommerce_after_single_product' ); ?>
