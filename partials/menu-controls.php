<?php
/**
 * Partial: Menu controls — fulfilment toggle + search + dietary filter chips.
 *
 * Per handoff /design_handoff_peppery_ordering/README.md "Menu page":
 *   - Fulfilment toggle (pickup vs delivery) with persistent state
 *   - Search input with X-clear button
 *   - Dietary filter chips: ★ Popular / 🌱 Vegetarian / 🥬 Vegan / 🌶 Spicy
 *   - Multi-select, "Clear all" link appears when any are on
 *
 * State persistence:
 *   - Fulfilment → localStorage.lafka.fulfilment
 *   - Filter chips → URL hash (so bookmarks/share work)
 *
 * @package Lafka
 * @since   5.68.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * Fulfilment localStorage contract (SSOT).
 *
 * The pickup/delivery choice persists under a single brand-neutral key shared
 * by the menu and cart controllers. It is defined ONCE here in PHP and handed
 * to the JS via window.lafkaCfg (wp_localize_script); the controllers only fall
 * back to their own literals when this object is missing. `fulfilmentLegacyKey`
 * drives a one-time migration of the pre-rename value so returning customers
 * keep their stored choice. Override all three via the
 * 'lafka_fulfilment_js_config' filter — the single customization point.
 *
 * NOTE: the helper is mirrored in woocommerce/cart/cart.php because the shared
 * enqueue site (incl/system/core-functions.php) is out of scope for this
 * change; consolidate it there when next touching the enqueue.
 */
if ( ! function_exists( 'lafka_localize_fulfilment_cfg' ) ) {
	/**
	 * Attach the brand-neutral fulfilment storage contract (window.lafkaCfg)
	 * to a registered script handle. Idempotent per handle.
	 *
	 * @param string $handle Registered script handle to localize.
	 */
	function lafka_localize_fulfilment_cfg( $handle ) {
		static $done = array();
		if ( isset( $done[ $handle ] ) || ! function_exists( 'wp_localize_script' ) ) {
			return;
		}
		$done[ $handle ] = true;
		wp_localize_script(
			$handle,
			'lafkaCfg',
			apply_filters(
				'lafka_fulfilment_js_config',
				array(
					'fulfilmentKey'       => 'lafka.fulfilment',
					'fulfilmentDefault'   => 'pickup',
					// Pre-rename key, read once for migration only (see JS).
					'fulfilmentLegacyKey' => 'peppery.fulfilment',
				)
			)
		);
	}
}
lafka_localize_fulfilment_cfg( 'lafka-menu-controls' );

$lafka_mc_eta = function_exists( 'lafka_service_eta_get_data' ) ? lafka_service_eta_get_data() : null;
$lafka_mc_info = function_exists( 'lafka_get_restaurant_info' ) ? lafka_get_restaurant_info() : array();
$lafka_mc_addr_short = isset( $lafka_mc_info['address_short'] ) ? (string) $lafka_mc_info['address_short'] : '';
$lafka_mc_city = isset( $lafka_mc_info['city'] ) ? (string) $lafka_mc_info['city'] : '';
// SSOT: read the same threshold the plugin's free-delivery rule enforces; fall
// back to the single shared theme_mod (0 = off) when the plugin isn't loaded.
$lafka_mc_threshold = function_exists( 'lafka_get_free_delivery_threshold' )
	? (float) lafka_get_free_delivery_threshold()
	: (float) get_theme_mod( 'lafka_announce_bar_delivery_threshold', 0 );
$lafka_mc_threshold_label = function_exists( 'wc_price' )
	? wp_strip_all_tags( wc_price( $lafka_mc_threshold ) )
	: sprintf( '$%s', number_format_i18n( $lafka_mc_threshold, 0 ) );

$lafka_mc_pickup_eta   = $lafka_mc_eta && ! empty( $lafka_mc_eta['pickup'] ) ? (string) $lafka_mc_eta['pickup'] : '';
$lafka_mc_delivery_eta = $lafka_mc_eta && ! empty( $lafka_mc_eta['delivery'] ) ? (string) $lafka_mc_eta['delivery'] : '';
// GX4: the counter header already carries the Pickup / Delivery choice (the
// same preference, synced by js/lafka-fulfilment.js) — don't ask twice.
$lafka_mc_show_tabs = ! ( function_exists( 'lafka_layout_is' ) && lafka_layout_is( 'header', 'counter' ) );

// Search: "live" filters the rows already on the page (and falls through to the
// server search when nothing on the page matches); "server" is the product
// search results page, where the box is a plain GET form, prefilled.
$lafka_mc_search_query = isset( $args['search_query'] ) ? (string) $args['search_query'] : '';
$lafka_mc_search_mode  = isset( $args['search_mode'] ) && 'server' === $args['search_mode'] ? 'server' : 'live';

// Dietary chips: only those with at least one matching product (M-02).
$lafka_mc_chips = function_exists( 'lafka_menu_filter_chips' ) ? lafka_menu_filter_chips() : array();
?>
<div class="lafka-menu__controls" data-lafka-menu-controls>

	<?php if ( $lafka_mc_show_tabs ) : ?>
	<div class="lafka-menu__tabs" role="radiogroup" aria-label="<?php esc_attr_e( 'Fulfilment method', 'lafka' ); ?>">
		<button
			type="button"
			class="lafka-menu__tab is-active"
			role="radio"
			aria-checked="true"
			tabindex="0"
			data-lafka-fulfilment="pickup"
		>
			<span class="lafka-menu__tab-label"><?php esc_html_e( 'Pickup', 'lafka' ); ?></span>
			<?php if ( '' !== $lafka_mc_pickup_eta ) : ?>
				<span class="lafka-menu__tab-meta">
					<?php
					/* translators: %s — pickup ETA, e.g. "~25 min" */
					printf( esc_html__( 'Ready in %s', 'lafka' ), esc_html( $lafka_mc_pickup_eta ) );
					if ( '' !== $lafka_mc_addr_short ) {
						echo ' · ' . esc_html( $lafka_mc_addr_short );
					}
					?>
				</span>
			<?php elseif ( '' !== $lafka_mc_addr_short ) : ?>
				<span class="lafka-menu__tab-meta"><?php echo esc_html( $lafka_mc_addr_short ); ?></span>
			<?php endif; ?>
		</button>
		<button
			type="button"
			class="lafka-menu__tab"
			role="radio"
			aria-checked="false"
			tabindex="-1"
			data-lafka-fulfilment="delivery"
		>
			<span class="lafka-menu__tab-label"><?php esc_html_e( 'Delivery', 'lafka' ); ?></span>
			<span class="lafka-menu__tab-meta">
				<?php
				if ( $lafka_mc_threshold > 0 ) {
					/* translators: 1: free-delivery threshold (e.g. "$30"); 2: city. */
					printf(
						esc_html__( 'Free over %1$s%2$s', 'lafka' ),
						esc_html( $lafka_mc_threshold_label ),
						'' !== $lafka_mc_city ? ' · ' . esc_html( $lafka_mc_city ) : ''
					);
				} elseif ( '' !== $lafka_mc_city ) {
					echo esc_html( $lafka_mc_city );
				}
				?>
			</span>
		</button>
	</div>
	<?php endif; ?>

	<form
		class="lafka-menu__search"
		role="search"
		method="get"
		action="<?php echo esc_url( home_url( '/' ) ); ?>"
		data-lafka-menu-search
		data-lafka-menu-search-mode="<?php echo esc_attr( $lafka_mc_search_mode ); ?>"
	>
		<label class="lafka-menu__search-label" for="lafka-menu-search-input">
			<span class="screen-reader-text"><?php esc_html_e( 'Search the menu', 'lafka' ); ?></span>
			<span class="lafka-menu__search-icon" aria-hidden="true">🔍</span>
			<input
				type="search"
				id="lafka-menu-search-input"
				class="lafka-menu__search-input"
				name="s"
				value="<?php echo esc_attr( $lafka_mc_search_query ); ?>"
				placeholder="<?php esc_attr_e( 'Search the menu…', 'lafka' ); ?>"
				autocomplete="off"
				data-lafka-menu-search-input
			>
			<input type="hidden" name="post_type" value="product">
			<button
				type="button"
				class="lafka-menu__search-clear"
				aria-label="<?php esc_attr_e( 'Clear search', 'lafka' ); ?>"
				data-lafka-menu-search-clear
				<?php echo '' === $lafka_mc_search_query ? 'hidden' : ''; ?>
			>×</button>
		</label>
	</form>

	<?php if ( ! empty( $lafka_mc_chips ) && 'server' !== $lafka_mc_search_mode ) : ?>
	<div class="lafka-menu__filters" data-lafka-menu-filters>
		<span class="lafka-menu__filters-label"><?php esc_html_e( 'Filter', 'lafka' ); ?></span>
		<?php foreach ( $lafka_mc_chips as $lafka_mc_slug => $lafka_mc_chip ) : ?>
			<button type="button" class="lafka-menu__chip" data-lafka-filter="<?php echo esc_attr( $lafka_mc_slug ); ?>" aria-pressed="false">
				<?php if ( '' !== $lafka_mc_chip['icon'] ) : ?>
					<span aria-hidden="true"><?php echo esc_html( $lafka_mc_chip['icon'] ); ?></span>
				<?php endif; ?>
				<?php echo esc_html( $lafka_mc_chip['label'] ); ?>
			</button>
		<?php endforeach; ?>
		<button
			type="button"
			class="lafka-menu__clear-filters"
			data-lafka-clear-filters
			hidden
		>
			<?php esc_html_e( 'Clear all', 'lafka' ); ?>
		</button>
	</div>
	<?php endif; ?>

</div>
