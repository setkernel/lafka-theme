<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F045: the free-delivery progress meter must track the SSOT threshold the
 * plugin's shipping rule enforces — not a single Customizer theme_mod — so the
 * promise on the conversion path can never disagree with what the cart charges.
 *
 * The partial has top-level executable code (early returns, WC() access) so it
 * can't be require_once'd in isolation; assert the structural contract on source.
 */
final class FreeDeliveryProgressTest extends TestCase {

	private string $src;

	protected function setUp(): void {
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/partials/free-delivery-progress.php' );
	}

	public function test_resolves_threshold_through_ssot_resolver(): void {
		self::assertStringContainsString(
			"function_exists( 'lafka_get_free_delivery_threshold' )",
			$this->src,
			'meter must resolve the threshold through the canonical SSOT resolver.'
		);
		self::assertStringContainsString(
			'lafka_get_free_delivery_threshold()',
			$this->src,
			'meter must call the resolver so it never diverges from the enforced rule.'
		);
	}

	public function test_honours_explicit_caller_override(): void {
		self::assertStringContainsString(
			"isset( \$args['threshold'] )",
			$this->src,
			'an explicit $args[\'threshold\'] must still win over the resolver.'
		);
	}

	public function test_plugin_absent_fallback_defaults_to_zero(): void {
		self::assertMatchesRegularExpression(
			"/get_theme_mod\(\s*'lafka_pdp_free_delivery_threshold'\s*,\s*0\s*\)/",
			$this->src,
			'plugin-absent fallback must default to 0 (feature off).'
		);
	}

	public function test_reapplies_legacy_filter_for_back_compat(): void {
		self::assertStringContainsString(
			"apply_filters( 'lafka_pdp_free_delivery_threshold'",
			$this->src,
			'legacy lafka_pdp_free_delivery_threshold filter must still fire for child back-compat.'
		);
	}
}
