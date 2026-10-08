<?php
/**
 * Partial: Editorial category cards grid (8 slots, 4-col layout).
 *
 * One card can be marked as "spotlight" (2×2). Settings per slot:
 *   lafka_editorial_home_card_{N}_label / _image / _url / _meta / _spotlight
 *
 * Only renders if at least one card has a label or image configured.
 *
 * @package Lafka
 */

defined( 'ABSPATH' ) || exit;

// Build card data from theme mods.
$lafka_cards = array();
for ( $lafka_i = 1; $lafka_i <= 8; $lafka_i++ ) {
	$lafka_cards[] = array(
		'label'     => get_theme_mod( "lafka_editorial_home_card_{$lafka_i}_label", '' ),
		'image'     => get_theme_mod( "lafka_editorial_home_card_{$lafka_i}_image", '' ),
		'url'       => get_theme_mod( "lafka_editorial_home_card_{$lafka_i}_url", '' ),
		'meta'      => get_theme_mod( "lafka_editorial_home_card_{$lafka_i}_meta", '' ),
		'spotlight' => (bool) get_theme_mod( "lafka_editorial_home_card_{$lafka_i}_spotlight", false ),
	);
}

// Only render when at least one card has content.
$lafka_has_content = false;
foreach ( $lafka_cards as $lafka_c ) {
	if ( $lafka_c['label'] || $lafka_c['image'] ) {
		$lafka_has_content = true;
		break;
	}
}
if ( ! $lafka_has_content ) {
	return;
}
?>
<section>
	<div class="cards-wrap">
		<?php
		foreach ( $lafka_cards as $lafka_card ) :
			if ( ! $lafka_card['label'] && ! $lafka_card['image'] ) {
				continue; // skip unconfigured slots
			}
			$lafka_class = $lafka_card['spotlight'] ? 'card spotlight' : 'card';
			$lafka_tag   = $lafka_card['url'] ? 'a' : 'div';
			?>
		<<?php echo esc_attr( $lafka_tag ); ?> class="<?php echo esc_attr( $lafka_class ); ?>"
			<?php
			if ( $lafka_card['url'] ) :
				?>
			href="<?php echo esc_url( $lafka_card['url'] ); ?>"<?php endif; ?>>
			<?php if ( $lafka_card['image'] ) : ?>
			<div class="photo" style="background-image: url('<?php echo esc_url( $lafka_card['image'] ); ?>')"></div>
			<?php endif; ?>
			<div class="content">
				<?php if ( $lafka_card['label'] ) : ?>
				<div class="name"><?php echo esc_html( $lafka_card['label'] ); ?></div>
				<?php endif; ?>
				<?php if ( $lafka_card['meta'] ) : ?>
				<div class="meta"><?php echo esc_html( $lafka_card['meta'] ); ?></div>
				<?php endif; ?>
			</div>
		</<?php echo esc_attr( $lafka_tag ); ?>>
		<?php endforeach; ?>
	</div>
</section>
