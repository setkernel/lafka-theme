<?php
/**
 * Partial: Editorial "Our Story" section.
 *
 * Settings: lafka_editorial_home_story_label / _h2_before / _h2_em / _h2_after
 *           _p1 / _pullquote / _p2 / _image
 *
 * Renders nothing if no content is configured.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_label     = get_theme_mod( 'lafka_editorial_home_story_label', '' );
$lafka_h2_before = get_theme_mod( 'lafka_editorial_home_story_h2_before', '' );
$lafka_h2_em     = get_theme_mod( 'lafka_editorial_home_story_h2_em', '' );
$lafka_h2_after  = get_theme_mod( 'lafka_editorial_home_story_h2_after', '' );
$lafka_p1        = get_theme_mod( 'lafka_editorial_home_story_p1', '' );
$lafka_pullquote = get_theme_mod( 'lafka_editorial_home_story_pullquote', '' );
$lafka_p2        = get_theme_mod( 'lafka_editorial_home_story_p2', '' );
$lafka_image     = get_theme_mod( 'lafka_editorial_home_story_image', '' );

if ( ! $lafka_h2_before && ! $lafka_h2_em && ! $lafka_h2_after && ! $lafka_p1 && ! $lafka_p2 && ! $lafka_image ) {
	return;
}
?>
<section class="story-section">
	<div class="story-grid">

		<?php if ( $lafka_image ) : ?>
		<div class="story-photo">
			<img src="<?php echo esc_url( $lafka_image ); ?>" alt="" loading="lazy">
		</div>
		<?php endif; ?>

		<div class="story-text">
			<?php if ( $lafka_label ) : ?>
			<div class="label"><?php echo esc_html( $lafka_label ); ?></div>
			<?php endif; ?>

			<?php if ( $lafka_h2_before || $lafka_h2_em || $lafka_h2_after ) : ?>
			<h2>
				<?php echo esc_html( $lafka_h2_before ); ?>
				<?php if ( $lafka_h2_em ) : ?>
				<em><?php echo esc_html( $lafka_h2_em ); ?></em>
				<?php endif; ?>
				<?php echo esc_html( $lafka_h2_after ); ?>
			</h2>
			<?php endif; ?>

			<?php if ( $lafka_p1 ) : ?>
			<p><?php echo esc_html( $lafka_p1 ); ?></p>
			<?php endif; ?>

			<?php if ( $lafka_pullquote ) : ?>
			<blockquote class="pullquote"><?php echo esc_html( $lafka_pullquote ); ?></blockquote>
			<?php endif; ?>

			<?php if ( $lafka_p2 ) : ?>
			<p><?php echo esc_html( $lafka_p2 ); ?></p>
			<?php endif; ?>
		</div>

	</div>
</section>
