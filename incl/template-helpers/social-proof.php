<?php
/**
 * Social proof widget — star rating + review count, optionally linking
 * to an external reviews page. Operator-driven; values come from the
 * "Lafka — Social Proof" Customizer panel. Defaults are empty so the
 * widget is invisible until the operator opts in.
 *
 * Implements conversion audit #5 (cited 32% PDP lift for visible
 * review counts).
 *
 * Filter surface:
 *   lafka_social_proof_data(array $data)  — override the data array
 *   lafka_social_proof_html(string $html, array $data) — replace markup
 *
 * @package Lafka
 * @since   5.29.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_social_proof_get_data' ) ) {
	/**
	 * Pull the social-proof data from Customizer settings.
	 *
	 * @return array{rating: float, count: int, provider: string, url: string}|null
	 *         Null when nothing is configured (caller should not render).
	 */
	function lafka_social_proof_get_data() {
		$rating   = trim( (string) get_theme_mod( 'lafka_social_proof_rating', '' ) );
		$count    = (int) get_theme_mod( 'lafka_social_proof_count', 0 );
		$provider = trim( (string) get_theme_mod( 'lafka_social_proof_provider', '' ) );
		$url      = trim( (string) get_theme_mod( 'lafka_social_proof_url', '' ) );

		// "Nothing configured" = both rating and count empty.
		if ( '' === $rating && 0 === $count ) {
			$data = null;
		} else {
			$data = array(
				'rating'   => '' === $rating ? 0.0 : (float) $rating,
				'count'    => $count,
				'provider' => $provider,
				'url'      => $url,
			);
		}

		return apply_filters( 'lafka_social_proof_data', $data );
	}
}

if ( ! function_exists( 'lafka_social_proof_render' ) ) {
	/**
	 * Echo the social proof widget. No-op if nothing is configured.
	 */
	function lafka_social_proof_render() {
		$data = lafka_social_proof_get_data();
		if ( ! $data ) {
			return;
		}

		$rating       = max( 0.0, min( 5.0, (float) $data['rating'] ) );
		$count        = (int) $data['count'];
		$provider     = (string) $data['provider'];
		$url          = (string) $data['url'];
		$rating_pct   = ( $rating / 5.0 ) * 100.0;
		$has_link     = '' !== $url;
		$tag          = $has_link ? 'a' : 'div';
		$href_attr    = $has_link ? ' href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow"' : '';
		$rating_label = '' !== (string) $data['rating'] ? number_format_i18n( $rating, 1 ) : '';

		// Accessible label: "4.8 out of 5 stars based on 312 Google reviews"
		$aria_parts = array();
		if ( $rating_label ) {
			/* translators: %s: rating value (e.g. 4.8) */
			$aria_parts[] = sprintf( __( '%s out of 5 stars', 'lafka' ), $rating_label );
		}
		if ( $count > 0 ) {
			if ( $provider ) {
				/* translators: 1: count, 2: provider (e.g. Google) */
				$aria_parts[] = sprintf( _n( '%1$d %2$s review', '%1$d %2$s reviews', $count, 'lafka' ), $count, $provider );
			} else {
				/* translators: %d: review count */
				$aria_parts[] = sprintf( _n( '%d review', '%d reviews', $count, 'lafka' ), $count );
			}
		}
		$aria_label = implode( ', ', $aria_parts );

		ob_start();
		?>
		<<?php echo esc_html( $tag ); ?>
			class="lafka-social-proof<?php echo $has_link ? ' lafka-social-proof--linked' : ''; ?>"
			<?php echo $href_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			aria-label="<?php echo esc_attr( $aria_label ); ?>"
		>
			<span class="lafka-social-proof__stars" aria-hidden="true">
				<span class="lafka-social-proof__stars-empty">★★★★★</span>
				<span class="lafka-social-proof__stars-filled" style="width: <?php echo esc_attr( (string) $rating_pct ); ?>%;">★★★★★</span>
			</span>
			<?php if ( $rating_label ) : ?>
				<span class="lafka-social-proof__rating"><?php echo esc_html( $rating_label ); ?></span>
			<?php endif; ?>
			<?php if ( $count > 0 ) : ?>
				<span class="lafka-social-proof__separator" aria-hidden="true">·</span>
				<span class="lafka-social-proof__count">
					<?php
					if ( $provider ) {
						printf(
							/* translators: 1: count, 2: provider (e.g. Google) */
							esc_html( _n( '%1$d %2$s review', '%1$d %2$s reviews', $count, 'lafka' ) ),
							(int) $count,
							esc_html( $provider )
						);
					} else {
						printf(
							/* translators: %d: review count */
							esc_html( _n( '%d review', '%d reviews', $count, 'lafka' ) ),
							(int) $count
						);
					}
					?>
				</span>
			<?php endif; ?>
		</<?php echo esc_html( $tag ); ?>>
		<?php
		$html = (string) ob_get_clean();
		echo apply_filters( 'lafka_social_proof_html', $html, $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'lafka_social_proof_render_pdp' ) ) {
	/**
	 * Hook into the WC single-product summary to render the widget under
	 * the title (priority 6 fires between title @ 5 and price @ 10).
	 */
	function lafka_social_proof_render_pdp() {
		if ( ! (bool) get_theme_mod( 'lafka_social_proof_show_pdp', true ) ) {
			return;
		}
		lafka_social_proof_render();
	}
}
add_action( 'woocommerce_single_product_summary', 'lafka_social_proof_render_pdp', 6 );
