<?php
/**
 * Counter layout: one deal.
 *
 *   featured  checkered band, big photo, name in the display face, per-person
 *             line, price in accent, primary button
 *   photo     photo | name, per-person line, price, outline button
 *   text      name, price, underlined action
 *
 * The per-person line ("For 2: about $11.50 each") renders only when the
 * product has a real "serves" value (lafka-plugin field); otherwise the short
 * description (if any) shows instead. Nothing is invented.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_deal = $args['product'] ?? null;
if ( ! is_object( $lafka_deal ) ) {
	return;
}
$lafka_deal_variant = in_array( $args['variant'] ?? '', array( 'featured', 'photo', 'text' ), true ) ? $args['variant'] : 'text';
$lafka_deal_name    = wp_strip_all_tags( (string) $lafka_deal->get_name() );
$lafka_deal_url     = (string) $lafka_deal->get_permalink();
$lafka_deal_prices  = lafka_price_columns( $lafka_deal );
$lafka_deal_price   = 'single' === $lafka_deal_prices['type']
	? lafka_price_plain( (float) $lafka_deal_prices['price'] )
	/* translators: %s: lowest price */
	: sprintf( __( 'from %s', 'lafka' ), lafka_price_plain( (float) $lafka_deal_prices['price'] ) );
$lafka_deal_line = lafka_per_person_text( (float) $lafka_deal_prices['price'], lafka_serves_for( $lafka_deal ) );
if ( '' === $lafka_deal_line && 'text' !== $lafka_deal_variant ) {
	$lafka_deal_line = trim( wp_strip_all_tags( (string) $lafka_deal->get_short_description() ) );
}
$lafka_deal_label = (string) apply_filters( 'lafka_counter_deal_add_label', __( 'Add to order', 'lafka' ), $lafka_deal );
$lafka_deal_img   = '';
if ( 'text' !== $lafka_deal_variant && (int) $lafka_deal->get_image_id() ) {
	$lafka_deal_img = (string) wp_get_attachment_image(
		(int) $lafka_deal->get_image_id(),
		'featured' === $lafka_deal_variant ? 'woocommerce_single' : 'woocommerce_thumbnail',
		false,
		array(
			'class'    => 'lafka-deal__img lafka-counter-dish',
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => 'featured' === $lafka_deal_variant ? '(min-width: 1024px) 420px, 90vw' : '(min-width: 1024px) 170px, 150px',
		)
	);
}
$lafka_deal_button_class = 'featured' === $lafka_deal_variant ? 'lafka-counter-btn--primary lafka-deal__add' : ( 'text' === $lafka_deal_variant ? 'lafka-counter-btn--link lafka-deal__add' : 'lafka-deal__add' );
?>
<article class="lafka-deal lafka-deal--<?php echo esc_attr( $lafka_deal_variant ); ?><?php echo '' === $lafka_deal_img ? ' lafka-deal--no-img' : ''; ?>">
	<?php if ( 'featured' === $lafka_deal_variant ) : ?>
		<div class="lafka-motif-check" aria-hidden="true"></div>
	<?php endif; ?>
	<?php if ( '' !== $lafka_deal_img ) : ?>
		<a class="lafka-deal__media" href="<?php echo esc_url( $lafka_deal_url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $lafka_deal_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() markup. ?>
		</a>
	<?php endif; ?>
	<div class="lafka-deal__body">
		<h3 class="lafka-deal__name"><a href="<?php echo esc_url( $lafka_deal_url ); ?>" data-lafka-item-id="<?php echo esc_attr( (string) $lafka_deal->get_id() ); ?>" data-lafka-item-name="<?php echo esc_attr( $lafka_deal_name ); ?>" data-lafka-item-price="<?php echo esc_attr( (string) $lafka_deal_prices['price'] ); ?>" data-lafka-list-name="<?php esc_attr_e( 'Deals', 'lafka' ); ?>"><?php echo esc_html( $lafka_deal_name ); ?></a></h3>
		<?php if ( '' !== $lafka_deal_line ) : ?>
			<p class="lafka-deal__line"><?php echo esc_html( $lafka_deal_line ); ?></p>
		<?php endif; ?>
		<div class="lafka-deal__foot">
			<p class="lafka-deal__price"><?php echo esc_html( $lafka_deal_price ); ?></p>
			<?php echo lafka_counter_add_action( $lafka_deal, $lafka_deal_label, $lafka_deal_button_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* in lafka_counter_add_action(). ?>
		</div>
	</div>
</article>
