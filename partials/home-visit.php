<?php
/**
 * Partial: Home "Visit us" — dark card (v5.60.0 handoff rebuild).
 *
 * Per handoff /design_handoff_peppery_ordering/README.md "Home page > 5. Visit us":
 *   - single dark card (--lafka-color-surface-dark)
 *   - 2-col grid ≥900px, rounded-xl, fills container width
 *   - Left: photo placeholder, 16:10 mobile / full-height ≥900px,
 *     absolute "pin" white card bottom-left (logo + address)
 *   - Right: padded, contains:
 *       - Brand-yellow eyebrow "Visit us"
 *       - Fraunces 800 h2 with address, white, line-height 1.05
 *       - Big yellow phone link in Fraunces 800, white-space:nowrap
 *       - 7-row hours grid (dl/dt/dd, day label + time)
 *       - Two CTAs: brand-yellow "Get directions" + ghost-inverse "Order online"
 *
 * @package Lafka
 * @since   5.60.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_visit_info  = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_visit_addr  = isset( $lafka_visit_info['address_display'] ) ? (string) $lafka_visit_info['address_display'] : '';
$lafka_visit_short = isset( $lafka_visit_info['address_short'] ) ? (string) $lafka_visit_info['address_short'] : '';
$lafka_visit_phone = isset( $lafka_visit_info['phone_display'] ) ? (string) $lafka_visit_info['phone_display'] : '';
$lafka_visit_tel   = isset( $lafka_visit_info['phone_e164'] ) ? (string) $lafka_visit_info['phone_e164'] : $lafka_visit_phone;
$lafka_visit_hours = isset( $lafka_visit_info['hours'] ) && is_array( $lafka_visit_info['hours'] ) ? $lafka_visit_info['hours'] : array();
$lafka_visit_directions = isset( $lafka_visit_info['directions_url'] ) ? (string) $lafka_visit_info['directions_url'] : '';

// Photo: Customizer override → operator's first product image fallback.
$lafka_visit_image_id  = (int) get_theme_mod( 'lafka_home_visit_image_id', 0 );
$lafka_visit_image_src = $lafka_visit_image_id ? wp_get_attachment_image_url( $lafka_visit_image_id, 'large' ) : '';

if ( '' === $lafka_visit_addr && '' === $lafka_visit_phone ) {
	return;
}

$lafka_visit_logo_id = function_exists( 'get_theme_mod' ) ? get_theme_mod( 'lafka_theme_logo', 0 ) : 0;
?>
<section class="lafka-visit" aria-labelledby="lafka-visit-heading">
	<div class="lafka-container">

		<article class="lafka-visit__card">

			<div class="lafka-visit__media">
				<?php if ( $lafka_visit_image_src ) : ?>
					<img class="lafka-visit__photo" src="<?php echo esc_url( $lafka_visit_image_src ); ?>" alt="" loading="lazy">
				<?php else : ?>
					<div class="lafka-visit__photo-placeholder" aria-hidden="true">🍕</div>
				<?php endif; ?>

				<?php if ( '' !== $lafka_visit_short ) : ?>
					<div class="lafka-visit__pin">
						<?php if ( $lafka_visit_logo_id ) : ?>
							<?php
							echo wp_get_attachment_image(
								$lafka_visit_logo_id,
								'thumbnail',
								false,
								array(
									'class' => 'lafka-visit__pin-logo',
									'alt'   => '',
								)
							);
							?>
						<?php endif; ?>
						<div class="lafka-visit__pin-text">
							<span class="lafka-visit__pin-label"><?php esc_html_e( 'You can find us at', 'lafka' ); ?></span>
							<span class="lafka-visit__pin-addr"><?php echo esc_html( $lafka_visit_short ); ?></span>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="lafka-visit__body">
				<p class="lafka-visit__eyebrow"><?php esc_html_e( 'Visit us', 'lafka' ); ?></p>

				<?php if ( '' !== $lafka_visit_addr ) : ?>
					<h2 id="lafka-visit-heading" class="lafka-visit__addr">
						<?php echo nl2br( esc_html( $lafka_visit_addr ) ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( '' !== $lafka_visit_phone ) : ?>
					<a class="lafka-visit__phone" href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_visit_tel ) ); ?>">
						<?php echo esc_html( $lafka_visit_phone ); ?>
					</a>
				<?php endif; ?>

				<?php if ( ! empty( $lafka_visit_hours ) ) : ?>
					<dl class="lafka-visit__hours">
						<?php foreach ( $lafka_visit_hours as $lafka_visit_day => $lafka_visit_range ) : ?>
							<div class="lafka-visit__hours-row">
								<dt><?php echo esc_html( $lafka_visit_day ); ?></dt>
								<dd><?php echo esc_html( $lafka_visit_range ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>

				<div class="lafka-visit__actions">
					<?php if ( '' !== $lafka_visit_directions ) : ?>
						<a class="lafka-visit__cta lafka-visit__cta--primary" href="<?php echo esc_url( $lafka_visit_directions ); ?>" target="_blank" rel="noopener noreferrer">
							<span aria-hidden="true">📍</span>
							<?php esc_html_e( 'Get directions', 'lafka' ); ?>
						</a>
					<?php endif; ?>
					<a class="lafka-visit__cta lafka-visit__cta--ghost" href="<?php echo esc_url( lafka_theme_menu_url() ); ?>">
						<?php esc_html_e( 'Order online', 'lafka' ); ?>
					</a>
				</div>
			</div>

		</article>

	</div>
</section>
