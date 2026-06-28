<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * f075 regression lock: the new-order push notifier must read branch-routing
 * order meta in an HPOS-safe way.
 *
 * Background: lafka_new_orders_notification() (the wp_ajax_lafka_new_orders_notification
 * handler) decides which branch operator to notify by reading the PLUGIN-owned order
 * meta `lafka_selected_branch_id`. Under WooCommerce High-Performance Order Storage
 * (HPOS) that meta lives in `wc_orders_meta`, NOT `wp_postmeta`, so the original raw
 * get_post_meta() calls returned empty and every branch operator was notified for every
 * order (or none) — silent multi-branch misrouting.
 *
 * The fix:
 *   - Reads branch meta through lafka_get_hpos_aware_order_meta(), which prefers the
 *     plugin's canonical Lafka_Shipping_Areas::get_order_meta_backward_compatible()
 *     accessor and falls back to the WC_Order object (HPOS + legacy safe).
 *   - Gates the bulk update_meta_cache( 'post', ... ) priming behind
 *     OrderUtil::custom_orders_table_usage_is_enabled(), because under HPOS the order
 *     meta is already loaded onto the WC_Order objects and the 'post' cache prime is
 *     unnecessary (and wrong for orders with no wp_posts row).
 *
 * These source-grep locks fail if either accessor regresses back to raw post-meta.
 *
 * @package Lafka\Theme\Tests\Unit
 */
final class OrderNotificationHposMetaTest extends TestCase {

	private function source(): string {
		$path = __DIR__ . '/../../incl/woocommerce-functions.php';
		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	/**
	 * Isolate the body of the new-order notifier so assertions don't accidentally
	 * match unrelated get_post_meta()/update_meta_cache() calls elsewhere in the file.
	 */
	private function notifier_body(): string {
		$src   = $this->source();
		$start = strpos( $src, 'function lafka_new_orders_notification(' );
		$this->assertNotFalse( $start, 'lafka_new_orders_notification() must still exist.' );

		// Slice up to the next function declaration that follows the notifier.
		$end = strpos( $src, 'function lafka_custom_related_products_heading(', $start );
		$this->assertNotFalse( $end, 'Could not locate the function following the notifier.' );

		return substr( $src, $start, $end - $start );
	}

	public function test_helper_exists(): void {
		$src = $this->source();

		$this->assertStringContainsString(
			'function lafka_get_hpos_aware_order_meta(',
			$src,
			'An HPOS-safe order-meta accessor must be defined.'
		);
	}

	public function test_helper_prefers_plugin_backward_compatible_accessor(): void {
		$src = $this->source();

		$this->assertStringContainsString(
			'Lafka_Shipping_Areas::get_order_meta_backward_compatible(',
			$src,
			'The HPOS-safe accessor must delegate to the plugin canonical reader when available.'
		);
	}

	public function test_branch_meta_no_longer_read_via_raw_post_meta(): void {
		$body = $this->notifier_body();

		$this->assertDoesNotMatchRegularExpression(
			'/get_post_meta\s*\([^;]*lafka_selected_branch_id/',
			$body,
			'Branch routing meta must not be read via raw get_post_meta() (breaks under HPOS).'
		);
	}

	public function test_branch_meta_read_via_hpos_aware_helper(): void {
		$body = $this->notifier_body();

		$this->assertGreaterThanOrEqual(
			2,
			substr_count( $body, "lafka_get_hpos_aware_order_meta( \$order_id" )
			+ substr_count( $body, "lafka_get_hpos_aware_order_meta( \$order_id_to_notify" ),
			'Both branch-id reads in the notifier must use the HPOS-aware helper.'
		);
	}

	public function test_post_meta_cache_prime_is_gated_behind_hpos_check(): void {
		$body = $this->notifier_body();

		$guard_pos = strpos( $body, 'custom_orders_table_usage_is_enabled' );
		$prime_pos = strpos( $body, "update_meta_cache( 'post'" );

		$this->assertNotFalse( $guard_pos, 'The notifier must consult OrderUtil before priming the post-meta cache.' );
		$this->assertNotFalse( $prime_pos, 'The notifier still primes the post-meta cache for legacy CPT storage.' );
		$this->assertLessThan(
			$prime_pos,
			$guard_pos,
			"update_meta_cache( 'post', ... ) must be guarded by the HPOS check, not run unconditionally."
		);
	}
}
