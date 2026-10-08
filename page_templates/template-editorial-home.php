<?php
/**
 * Template Name: Editorial Home (Lafka)
 * Template Post Type: page
 *
 * P6-UX-1 W3-T8: alternate homepage template with editorial / magazine
 * design language. Selectable via Page Attributes → Template dropdown.
 *
 * Content lives in Customizer (panel "Lafka — Editorial Home"). Defaults
 * to empty so this template is OSS-safe; restaurant-specific text comes
 * from the operator's WP install via Customizer.
 *
 * Pulls NAP / hours from lafka_get_restaurant_info() (W2-T1 source-of-truth).
 *
 * Assets (Fraunces font + editorial.css) are conditionally enqueued by a
 * wp_enqueue_scripts closure in functions.php — only when this template is active.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="lafka-editorial-home">

	<?php /* 1. Utility bar (top dark strip) */ ?>
	<?php get_template_part( 'partials/editorial-utility-bar' ); ?>

	<?php /* 2. Hero */ ?>
	<?php
	$lafka_hero_image = get_theme_mod( 'lafka_editorial_home_hero_image', '' );
	$lafka_eyebrow    = get_theme_mod( 'lafka_editorial_home_hero_eyebrow', '' );
	$lafka_h1_before  = get_theme_mod( 'lafka_editorial_home_hero_h1_before', '' );
	$lafka_h1_accent  = get_theme_mod( 'lafka_editorial_home_hero_h1_accent', '' );
	$lafka_h1_after   = get_theme_mod( 'lafka_editorial_home_hero_h1_after', '' );
	$lafka_subtitle   = get_theme_mod( 'lafka_editorial_home_hero_subtitle', '' );
	$lafka_cta1_label = get_theme_mod( 'lafka_editorial_home_cta1_label', '' );
	$lafka_cta1_url   = get_theme_mod( 'lafka_editorial_home_cta1_url', '' );
	$lafka_cta2_label = get_theme_mod( 'lafka_editorial_home_cta2_label', '' );
	$lafka_cta2_url   = get_theme_mod( 'lafka_editorial_home_cta2_url', '' );
	$lafka_cta3_label = get_theme_mod( 'lafka_editorial_home_cta3_label', '' );
	$lafka_cta3_url   = get_theme_mod( 'lafka_editorial_home_cta3_url', '' );
	?>

	<?php if ( $lafka_hero_image ) : ?>
	<style>
	.lafka-editorial-home .hero::before {
		background-image:
			linear-gradient(180deg, rgba(26,26,26,0.05) 0%, rgba(26,26,26,0.45) 60%, rgba(26,26,26,0.85) 100%),
			url('<?php echo esc_url( $lafka_hero_image ); ?>');
	}
	</style>
	<?php endif; ?>

	<section class="hero">
		<div class="hero-content">

			<?php if ( $lafka_eyebrow ) : ?>
			<div class="hero-eyebrow"><?php echo esc_html( $lafka_eyebrow ); ?></div>
			<?php endif; ?>

			<?php if ( $lafka_h1_before || $lafka_h1_accent || $lafka_h1_after ) : ?>
			<h1>
				<?php echo esc_html( $lafka_h1_before ); ?>
				<?php if ( $lafka_h1_accent ) : ?>
				<span class="accent"><?php echo esc_html( $lafka_h1_accent ); ?></span>
				<?php endif; ?>
				<?php echo esc_html( $lafka_h1_after ); ?>
			</h1>
			<?php endif; ?>

			<?php if ( $lafka_subtitle ) : ?>
			<p class="hero-sub"><?php echo esc_html( $lafka_subtitle ); ?></p>
			<?php endif; ?>

			<?php if ( $lafka_cta1_label || $lafka_cta2_label || $lafka_cta3_label ) : ?>
			<div class="hero-cta-row">
				<?php if ( $lafka_cta1_label && $lafka_cta1_url ) : ?>
				<a href="<?php echo esc_url( $lafka_cta1_url ); ?>" class="btn btn-primary">
					<?php echo esc_html( $lafka_cta1_label ); ?> <span class="arrow">&rarr;</span>
				</a>
				<?php endif; ?>

				<?php if ( $lafka_cta2_label && $lafka_cta2_url ) : ?>
				<a href="<?php echo esc_url( $lafka_cta2_url ); ?>" class="btn btn-secondary">
					<?php echo esc_html( $lafka_cta2_label ); ?>
				</a>
				<?php endif; ?>

				<?php if ( $lafka_cta3_label && $lafka_cta3_url ) : ?>
				<a href="<?php echo esc_url( $lafka_cta3_url ); ?>" class="btn btn-text">
					<?php echo esc_html( $lafka_cta3_label ); ?> <span class="arrow">&rarr;</span>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div>
	</section>

	<?php /* 3. Social proof strip */ ?>
	<?php get_template_part( 'partials/editorial-social-proof' ); ?>

	<?php /* 4. Category cards */ ?>
	<?php get_template_part( 'partials/editorial-cards' ); ?>

	<?php /* 5. Featured WC products */ ?>
	<?php get_template_part( 'partials/editorial-featured-products' ); ?>

	<?php /* 6. Our Story */ ?>
	<?php get_template_part( 'partials/editorial-story' ); ?>

	<?php /* 7. Visit / Map */ ?>
	<?php get_template_part( 'partials/editorial-visit' ); ?>

	<?php /* 8. Newsletter */ ?>
	<?php get_template_part( 'partials/editorial-newsletter' ); ?>

</div>

<?php
get_footer();
