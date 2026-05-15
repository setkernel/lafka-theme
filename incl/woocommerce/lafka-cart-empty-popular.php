<?php
/**
 * Empty-cart "Popular tonight" section — conversion audit #10.
 *
 * When a customer lands on /cart/ with no items (accidental tap, expired
 * session, etc.) WC renders a dead-end "Your cart is currently empty.
 * Return to shop." message. This module hooks `woocommerce_cart_is_empty`
 * and surfaces the top-selling products as quick-tap entry points back
 * into the funnel.
 *
 * Filter surface:
 *   lafka_cart_empty_popular_count(int $count) — number of products
 *   lafka_cart_empty_popular_args(array $args) — WP_Query args
 *   lafka_cart_empty_popular_html(string $html) — replace markup
 *
 * @package Lafka\WooCommerce
 * @since   5.32.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_cart_empty_popular_render' ) ) {
	/**
	 * Render the "Popular" section on the empty cart page.
	 */
	function lafka_cart_empty_popular_render() {
		if ( ! (bool) get_theme_mod( 'lafka_cart_empty_popular_enabled', true ) ) {
			return;
		}
		if ( ! function_exists( 'wc_get_products' ) ) {
			return;
		}

		$count = (int) apply_filters( 'lafka_cart_empty_popular_count', 6 );

		$args = array(
			'status'  => 'publish',
			'limit'   => $count,
			'orderby' => 'meta_value_num',
			'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'   => 'DESC',
			'visibility' => 'visible',
		);
		$args = (array) apply_filters( 'lafka_cart_empty_popular_args', $args );

		$products = wc_get_products( $args );
		if ( empty( $products ) ) {
			return;
		}

		ob_start();
		?>
		<section class="lafka-cart-empty-popular" aria-label="<?php esc_attr_e( 'Add a side', 'lafka' ); ?>">
			<h2 class="lafka-cart-empty-popular__title"><?php esc_html_e( 'Add a side?', 'lafka' ); ?></h2>
			<p class="lafka-cart-empty-popular__intro"><?php esc_html_e( 'Pick up where the regulars left off — tap to start a new order.', 'lafka' ); ?></p>
			<ul class="lafka-cart-empty-popular__grid" role="list">
				<?php foreach ( $products as $product ) : ?>
					<?php
					if ( ! is_a( $product, 'WC_Product' ) ) {
						continue;
					}
					$thumb_id   = $product->get_image_id();
					$image_html = $thumb_id
						? wp_get_attachment_image(
							$thumb_id,
							'woocommerce_thumbnail',
							false,
							array(
								'loading'  => 'lazy',
								'decoding' => 'async',
								'class'    => 'lafka-cart-empty-popular__img',
								'alt'      => $product->get_name(),
							)
						)
						: '';
					$price = '';
					if ( $product->is_type( 'variable' ) ) {
						$min   = $product->get_variation_price( 'min' );
						if ( '' !== $min ) {
							/* translators: %s: starting price */
							$price = sprintf( esc_html__( 'from %s', 'lafka' ), wc_price( $min ) );
						}
					} else {
						$p = $product->get_price();
						if ( '' !== $p ) {
							$price = wc_price( $p );
						}
					}
					?>
					<li class="lafka-cart-empty-popular__item">
						<a class="lafka-cart-empty-popular__link" href="<?php echo esc_url( $product->get_permalink() ); ?>">
							<span class="lafka-cart-empty-popular__media">
								<?php
								if ( $image_html ) {
									echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									echo '<span class="lafka-cart-empty-popular__img lafka-cart-empty-popular__img--placeholder" aria-hidden="true"></span>';
								}
								?>
							</span>
							<span class="lafka-cart-empty-popular__name"><?php echo esc_html( $product->get_name() ); ?></span>
							<?php if ( $price ) : ?>
								<span class="lafka-cart-empty-popular__price"><?php echo wp_kses_post( $price ); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		$html = (string) ob_get_clean();
		echo apply_filters( 'lafka_cart_empty_popular_html', $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

add_action( 'woocommerce_cart_is_empty', 'lafka_cart_empty_popular_render', 20 );
