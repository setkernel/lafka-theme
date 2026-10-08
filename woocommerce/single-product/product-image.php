<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * Lafka: reconciled with WooCommerce core 11.1.0. The main slot renders the
 * first item of core's media-gallery ordering (image, gallery video or
 * variation-gallery image) so core's product-thumbnails.php — which renders
 * "every item after the first" — never duplicates or drops one. On
 * WooCommerce < 11.1 the featured image fills the main slot as before
 * (lafka_wc_product_media_items()). The legacy "Play the video" trigger
 * (Lafka video-URL meta) stays as a fallback and hides once the gallery holds
 * a native video (lafka_product_video_trigger_url()).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.1.0
 */

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

global $product;

$lafka_columns           = lafka_core_filter( 'woocommerce_product_thumbnails_columns', 4 );
$lafka_post_thumbnail_id = $product->get_image_id();
$lafka_media_items       = lafka_wc_product_media_items( $product );
// The helper returns the full gallery order; product-thumbnails.php renders the remaining items.
$lafka_first_media_item = $lafka_media_items[0] ?? array();
$lafka_first_media_id   = isset( $lafka_first_media_item['id'] ) ? absint( $lafka_first_media_item['id'] ) : $lafka_post_thumbnail_id;
$lafka_has_media        = ! empty( $lafka_first_media_item ) && 'placeholder' !== ( $lafka_first_media_item['source_type'] ?? '' );
$lafka_is_video         = $lafka_has_media && 'video' === ( $lafka_first_media_item['media_type'] ?? '' ) && lafka_wc_has_product_media_gallery();
$lafka_wrapper_classes  = lafka_core_filter(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ( $lafka_has_media ? 'with-images' : 'without-images' ),
		'woocommerce-product-gallery--columns-' . absint( $lafka_columns ),
		'images',
	)
);

$lafka_product_video_url = lafka_product_video_trigger_url( $product, $lafka_media_items );

?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $lafka_wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $lafka_columns ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">

	<?php if ( $lafka_product_video_url ) : ?>
		<a title="<?php esc_attr_e( 'Play the video', 'lafka' ); ?>" class="lafka_product_video_trigger" href="<?php echo esc_url( $lafka_product_video_url ); ?>" ><span class="fa fa-play-circle"></span><?php esc_html_e( 'Play the video', 'lafka' ); ?></a>
	<?php endif; ?>

	<div class="woocommerce-product-gallery__wrapper">
		<?php
		if ( $lafka_is_video ) {
			$lafka_media_gallery_class = LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS;
			$lafka_html                = $lafka_media_gallery_class::get_gallery_video_html( $lafka_first_media_item, true );
		} elseif ( $lafka_has_media && $lafka_first_media_id ) {
			$lafka_html = wc_get_gallery_image_html( $lafka_first_media_id, true );
		} else {
			// Check for visible children with prices to determine if variation image swapping is possible.
			$lafka_wrapper_classname = $product->is_type( ProductType::VARIABLE ) && ! empty( $product->get_visible_children() ) && '' !== $product->get_price() ?
				'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder' :
				'woocommerce-product-gallery__image--placeholder';
			$lafka_html              = sprintf( '<div class="%s">', esc_attr( $lafka_wrapper_classname ) );
			$lafka_html             .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'lafka' ) );
			$lafka_html             .= '</div>';
		}

		if ( $lafka_is_video ) {
			echo wp_kses( lafka_core_filter( 'woocommerce_single_product_video_thumbnail_html', $lafka_html, $lafka_first_media_id, $lafka_first_media_item ), lafka_allowed_html() );
		} else {
			echo wp_kses( lafka_core_filter( 'woocommerce_single_product_image_thumbnail_html', $lafka_html, $lafka_first_media_id ), lafka_allowed_html() );
		}

		lafka_core_action( 'woocommerce_product_thumbnails' );
		?>
	</div>
</div>
