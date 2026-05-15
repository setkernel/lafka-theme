<?php
/**
 * Partial: Home "Made here" story strip (v5.49.0)
 *
 * 2-column split: kitchen/owner/dough photo on one side, short
 * editorial paragraph on the other. Builds local-brand trust and
 * differentiates from chains — per UX spec, this is positioned in
 * the lower half of the page so it doesn't compete with the menu CTA.
 *
 * Customizer reads:
 *  - lafka_home_story_eyebrow  (default: "Made here")
 *  - lafka_home_story_headline
 *  - lafka_home_story_body
 *  - lafka_home_story_image_id
 *  - lafka_home_story_cta_label / url
 *  - lafka_home_story_visible (boolean — default true)
 *
 * @package Lafka
 * @since   5.49.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! (bool) get_theme_mod( 'lafka_home_story_visible', true ) ) {
	return;
}

// v5.52.0: auto-hide when operator hasn't set BOTH a custom headline
// AND a body text. Without either, the section is just the default
// placeholder copy — reads as filler, makes the page feel empty.
$lafka_story_custom_headline = trim( (string) get_theme_mod( 'lafka_home_story_headline', '' ) );
$lafka_story_custom_body     = trim( (string) get_theme_mod( 'lafka_home_story_body', '' ) );
$lafka_story_custom_image    = (int) get_theme_mod( 'lafka_home_story_image_id', 0 );
if ( '' === $lafka_story_custom_headline && '' === $lafka_story_custom_body && ! $lafka_story_custom_image ) {
	return;
}

$lafka_story_eyebrow  = (string) get_theme_mod( 'lafka_home_story_eyebrow', __( 'Made here', 'lafka' ) );
$lafka_story_headline = (string) get_theme_mod(
	'lafka_home_story_headline',
	sprintf(
		/* translators: %s — locality/city. */
		__( 'From-scratch food, made fresh in %s.', 'lafka' ),
		(string) get_option( 'woocommerce_store_city', __( 'our kitchen', 'lafka' ) )
	)
);
$lafka_story_body = (string) get_theme_mod(
	'lafka_home_story_body',
	__( 'Hand-stretched dough, locally sourced ingredients, and recipes refined over years of serving our neighbors. Every order is made when you place it — never sitting under a heat lamp.', 'lafka' )
);

$lafka_story_cta_label = (string) get_theme_mod( 'lafka_home_story_cta_label', __( 'Visit us', 'lafka' ) );
$lafka_story_cta_url   = (string) get_theme_mod( 'lafka_home_story_cta_url', '' );

$lafka_story_image_id  = (int) get_theme_mod( 'lafka_home_story_image_id', 0 );
$lafka_story_image_src = $lafka_story_image_id ? wp_get_attachment_image_url( $lafka_story_image_id, 'large' ) : '';
?>
<?php
// Variant: when no image is set, render single-column centered to avoid
// the awkward half-empty 2-col layout from v5.49.0.
$lafka_story_classes = array( 'lafka-home-story' );
if ( ! $lafka_story_image_src ) {
	$lafka_story_classes[] = 'lafka-home-story--text-only';
}
?>
<section class="<?php echo esc_attr( implode( ' ', $lafka_story_classes ) ); ?>" aria-labelledby="lafka-home-story-heading">
	<div class="lafka-container lafka-home-story__inner">

		<?php if ( $lafka_story_image_src ) : ?>
			<div class="lafka-home-story__media">
				<img
					class="lafka-home-story__image"
					src="<?php echo esc_url( $lafka_story_image_src ); ?>"
					alt=""
					role="presentation"
					loading="lazy"
				>
			</div>
		<?php endif; ?>

		<header class="lafka-section-head <?php echo $lafka_story_image_src ? 'lafka-section-head--start' : ''; ?> lafka-home-story__copy">
			<?php if ( '' !== $lafka_story_eyebrow ) : ?>
				<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_story_eyebrow ); ?></p>
			<?php endif; ?>
			<h2 id="lafka-home-story-heading" class="lafka-section-headline"><?php echo esc_html( $lafka_story_headline ); ?></h2>
			<p class="lafka-section-subhead"><?php echo esc_html( $lafka_story_body ); ?></p>

			<?php if ( '' !== $lafka_story_cta_url ) : ?>
				<a class="lafka-btn lafka-btn--ghost" href="<?php echo esc_url( $lafka_story_cta_url ); ?>">
					<?php echo esc_html( $lafka_story_cta_label ); ?>
				</a>
			<?php endif; ?>
		</header>
	</div>
</section>
