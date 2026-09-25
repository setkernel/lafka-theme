<?php
declare(strict_types=1);

/**
 * GX4 C9 (+ polish): the quiet counter footer — "{name} · {address}" from the
 * NAP resolver and a few plain links: the counter's own footer location when
 * assigned (capped), else Menu · Deals · Find us, plus Privacy. The legacy
 * tertiary "Footer Menu" (a 23-link wall on migrated stores) is never printed.
 *
 * @package Lafka\Tests
 */

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterFooterRenderTest extends TestCase {

		private function render(): string {
			$GLOBALS['lafka_test_parts_live'] = true;
			ob_start();
			\get_template_part( 'partials/counter/footer' );
			return (string) ob_get_clean();
		}

		/** @return list<string> the footer's link labels, in order. */
		private static function labels( string $html ): array {
			preg_match_all( '#<li[^>]*><a[^>]*>([^<]+)</a></li>#', $html, $m );
			return $m[1];
		}

		public function test_default_links_and_the_nap_line(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_options']['woocommerce_myaccount_page_id'] = 9;
			$GLOBALS['lafka_test_privacy_link']                              = '<a class="privacy-policy-link" href="http://example.test/privacy/">Privacy</a>';

			$html = $this->render();
			$this->assertStringContainsString( '<footer id="footer" class="lafka-footer lafka-footer--counter" role="contentinfo">', $html );
			$this->assertStringContainsString( 'Example Kitchen · 1 Example St, Exampletown', $html );
			$this->assertSame( array( 'Menu', 'Deals', 'Find us', 'Privacy' ), self::labels( $html ) );
			$this->assertStringContainsString( 'href="http://example.test/#find-us"', $html );
			$this->assertStringNotContainsString( 'lafka-footer__signup', $html, 'no signup in the quiet footer' );
		}

		public function test_the_legacy_tertiary_menu_is_never_printed(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_nav_menus']['tertiary'] = array_map(
				static fn( $i ) => array(
					'label' => 'Legacy ' . $i,
					'url'   => 'http://example.test/legacy-' . $i . '/',
				),
				range( 1, 23 )
			);
			$html = $this->render();
			$this->assertStringNotContainsString( 'Legacy', $html );
			$this->assertSame( array( 'Menu', 'Deals', 'Find us' ), self::labels( $html ) );
		}

		public function test_assigned_counter_footer_menu_replaces_the_defaults_and_is_capped(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_privacy_link'] = '<a class="privacy-policy-link" href="http://example.test/privacy/">Privacy</a>';
			$GLOBALS['lafka_test_nav_menus']['lafka-counter-footer'] = array_map(
				static fn( $i ) => array(
					'label' => 'Link ' . $i,
					'url'   => 'http://example.test/link-' . $i . '/',
				),
				range( 1, 9 )
			);
			$html = $this->render();
			$this->assertSame( array( 'Link 1', 'Link 2', 'Link 3', 'Link 4', 'Link 5', 'Link 6', 'Privacy' ), self::labels( $html ), 'first six items + Privacy' );

			\add_filter( 'lafka_counter_footer_max_links', static fn() => 2 );
			$this->assertSame( array( 'Link 1', 'Link 2', 'Privacy' ), self::labels( $this->render() ) );
		}

		public function test_the_cap_leaves_other_menus_alone(): void {
			$items = range( 1, 9 );
			$this->assertSame( $items, \lafka_counter_footer_cap_links( $items, (object) array( 'theme_location' => 'lafka-counter' ) ) );
		}

		public function test_both_counter_locations_are_registered(): void {
			$GLOBALS['lafka_test_registered_menus'] = array();
			\lafka_counter_register_nav_location();
			$this->assertArrayHasKey( 'lafka-counter', $GLOBALS['lafka_test_registered_menus'] );
			$this->assertArrayHasKey( 'lafka-counter-footer', $GLOBALS['lafka_test_registered_menus'] );
		}
	}
}
