<?php
declare(strict_types=1);

/**
 * GX4 C2: the counter header inner (partials/counter/header.php), rendered
 * with the shim store. NAP comes only from lafka_get_restaurant_info(), the
 * status follows the order gate, the toggle only appears with two modes.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/layout.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-url.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/phone-display.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/open-status.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/hours-display.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/price-columns.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/menu-data.php';
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/counter-chrome.php';

	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			return $GLOBALS['lafka_test_wc'];
		}
	}
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		function wc_get_cart_url() {
			return 'http://example.test/cart/';
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class CounterHeaderRenderTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_parts_live']      = true;
			$GLOBALS['lafka_test_wc']              = null;
			$GLOBALS['lafka_test_restaurant_info'] = array(
				'name'          => 'Example Kitchen',
				'phone_e164'    => '+15550100',
				'phone_display' => '(555) 0100',
				'hours'         => array( 'Monday' => '11:00-23:00' ),
			);
		}

		private static function cart( int $count ): object {
			return (object) array(
				'cart' => new class( $count ) {
					public function __construct( private int $count ) {}
					public function get_cart_contents_count() {
						return $this->count;
					}
				},
			);
		}

		private function render(): string {
			ob_start();
			\get_template_part( 'partials/counter/header' );
			return (string) ob_get_clean();
		}

		public function test_nap_comes_from_the_resolver(): void {
			$html = $this->render();
			$this->assertStringContainsString( 'Example Kitchen', $html );
			$this->assertStringContainsString( 'href="tel:+15550100"', $html );
			$this->assertStringContainsString( '(555) 0100', $html );
			$this->assertStringContainsString( 'data-lafka-channel="phone"', $html );
			$this->assertStringNotContainsString( '<header', $html, 'header.php keeps the single <header id="header"> banner' );
		}

		public function test_phone_is_hidden_when_empty(): void {
			$GLOBALS['lafka_test_restaurant_info'] = array( 'name' => 'Example Kitchen' );
			$html                                  = $this->render();
			$this->assertStringNotContainsString( 'tel:', $html );
			$this->assertStringNotContainsString( 'data-lafka-open-status', $html, 'no hours, no gate -> no status' );
		}

		public function test_toggle_needs_two_modes(): void {
			$html = $this->render();
			$this->assertSame( 2, substr_count( $html, 'data-lafka-fulfilment-input' ) );
			$this->assertStringContainsString( '<legend class="screen-reader-text">How do you want your order?</legend>', $html );

			$GLOBALS['lafka_test_fulfilment_modes'] = array( 'pickup' );
			$this->assertStringNotContainsString( 'data-lafka-fulfilment-input', $this->render(), 'a single mode needs no toggle' );
		}

		public function test_preference_is_checked(): void {
			$GLOBALS['lafka_test_fulfilment_pref'] = 'delivery';
			$this->assertMatchesRegularExpression( '/value="delivery" data-lafka-fulfilment-input checked/', $this->render() );
		}

		public function test_force_closed_gate_reads_closed_with_next_opening(): void {
			$GLOBALS['lafka_test_order_hours_on'] = true;
			\Lafka_Order_Hours::$shop_open        = false;
			\Lafka_Order_Hours::$next_open_human  = 'Tuesday at 11:00 AM';
			$GLOBALS['lafka_test_restaurant_info']['hours'] = array_fill_keys( array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ), '00:00-23:59' );

			$html = $this->render();
			$this->assertStringContainsString( 'data-lafka-gate="override"', $html );
			$this->assertStringContainsString( '<strong>Closed</strong> · opens Tuesday at 11:00 AM', $html );
			$this->assertStringContainsString( 'lafka-counter-status is-closed', $html );
		}

		public function test_cart_link_names_the_count(): void {
			$GLOBALS['lafka_test_wc'] = self::cart( 3 );
			$html                     = $this->render();
			$this->assertStringContainsString( 'aria-label="Cart, 3 items"', $html );
			$this->assertStringContainsString( 'data-lafka-cart-open', $html );
			$this->assertMatchesRegularExpression( '/data-lafka-cart-count aria-hidden="true">3</', $html );
		}

		public function test_nav_defaults_then_assigned_menu(): void {
			$html = $this->render();
			$this->assertStringContainsString( '<nav class="lafka-counter-nav" aria-label="Main">', $html );
			$this->assertStringContainsString( '>Menu</a>', $html );

			$GLOBALS['lafka_test_nav_menus']['lafka-counter'] = array(
				array(
					'label' => 'About us',
					'url'   => 'http://example.test/about/',
				),
			);
			$this->assertStringContainsString( '>About us</a>', $this->render() );

			$GLOBALS['lafka_test_theme_mods']['lafka_counter_header_nav'] = false;
			$this->assertStringNotContainsString( 'lafka-counter-nav', $this->render() );
		}

		public function test_phone_number_is_the_links_text_even_when_shown_as_an_icon(): void {
			// <1440 the number is visually hidden (clip), never removed: the
			// icon-only link still announces the number.
			$this->assertMatchesRegularExpression(
				'#class="lafka-counter-header__phone"[^>]*>.*<span class="lafka-counter-header__phone-number">\(555\) 0100</span>#s',
				$this->render()
			);
		}

		public function test_full_name_only_without_a_short_name(): void {
			$html = $this->render();
			$this->assertStringContainsString( '<span class="lafka-counter-header__name">Example Kitchen</span>', $html );
			$this->assertStringNotContainsString( 'lafka-counter-header__name-short', $html );
		}

		public function test_short_name_is_the_phone_label_and_the_full_name_stays_accessible(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_counter_brand_short'] = 'Example';
			$html = $this->render();
			$this->assertStringContainsString( 'lafka-counter-header__name--has-short', $html );
			$this->assertStringContainsString( '<span class="lafka-counter-header__name-full">Example Kitchen</span>', $html );
			$this->assertStringContainsString( '<span class="lafka-counter-header__name-short" aria-hidden="true">Example</span>', $html );
		}

		public function test_short_name_equal_to_the_full_name_is_ignored_and_filterable(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_counter_brand_short'] = 'Example Kitchen';
			$this->assertStringNotContainsString( 'name-short', $this->render() );

			\add_filter( 'lafka_counter_brand_short', static fn() => 'EK' );
			$this->assertStringContainsString( 'aria-hidden="true">EK</span>', $this->render() );
		}
	
		public function test_a_long_name_without_a_short_name_falls_back_to_its_first_part(): void {
			$this->assertSame( 'Harbour Pizza', \lafka_counter_brand_short_auto( 'Harbour Pizza &amp; Poutine' ) );
			$this->assertSame( 'Harbour Pizza', \lafka_counter_brand_short( 'Harbour Pizza and Poutine' ) );
			$this->assertSame( '', \lafka_counter_brand_short( 'Example Kitchen' ), 'short names stay whole' );
			$this->assertSame( '', \lafka_counter_brand_short_auto( 'The Very Long Kitchen Of Example' ), 'no joiner, nothing to cut at' );
		}
	
		public function test_menu_button_always_has_a_name(): void {
			$this->assertMatchesRegularExpression( '#<button type="button" class="lafka-counter-header__menu" aria-label="Menu"#', $this->render() );
		}

		public function test_nav_marks_the_current_page_but_not_anchors(): void {
			$this->assertTrue( \lafka_counter_nav_is_current( 'http://example.test/menu/', '/menu/?x=1' ) );
			$this->assertFalse( \lafka_counter_nav_is_current( 'http://example.test/#deals', '/' ) );
			$this->assertFalse( \lafka_counter_nav_is_current( 'http://example.test/', '/' ), 'home is not a nav destination' );
			$this->assertFalse( \lafka_counter_nav_is_current( 'http://example.test/menu/', '/order/' ) );
		}
	}
}
