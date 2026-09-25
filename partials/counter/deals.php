<?php
/**
 * Counter layout: "Today's deals".
 *
 * The deals category is a Customizer setting (else the first category whose
 * slug is deals / combos / specials — filter `lafka_counter_deals_slugs`).
 * One featured deal (Customizer pick → WC-featured → menu order) as a big card,
 * the next two as photo cards, the rest as a text list, up to the limit.
 * Hidden when there is no deals category or it has no products.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_deals          = ( $args['sections'] ?? lafka_counter_sections() )['deals'];
$lafka_deals_settings = $args['settings'] ?? lafka_counter_settings();
if ( ! $lafka_deals || ! function_exists( 'wc_get_product' ) ) {
	return;
}

$lafka_deals_products = array();
foreach ( $lafka_deals['ids'] as $lafka_deals_id ) {
	$lafka_deals_product = wc_get_product( (int) $lafka_deals_id );
	if ( $lafka_deals_product ) {
		$lafka_deals_products[ (int) $lafka_deals_id ] = $lafka_deals_product;
	}
}
if ( ! $lafka_deals_products ) {
	return;
}
$lafka_deals_featured = $lafka_deals_products[ (int) $lafka_deals['featured_id'] ] ?? reset( $lafka_deals_products );
unset( $lafka_deals_products[ (int) $lafka_deals_featured->get_id() ] );
$lafka_deals_photo = array_slice( $lafka_deals_products, 0, 2 );
$lafka_deals_text  = array_slice( $lafka_deals_products, 2 );
$lafka_deals_link  = get_term_link( $lafka_deals['term'] );
?>
<section id="deals" class="lafka-counter-deals" aria-labelledby="lafka-counter-deals-title">
	<div class="lafka-counter-wrap">
		<div class="lafka-counter-head">
			<div>
				<h2 id="lafka-counter-deals-title" class="lafka-counter-head__title"><?php echo esc_html( $lafka_deals_settings['deals_heading'] ); ?></h2>
				<?php if ( '' !== trim( $lafka_deals_settings['deals_lead'] ) ) : ?>
					<p class="lafka-counter-head__lead"><?php echo esc_html( $lafka_deals_settings['deals_lead'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( is_string( $lafka_deals_link ) ) : ?>
				<a class="lafka-counter-link" href="<?php echo esc_url( $lafka_deals_link ); ?>">
					<?php esc_html_e( 'See all deals', 'lafka' ); ?>
					<?php echo lafka_counter_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="lafka-counter-deals__grid<?php echo $lafka_deals_text ? ' has-list' : ''; ?><?php echo $lafka_deals_photo ? ' has-photos' : ''; ?>">
			<?php
			get_template_part(
				'partials/counter/deal-card',
				null,
				array(
					'product' => $lafka_deals_featured,
					'variant' => 'featured',
				)
			);
			?>
			<?php if ( $lafka_deals_photo ) : ?>
				<div class="lafka-counter-deals__photos">
					<?php
					foreach ( $lafka_deals_photo as $lafka_deals_item ) {
						get_template_part(
							'partials/counter/deal-card',
							null,
							array(
								'product' => $lafka_deals_item,
								'variant' => 'photo',
							)
						);
					}
					?>
				</div>
			<?php endif; ?>
			<?php if ( $lafka_deals_text ) : ?>
				<div class="lafka-counter-deals__list">
					<?php
					foreach ( $lafka_deals_text as $lafka_deals_item ) {
						get_template_part(
							'partials/counter/deal-card',
							null,
							array(
								'product' => $lafka_deals_item,
								'variant' => 'text',
							)
						);
					}
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
