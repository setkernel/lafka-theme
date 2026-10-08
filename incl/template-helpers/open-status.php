<?php
/**
 * Open/closed status for the storefront.
 *
 * A thin reader of the plugin's Lafka_Order_Hours::status(): the one answer
 * the order gate, schema, the product-page trust line and this theme's badges
 * all give. The plugin owns the hours, the "until 11 pm" / "opens tomorrow at
 * 11 am" wording and the clock; the theme only dresses the result. Without the
 * plugin there are no hours to show and the strip simply does not render.
 *
 * The live script (js/lafka-open-status.js) keeps a cached page honest: it
 * reads the same status as JSON (lafkaOpenStatus) and asks the plugin's
 * /lafka/v1/open-status route for the next wording when the time passes.
 *
 * Filter surface:
 *   lafka_open_status( array|null $status, int|null $now ) - override the result
 *
 * @package Lafka
 * @since   5.54.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_open_status' ) ) {
	/**
	 * Open/closed status right now (or at $now).
	 *
	 * Returns null when there are no hours to show (the caller hides the strip),
	 * else:
	 *   [
	 *     'is_open'   => bool,
	 *     'short'     => 'Open now' | 'Closed',
	 *     'strong'    => the same,
	 *     'rest'      => 'until 11 pm' | 'opens tomorrow at 11 am' | '',
	 *     'label'     => 'Open now · until 11 pm',
	 *     'dot_color' => css var for the status dot,
	 *     'source'    => 'schedule' | 'display' | 'forced' | 'holiday',
	 *     'close'     => 'HH:MM' closing time while open, else '',
	 *   ]
	 *
	 * @param int|null $now Unix timestamp; default now.
	 * @return array|null
	 */
	function lafka_open_status( $now = null ) {
		$status = null;

		if ( class_exists( 'Lafka_Order_Hours' ) ) {
			$raw = Lafka_Order_Hours::status( null === $now ? null : new DateTimeImmutable( '@' . (int) $now ) );
			if ( $raw['has_hours'] || $raw['forced'] || $raw['holiday'] ) {
				$text   = Lafka_Order_Hours::status_text( $raw );
				$status = array(
					'is_open'   => $raw['is_open'],
					'short'     => $text['strong'],
					'strong'    => $text['strong'],
					'rest'      => $text['rest'],
					'label'     => $text['label'],
					'dot_color' => $raw['is_open'] ? 'var(--lafka-color-success-500)' : 'var(--lafka-color-brand-500)',
					'source'    => $raw['source'],
					'close'     => $raw['is_open'] && $raw['closes_at'] instanceof DateTimeInterface ? $raw['closes_at']->format( 'H:i' ) : '',
				);
			}
		}

		/**
		 * Filter the open/closed status.
		 *
		 * @param array|null $status Status array (null hides the strip).
		 * @param int|null   $now    Timestamp asked for (null = now).
		 */
		return apply_filters( 'lafka_open_status', $status, $now );
	}
}

if ( ! function_exists( 'lafka_enqueue_open_status_script' ) ) {
	/**
	 * Load the live status script once, with the status as one JSON object.
	 * Does nothing without the plugin (no hours, no shared core script).
	 *
	 * @return void
	 */
	function lafka_enqueue_open_status_script(): void {
		if ( wp_script_is( 'lafka-open-status', 'enqueued' ) || ! class_exists( 'Lafka_Order_Hours' ) || ! wp_script_is( 'lafka-core', 'registered' ) ) {
			return;
		}
		if ( null === lafka_open_status() ) {
			return;
		}
		wp_enqueue_script(
			'lafka-open-status',
			get_template_directory_uri() . '/js/lafka-open-status.js',
			array( 'lafka-core' ),
			lafka_asset_version( '/js/lafka-open-status.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_add_inline_script( 'lafka-open-status', 'window.lafkaOpenStatus = ' . wp_json_encode( Lafka_Order_Hours::client_status() ) . ';', 'before' );
	}
}
