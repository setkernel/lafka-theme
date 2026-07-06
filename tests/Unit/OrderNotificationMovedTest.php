<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NX1-08b (audit finding #58 / wp.org theme-review blocker): the new-order
 * notification poller — a wp_ajax handler plus its option state and admin JS —
 * was MOVED out of the parent theme into lafka-plugin
 * (incl/admin/class-lafka-order-notifications.php).
 *
 * This scan test locks that removal: the theme must register NO
 * wp_ajax_lafka_new_orders_notification handler and must retain no remnant of the
 * handler, its HPOS-aware meta helper, the permission dialog, the poller JS, or
 * the notification-specific script localisation. Business logic belongs in the
 * plugin; the theme owns appearance only.
 *
 * @package Lafka\Theme\Tests\Unit
 */
final class OrderNotificationMovedTest extends TestCase {

	private function read( string $relative ): string {
		$path = __DIR__ . '/../../' . ltrim( $relative, '/' );
		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	public function test_theme_does_not_register_the_ajax_action(): void {
		$wc = $this->read( 'incl/woocommerce-functions.php' );

		$this->assertStringNotContainsString(
			"add_action( 'wp_ajax_lafka_new_orders_notification'",
			$wc,
			'The theme must not register the order-notification AJAX handler (moved to the plugin).'
		);
	}

	public function test_theme_has_no_handler_remnants(): void {
		$wc = $this->read( 'incl/woocommerce-functions.php' );

		foreach (
			array(
				'function lafka_new_orders_notification(',
				'function lafka_get_hpos_aware_order_meta(',
				'function lafka_admin_push_permission_dialog(',
				'lafka_last_processed_order_ids',
				'lafka-push-confirm',
			) as $needle
		) {
			$this->assertStringNotContainsString(
				$needle,
				$wc,
				"Order-notification remnant '{$needle}' must not remain in the theme."
			);
		}
	}

	public function test_theme_admin_enqueue_drops_notification_localisation(): void {
		$core = $this->read( 'incl/system/core-functions.php' );

		// Note: 'lafka_ajax_nonce' itself is a SHARED theme AJAX nonce (used by other
		// features too), so we assert only the poller-specific localisation keys.
		foreach (
			array(
				'new_orders_push_notifications',
				'service_worker_path',
			) as $needle
		) {
			$this->assertStringNotContainsString(
				$needle,
				$core,
				"The theme admin enqueue must not localise '{$needle}' (poller moved to the plugin)."
			);
		}
	}

	public function test_backend_js_no_longer_contains_the_poller(): void {
		$js = $this->read( 'js/lafka-back.js' );

		$this->assertStringNotContainsString( 'lafka_new_orders_notification', $js );
		$this->assertStringNotContainsString( 'new_orders_push_notifications', $js );
		$this->assertStringNotContainsString( 'serviceWorker.register', $js );
	}
}
