<?php
/**
 * page-menu.php — Custom landing template for the /menu/ slug.
 *
 * Auto-renders all top-level WC product_cat terms as a mobile-first
 * grid. All operator-tunable knobs live in the "Lafka — Menu Landing"
 * Customizer panel (see incl/customizer-menu-landing.php). All render
 * hooks are filterable so power users can override without forking.
 *
 * Filter surface:
 *   lafka_menu_landing_categories(WP_Term[] $cats)  — re-order/filter the term list
 *   lafka_menu_landing_intro_text(string $text)     — replace the intro tagline
 *   lafka_menu_landing_show_count(bool)             — override item-count visibility
 *   lafka_menu_landing_show_subcats(bool)           — override subcategory chip visibility
 *   lafka_menu_landing_card_html(string, WP_Term)   — replace per-card HTML
 *   lafka_menu_landing_excluded_term_ids(int[])     — extend the excluded-cat list
 *
 * @since 5.23.0
 * @since 5.25.0 Customizer integration + filter hooks + CSS custom properties.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_menu_landing_render_grid' ) ) {
	/**
	 * Replace the post content with the auto-built product_cat grid.
	 *
	 * @param string $content Existing page content.
	 * @return string Either the grid markup, or the original content if
	 *                preconditions fail (so secondary loops are untouched).
	 */
	function lafka_menu_landing_render_grid( $content ) {
		if ( ! is_singular( 'page' ) || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Hide WC's default "uncategorized" bucket plus any operator-added
		// exclusions. The default-cat ID is read dynamically so renames /
		// non-default values are still caught.
		$default_cat_id     = (int) get_option( 'default_product_cat', 0 );
		$exclude_ids        = array();
		if ( $default_cat_id ) {
			$exclude_ids[] = $default_cat_id;
		}
		$uncategorized_term = get_term_by( 'slug', 'uncategorized', 'product_cat' );
		if ( $uncategorized_term && ! in_array( (int) $uncategorized_term->term_id, $exclude_ids, true ) ) {
			$exclude_ids[] = (int) $uncategorized_term->term_id;
		}
		$exclude_ids = (array) apply_filters( 'lafka_menu_landing_excluded_term_ids', $exclude_ids );

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

		$categories = (array) apply_filters( 'lafka_menu_landing_categories', $categories );

		$default_intro = __( 'Fresh, made-to-order. Tap any category to see what we have.', 'lafka' );
		$intro         = get_theme_mod( 'lafka_menu_landing_intro', '' );
		if ( '' === $intro ) {
			$intro = $default_intro;
		}
		$intro = (string) apply_filters( 'lafka_menu_landing_intro_text', $intro );

		$show_count   = (bool) apply_filters( 'lafka_menu_landing_show_count', (bool) get_theme_mod( 'lafka_menu_landing_show_count', true ) );
		$show_subcats = (bool) apply_filters( 'lafka_menu_landing_show_subcats', (bool) get_theme_mod( 'lafka_menu_landing_show_subcats', true ) );
		$card_style   = (string) get_theme_mod( 'lafka_menu_landing_style', 'text' );
		$card_style   = in_array( $card_style, array( 'text', 'image' ), true ) ? $card_style : 'text';

		ob_start();
		?>
		<section class="lafka-menu-landing lafka-menu-landing--style-<?php echo esc_attr( $card_style ); ?>" aria-label="<?php esc_attr_e( 'Menu categories', 'lafka' ); ?>">
			<?php if ( $intro ) : ?>
				<p class="lafka-menu-landing__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
			<ul class="lafka-menu-landing__grid" role="list">
				<?php
				foreach ( $categories as $cat ) :
					echo lafka_menu_landing_render_card( $cat, $card_style, $show_count, $show_subcats ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endforeach;
				?>
			</ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'lafka_menu_landing_render_card' ) ) {
	/**
	 * Render a single category card. Filterable via `lafka_menu_landing_card_html`.
	 *
	 * @param WP_Term $cat          The category term.
	 * @param string  $card_style   Either 'text' or 'image'.
	 * @param bool    $show_count   Whether to render the item-count line.
	 * @param bool    $show_subcats Whether to surface direct children as inline chips.
	 * @return string Pre-escaped HTML.
	 */
	function lafka_menu_landing_render_card( $cat, $card_style, $show_count, $show_subcats ) {
		$term_link = get_term_link( $cat );
		if ( is_wp_error( $term_link ) ) {
			return '';
		}

		$children = array();
		if ( $show_subcats ) {
			$kids = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'parent'     => (int) $cat->term_id,
					'orderby'    => 'menu_order',
				)
			);
			if ( ! is_wp_error( $kids ) ) {
				$children = $kids;
			}
		}

		$card_classes = array( 'lafka-menu-landing__card' );
		if ( $children ) {
			$card_classes[] = 'lafka-menu-landing__card--has-children';
		}

		$image_html = '';
		if ( 'image' === $card_style ) {
			$thumbnail_id = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );
			if ( $thumbnail_id ) {
				$image_html = wp_get_attachment_image(
					$thumbnail_id,
					'woocommerce_thumbnail',
					false,
					array(
						'loading'  => 'lazy',
						'decoding' => 'async',
						'class'    => 'lafka-menu-landing__card-image',
						'alt'      => $cat->name,
					)
				);
			}
		}

		$count_label = '';
		if ( $show_count ) {
			$count_label = sprintf(
				/* translators: %d: number of items in this menu category */
				_n( '%d item', '%d items', (int) $cat->count, 'lafka' ),
				(int) $cat->count
			);
		}

		ob_start();
		?>
		<li class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">
			<a class="lafka-menu-landing__card-link" href="<?php echo esc_url( $term_link ); ?>">
				<?php if ( 'image' === $card_style ) : ?>
					<span class="lafka-menu-landing__card-media">
						<?php
						if ( $image_html ) {
							echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<span class="lafka-menu-landing__card-image lafka-menu-landing__card-image--placeholder" aria-hidden="true"></span>';
						}
						?>
					</span>
				<?php endif; ?>
				<span class="lafka-menu-landing__card-title"><?php echo esc_html( $cat->name ); ?></span>
				<?php if ( $count_label ) : ?>
					<span class="lafka-menu-landing__card-count"><?php echo esc_html( $count_label ); ?></span>
				<?php endif; ?>
			</a>
			<?php if ( $children ) : ?>
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
		<?php
		$html = (string) ob_get_clean();
		return (string) apply_filters( 'lafka_menu_landing_card_html', $html, $cat );
	}
}

add_filter( 'the_content', 'lafka_menu_landing_render_grid', 99 );

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'lafka-menu-landing',
			get_template_directory_uri() . '/styles/page-menu.css',
			array( 'lafka-style' ),
			lafka_asset_version( '/styles/page-menu.css' )
		);

		// Emit the Customizer accent colour as a CSS custom property so
		// stylesheet can stay static and operators get live re-skin via
		// the Customizer's "Accent colour" control. Fallback is the
		// shipped default (#fccc4c) — see customizer-menu-landing.php.
		$accent = get_theme_mod( 'lafka_menu_landing_accent', '#fccc4c' );
		$accent = sanitize_hex_color( $accent );
		if ( $accent ) {
			wp_add_inline_style(
				'lafka-menu-landing',
				sprintf( '.lafka-menu-landing { --lafka-menu-accent: %s; }', $accent )
			);
		}
	},
	20
);

require __DIR__ . '/page.php';
