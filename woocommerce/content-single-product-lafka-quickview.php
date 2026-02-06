<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/** @var  $product WC_Product $product is coming from the ajax call param */
global $product;

$attachment_ids = $product->get_gallery_image_ids();

$schema = 'Product';

// Downloadable product schema handling
if ( $product->is_downloadable() ) {
	switch ( $product->get_type() ) {
		case 'application':
			$schema = 'SoftwareApplication';
			break;
		case 'music':
			$schema = 'MusicAlbum';
			break;
		default:
			$schema = 'Product';
			break;
	}
}
?>

<div itemscope itemtype="<?php echo esc_url( 'http://schema.org/' . $schema ); ?>" id="product-<?php the_ID(); ?>" <?php post_class( 'box box-common fixed lafka-single-product' ); ?>>

	<?php
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
	if ( lafka_is_product_eligible_for_variation_in_listings( $product ) ) {
		/** @var WC_Product_Variable $lafka_variable_product */
		$lafka_variable_product = wc_get_product( $product );
		// Only if it has default variation
		if ( $lafka_variable_product->get_default_attributes() ) {
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		}
	}
	/**
	 * woocommerce_before_single_product_summary hook
	 *
	 * @hooked woocommerce_show_product_sale_flash - 10
	 * @hooked woocommerce_show_product_images - 20 - removed for the quickview
	 */
	do_action( 'woocommerce_before_single_product_summary' );
	?>
	<div class="lafka-quickview-images images 
	<?php
	if ( count( $attachment_ids ) ) :
		?>
		owl-carousel lafka-owl-carousel<?php endif; ?>">

		<?php
		if ( has_post_thumbnail() ) {
			?>
			<div class="woocommerce-product-gallery__image" >
				<?php the_post_thumbnail( 'woocommerce_single' ); ?>
			</div>
			<?php foreach ( $attachment_ids as $img_att_id ) : ?>
				<div class="woocommerce-product-gallery__image" >
					<?php echo wp_get_attachment_image( $img_att_id, 'woocommerce_single' ); ?>
				</div>
			<?php endforeach; ?>
			<?php
		} else {
			echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(), esc_html__( 'Placeholder', 'lafka' ) ), $product->get_id() );
		}
		?>
	</div>
	<?php if ( count( $attachment_ids ) ) : ?>
		<script>
			jQuery(".lafka-quickview-images").owlCarousel({
				rtl: <?php echo is_rtl() ? 'true' : 'false'; ?>,
				items: 1,
				dots: false,
				loop: false,
				rewind: true,
				nav: true,
				navText: [
					"<i class='fas fa-angle-left'></i>",
					"<i class='fas fa-angle-right'></i>"
				],
			});
		</script>
	<?php endif; ?>
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
		 * woocommerce_single_product_summary hook
		 *
		 * @hooked woocommerce_template_single_title - 5
		 * @hooked woocommerce_template_single_rating - 10
		 * @hooked woocommerce_template_single_price - 10
		 * @hooked woocommerce_template_single_excerpt - 20
		 * @hooked woocommerce_template_single_add_to_cart - 30
		 * @hooked woocommerce_template_single_meta - 40
		 * @hooked woocommerce_template_single_sharing - 50
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>

	</div><!-- .summary -->
	<div class="clear"></div>
	<meta itemprop="url" content="<?php the_permalink(); ?>" />
</div><!-- closing div of content-holder -->
