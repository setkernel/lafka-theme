<?php
/**
 * Counter layout: the two co-star categories side by side (≥1024), stacked
 * below — photo rows, `lafka_counter_costar_limit` each.
 *
 * @package Lafka
 * @since   7.2.0 (GX4)
 */

defined( 'ABSPATH' ) || exit;

$lafka_costars = ( $args['sections'] ?? lafka_counter_sections() )['costars'];
if ( ! $lafka_costars ) {
	return;
}
?>
<div class="lafka-counter-costars">
	<div class="lafka-counter-costars__grid lafka-counter-wrap">
		<?php
		foreach ( $lafka_costars as $lafka_costar ) {
			get_template_part(
				'partials/counter/menu-section',
				null,
				array(
					'term'        => $lafka_costar['term'],
					'ids'         => $lafka_costar['ids'],
					'total'       => $lafka_costar['total'],
					'style'       => 'photo',
					'always_link' => true,
					'list'        => __( 'Home', 'lafka' ),
				)
			);
		}
		?>
	</div>
</div>
