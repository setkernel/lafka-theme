<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/incl/lafka-active-promos.php';

/**
 * Active-promo surfacing: formatting helpers, the no-plugin guard and the render.
 */
final class ActivePromosTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		$this->src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/incl/lafka-active-promos.php' );
	}

	public function test_fmt_pct_trims_trailing_zeros(): void {
		self::assertSame( '15', lafka_active_promo_fmt_pct( 15.00 ) );
		self::assertSame( '12.5', lafka_active_promo_fmt_pct( 12.5 ) );
		self::assertSame( '7.25', lafka_active_promo_fmt_pct( 7.25 ) );
	}

	public function test_price_fallback_without_wc(): void {
		// wc_price is undefined in this unit context → plain fallback.
		self::assertSame( '$45.00', lafka_active_promo_price( 45 ) );
	}

	public function test_reads_each_promo_resolver_defensively(): void {
		foreach ( array(
			'lafka_slow_day_percent',
			'lafka_is_slow_day',
			'lafka_first_order_discount_percent',
			'lafka_combo_deal_config',
			'lafka_get_free_delivery_threshold',
		) as $fn ) {
			self::assertStringContainsString( "function_exists( '" . $fn . "' )", $this->src,
				"must guard $fn with function_exists (works without the plugin)." );
		}
	}

	public function test_renders_nothing_when_no_offer_is_active(): void {
		ob_start();
		lafka_render_active_promos();
		self::assertSame( '', ob_get_clean(), 'No fabricated offers: an empty promo set renders no markup.' );
	}

	public function test_renders_a_tracked_chip_per_active_offer(): void {
		$GLOBALS['lafka_test_free_delivery_threshold'] = 30;
		ob_start();
		lafka_render_active_promos();
		$html = (string) ob_get_clean();
		self::assertStringContainsString( 'data-lafka-promo="free_delivery"', $html );
	}
}
