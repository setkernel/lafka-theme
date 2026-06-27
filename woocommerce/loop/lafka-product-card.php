<?php
/**
 * Reusable product card — handoff spec (v5.61.0).
 *
 * Expects `$lafka_arch_p` (WC_Product) in scope when included from a loop.
 * Renders a card identical to the home customer-favourites grid so the
 * design system stays consistent across pages.
 *
 * @package Lafka
 * @since   5.61.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $lafka_arch_p ) || ! is_object( $lafka_arch_p ) ) {
	return;
}

$lafka_arch_url   = get_permalink( $lafka_arch_p->get_id() );
$lafka_arch_img   = get_the_post_thumbnail_url( $lafka_arch_p->get_id(), 'medium_large' );
$lafka_arch_name  = $lafka_arch_p->get_name();
$lafka_arch_short = $lafka_arch_p->get_short_description();
if ( '' === $lafka_arch_short ) {
	$lafka_arch_short = wp_trim_words( (string) $lafka_arch_p->get_description(), 18 );
}
$lafka_arch_price    = $lafka_arch_p->get_price_html();
$lafka_arch_featured = $lafka_arch_p->is_featured();

// v5.68.0: emit data-* attributes for menu-controls JS filter matching.
// Tags come from WC product_tag slugs ('popular', 'vegetarian', 'vegan', 'spicy').
// Featured products auto-tagged 'popular' regardless of WC tag.
$lafka_arch_tag_slugs = array();
$lafka_arch_tags = function_exists( 'wp_get_post_terms' ) ? wp_get_post_terms( $lafka_arch_p->get_id(), 'product_tag', array( 'fields' => 'slugs' ) ) : array();
if ( ! is_wp_error( $lafka_arch_tags ) && is_array( $lafka_arch_tags ) ) {
	$lafka_arch_tag_slugs = array_map( 'strtolower', $lafka_arch_tags );
}
if ( $lafka_arch_featured && ! in_array( 'popular', $lafka_arch_tag_slugs, true ) ) {
	$lafka_arch_tag_slugs[] = 'popular';
}
$lafka_arch_tags_attr = implode( ',', $lafka_arch_tag_slugs );

// v6.14.0: the quick-add render fn + GA4 select_item both key off global $product.
$GLOBALS['product'] = $lafka_arch_p;

// select_item tracking contract (docs/TRACKING.md): lafka-dl-client.js reads
// these on the card link to push GA4 select_item.
$lafka_arch_cat_names = function_exists( 'wp_get_post_terms' ) ? wp_get_post_terms( $lafka_arch_p->get_id(), 'product_cat', array( 'fields' => 'names' ) ) : array();
$lafka_arch_cat       = ( ! is_wp_error( $lafka_arch_cat_names ) && ! empty( $lafka_arch_cat_names ) ) ? (string) $lafka_arch_cat_names[0] : '';
$lafka_arch_list      = ( function_exists( 'is_tax' ) && is_tax( 'product_cat' ) ) ? (string) single_term_title( '', false ) : ( is_page() ? (string) get_the_title() : 'Menu' );
?>
<li
	class="lafka-favs__item"
	data-lafka-product-name="<?php echo esc_attr( $lafka_arch_name ); ?>"
	data-lafka-product-tags="<?php echo esc_attr( $lafka_arch_tags_attr ); ?>"
>
	<a class="lafka-favs__card" href="<?php echo esc_url( $lafka_arch_url ); ?>"
		data-lafka-item-id="<?php echo esc_attr( (string) $lafka_arch_p->get_id() ); ?>"
		data-lafka-item-name="<?php echo esc_attr( $lafka_arch_name ); ?>"
		data-lafka-item-category="<?php echo esc_attr( $lafka_arch_cat ); ?>"
		data-lafka-item-price="<?php echo esc_attr( (string) wc_get_price_to_display( $lafka_arch_p ) ); ?>"
		data-lafka-list-name="<?php echo esc_attr( $lafka_arch_list ); ?>">
		<div class="lafka-favs__media">
			<?php if ( $lafka_arch_img ) : ?>
				<img class="lafka-favs__img" src="<?php echo esc_url( $lafka_arch_img ); ?>" alt="" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="lafka-favs__img-placeholder" aria-hidden="true">🍕</span>
			<?php endif; ?>
			<?php if ( $lafka_arch_featured ) : ?>
				<span class="lafka-favs__badge">★ <?php esc_html_e( 'Popular', 'lafka' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="lafka-favs__body">
			<h3 class="lafka-favs__name"><?php echo esc_html( $lafka_arch_name ); ?></h3>
			<?php if ( '' !== $lafka_arch_short ) : ?>
				<p class="lafka-favs__desc"><?php echo esc_html( wp_strip_all_tags( $lafka_arch_short ) ); ?></p>
			<?php endif; ?>
			<div class="lafka-favs__foot">
				<span class="lafka-favs__price"><?php echo wp_kses_post( $lafka_arch_price ); ?></span>
				<?php
				// v6.14.0: one-tap quick-add for simple products ("+ Add"), or
				// "Choose" → PDP for variable/combo. Reuses the proven pill
				// (js/lafka-archive-quickadd.js intercepts taps). Falls back to a
				// static label if the helper is unavailable.
				if ( function_exists( 'lafka_archive_quickadd_render' ) ) {
					lafka_archive_quickadd_render();
				} else {
					?>
					<span class="lafka-favs__cta"><?php esc_html_e( 'Customize', 'lafka' ); ?></span>
					<?php
				}
				?>
			</div>
		</div>
	</a>
</li>
