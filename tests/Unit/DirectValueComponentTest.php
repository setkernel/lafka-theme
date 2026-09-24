<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Conversion: "Order Direct — skip the app fees" component + honest PDP reviews.
 */
final class DirectValueComponentTest extends TestCase {
	private string $logic;
	private string $partial;
	private string $pdp;

	protected function setUp(): void {
		$root          = dirname( __DIR__, 2 );
		$this->logic   = file_get_contents( $root . '/incl/customizer-direct-value.php' );
		$this->partial = file_get_contents( $root . '/partials/direct-value.php' );
		$this->pdp     = file_get_contents( $root . '/partials/pdp-ingredients-reviews.php' );
	}

	public function test_component_emits_order_channel_tracking_contract(): void {
		$this->assertStringContainsString( 'data-lafka-order-channel="direct"', $this->partial,
			'The direct CTA must carry the order_channel="direct" tracking contract.' );
		$this->assertMatchesRegularExpression( '/data-lafka-order-source="(home_strip|menu_badge)"/', $this->partial );
	}

	public function test_copy_is_honest_and_customizer_driven(): void {
		// No hardcoded "save 30%"-style fabricated savings figure; default heading present.
		$this->assertStringContainsString( "'lafka_direct_value_heading'", $this->logic );
		$this->assertDoesNotMatchRegularExpression( '/save\s+\d+%/i', $this->partial,
			'Avoid a fabricated savings percentage; keep the claim qualitative + true.' );
	}

	public function test_pdp_reviews_no_longer_fabricated(): void {
		$this->assertDoesNotMatchRegularExpression( "/lafka_pdp_rating_avg',\s*4\.8/", $this->pdp );
		$this->assertDoesNotMatchRegularExpression( "/lafka_pdp_rating_count',\s*312/", $this->pdp );
		$this->assertStringNotContainsString( 'Marcus T.', $this->pdp );
		$this->assertStringContainsString( 'get_review_count', $this->pdp,
			'PDP should source honest social proof from real WC reviews.' );
	}
}
