<?php
declare(strict_types=1);

/**
 * GX4 C9: the quiet counter footer — "{name} · {address}" from the NAP
 * resolver and a few plain links (Footer Menu location when assigned).
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

		public function test_default_links_and_the_nap_line(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_options']['woocommerce_myaccount_page_id'] = 9;
			$GLOBALS['lafka_test_privacy_link']                              = '<a class="privacy-policy-link" href="http://example.test/privacy/">Privacy</a>';

			$html = $this->render();
			$this->assertStringContainsString( '<footer id="footer" class="lafka-footer lafka-footer--counter" role="contentinfo">', $html );
			$this->assertStringContainsString( 'Example Kitchen · 1 Example St, Exampletown', $html );
			preg_match_all( '#<li><a href="[^"]*">([^<]+)</a></li>#', $html, $m );
			$this->assertSame( array( 'Full menu', 'Deals', 'Your account' ), $m[1] );
			$this->assertStringContainsString( 'class="privacy-policy-link"', $html );
			$this->assertStringNotContainsString( 'lafka-footer__signup', $html, 'no signup in the quiet footer' );
		}

		public function test_assigned_footer_menu_replaces_the_defaults(): void {
			CounterHomeRenderTest::seed();
			$GLOBALS['lafka_test_nav_menus']['tertiary'] = array(
				array(
					'label' => 'About',
					'url'   => 'http://example.test/about/',
				),
			);
			$html = $this->render();
			$this->assertStringContainsString( '>About</a>', $html );
			$this->assertStringNotContainsString( '>Full menu</a>', $html );
		}
	}
}
