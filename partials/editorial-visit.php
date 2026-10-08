<?php
/**
 * Partial: Editorial Visit / Map section.
 *
 * NAP and hours come from lafka_get_restaurant_info() — the W2-T1 single
 * source of truth. Map embed URL comes from the Customizer.
 *
 * Renders nothing if neither map URL nor restaurant info is available.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_info = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();

$lafka_map_url       = get_theme_mod( 'lafka_editorial_home_map_embed_url', '' );
$lafka_phone_e164    = ! empty( $lafka_info['phone_e164'] ) ? $lafka_info['phone_e164'] : '';
$lafka_phone_display = lafka_theme_phone_display( $lafka_info['phone_display'] ?? '', $lafka_phone_e164 );
$lafka_address       = ! empty( $lafka_info['address_display'] ) ? $lafka_info['address_display'] : '';
$lafka_hours         = ! empty( $lafka_info['hours'] ) ? $lafka_info['hours'] : array();
$lafka_address_h2    = ! empty( $lafka_info['address_short'] ) ? $lafka_info['address_short'] : $lafka_address;

if ( ! $lafka_map_url && ! $lafka_phone_e164 && ! $lafka_address && empty( $lafka_hours ) ) {
	return;
}

// Determine today's day name for "today" highlight.
$lafka_today_name = wp_date( 'l' ); // e.g. "Monday"
?>
<section class="visit-section">
	<div class="visit-grid">

		<?php if ( $lafka_map_url ) : ?>
		<div class="map-frame">
			<iframe
				src="<?php echo esc_url( $lafka_map_url ); ?>"
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				title="<?php esc_attr_e( 'Map', 'lafka' ); ?>"
				allowfullscreen
			></iframe>
		</div>
		<?php endif; ?>

		<div class="visit-info">
			<div class="label"><?php esc_html_e( '&mdash; Come say hi', 'lafka' ); ?></div>

			<?php if ( $lafka_address_h2 ) : ?>
			<h2><?php echo esc_html( $lafka_address_h2 ); ?></h2>
			<?php endif; ?>

			<?php if ( $lafka_phone_e164 ) : ?>
			<div class="visit-block">
				<div class="small-label"><?php esc_html_e( 'Phone', 'lafka' ); ?></div>
				<div class="value">
					<a href="tel:<?php echo esc_attr( $lafka_phone_e164 ); ?>"><?php echo esc_html( $lafka_phone_display ); ?></a>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $lafka_address ) : ?>
			<div class="visit-block">
				<div class="small-label"><?php esc_html_e( 'Address', 'lafka' ); ?></div>
				<div class="value"><?php echo nl2br( esc_html( $lafka_address ) ); ?></div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $lafka_hours ) ) : ?>
			<div class="visit-block">
				<div class="small-label"><?php esc_html_e( 'Hours', 'lafka' ); ?></div>
				<div class="value">
					<div class="hours-table">
						<?php
						foreach ( $lafka_hours as $lafka_day => $lafka_time ) :
							$lafka_is_today   = ( strtolower( $lafka_today_name ) === strtolower( $lafka_day ) );
							$lafka_day_class  = $lafka_is_today ? 'day today' : 'day';
							$lafka_time_class = $lafka_is_today ? 'time today' : 'time';
							?>
						<div class="<?php echo esc_attr( $lafka_day_class ); ?>">
							<?php echo esc_html( $lafka_day ); ?>
							<?php if ( $lafka_is_today ) : ?>
							<span class="sr-only"> (<?php esc_html_e( 'today', 'lafka' ); ?>)</span>
							<?php endif; ?>
						</div>
						<div class="<?php echo esc_attr( $lafka_time_class ); ?>"><?php echo esc_html( $lafka_time ); ?></div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $lafka_info['directions_url'] ) ) : ?>
			<a href="<?php echo esc_url( $lafka_info['directions_url'] ); ?>" class="visit-cta" rel="noopener noreferrer" target="_blank">
				<?php esc_html_e( 'Get directions', 'lafka' ); ?> &rarr;
			</a>
			<?php endif; ?>
		</div>

	</div>
</section>
