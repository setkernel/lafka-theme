<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * F091: every storefront "free delivery" messaging surface must read the same
 * SSOT threshold the plugin's shipping rule enforces, so the on-page promise
 * can never diverge from what the cart actually charges.
 *
 * Before the fix these surfaces read get_theme_mod( '…', 30 ) directly, so a
 * stock OSS install advertised "Free over $30" while the resolver (and thus the
 * shipping rule + progress meter) defaulted to 0 — i.e. delivery was charged
 * despite the promise. The reconciliation:
 *   1. route through lafka_get_free_delivery_threshold() (guarded), and
 *   2. when the plugin is absent, fall back to the shared theme_mod with a
 *      default of 0 (NOT 30), and
 *   3. hide the "Free over $X" copy entirely when the resolver returns 0.
 *
 * These partials carry top-level executable code (WC()/global $product access,
 * early returns) so they can't be require_once'd in isolation; assert the
 * structural contract on source, matching FreeDeliveryProgressTest.
 */
final class MessagingFreeDeliverySsotTest extends TestCase {

	/**
	 * @return array<string,array{0:string,1:string}> label => [ relative path, threshold var name ]
	 */
	public static function messagingSurfaceProvider(): array {
		return array(
			'menu-controls delivery tab'  => array( 'partials/menu-controls.php', 'lafka_mc_threshold' ),
			'cart delivery tab'           => array( 'woocommerce/cart/cart.php', 'lafka_cart_threshold' ),
			'pdp free-delivery assurance' => array( 'partials/pdp-summary.php', 'lafka_pdp_threshold' ),
		);
	}

	private function read( string $relpath ): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $relpath );
	}

	#[DataProvider('messagingSurfaceProvider')]
	public function test_routes_through_ssot_resolver( string $relpath, string $var ): void {
		$src = $this->read( $relpath );
		self::assertStringContainsString(
			"function_exists( 'lafka_get_free_delivery_threshold' )",
			$src,
			"$relpath must guard the SSOT resolver with function_exists (works without the plugin)."
		);
		self::assertStringContainsString(
			'lafka_get_free_delivery_threshold()',
			$src,
			"$relpath must read the threshold through the resolver so the promise never diverges from the enforced rule."
		);
	}

	#[DataProvider('messagingSurfaceProvider')]
	public function test_plugin_absent_fallback_defaults_to_zero( string $relpath, string $var ): void {
		$src = $this->read( $relpath );
		self::assertMatchesRegularExpression(
			"/get_theme_mod\(\s*'lafka_announce_bar_delivery_threshold'\s*,\s*0\s*\)/",
			$src,
			"$relpath plugin-absent fallback must default to 0 (no free-delivery promise)."
		);
		self::assertDoesNotMatchRegularExpression(
			"/get_theme_mod\(\s*'lafka_announce_bar_delivery_threshold'\s*,\s*30\s*\)/",
			$src,
			"$relpath must not default the threshold to 30 — that re-introduces the f091 promise/enforcement mismatch on a stock install."
		);
	}

	#[DataProvider('messagingSurfaceProvider')]
	public function test_free_delivery_copy_gated_on_positive_threshold( string $relpath, string $var ): void {
		$src = $this->read( $relpath );
		self::assertMatchesRegularExpression(
			'/\$' . preg_quote( $var, '/' ) . '\s*>\s*0/',
			$src,
			"$relpath must hide the \"Free over \$X\" copy when the resolver returns 0."
		);
	}
}
