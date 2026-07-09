<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * NX2-04 part 3: the highest-traffic copy controls must stop forcing a full
 * preview reload. Hero copy + announce bar move to postMessage transport
 * with selective-refresh partials re-rendering the real template parts.
 */
final class SelectiveRefreshWiringTest extends TestCase {

	private string $home;
	private string $announce;

	protected function setUp(): void {
		parent::setUp();
		$root           = dirname( __DIR__, 2 );
		$this->home     = (string) file_get_contents( $root . '/incl/customizer-home.php' );
		$this->announce = (string) file_get_contents( $root . '/incl/customizer-announce-bar.php' );
	}

	public function test_hero_partial_rerenders_the_real_template_part(): void {
		$this->assertStringContainsString( 'selective_refresh->add_partial', $this->home );
		$this->assertStringContainsString( "'.lafka-hero'", $this->home );
		$this->assertStringContainsString( "get_template_part( 'partials/home-hero' )", $this->home );
	}

	public function test_hero_copy_settings_use_postmessage(): void {
		foreach ( array( 'lafka_home_hero_headline', 'lafka_home_hero_lead', 'lafka_home_hero_primary_cta_label' ) as $id ) {
			$pos = strpos( $this->home, "'" . $id . "'" );
			$this->assertNotFalse( $pos, "$id must exist" );
		}
		$this->assertStringContainsString( "'transport'         => 'postMessage'", $this->home );
	}

	public function test_announce_bar_partial_and_transport(): void {
		$this->assertStringContainsString( 'selective_refresh->add_partial', $this->announce );
		$this->assertStringContainsString( "'.lafka-announce-bar'", $this->announce );
		$this->assertStringContainsString( "get_template_part( 'partials/announce-bar' )", $this->announce );
		$this->assertStringContainsString( "'postMessage'", $this->announce );
	}

	public function test_accent_and_brand_ride_postmessage(): void {
		$bridge = (string) file_get_contents( dirname( __DIR__, 2 ) . '/incl/customizer-bridge.php' );
		$this->assertStringContainsString( '$transport = \'refresh\'', $bridge, 'add_color() must default to refresh so 30+ other color controls are untouched.' );
		$this->assertSame(
			2,
			substr_count( $bridge, "'postMessage'" ),
			'Exactly accent + brand opt into postMessage (Task 4 preview JS binds only these two).'
		);
	}
}
