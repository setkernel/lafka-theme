<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.7.0
 */

use Automattic\WooCommerce\Enums\ProductType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WC_Product $product */
global $product;
?>
<div class="tagcloud product_meta">
	<?php do_action( 'woocommerce_product_meta_start' ); ?>
	<?php
		$categories      = wc_get_product_category_list( $product->get_id() );
		$size_categories = count( $product->get_category_ids() );

		$tags      = wc_get_product_tag_list( $product->get_id() );
		$size_tags = count( $product->get_tag_ids() );

	if ( $categories ) {
		echo '<span class="posted_in">' . _n( 'Category:', 'Categories:', $size_categories, 'lafka' ) . '</span>' . $categories;
	}

	if ( $tags ) {
		echo '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', $size_tags, 'lafka' ) . '</span>' . $tags;
	}
	?>
	<?php if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( ProductType::VARIABLE ) ) ) : ?>

		<span class="sku_wrapper">
			<?php esc_html_e( 'SKU:', 'lafka' ); ?> <span class="sku">
			<?php
			if ( $sku = $product->get_sku() ) {
				echo esc_html( $sku );
			} else {
				esc_html_e( 'N/A', 'lafka' );
			}
			?>
			</span>
		</span>

	<?php endif; ?>
	<?php do_action( 'woocommerce_product_meta_end' ); ?>
</div>
