<?php
declare(strict_types=1);

/**
 * GX T-04: counter images carry `sizes` that match their rendered slot (the
 * featured deal no longer asks for 90vw, the logo no longer for 150px), and
 * degenerate srcset candidates (1w / 10w) are dropped.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/image-sizes.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterImageSizesTest extends TestCase {

		public function test_slots_describe_their_measured_widths(): void {
			$this->assertSame( '(min-width: 1280px) 64px, (min-width: 600px) 52px, 40px', \lafka_counter_image_sizes( 'logo' ) );
			$this->assertStringNotContainsString( '90vw', \lafka_counter_image_sizes( 'deal-featured' ), 'the featured deal is ~85vw on phones and 28vw on desktop' );
			$this->assertStringEndsWith( ' 85vw', \lafka_counter_image_sizes( 'deal-featured' ) );
			$this->assertSame( '(min-width: 1024px) 160px, 120px', \lafka_counter_image_sizes( 'row-photo' ) );
			foreach ( array( 'hero-front', 'hero-back', 'deal-small', 'row-compact' ) as $slot ) {
				$this->assertNotSame( '', \lafka_counter_image_sizes( $slot ), $slot );
			}
			$this->assertSame( '', \lafka_counter_image_sizes( 'unknown' ) );
		}

		public function test_slots_are_filterable(): void {
			\add_filter(
				'lafka_counter_image_sizes',
				static function ( $sizes, $slot ) {
					return 'logo' === $slot ? '48px' : $sizes;
				},
				10,
				2
			);
			$this->assertSame( '48px', \lafka_counter_image_sizes( 'logo' ) );
			$this->assertSame( '(min-width: 1024px) 160px, 120px', \lafka_counter_image_sizes( 'row-photo' ) );
		}

		public function test_home_images_use_the_slot_sizes(): void {
			CounterHomeRenderTest::seed();
			$html = CounterHomeRenderTest::render();

			$this->assertMatchesRegularExpression( '#lafka-counter-hero__dish--front">\s*<img [^>]*sizes="' . preg_quote( \lafka_counter_image_sizes( 'hero-front' ), '#' ) . '"#', $html );
			$this->assertMatchesRegularExpression( '#lafka-counter-hero__dish--back">\s*<img [^>]*sizes="' . preg_quote( \lafka_counter_image_sizes( 'hero-back' ), '#' ) . '"#', $html );
			$this->assertStringNotContainsString( '420px, 90vw', $html );
		}

		public function test_srcset_drops_degenerate_candidates(): void {
			$sources = array(
				150 => array( 'url' => 'a-150.png', 'descriptor' => 'w', 'value' => 150 ),
				1   => array( 'url' => 'a-1x1.png', 'descriptor' => 'w', 'value' => 1 ),
				10  => array( 'url' => 'a-10.png', 'descriptor' => 'w', 'value' => 10 ),
				300 => array( 'url' => 'a-300.png', 'descriptor' => 'w', 'value' => 300 ),
			);
			$this->assertSame( array( 150, 300 ), array_keys( \lafka_srcset_drop_tiny_candidates( $sources ) ) );
		}

		public function test_srcset_never_empties_and_ignores_non_arrays(): void {
			$only_tiny = array( 1 => array( 'url' => 'a.png', 'descriptor' => 'w', 'value' => 1 ) );
			$this->assertSame( $only_tiny, \lafka_srcset_drop_tiny_candidates( $only_tiny ) );
			$this->assertFalse( \lafka_srcset_drop_tiny_candidates( false ) );
		}

		public function test_srcset_hook_is_registered(): void {
			$this->assertContains( 'lafka_srcset_drop_tiny_candidates', $GLOBALS['lafka_test_filters']['wp_calculate_image_srcset'][10] ?? array() );
		}
	}
}
