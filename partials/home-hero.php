<?php
/**
 * Partial: Home hero (v5.49.0)
 *
 * Full-bleed hero with optional background image, optional service-status
 * pill, headline, subhead, and primary/secondary CTAs. The default
 * background image preserves the operator's existing brand visual
 * (yellow textured wash uploaded 2021-06 to /wp-content/uploads/) so
 * the home page doesn't feel emptier than the WPBakery version it
 * replaced.
 *
 * Customizer reads (all in panel "Lafka — Home Page" → Hero):
 *  - lafka_home_hero_eyebrow         (text)
 *  - lafka_home_hero_headline        (text)
 *  - lafka_home_hero_subhead         (textarea)
 *  - lafka_home_hero_primary_cta_*   (label + url)
 *  - lafka_home_hero_secondary_cta_* (label + url)
 *  - lafka_home_hero_image_id        (media)
 *  - lafka_home_hero_bg_url          (text — string URL default, when no
 *    media uploaded yet, lets operators preview the OSS bundle look
 *    without picking an image)
 *  - lafka_home_hero_overlay         (boolean — scrim toggle for dark
 *    bg images; off for the default light yellow texture)
 *  - lafka_home_hero_show_status     (boolean — render the open/closed
 *    status pill via lafka_service_eta_get_data())
 *
 * @package Lafka
 * @since   5.46.0
 */

defined( 'ABSPATH' ) || exit;

$lafka_hero_eyebrow      = (string) get_theme_mod( 'lafka_home_hero_eyebrow', __( 'Order online', 'lafka' ) );
$lafka_hero_headline     = (string) get_theme_mod( 'lafka_home_hero_headline', get_bloginfo( 'name' ) );
$lafka_hero_subhead      = (string) get_theme_mod( 'lafka_home_hero_subhead', get_bloginfo( 'description' ) );
$lafka_hero_primary_label = (string) get_theme_mod( 'lafka_home_hero_primary_cta_label', __( 'Order Now', 'lafka' ) );
$lafka_hero_primary_url   = (string) get_theme_mod( 'lafka_home_hero_primary_cta_url', function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
$lafka_hero_secondary_label = (string) get_theme_mod( 'lafka_home_hero_secondary_cta_label', __( 'View Menu', 'lafka' ) );

$lafka_hero_secondary_default = '';
$lafka_hero_menu_page         = get_page_by_path( 'menu' );
if ( $lafka_hero_menu_page instanceof WP_Post ) {
	$lafka_hero_secondary_default = get_permalink( $lafka_hero_menu_page );
}
$lafka_hero_secondary_url = (string) get_theme_mod( 'lafka_home_hero_secondary_cta_url', $lafka_hero_secondary_default );

// Background image — Customizer media picker preferred, falls back to a
// Customizer text-URL field, falls back to a theme-defined default via
// `lafka_home_hero_default_bg_url` filter. The filter is the OSS-bundle
// hook for shipping a brand-aligned default without the operator
// having to upload anything.
$lafka_hero_image_id  = (int) get_theme_mod( 'lafka_home_hero_image_id', 0 );
$lafka_hero_image_src = $lafka_hero_image_id ? wp_get_attachment_image_url( $lafka_hero_image_id, 'full' ) : '';
if ( '' === $lafka_hero_image_src ) {
	$lafka_hero_image_src = (string) get_theme_mod(
		'lafka_home_hero_bg_url',
		(string) apply_filters( 'lafka_home_hero_default_bg_url', '' )
	);
}

$lafka_hero_overlay      = (bool) get_theme_mod( 'lafka_home_hero_overlay', false );
$lafka_hero_show_status  = (bool) get_theme_mod( 'lafka_home_hero_show_status', true );
$lafka_hero_service_data = ( $lafka_hero_show_status && function_exists( 'lafka_service_eta_get_data' ) ) ? lafka_service_eta_get_data() : null;

$lafka_hero_classes = array( 'lafka-home-hero' );
if ( $lafka_hero_image_src ) {
	$lafka_hero_classes[] = 'lafka-home-hero--has-image';
}
if ( $lafka_hero_image_src && $lafka_hero_overlay ) {
	$lafka_hero_classes[] = 'lafka-home-hero--has-overlay';
}
?>
<section class="<?php echo esc_attr( implode( ' ', $lafka_hero_classes ) ); ?>" aria-label="<?php esc_attr_e( 'Welcome', 'lafka' ); ?>">

	<?php if ( $lafka_hero_image_src ) : ?>
		<div class="lafka-home-hero__media" aria-hidden="true">
			<img
				class="lafka-home-hero__image"
				src="<?php echo esc_url( $lafka_hero_image_src ); ?>"
				alt=""
				role="presentation"
				loading="eager"
				fetchpriority="high"
			>
			<?php if ( $lafka_hero_overlay ) : ?>
				<div class="lafka-home-hero__scrim"></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="lafka-home-hero__inner">
		<div class="lafka-home-hero__copy">

			<?php
            if ( $lafka_hero_service_data ) :
				$lafka_is_open = ! empty( $lafka_hero_service_data['is_open'] );
				$lafka_pickup  = isset( $lafka_hero_service_data['pickup_minutes'] ) ? (int) $lafka_hero_service_data['pickup_minutes'] : 0;
				?>
				<p class="lafka-status-pill <?php echo $lafka_is_open ? 'lafka-status-pill--open' : 'lafka-status-pill--closed'; ?>">
					<span class="lafka-status-pill__dot" aria-hidden="true"></span>
					<?php if ( $lafka_is_open && $lafka_pickup ) : ?>
						<?php printf( esc_html__( 'Open now · Pickup ~%d min', 'lafka' ), (int) $lafka_pickup ); ?>
					<?php elseif ( $lafka_is_open ) : ?>
						<?php esc_html_e( 'Open now', 'lafka' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Closed — order ahead', 'lafka' ); ?>
					<?php endif; ?>
				</p>
			<?php elseif ( '' !== $lafka_hero_eyebrow ) : ?>
				<p class="lafka-home-hero__eyebrow"><?php echo esc_html( $lafka_hero_eyebrow ); ?></p>
			<?php endif; ?>

			<h1 class="lafka-home-hero__headline lafka-display"><?php echo esc_html( $lafka_hero_headline ); ?></h1>

			<?php if ( '' !== $lafka_hero_subhead ) : ?>
				<p class="lafka-home-hero__subhead"><?php echo esc_html( $lafka_hero_subhead ); ?></p>
			<?php endif; ?>

			<div class="lafka-home-hero__actions">

				<?php if ( '' !== $lafka_hero_primary_url ) : ?>
					<a class="lafka-btn lafka-btn--primary lafka-btn--lg" href="<?php echo esc_url( $lafka_hero_primary_url ); ?>">
						<?php echo esc_html( $lafka_hero_primary_label ); ?>
					</a>
				<?php endif; ?>

				<?php if ( '' !== $lafka_hero_secondary_url ) : ?>
					<a class="lafka-btn lafka-btn--ghost lafka-btn--lg" href="<?php echo esc_url( $lafka_hero_secondary_url ); ?>">
						<?php echo esc_html( $lafka_hero_secondary_label ); ?>
					</a>
				<?php endif; ?>

			</div>
		</div>
	</div>
</section>
