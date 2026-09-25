<?php
/**
 * Counter layout: "Find us" — address, grouped hours and phone, all from the
 * single NAP resolver lafka_get_restaurant_info(). Renders nothing without an
 * address and a phone. Customizer: lafka_counter_show_find_us.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_find_settings = $args['settings'] ?? lafka_counter_settings();
if ( empty( $lafka_find_settings['show_find_us'] ) ) {
	return;
}
$lafka_find = lafka_counter_nap();
if ( ! $lafka_find['address_lines'] && '' === $lafka_find['phone'] ) {
	return;
}
$lafka_find_hours = lafka_hours_grouped( $lafka_find['hours'] );
$lafka_find_modes = lafka_counter_fulfilment_modes();
if ( 2 === count( $lafka_find_modes ) ) {
	$lafka_find_call = __( 'Call for pickup or delivery', 'lafka' );
} elseif ( array( 'delivery' ) === $lafka_find_modes ) {
	$lafka_find_call = __( 'Call for delivery', 'lafka' );
} else {
	$lafka_find_call = __( 'Call for pickup', 'lafka' );
}
?>
<section id="find-us" class="lafka-counter-find" aria-labelledby="lafka-counter-find-title">
	<div class="lafka-counter-find__grid lafka-counter-wrap">
		<div class="lafka-counter-find__head">
			<h2 id="lafka-counter-find-title" class="lafka-counter-find__title"><?php esc_html_e( 'Find us', 'lafka' ); ?></h2>
			<?php if ( '' !== $lafka_find['map_url'] ) : ?>
				<a class="lafka-counter-btn" href="<?php echo esc_url( $lafka_find['map_url'] ); ?>" target="_blank" rel="noopener">
					<?php echo lafka_counter_icon( 'directions', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span><?php esc_html_e( 'Get directions', 'lafka' ); ?></span>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'lafka' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
		<?php if ( $lafka_find['address_lines'] ) : ?>
			<div class="lafka-counter-find__block">
				<h3 class="lafka-counter-find__label"><?php esc_html_e( 'Address', 'lafka' ); ?></h3>
				<address class="lafka-counter-find__text"><?php echo implode( '<br>', array_map( 'esc_html', $lafka_find['address_lines'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each line esc_html'd. ?></address>
			</div>
		<?php endif; ?>
		<?php if ( $lafka_find_hours ) : ?>
			<div class="lafka-counter-find__block">
				<h3 class="lafka-counter-find__label"><?php echo esc_html( lafka_hours_open_every_day( $lafka_find['hours'] ) ? __( 'Hours, every day', 'lafka' ) : __( 'Hours', 'lafka' ) ); ?></h3>
				<dl class="lafka-counter-find__hours">
					<?php foreach ( $lafka_find_hours as $lafka_find_row ) : ?>
						<div>
							<dt><?php echo esc_html( $lafka_find_row['days'] ); ?></dt>
							<dd><?php echo esc_html( $lafka_find_row['hours'] ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>
		<?php endif; ?>
		<?php if ( '' !== $lafka_find['phone'] ) : ?>
			<div class="lafka-counter-find__block">
				<h3 class="lafka-counter-find__label"><?php esc_html_e( 'Phone', 'lafka' ); ?></h3>
				<p class="lafka-counter-find__text">
					<a class="lafka-counter-find__phone" href="<?php echo esc_attr( 'tel:' . $lafka_find['tel'] ); ?>" data-lafka-channel="phone"><?php echo esc_html( $lafka_find['phone'] ); ?></a><br>
					<?php echo esc_html( $lafka_find_call ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</section>
