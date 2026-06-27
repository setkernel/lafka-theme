<?php
/**
 * Partial: "Order Direct & save" value component.
 *
 * Rendered via lafka_render_direct_value( $context ) which sets
 * $GLOBALS['lafka_direct_value_context'] to: home | menu | cart | checkout.
 * Data + Customizer in incl/customizer-direct-value.php. The CTA carries the
 * data-lafka-order-channel="direct" contract (docs/TRACKING.md).
 *
 * @package Lafka
 * @since   6.14.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_direct_value_data' ) ) {
	return;
}

$lafka_dv_ctx  = isset( $GLOBALS['lafka_direct_value_context'] ) ? (string) $GLOBALS['lafka_direct_value_context'] : 'home';
$lafka_dv      = lafka_direct_value_data();
if ( empty( $lafka_dv['enabled'] ) ) {
	return;
}

if ( 'home' === $lafka_dv_ctx ) :
	?>
	<section class="lafka-direct lafka-direct--strip" aria-labelledby="lafka-direct-heading">
		<div class="lafka-container lafka-direct__inner">
			<div class="lafka-direct__copy">
				<h2 id="lafka-direct-heading" class="lafka-direct__heading"><?php echo esc_html( $lafka_dv['heading'] ); ?></h2>
				<?php if ( '' !== $lafka_dv['subheading'] ) : ?>
					<p class="lafka-direct__subheading"><?php echo esc_html( $lafka_dv['subheading'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $lafka_dv['points'] ) ) : ?>
					<ul class="lafka-direct__points" role="list">
						<?php foreach ( $lafka_dv['points'] as $lafka_dv_point ) : ?>
							<li class="lafka-direct__point">
								<span class="lafka-direct__check" aria-hidden="true">✓</span>
								<?php echo esc_html( $lafka_dv_point ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $lafka_dv['cta_label'] ) : ?>
				<a class="lafka-direct__cta" href="<?php echo esc_url( $lafka_dv['cta_url'] ); ?>"
					data-lafka-order-channel="direct" data-lafka-order-source="home_strip">
					<?php echo esc_html( $lafka_dv['cta_label'] ); ?>
				</a>
			<?php endif; ?>
		</div>
	</section>
	<?php
elseif ( 'menu' === $lafka_dv_ctx ) :
	?>
	<p class="lafka-direct lafka-direct--badge" data-lafka-order-channel="direct" data-lafka-order-source="menu_badge">
		<span class="lafka-direct__badge-icon" aria-hidden="true">🛵</span>
		<?php echo esc_html( $lafka_dv['line'] ); ?>
	</p>
	<?php
else : // cart | checkout
	?>
	<p class="lafka-direct lafka-direct--line" data-lafka-order-channel="direct" data-lafka-order-source="<?php echo esc_attr( $lafka_dv_ctx ); ?>">
		<span class="lafka-direct__check" aria-hidden="true">✓</span>
		<?php echo esc_html( $lafka_dv['line'] ); ?>
	</p>
	<?php
endif;
