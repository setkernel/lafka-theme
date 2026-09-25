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

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$media_items       = lafka_wc_product_media_items( $product );
// The helper returns the full gallery order; product-thumbnails.php renders the remaining items.
$first_media_item = $media_items[0] ?? array();
$first_media_id   = isset( $first_media_item['id'] ) ? absint( $first_media_item['id'] ) : $post_thumbnail_id;
$has_media        = ! empty( $first_media_item ) && 'placeholder' !== ( $first_media_item['source_type'] ?? '' );
$is_video         = $has_media && 'video' === ( $first_media_item['media_type'] ?? '' ) && lafka_wc_has_product_media_gallery();
$wrapper_classes  = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ( $has_media ? 'with-images' : 'without-images' ),
		'woocommerce-product-gallery--columns-' . absint( $columns ),
		'images',
	)
);

$lafka_product_video_url = lafka_product_video_trigger_url( $product, $media_items );

?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">

	<?php if ( $lafka_product_video_url ) : ?>
		<a title="<?php esc_attr_e( 'Play the video', 'lafka' ); ?>" class="lafka_product_video_trigger" href="<?php echo esc_url( $lafka_product_video_url ); ?>" ><span class="fa fa-play-circle"></span><?php esc_html_e( 'Play the video', 'lafka' ); ?></a>
	<?php endif; ?>

	<div class="woocommerce-product-gallery__wrapper">
		<?php
		if ( $is_video ) {
			$lafka_media_gallery_class = LAFKA_WC_PRODUCT_MEDIA_GALLERY_CLASS;
			$html                      = $lafka_media_gallery_class::get_gallery_video_html( $first_media_item, true );
		} elseif ( $has_media && $first_media_id ) {
			$html = wc_get_gallery_image_html( $first_media_id, true );
		} else {
			// Check for visible children with prices to determine if variation image swapping is possible.
			$wrapper_classname = $product->is_type( ProductType::VARIABLE ) && ! empty( $product->get_visible_children() ) && '' !== $product->get_price() ?
				'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder' :
				'woocommerce-product-gallery__image--placeholder';
			$html              = sprintf( '<div class="%s">', esc_attr( $wrapper_classname ) );
			$html             .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'lafka' ) );
			$html             .= '</div>';
		}

		if ( $is_video ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- matches WC core; markup built by ProductMediaGallery; filter consumers responsible for safe output.
			echo apply_filters( 'woocommerce_single_product_video_thumbnail_html', $html, $first_media_id, $first_media_item );
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- matches WC core pattern; $html is built with esc_url/esc_html__ above; filter consumers responsible for safe output.
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $first_media_id );
		}

		do_action( 'woocommerce_product_thumbnails' );
		?>
	</div>
</div>
