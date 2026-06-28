<?php
/**
 * Partial: Home categories — "What are you craving?" (v5.60.0 handoff rebuild).
 *
 * Per handoff /design_handoff_peppery_ordering/README.md "Home page > 2. Categories":
 *   - 6-tile grid (2 cols mobile, 3 tablet, 6 laptop+)
 *   - brand-50 background per tile, no border
 *   - 40px emoji icon, Fraunces 700 17px name, caption count
 *   - hover: brand-100 bg, translateY(-3px), shadow-2
 *
 * Categories auto-discovered from product_cat with ≥1 published product.
 *
 * @package Lafka
 * @since   5.60.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}

$lafka_cat_eyebrow  = (string) get_theme_mod( 'lafka_home_categories_eyebrow', __( 'Browse the menu', 'lafka' ) );
$lafka_cat_headline = (string) get_theme_mod( 'lafka_home_categories_headline', __( 'What are you craving?', 'lafka' ) );
$lafka_cat_limit    = (int) get_theme_mod( 'lafka_home_categories_limit', 6 );

// Map the "Order categories by" Customizer choice to get_terms orderby/order.
// Whitelisting via the map (rather than passing the stored value straight
// through) guards against an invalid value reaching get_terms.
$lafka_cat_orderby   = sanitize_key( (string) get_theme_mod( 'lafka_home_categories_orderby', 'count' ) );
$lafka_cat_order_map = array(
	'count'      => array( 'count', 'DESC' ),
	'name'       => array( 'name', 'ASC' ),
	'menu_order' => array( 'menu_order', 'ASC' ),
);
if ( ! isset( $lafka_cat_order_map[ $lafka_cat_orderby ] ) ) {
	$lafka_cat_orderby = 'count';
}
list( $lafka_cat_ob, $lafka_cat_dir ) = $lafka_cat_order_map[ $lafka_cat_orderby ];

$lafka_cat_args = array(
	'taxonomy'   => 'product_cat',
	'number'     => max( 1, $lafka_cat_limit ),
	'hide_empty' => true,
	'orderby'    => $lafka_cat_ob,
	'order'      => $lafka_cat_dir,
);

if ( function_exists( 'lafka_uncategorized_excluded_ids' ) ) {
	$lafka_cat_args['exclude'] = lafka_uncategorized_excluded_ids();
}

$lafka_cat_terms = get_terms( $lafka_cat_args );
if ( is_wp_error( $lafka_cat_terms ) || empty( $lafka_cat_terms ) ) {
	return;
}

// Emoji map for common category names — first hit wins. Operator can
// override per-term via lafka_category_emoji filter.
$lafka_cat_emoji_map = array(
	'pizza'    => '🍕',
	'poutine'  => '🍟',
	'burger'   => '🍔',
	'donair'   => '🥙',
	'wing'     => '🍗',
	'fish'     => '🐟',
	'salad'    => '🥗',
	'dessert'  => '🍰',
	'drink'    => '🥤',
	'beer'     => '🍺',
	'wine'     => '🍷',
	'combo'    => '🍽',
	'side'     => '🍞',
	'appetizer' => '🍤',
	'sub'      => '🥖',
	'sauce'    => '🥫',
	'kid'      => '🥪',
);
?>
<section class="lafka-cats" aria-labelledby="lafka-cats-heading">
	<div class="lafka-container">

		<header class="lafka-section-head">
			<p class="lafka-section-eyebrow"><?php echo esc_html( $lafka_cat_eyebrow ); ?></p>
			<h2 id="lafka-cats-heading" class="lafka-section-headline"><?php echo esc_html( $lafka_cat_headline ); ?></h2>
		</header>

		<ul class="lafka-cats__grid" role="list">
			<?php
            foreach ( $lafka_cat_terms as $lafka_cat_term ) :
				$lafka_cat_url  = get_term_link( $lafka_cat_term );
				$lafka_cat_slug = strtolower( (string) $lafka_cat_term->slug );

				// Pick emoji by fuzzy-matching name/slug to map.
				$lafka_cat_emoji = '';
				foreach ( $lafka_cat_emoji_map as $needle => $glyph ) {
					if ( false !== strpos( $lafka_cat_slug, $needle ) || false !== stripos( $lafka_cat_term->name, $needle ) ) {
						$lafka_cat_emoji = $glyph;
						break;
					}
				}
				$lafka_cat_emoji = (string) apply_filters( 'lafka_category_emoji', $lafka_cat_emoji, $lafka_cat_term );
				if ( '' === $lafka_cat_emoji ) {
					$lafka_cat_emoji = '🍽';
				}
				?>
				<li>
					<a class="lafka-cats__tile" href="<?php echo esc_url( $lafka_cat_url ); ?>">
						<span class="lafka-cats__icon" aria-hidden="true"><?php echo esc_html( $lafka_cat_emoji ); ?></span>
						<span class="lafka-cats__name"><?php echo esc_html( $lafka_cat_term->name ); ?></span>
						<span class="lafka-cats__count">
							<?php
							printf(
								esc_html( _n( '%d item', '%d items', (int) $lafka_cat_term->count, 'lafka' ) ),
								(int) $lafka_cat_term->count
							);
							?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
