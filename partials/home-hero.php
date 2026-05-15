<?php
/**
 * Partial: Home hero
 *
 * Edge-to-edge hero with contained text area. Headline + subhead + primary
 * CTA + secondary CTA + optional featured image. All copy comes from the
 * Customizer panel "Lafka — Home Page" with smart defaults from site
 * identity (blog name / tagline) and WC ("Order Online" → /shop).
 *
 * Reads:
 *  - lafka_home_hero_eyebrow   (text, default: "Order online")
 *  - lafka_home_hero_headline  (text, default: get_bloginfo('name'))
 *  - lafka_home_hero_subhead   (textarea, default: get_bloginfo('description'))
 *  - lafka_home_hero_primary_cta_label  (text, default: "Order Now")
 *  - lafka_home_hero_primary_cta_url    (url, default: wc_get_page_permalink('shop'))
 *  - lafka_home_hero_secondary_cta_label (text, default: "View Menu")
 *  - lafka_home_hero_secondary_cta_url   (url, default: /menu/ if exists else '')
 *  - lafka_home_hero_image_id (image attachment, default: 0 — falls back
 *    to a placeholder gradient if no image set)
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

$lafka_hero_image_id  = (int) get_theme_mod( 'lafka_home_hero_image_id', 0 );
$lafka_hero_image_src = $lafka_hero_image_id ? wp_get_attachment_image_url( $lafka_hero_image_id, 'full' ) : '';
?>
<section class="lafka-home-hero<?php echo $lafka_hero_image_src ? ' lafka-home-hero--has-image' : ' lafka-home-hero--no-image'; ?>" aria-label="<?php esc_attr_e( 'Welcome', 'lafka' ); ?>">

	<?php if ( $lafka_hero_image_src ) : ?>
		<div class="lafka-home-hero__media">
			<img
				class="lafka-home-hero__image"
				src="<?php echo esc_url( $lafka_hero_image_src ); ?>"
				alt=""
				role="presentation"
				loading="eager"
				fetchpriority="high"
			>
		</div>
	<?php endif; ?>

	<div class="lafka-home-hero__inner">
		<div class="lafka-home-hero__copy">

			<?php if ( '' !== $lafka_hero_eyebrow ) : ?>
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
