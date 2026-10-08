<?php
/**
 * Partial: Editorial social proof strip (dark bar below hero).
 *
 * Settings: lafka_editorial_home_proof_quote / _stars / _stats
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$lafka_quote = get_theme_mod( 'lafka_editorial_home_proof_quote', '' );
$lafka_stars = (int) get_theme_mod( 'lafka_editorial_home_proof_stars', 0 );
$lafka_stats = get_theme_mod( 'lafka_editorial_home_proof_stats', '' );
/**
 * Filter the editorial social-proof star rating ( 0 hides the stars row ).
 *
 * @param int $stars Star rating, 0-5.
 */
$lafka_stars = (int) apply_filters( 'lafka_editorial_home_proof_stars', $lafka_stars );
$lafka_stars = max( 0, min( 5, $lafka_stars ) );

if ( ! $lafka_quote && ! $lafka_stats ) {
	return; // nothing to show — render nothing
}

$lafka_star_str = '';
if ( $lafka_stars > 0 ) {
	$lafka_star_str = str_repeat( '&#9733; ', $lafka_stars );
}
?>
<div class="social-proof">
	<?php if ( $lafka_stars > 0 ) : ?>
	<div class="stars"><?php echo wp_kses( $lafka_star_str, lafka_allowed_html() ); ?></div>
	<?php endif; ?>
	<?php if ( $lafka_quote ) : ?>
	<div class="quote">&ldquo;<?php echo esc_html( $lafka_quote ); ?>&rdquo;</div>
	<?php endif; ?>
	<?php if ( $lafka_stats ) : ?>
	<div class="stats"><?php echo esc_html( $lafka_stats ); ?></div>
	<?php endif; ?>
</div>
