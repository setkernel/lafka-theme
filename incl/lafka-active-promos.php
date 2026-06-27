<?php
/**
 * Active-promo surfacing.
 *
 * Turns the plugin's promotion ENGINES (free-delivery, first-order, slow-day,
 * combo — each independently toggled, default off) into visible storefront
 * messaging, so customers actually see the offers that make ordering direct
 * cheaper than the apps. A promo nobody sees drives no orders.
 *
 * Presentation only: this reads the plugin's resolvers defensively
 * (function_exists), so the theme works with or without the plugin and never
 * invents an offer that isn't really active. All copy is filterable.
 *
 * @package Lafka
 * @since   6.16.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lafka_active_promo_fmt_pct' ) ) {
	/** Trim trailing zeros: 15.00 → "15", 12.50 → "12.5". */
	function lafka_active_promo_fmt_pct( $p ): string {
		return rtrim( rtrim( number_format( (float) $p, 2, '.', '' ), '0' ), '.' );
	}
}

if ( ! function_exists( 'lafka_active_promo_price' ) ) {
	/** Plain-text money, e.g. "$45.00", using WC formatting when available. */
	function lafka_active_promo_price( $amount ): string {
		if ( function_exists( 'wc_price' ) && function_exists( 'wp_strip_all_tags' ) ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( (float) $amount ) ), ENT_QUOTES );
		}
		return '$' . number_format( (float) $amount, 2 );
	}
}

if ( ! function_exists( 'lafka_active_promo_messages' ) ) {
	/**
	 * Active promotions as display messages, urgency-ordered.
	 *
	 * @return array<int,array{key:string,text:string}>
	 */
	function lafka_active_promo_messages(): array {
		$out = array();

		// Slow-day — most urgent (today only), surface first.
		if ( function_exists( 'lafka_slow_day_percent' ) && function_exists( 'lafka_is_slow_day' ) ) {
			$p = lafka_slow_day_percent();
			if ( $p > 0 && lafka_is_slow_day() ) {
				$out[] = array(
					'key'  => 'slow_day',
					/* translators: %s = discount percent */
					'text' => sprintf( __( 'Today only — %s%% off', 'lafka' ), lafka_active_promo_fmt_pct( $p ) ),
				);
			}
		}

		// First-order — acquisition teaser (shown to everyone; applies to first-timers at checkout).
		if ( function_exists( 'lafka_first_order_discount_percent' ) ) {
			$p = lafka_first_order_discount_percent();
			if ( $p > 0 ) {
				$out[] = array(
					'key'  => 'first_order',
					/* translators: %s = discount percent */
					'text' => sprintf( __( '%s%% off your first online order', 'lafka' ), lafka_active_promo_fmt_pct( $p ) ),
				);
			}
		}

		// Combo deal.
		if ( function_exists( 'lafka_combo_deal_config' ) ) {
			$c = lafka_combo_deal_config();
			if ( ! empty( $c['enabled'] ) && function_exists( 'get_term' ) ) {
				$a = get_term( (int) $c['cat_a'], 'product_cat' );
				$b = get_term( (int) $c['cat_b'], 'product_cat' );
				if ( $a && $b && ! is_wp_error( $a ) && ! is_wp_error( $b ) ) {
					$amount = 'percent' === ( $c['type'] ?? 'fixed' )
						? lafka_active_promo_fmt_pct( $c['amount'] ) . '%'
						: lafka_active_promo_price( $c['amount'] );
					$out[] = array(
						'key'  => 'combo',
						/* translators: 1: category A, 2: category B, 3: amount */
						'text' => sprintf( __( '%1$s + %2$s? Save %3$s', 'lafka' ), $a->name, $b->name, $amount ),
					);
				}
			}
		}

		// Free delivery.
		if ( function_exists( 'lafka_get_free_delivery_threshold' ) ) {
			$t = lafka_get_free_delivery_threshold();
			if ( $t > 0 ) {
				$out[] = array(
					'key'  => 'free_delivery',
					/* translators: %s = order subtotal threshold */
					'text' => sprintf( __( 'Free delivery over %s', 'lafka' ), lafka_active_promo_price( $t ) ),
				);
			}
		}

		return (array) apply_filters( 'lafka_active_promo_messages', $out );
	}
}

if ( ! function_exists( 'lafka_render_active_promos' ) ) {
	/**
	 * Render the active-promo chip strip. No output when nothing is active.
	 *
	 * @param string $context Where it renders (menu|home) — used as a tracking source.
	 * @return void
	 */
	function lafka_render_active_promos( string $context = 'menu' ): void {
		$messages = lafka_active_promo_messages();
		if ( empty( $messages ) ) {
			return;
		}
		echo '<div class="lafka-active-promos" role="list" aria-label="' . esc_attr__( 'Current offers', 'lafka' ) . '" data-lafka-promos-context="' . esc_attr( $context ) . '">';
		echo '<span class="lafka-active-promos__lead">' . esc_html__( 'Order direct & save:', 'lafka' ) . '</span>';
		foreach ( $messages as $m ) {
			printf(
				'<span class="lafka-active-promos__chip" role="listitem" data-lafka-promo="%1$s"><span class="lafka-active-promos__chip-dot" aria-hidden="true"></span>%2$s</span>',
				esc_attr( $m['key'] ),
				esc_html( $m['text'] )
			);
		}
		echo '</div>';
	}
}

if ( ! function_exists( 'lafka_active_promos_enqueue' ) ) {
	function lafka_active_promos_enqueue(): void {
		// The /menu/ page resolves via the slug-matched page-menu.php template,
		// so is_page_template() is false there — detect by slug like quick-add.
		$show = is_page( 'menu' ) || is_page_template( 'page-menu.php' );
		if ( ! $show ) {
			return;
		}
		wp_enqueue_style(
			'lafka-active-promos',
			get_template_directory_uri() . '/styles/lafka-active-promos.css',
			array( 'lafka-tokens' ),
			lafka_asset_version( '/styles/lafka-active-promos.css' )
		);
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_enqueue_scripts', 'lafka_active_promos_enqueue', 21 );
}
