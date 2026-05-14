<?php
/**
 * page-menu.php — Custom landing template for the /menu/ slug.
 *
 * The legacy /menu/ page content is a hand-built WPBakery Visual Composer
 * layout that only renders a single category. This template replaces it
 * with an auto-generated mobile-first grid of every top-level WooCommerce
 * product category so the menu landing always reflects the current catalog.
 *
 * Implementation: registers a one-shot the_content filter then includes
 * page.php so all the existing chrome (title bar, breadcrumb, sidebar,
 * RTL handling, off-canvas, header/footer) is reused verbatim.
 *
 * Operators who want to add a custom intro can edit this template; the
 * page content in wp-admin is intentionally bypassed.
 *
 * @since 5.23.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_menu_landing_render_grid' ) ) {
	/**
	 * Replace the post content with an auto-built product_cat grid.
	 */
	function lafka_menu_landing_render_grid( $content ) {
		// Only act on the canonical /menu/ page in the main loop.
		if ( ! is_singular( 'page' ) || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Hide WC's default "uncategorized" slug — it's a system bucket and
		// looks like a real category to customers. Operators can configure
		// the default via WC settings; we read it dynamically so renames
		// (or non-default values) are still excluded.
		$default_cat_id = (int) get_option( 'default_product_cat', 0 );
		$exclude_ids    = array();
		if ( $default_cat_id ) {
			$exclude_ids[] = $default_cat_id;
		}
		$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
		if ( $uncategorized_term && ! in_array( (int) $uncategorized_term->term_id, $exclude_ids, true ) ) {
			$exclude_ids[] = (int) $uncategorized_term->term_id;
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => 0,
				'orderby'    => 'menu_order',
				'exclude'    => $exclude_ids,
			)
		);

		if ( is_wp_error( $categories ) || ! $categories ) {
			return $content;
		}

		ob_start();
		?>
		<section class="lafka-menu-landing" aria-label="<?php esc_attr_e( 'Menu categories', 'lafka' ); ?>">
			<p class="lafka-menu-landing__intro">
				<?php esc_html_e( 'Fresh, made-to-order. Tap any category to see what we have.', 'lafka' ); ?>
			</p>
			<ul class="lafka-menu-landing__grid" role="list">
				<?php foreach ( $categories as $cat ) : ?>
					<?php
					$count_label = sprintf(
						/* translators: %d: number of items in this menu category */
						_n( '%d item', '%d items', (int) $cat->count, 'lafka' ),
						(int) $cat->count
					);
					$term_link = get_term_link( $cat );
					if ( is_wp_error( $term_link ) ) {
						continue;
					}
					$children = get_terms(
						array(
							'taxonomy'   => 'product_cat',
							'hide_empty' => true,
							'parent'     => (int) $cat->term_id,
							'orderby'    => 'menu_order',
						)
					);
					$has_children = ! is_wp_error( $children ) && ! empty( $children );
					$card_classes = 'lafka-menu-landing__card';
					if ( $has_children ) {
						$card_classes .= ' lafka-menu-landing__card--has-children';
					}
					?>
					<li class="<?php echo esc_attr( $card_classes ); ?>">
						<a class="lafka-menu-landing__card-link" href="<?php echo esc_url( $term_link ); ?>">
							<span class="lafka-menu-landing__card-title"><?php echo esc_html( $cat->name ); ?></span>
							<span class="lafka-menu-landing__card-count"><?php echo esc_html( $count_label ); ?></span>
						</a>
						<?php if ( $has_children ) : ?>
							<ul class="lafka-menu-landing__subcats" role="list">
								<?php foreach ( $children as $child ) : ?>
									<?php
									$child_link = get_term_link( $child );
									if ( is_wp_error( $child_link ) ) {
										continue;
									}
									?>
									<li class="lafka-menu-landing__subcat">
										<a class="lafka-menu-landing__subcat-link" href="<?php echo esc_url( $child_link ); ?>">
											<?php echo esc_html( $child->name ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

add_filter( 'the_content', 'lafka_menu_landing_render_grid', 99 );
add_action(
	'wp_enqueue_scripts',
	function () {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '';
		wp_enqueue_style(
			'lafka-menu-landing',
			get_template_directory_uri() . '/styles/page-menu' . $suffix . '.css',
			array( 'lafka-style' ),
			lafka_asset_version( '/styles/page-menu' . $suffix . '.css' )
		);
	},
	20
);

require __DIR__ . '/page.php';
