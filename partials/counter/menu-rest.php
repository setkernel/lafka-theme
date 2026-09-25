<?php
/**
 * Counter layout: "More from our menu" — every remaining category in
 * WooCommerce order (compact rows, `lafka_counter_menu_limit` each, "See all
 * N" when truncated), with a plain jump index of links. Two columns at ≥1024.
 * Off-screen sections skip rendering work (content-visibility).
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_rest          = ( $args['sections'] ?? lafka_counter_sections() )['rest'];
$lafka_rest_settings = $args['settings'] ?? lafka_counter_settings();
if ( ! $lafka_rest ) {
	return;
}
?>
<section class="lafka-counter-rest" aria-labelledby="lafka-counter-rest-title">
	<div class="lafka-counter-wrap">
		<div class="lafka-counter-head">
			<h2 id="lafka-counter-rest-title" class="lafka-counter-head__title"><?php echo esc_html( $lafka_rest_settings['menu_heading'] ); ?></h2>
			<a class="lafka-counter-link" href="<?php echo esc_url( lafka_theme_menu_url() ); ?>"><?php esc_html_e( 'See the full menu', 'lafka' ); ?></a>
		</div>
		<?php if ( $lafka_rest_settings['jump_links'] && count( $lafka_rest ) > 1 ) : ?>
			<nav class="lafka-jump" aria-label="<?php esc_attr_e( 'Menu sections', 'lafka' ); ?>">
				<ul class="lafka-jump__list">
					<?php foreach ( $lafka_rest as $lafka_rest_block ) : ?>
						<li><a href="<?php echo esc_attr( '#lafka-cat-' . sanitize_title( (string) $lafka_rest_block['term']->slug ) ); ?>"><?php echo esc_html( $lafka_rest_block['term']->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
		<div class="lafka-counter-rest__grid">
			<?php
			foreach ( $lafka_rest as $lafka_rest_block ) {
				get_template_part(
					'partials/counter/menu-section',
					null,
					array(
						'term'   => $lafka_rest_block['term'],
						'ids'    => $lafka_rest_block['ids'],
						'total'  => $lafka_rest_block['total'],
						'style'  => $lafka_rest_settings['menu_style'],
						'thumbs' => $lafka_rest_settings['menu_thumbs'],
						'list'   => __( 'Home', 'lafka' ),
						'heading_level' => 3,
					)
				);
			}
			?>
		</div>
	</div>
</section>
