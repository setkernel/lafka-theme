<?php
/**
 * Partial: Home hero (v5.59.0 — handoff rebuild).
 *
 * Per handoff spec at /design_handoff_peppery_ordering/README.md
 * "Home page > 1. Hero":
 *
 *   - warm brand-50 bg
 *   - 2-col grid (1.1fr 1fr) ≥900px, stacked below
 *   - Left:
 *       - status pill (white bg, success dot, "Open now · until 11:00 pm")
 *       - H1 Fraunces 800 clamp(2.5rem, 6vw, 4.25rem)
 *           "Pizza, poutine & <em class='lafka-hero__accent'>everything craveable</em>."
 *       - Lead paragraph
 *       - Two CTAs: primary red pill + ghost phone
 *       - 3-item stats row (icon-disc + Fraunces bold number + caption)
 *   - Right: square aspect-ratio photo with brand-300 bg, radius-xl, shadow-3 + red glow
 *
 * @package Lafka
 * @since   5.59.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_hero_status = function_exists( 'lafka_open_status' ) ? lafka_open_status() : null;

// v5.79.0: suppress hero status pill when the announce bar is already
// showing the same open/closed signal sitewide — avoids the duplicated
// "Open now · until 12:00 am" reading at the top of every home view.
// Operators who disable the announce bar still see the hero pill.
if ( $lafka_hero_status && (bool) get_theme_mod( 'lafka_announce_bar_enabled', true ) ) {
	$lafka_hero_status = null;
}
$lafka_hero_info        = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_hero_phone       = isset( $lafka_hero_info['phone_display'] ) ? (string) $lafka_hero_info['phone_display'] : '';
$lafka_hero_phone_tel   = isset( $lafka_hero_info['phone_e164'] ) ? (string) $lafka_hero_info['phone_e164'] : $lafka_hero_phone;

$lafka_hero_headline_default = sprintf(
	/* translators: HTML allowed — second clause wrapped in an <em> for the accent treatment. */
	__( 'Pizza, poutine & %s.', 'lafka' ),
	'<em class="lafka-hero__accent">' . esc_html__( 'everything craveable', 'lafka' ) . '</em>'
);
$lafka_hero_headline = (string) get_theme_mod( 'lafka_home_hero_headline', $lafka_hero_headline_default );

$lafka_hero_lead = (string) get_theme_mod(
	'lafka_home_hero_lead',
	__( 'Fresh dough, locally-sourced toppings, and recipes refined over years of serving our neighbors. Ready in about 25 minutes.', 'lafka' )
);

$lafka_hero_cta_primary_label = (string) get_theme_mod( 'lafka_home_hero_primary_cta_label', __( 'Start your order', 'lafka' ) );
$lafka_hero_cta_primary_url   = (string) get_theme_mod( 'lafka_home_hero_primary_cta_url', home_url( '/menu/' ) );

$lafka_hero_image_id  = (int) get_theme_mod( 'lafka_home_hero_image_id', 0 );
$lafka_hero_image_src = $lafka_hero_image_id ? wp_get_attachment_image_url( $lafka_hero_image_id, 'large' ) : '';
if ( '' === $lafka_hero_image_src ) {
	$lafka_hero_image_src = (string) apply_filters( 'lafka_home_hero_default_bg_url', '' );
}

// Stats — operator may override via Customizer. Order matches the handoff
// prototype: rating first (trust signal), pickup time (urgency), free
// delivery (commerce hook). The rating stat defaults to EMPTY — shipping a
// fabricated rating + review count as a default would publish fake social
// proof on every install (audit 2026-06-27 #5). It renders only once the
// operator supplies a real value.
$lafka_hero_stat_1_value = (string) get_theme_mod( 'lafka_home_hero_stat_1_value', '' );
$lafka_hero_stat_1_label = (string) get_theme_mod( 'lafka_home_hero_stat_1_label', '' );
$lafka_hero_stat_2_value = (string) get_theme_mod( 'lafka_home_hero_stat_2_value', '25 min' );
$lafka_hero_stat_2_label = (string) get_theme_mod( 'lafka_home_hero_stat_2_label', __( 'avg. pickup', 'lafka' ) );
$lafka_hero_stat_3_value = (string) get_theme_mod( 'lafka_home_hero_stat_3_value', 'Free' );
$lafka_hero_stat_3_label = (string) get_theme_mod( 'lafka_home_hero_stat_3_label', __( 'delivery $30+', 'lafka' ) );
?>
<section class="lafka-hero" aria-label="<?php esc_attr_e( 'Welcome', 'lafka' ); ?>">
	<div class="lafka-container lafka-hero__inner">

		<div class="lafka-hero__copy">

			<?php if ( $lafka_hero_status ) : ?>
				<p class="lafka-hero__status">
					<span
						class="lafka-hero__status-dot"
						aria-hidden="true"
						style="<?php echo esc_attr( '--lafka-dot: ' . $lafka_hero_status['dot_color'] ); ?>"
					></span>
					<span class="lafka-hero__status-text"><?php echo esc_html( $lafka_hero_status['label'] ); ?></span>
				</p>
			<?php endif; ?>

			<h1 class="lafka-hero__headline">
				<?php
				// Headline allows a single <em class="lafka-hero__accent"> for the red italic span.
				echo wp_kses(
					$lafka_hero_headline,
					array(
						'em'     => array( 'class' => array() ),
						'span'   => array( 'class' => array() ),
						'strong' => array( 'class' => array() ),
						'br'     => array(),
					)
				);
				?>
			</h1>

			<p class="lafka-hero__lead"><?php echo esc_html( $lafka_hero_lead ); ?></p>

			<div class="lafka-hero__actions">
				<a class="lafka-hero__cta" href="<?php echo esc_url( $lafka_hero_cta_primary_url ); ?>">
					<?php echo esc_html( $lafka_hero_cta_primary_label ); ?>
					<span class="lafka-hero__cta-arrow" aria-hidden="true">→</span>
				</a>
				<?php if ( '' !== $lafka_hero_phone ) : ?>
					<a class="lafka-hero__cta-ghost" href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $lafka_hero_phone_tel ) ); ?>">
						<span class="lafka-hero__cta-ghost-icon" aria-hidden="true">📞</span>
						<?php echo esc_html( $lafka_hero_phone ); ?>
					</a>
				<?php endif; ?>
			</div>

			<dl class="lafka-hero__stats">
				<?php if ( '' !== $lafka_hero_stat_1_value ) : ?>
					<div class="lafka-hero__stat">
						<dt class="lafka-hero__stat-icon" aria-hidden="true">⭐</dt>
						<dd class="lafka-hero__stat-body">
							<span class="lafka-hero__stat-value"><?php echo esc_html( $lafka_hero_stat_1_value ); ?></span>
							<span class="lafka-hero__stat-label"><?php echo esc_html( $lafka_hero_stat_1_label ); ?></span>
						</dd>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $lafka_hero_stat_2_value ) : ?>
					<div class="lafka-hero__stat">
						<dt class="lafka-hero__stat-icon" aria-hidden="true">⏱</dt>
						<dd class="lafka-hero__stat-body">
							<span class="lafka-hero__stat-value"><?php echo esc_html( $lafka_hero_stat_2_value ); ?></span>
							<span class="lafka-hero__stat-label"><?php echo esc_html( $lafka_hero_stat_2_label ); ?></span>
						</dd>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $lafka_hero_stat_3_value ) : ?>
					<div class="lafka-hero__stat">
						<dt class="lafka-hero__stat-icon" aria-hidden="true">🚚</dt>
						<dd class="lafka-hero__stat-body">
							<span class="lafka-hero__stat-value"><?php echo esc_html( $lafka_hero_stat_3_value ); ?></span>
							<span class="lafka-hero__stat-label"><?php echo esc_html( $lafka_hero_stat_3_label ); ?></span>
						</dd>
					</div>
				<?php endif; ?>
			</dl>

		</div>

		<div class="lafka-hero__media" aria-hidden="true">
			<?php
			if ( $lafka_hero_image_id ) {
				// v5.99.0: render via wp_get_attachment_image() so width/
				// height attrs are emitted from the attachment metadata.
				// Without explicit dimensions, the browser reserves zero
				// space until the image loads, jolting layout (home CLS
				// was 0.83 — biggest core-web-vital regression on the
				// site). The intrinsic ratio now preserves the slot.
				echo wp_get_attachment_image(
					$lafka_hero_image_id,
					'large',
					false,
					array(
						'class'         => 'lafka-hero__image',
						'alt'           => '',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
					)
				);
			} elseif ( $lafka_hero_image_src ) {
				// Filter-provided default URL — no attachment ID, so we
				// can't read intrinsic dims. Set a reasonable default
				// ratio so layout still reserves space.
				printf(
					'<img class="lafka-hero__image" src="%s" alt="" width="640" height="640" loading="eager" fetchpriority="high" decoding="async">',
					esc_url( $lafka_hero_image_src )
				);
			} else {
				?>
				<div class="lafka-hero__image-placeholder">🍕</div>
				<?php
			}
			?>
		</div>

	</div>
</section>
