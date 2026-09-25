<?php
/**
 * Counter layout: hero with the two co-star dishes.
 *
 * Copy is the operator's (Customizer → Lafka — Home Page → Hero); the counter
 * defaults are neutral: headline "<Co-star A> and <co-star b>", no lead. Meta
 * lines render ONLY from real data: the ETA when Service ETA is set, the
 * offered fulfilment modes, the address + late-night note from the hours, and
 * a rating line only when social proof is configured.
 *
 * The front dish is the LCP image (eager, fetchpriority=high); both sit in
 * fixed-aspect boxes (critical-counter.css) so nothing shifts.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_hero_sections = $args['sections'] ?? lafka_counter_sections();
$lafka_hero_settings = $args['settings'] ?? lafka_counter_settings();
$lafka_hero_nap      = lafka_counter_nap();

$lafka_hero_costars = array_map( static fn( $b ) => $b['term'], $lafka_hero_sections['costars'] );
if ( count( $lafka_hero_costars ) >= 2 ) {
	/* translators: 1: first featured category (e.g. "Poutine"), 2: second, lowercased (e.g. "pizza") */
	$lafka_hero_default = sprintf( __( '%1$s and %2$s', 'lafka' ), $lafka_hero_costars[0]->name, function_exists( 'mb_strtolower' ) ? mb_strtolower( $lafka_hero_costars[1]->name ) : strtolower( $lafka_hero_costars[1]->name ) );
} elseif ( $lafka_hero_costars ) {
	$lafka_hero_default = $lafka_hero_costars[0]->name;
} else {
	$lafka_hero_default = $lafka_hero_nap['name'];
}
$lafka_hero_headline = trim( (string) get_theme_mod( 'lafka_home_hero_headline', '' ) );
if ( '' === $lafka_hero_headline ) {
	$lafka_hero_headline = $lafka_hero_default;
}
$lafka_hero_lead      = trim( (string) get_theme_mod( 'lafka_home_hero_lead', '' ) );
$lafka_hero_cta_label = trim( (string) get_theme_mod( 'lafka_home_hero_primary_cta_label', '' ) );
if ( '' === $lafka_hero_cta_label ) {
	$lafka_hero_cta_label = __( 'Start an order', 'lafka' );
}
$lafka_hero_cta_url = trim( (string) get_theme_mod( 'lafka_home_hero_primary_cta_url', '' ) );
if ( '' === $lafka_hero_cta_url ) {
	$lafka_hero_cta_url = lafka_theme_menu_url();
}

// Meta lines — real data only.
$lafka_hero_eta   = function_exists( 'lafka_service_eta_get_data' ) ? lafka_service_eta_get_data() : null;
$lafka_hero_eta   = is_array( $lafka_hero_eta ) ? trim( (string) ( '' !== $lafka_hero_eta['pickup'] ? $lafka_hero_eta['pickup'] : $lafka_hero_eta['delivery'] ) ) : '';
$lafka_hero_modes = lafka_counter_fulfilment_modes();
if ( 2 === count( $lafka_hero_modes ) ) {
	$lafka_hero_modes_text = __( 'Pickup or delivery', 'lafka' );
} elseif ( array( 'pickup' ) === $lafka_hero_modes ) {
	$lafka_hero_modes_text = __( 'Pickup', 'lafka' );
} elseif ( array( 'delivery' ) === $lafka_hero_modes ) {
	$lafka_hero_modes_text = __( 'Delivery', 'lafka' );
} else {
	$lafka_hero_modes_text = '';
}
$lafka_hero_place = array_filter( array( $lafka_hero_nap['address_short'], lafka_hours_late_note( $lafka_hero_nap['hours'] ) ) );

$lafka_hero_rating = '';
$lafka_hero_proof  = function_exists( 'lafka_social_proof_get_data' ) ? lafka_social_proof_get_data() : null;
if ( is_array( $lafka_hero_proof ) ) {
	$lafka_hero_bits = array();
	if ( ! empty( $lafka_hero_proof['has_rating'] ) ) {
		$lafka_hero_bits[] = '' !== $lafka_hero_proof['provider']
			/* translators: 1: rating (e.g. "4.4"), 2: provider (e.g. "Google") */
			? sprintf( __( '%1$s on %2$s', 'lafka' ), number_format_i18n( (float) $lafka_hero_proof['rating'], 1 ), $lafka_hero_proof['provider'] )
			/* translators: %s: rating (e.g. "4.4") */
			: sprintf( __( '%s out of 5', 'lafka' ), number_format_i18n( (float) $lafka_hero_proof['rating'], 1 ) );
	}
	if ( (int) $lafka_hero_proof['count'] > 0 ) {
		/* translators: %s: number of reviews */
		$lafka_hero_bits[] = sprintf( _n( '%s review', '%s reviews', (int) $lafka_hero_proof['count'], 'lafka' ), number_format_i18n( (int) $lafka_hero_proof['count'] ) );
	}
	$lafka_hero_rating = implode( ' · ', $lafka_hero_bits );
}

$lafka_hero_dishes = lafka_counter_hero_products( $lafka_hero_sections, $lafka_hero_settings );
?>
<section class="lafka-counter-hero" aria-labelledby="lafka-counter-hero-title">
	<div class="lafka-counter-hero__inner lafka-counter-wrap">
		<div class="lafka-counter-hero__copy">
			<h1 id="lafka-counter-hero-title" class="lafka-counter-hero__title"><?php echo esc_html( $lafka_hero_headline ); ?></h1>
			<?php if ( '' !== $lafka_hero_lead ) : ?>
				<p class="lafka-counter-hero__lead"><?php echo esc_html( $lafka_hero_lead ); ?></p>
			<?php endif; ?>
			<div class="lafka-counter-hero__ctas">
				<a class="lafka-counter-btn lafka-counter-btn--primary lafka-counter-btn--lg" href="<?php echo esc_url( $lafka_hero_cta_url ); ?>">
					<span><?php echo esc_html( $lafka_hero_cta_label ); ?></span>
					<?php echo lafka_counter_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				</a>
				<?php if ( '' !== $lafka_hero_nap['phone'] ) : ?>
					<a class="lafka-counter-btn lafka-counter-btn--lg" href="<?php echo esc_attr( 'tel:' . $lafka_hero_nap['tel'] ); ?>" data-lafka-channel="phone">
						<?php echo lafka_counter_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span>
						<?php
						// The number gets its own span so it can use the display face's figures.
						echo wp_kses(
							sprintf(
								/* translators: %s: phone number */
								esc_html__( 'Call %s', 'lafka' ),
								'<span class="lafka-counter-num">' . esc_html( $lafka_hero_nap['phone'] ) . '</span>'
							),
							array( 'span' => array( 'class' => true ) )
						);
						?>
						</span>
					</a>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $lafka_hero_eta || '' !== $lafka_hero_modes_text || $lafka_hero_place || '' !== $lafka_hero_rating ) : ?>
				<ul class="lafka-counter-hero__meta">
					<?php if ( '' !== $lafka_hero_eta || '' !== $lafka_hero_modes_text ) : ?>
						<li>
							<?php echo lafka_counter_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<span>
								<?php if ( '' !== $lafka_hero_eta ) : ?>
									<strong>
									<?php
									/* translators: %s: operator's ready-time text, e.g. "about 25 minutes" */
									echo esc_html( sprintf( __( 'Ready in %s', 'lafka' ), $lafka_hero_eta ) );
									?>
									</strong><?php echo '' !== $lafka_hero_modes_text ? ' · ' : ''; ?>
								<?php endif; ?>
								<?php echo esc_html( $lafka_hero_modes_text ); ?>
							</span>
						</li>
					<?php endif; ?>
					<?php if ( $lafka_hero_place ) : ?>
						<li>
							<?php echo lafka_counter_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<span><?php echo esc_html( implode( ' · ', $lafka_hero_place ) ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( '' !== $lafka_hero_rating ) : ?>
						<li class="lafka-counter-hero__rating">
							<?php echo lafka_counter_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
							<span><?php echo esc_html( $lafka_hero_rating ); ?></span>
						</li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( $lafka_hero_dishes ) : ?>
			<div class="lafka-counter-hero__art lafka-counter-hero__art--<?php echo count( $lafka_hero_dishes ); ?>">
				<?php
				foreach ( $lafka_hero_dishes as $lafka_hero_i => $lafka_hero_dish ) :
					$lafka_hero_slot = 0 === $lafka_hero_i ? 'front' : 'back';
					?>
					<figure class="lafka-counter-hero__dish lafka-counter-hero__dish--<?php echo esc_attr( $lafka_hero_slot ); ?>">
						<?php
						echo wp_get_attachment_image(
							(int) $lafka_hero_dish->get_image_id(),
							'woocommerce_single',
							false,
							array(
								'class'         => 'lafka-counter-hero__img lafka-counter-dish lafka-counter-dish--' . ( function_exists( 'lafka_dish_kind' ) ? lafka_dish_kind( (int) $lafka_hero_dish->get_image_id() ) : 'cutout' ),
								'alt'           => function_exists( 'lafka_card_image_alt' ) ? lafka_card_image_alt( $lafka_hero_dish, (int) $lafka_hero_dish->get_image_id() ) : $lafka_hero_dish->get_name(),
								'loading'       => 'eager',
								'decoding'      => 'async',
								'fetchpriority' => 'front' === $lafka_hero_slot ? 'high' : 'auto',
								'sizes'         => 'front' === $lafka_hero_slot ? '(min-width: 1280px) 600px, (min-width: 768px) 380px, 250px' : '(min-width: 1280px) 500px, (min-width: 768px) 320px, 210px',
							)
						);
						?>
						<figcaption class="lafka-counter-hero__caption">
							<strong><?php echo esc_html( wp_strip_all_tags( (string) $lafka_hero_dish->get_name() ) ); ?></strong>
							<span><?php echo esc_html( lafka_counter_price_text( $lafka_hero_dish ) ); ?></span>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
