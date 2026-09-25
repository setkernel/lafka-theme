<?php
/**
 * Counter layout: one category section — ruled head (h2 name, tagline, "See
 * all") + product rows.
 *
 * $args:
 *   term        WP_Term (required)
 *   ids         list<int> product ids (featured first, then menu order)
 *   total       int — the category's product count
 *   style       photo | compact
 *   thumbs      bool (compact)
 *   always_link bool — show "See all" even when nothing is truncated
 *   list        GA4 list name
 *   heading_level int — the section heading's level (default 2; 3 inside
 *               "More from our menu", whose own heading is the h2 — H-22)
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_sec_term = $args['term'] ?? null;
$lafka_sec_ids  = array_map( 'intval', (array) ( $args['ids'] ?? array() ) );
if ( ! is_object( $lafka_sec_term ) || ! $lafka_sec_ids || ! function_exists( 'wc_get_product' ) ) {
	return;
}
$lafka_sec_style   = 'compact' === ( $args['style'] ?? 'photo' ) ? 'compact' : 'photo';
$lafka_sec_level   = max( 2, min( 5, (int) ( $args['heading_level'] ?? 2 ) ) );
$lafka_sec_total   = (int) ( $args['total'] ?? count( $lafka_sec_ids ) );
$lafka_sec_anchor  = 'lafka-cat-' . sanitize_title( (string) $lafka_sec_term->slug );
$lafka_sec_tagline = lafka_category_tagline( $lafka_sec_term );
$lafka_sec_link    = get_term_link( $lafka_sec_term );
$lafka_sec_show    = is_string( $lafka_sec_link ) && ( ! empty( $args['always_link'] ) || $lafka_sec_total > count( $lafka_sec_ids ) );
/* translators: %s: number of items in the category */
$lafka_sec_all = (string) apply_filters( 'lafka_counter_see_all_label', sprintf( __( 'See all %s', 'lafka' ), number_format_i18n( $lafka_sec_total ) ), $lafka_sec_term, $lafka_sec_total );
?>
<section class="lafka-counter-section lafka-counter-section--<?php echo esc_attr( $lafka_sec_style ); ?>" id="<?php echo esc_attr( $lafka_sec_anchor ); ?>" style="<?php echo esc_attr( '--lafka-rows: ' . count( $lafka_sec_ids ) ); ?>" aria-labelledby="<?php echo esc_attr( $lafka_sec_anchor . '-h' ); ?>">
	<div class="lafka-counter-head lafka-counter-head--ruled">
		<div>
			<h<?php echo (int) $lafka_sec_level; ?> id="<?php echo esc_attr( $lafka_sec_anchor . '-h' ); ?>" class="lafka-counter-head__title"><?php echo esc_html( $lafka_sec_term->name ); ?></h<?php echo (int) $lafka_sec_level; ?>>
			<?php if ( '' !== $lafka_sec_tagline ) : ?>
				<p class="lafka-counter-head__lead"><?php echo esc_html( $lafka_sec_tagline ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $lafka_sec_show ) : ?>
			<a class="lafka-counter-link" href="<?php echo esc_url( $lafka_sec_link ); ?>"><?php echo esc_html( $lafka_sec_all ); ?><span class="screen-reader-text"> <?php echo esc_html( $lafka_sec_term->name ); ?></span></a>
		<?php endif; ?>
	</div>
	<ul class="lafka-rows">
		<?php
		foreach ( $lafka_sec_ids as $lafka_sec_id ) {
			$lafka_sec_product = wc_get_product( $lafka_sec_id );
			if ( ! $lafka_sec_product ) {
				continue;
			}
			get_template_part(
				'partials/counter/menu-row',
				null,
				array(
					'product' => $lafka_sec_product,
					'style'   => $lafka_sec_style,
					'thumbs'  => $args['thumbs'] ?? true,
					'list'    => (string) ( $args['list'] ?? $lafka_sec_term->name ),
					'heading' => $lafka_sec_level + 1,
				)
			);
		}
		?>
	</ul>
</section>
