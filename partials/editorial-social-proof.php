<?php
/**
 * Partial: Editorial social proof strip (dark bar below hero).
 *
 * Settings: lafka_editorial_home_proof_quote / _stars / _stats
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

$quote = get_theme_mod( 'lafka_editorial_home_proof_quote', '' );
$stars = (int) get_theme_mod( 'lafka_editorial_home_proof_stars', 0 );
$stats = get_theme_mod( 'lafka_editorial_home_proof_stats', '' );
/**
 * Filter the editorial social-proof star rating ( 0 hides the stars row ).
 *
 * @param int $stars Star rating, 0-5.
 */
$stars = (int) apply_filters( 'lafka_editorial_home_proof_stars', $stars );
$stars = max( 0, min( 5, $stars ) );

if ( ! $quote && ! $stats ) {
    return; // nothing to show — render nothing
}

$star_str = '';
if ( $stars > 0 ) {
    $star_str = str_repeat( '&#9733; ', $stars );
}
?>
<div class="social-proof">
    <?php if ( $stars > 0 ) : ?>
    <div class="stars"><?php echo $star_str; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- only &# entities ?></div>
    <?php endif; ?>
    <?php if ( $quote ) : ?>
    <div class="quote">&ldquo;<?php echo esc_html( $quote ); ?>&rdquo;</div>
    <?php endif; ?>
    <?php if ( $stats ) : ?>
    <div class="stats"><?php echo esc_html( $stats ); ?></div>
    <?php endif; ?>
</div>
