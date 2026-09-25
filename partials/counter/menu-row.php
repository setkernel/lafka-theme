<?php
/**
 * Counter layout: one product row (home co-stars + menu sections, /menu/, and
 * the category archive via the early hand-off in
 * woocommerce/loop/lafka-product-card.php).
 *
 *   [photo]  Name (link, GA4 select_item contract)
 *            one-line description
 *            Small $13.95 · Medium $19.45 · …        [ Add ]
 *
 * The row is NOT one big link: the name and photo are links, "Add" is a real
 * <button> (variable -> 2-tap size chooser, simple -> direct add) or, when a
 * 2-tap add could build a wrong cart line, a "Choose" link to the product page.
 *
 * $args:
 *   product  WC_Product (required)
 *   style    photo | compact           (default photo)
 *   thumbs   bool — compact rows with a small thumbnail (default true)
 *   list     GA4 list name             (default: archive / page title / Menu)
 *   heading  int — the name's heading level (default 3; T-19)
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_row_p = isset( $args['product'] ) ? $args['product'] : null;
if ( ! is_object( $lafka_row_p ) || ! method_exists( $lafka_row_p, 'get_id' ) ) {
	return;
}
$lafka_row_style  = isset( $args['style'] ) && 'compact' === $args['style'] ? 'compact' : 'photo';
$lafka_row_level  = max( 2, min( 6, (int) ( $args['heading'] ?? 3 ) ) );
$lafka_row_thumbs = 'photo' === $lafka_row_style || ! isset( $args['thumbs'] ) || (bool) $args['thumbs'];
$lafka_row_id     = (int) $lafka_row_p->get_id();
$lafka_row_name   = wp_strip_all_tags( (string) $lafka_row_p->get_name() );
$lafka_row_url    = (string) $lafka_row_p->get_permalink();

$lafka_row_desc = wp_strip_all_tags( (string) $lafka_row_p->get_short_description() );
if ( '' === trim( $lafka_row_desc ) && method_exists( $lafka_row_p, 'get_description' ) ) {
	$lafka_row_desc = wp_trim_words( wp_strip_all_tags( (string) $lafka_row_p->get_description() ), 18 );
}
$lafka_row_desc = trim( (string) preg_replace( '/\s+/', ' ', $lafka_row_desc ) );

$lafka_row_prices = lafka_price_columns( $lafka_row_p );

// Menu-controls filters (search / dietary chips) key off these, exactly as the
// classic card li does.
$lafka_row_tags = array();
$lafka_row_tag_terms = function_exists( 'wp_get_post_terms' ) ? wp_get_post_terms( $lafka_row_id, 'product_tag', array( 'fields' => 'slugs' ) ) : array();
if ( is_array( $lafka_row_tag_terms ) ) {
	$lafka_row_tags = array_map( 'strtolower', $lafka_row_tag_terms );
}
if ( $lafka_row_p->is_featured() && ! in_array( 'popular', $lafka_row_tags, true ) ) {
	$lafka_row_tags[] = 'popular';
}

// GA4 select_item contract (docs/TRACKING.md), on the name link.
$lafka_row_cats = function_exists( 'wp_get_post_terms' ) ? wp_get_post_terms( $lafka_row_id, 'product_cat', array( 'fields' => 'names' ) ) : array();
$lafka_row_cat  = is_array( $lafka_row_cats ) && $lafka_row_cats ? (string) $lafka_row_cats[0] : '';
if ( isset( $args['list'] ) ) {
	$lafka_row_list = (string) $args['list'];
} elseif ( function_exists( 'is_tax' ) && is_tax( 'product_cat' ) ) {
	$lafka_row_list = (string) single_term_title( '', false );
} elseif ( function_exists( 'is_page' ) && is_page() ) {
	$lafka_row_list = (string) get_the_title();
} else {
	$lafka_row_list = 'Menu';
}

// Action (lafka_counter_add_action()): chooser (variable), direct (simple,
// quick-add on), or a "Choose" link to the product page.
$lafka_row_add_label = (string) apply_filters( 'lafka_counter_add_label', __( 'Add', 'lafka' ), $lafka_row_p );

$lafka_row_img  = '';
$lafka_row_kind = '';
if ( $lafka_row_thumbs && function_exists( 'lafka_card_image_html' ) ) {
	// Cut-out → contact shadow; opaque photo → rounded crop (dish-image.php).
	$lafka_row_kind = function_exists( 'lafka_dish_kind' ) ? lafka_dish_kind( (int) $lafka_row_p->get_image_id() ) : 'cutout';
	$lafka_row_img  = lafka_card_image_html(
		$lafka_row_p,
		array(
			'size'  => 'woocommerce_thumbnail',
			'class' => 'lafka-row__img lafka-counter-dish lafka-counter-dish--' . $lafka_row_kind,
			'sizes' => (string) apply_filters( 'lafka_counter_row_image_sizes', 'compact' === $lafka_row_style ? '96px' : '(min-width: 1024px) 140px, 104px', $lafka_row_p ),
		)
	);
}
?>
<li
	class="lafka-row lafka-row--<?php echo esc_attr( $lafka_row_style ); ?><?php echo '' === $lafka_row_img ? ' lafka-row--no-img' : ''; ?><?php echo 'columns' === $lafka_row_prices['type'] && count( $lafka_row_prices['columns'] ) > 2 ? ' lafka-row--wide-prices' : ''; ?>"
	data-lafka-product-name="<?php echo esc_attr( $lafka_row_name ); ?>"
	data-lafka-product-search="<?php echo esc_attr( trim( $lafka_row_name . ' ' . $lafka_row_desc ) ); ?>"
	data-lafka-product-tags="<?php echo esc_attr( implode( ',', $lafka_row_tags ) ); ?>"
>
	<?php if ( '' !== $lafka_row_img ) : ?>
		<a class="lafka-row__media lafka-dish-frame lafka-dish-frame--<?php echo esc_attr( $lafka_row_kind ); ?>" href="<?php echo esc_url( $lafka_row_url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $lafka_row_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() markup, attributes escaped by core. ?>
		</a>
	<?php endif; ?>
	<div class="lafka-row__body">
		<h<?php echo (int) $lafka_row_level; ?> class="lafka-row__name">
			<a
				href="<?php echo esc_url( $lafka_row_url ); ?>"
				data-lafka-item-id="<?php echo esc_attr( (string) $lafka_row_id ); ?>"
				data-lafka-item-name="<?php echo esc_attr( $lafka_row_name ); ?>"
				data-lafka-item-category="<?php echo esc_attr( $lafka_row_cat ); ?>"
				data-lafka-item-price="<?php echo esc_attr( (string) $lafka_row_prices['price'] ); ?>"
				data-lafka-list-name="<?php echo esc_attr( $lafka_row_list ); ?>"
			><?php echo esc_html( $lafka_row_name ); ?></a>
		</h<?php echo (int) $lafka_row_level; ?>>
		<?php if ( '' !== $lafka_row_desc ) : ?>
			<p class="lafka-row__desc"><?php echo esc_html( $lafka_row_desc ); ?></p>
		<?php endif; ?>
		<div class="lafka-row__foot">
			<?php if ( 'columns' === $lafka_row_prices['type'] ) : ?>
				<dl class="lafka-prices lafka-prices--cols-<?php echo esc_attr( (string) min( 6, count( $lafka_row_prices['columns'] ) ) ); ?>">
					<?php foreach ( $lafka_row_prices['columns'] as $lafka_row_col ) : ?>
						<div class="lafka-prices__col">
							<dt><?php echo esc_html( $lafka_row_col['label'] ); ?></dt>
							<dd><?php echo esc_html( lafka_price_plain( (float) $lafka_row_col['price'] ) ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php elseif ( 'from' === $lafka_row_prices['type'] ) : ?>
				<p class="lafka-row__price">
					<?php
					/* translators: %s: lowest price */
					echo esc_html( sprintf( __( 'from %s', 'lafka' ), lafka_price_plain( (float) $lafka_row_prices['price'] ) ) );
					?>
				</p>
			<?php else : ?>
				<p class="lafka-row__price"><?php echo esc_html( lafka_price_plain( (float) $lafka_row_prices['price'] ) ); ?></p>
			<?php endif; ?>

			<?php echo lafka_counter_add_action( $lafka_row_p, $lafka_row_add_label, 'lafka-row__add' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* in lafka_counter_add_action(). ?>
		</div>
	</div>
</li>
