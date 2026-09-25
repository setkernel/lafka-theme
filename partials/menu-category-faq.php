<?php
/**
 * Partial: optional FAQ block below a product-category grid (GX3).
 *
 * Expects `$args['term_id']` (get_template_part() args). Renders nothing
 * unless lafka-plugin supplies at least one filled question/answer pair for
 * the category — the plugin emits the matching FAQPage JSON-LD, so visible
 * copy and structured data always come from the same store.
 *
 * @package Lafka
 * @since   7.2.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_cfaq_term_id = isset( $args['term_id'] ) ? (int) $args['term_id'] : 0;
$lafka_cfaq_items   = function_exists( 'lafka_menu_category_faq_items' ) ? lafka_menu_category_faq_items( $lafka_cfaq_term_id ) : array();
if ( empty( $lafka_cfaq_items ) ) {
	return;
}

/**
 * Filter the heading of the category FAQ block.
 *
 * @since 7.2.0
 * @param string $heading Heading text.
 * @param int    $term_id Product category term ID.
 */
$lafka_cfaq_heading = (string) apply_filters( 'lafka_category_faq_heading', __( 'Frequently asked questions', 'lafka' ), $lafka_cfaq_term_id );
$lafka_cfaq_id      = 'lafka-menu-faq-' . $lafka_cfaq_term_id;
?>
<section class="lafka-menu__faq" aria-labelledby="<?php echo esc_attr( $lafka_cfaq_id ); ?>">
	<h2 id="<?php echo esc_attr( $lafka_cfaq_id ); ?>" class="lafka-menu__faq-title"><?php echo esc_html( $lafka_cfaq_heading ); ?></h2>
	<div class="lafka-menu__faq-list">
		<?php foreach ( $lafka_cfaq_items as $lafka_cfaq_item ) : ?>
			<details class="lafka-menu__faq-item">
				<summary class="lafka-menu__faq-q"><?php echo esc_html( $lafka_cfaq_item['q'] ); ?></summary>
				<div class="lafka-menu__faq-a"><?php echo wp_kses_post( wpautop( $lafka_cfaq_item['a'] ) ); ?></div>
			</details>
		<?php endforeach; ?>
	</div>
</section>
