<?php
declare(strict_types=1);

/**
 * Visible phone text must never be a raw E.164 string ("+19025550100").
 *
 * Several partials fell back to printing phone_e164 as the link TEXT when the
 * operator left the display field empty. lafka_theme_phone_display() routes
 * that visible text through lafka-plugin's lafka_format_phone_display() when
 * the plugin provides it ("(902) 555-0100"), and otherwise leaves the value
 * untouched. `tel:` hrefs keep the E.164 value.
 *
 * The plugin formatter is a fixture loaded on demand (with_plugin_formatter())
 * so the plugin-absent case can run in its own process without it.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/phone-display.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunInSeparateProcess;
	use PHPUnit\Framework\TestCase;

	final class PhoneDisplayTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_phone_format_calls'] = array();
			$GLOBALS['lafka_test_phone_formats']      = array();
		}

		private static function with_plugin_formatter(): void {
			require_once dirname( __DIR__ ) . '/fixtures/plugin-phone-formatter.php';
		}

		public function test_empty_display_formats_the_e164_number_via_the_plugin(): void {
			self::with_plugin_formatter();

			$this->assertSame( '(902) 555-0100', \lafka_theme_phone_display( '', '+19025550100' ) );
			$this->assertSame( array( '+19025550100' ), $GLOBALS['lafka_test_phone_format_calls'] );
		}

		public function test_bare_e164_display_is_formatted_too(): void {
			self::with_plugin_formatter();

			$this->assertSame( '(902) 555-0100', \lafka_theme_phone_display( '+19025550100', '+19025550100' ) );
		}

		public function test_operator_formatted_display_is_kept_verbatim(): void {
			self::with_plugin_formatter();

			$this->assertSame( '902-555-0100 ext. 2', \lafka_theme_phone_display( ' 902-555-0100 ext. 2 ', '+19025550100' ) );
			$this->assertSame( array(), $GLOBALS['lafka_test_phone_format_calls'], 'An operator-typed display value is never reformatted.' );
		}

		public function test_formatter_returning_nothing_falls_back_to_the_raw_value(): void {
			self::with_plugin_formatter();
			$GLOBALS['lafka_test_phone_formats']['+442071234567'] = '';

			$this->assertSame( '+442071234567', \lafka_theme_phone_display( '', '+442071234567' ) );
		}

		public function test_no_number_at_all_is_empty(): void {
			$this->assertSame( '', \lafka_theme_phone_display( '', '' ) );
			$this->assertSame( '', \lafka_theme_phone_display( '  ', '' ) );
		}

		#[RunInSeparateProcess]
		#[PreserveGlobalState( false )]
		public function test_without_the_plugin_formatter_the_value_is_unchanged(): void {
			$this->assertFalse( function_exists( 'lafka_format_phone_display' ) );
			$this->assertSame( '+19025550100', \lafka_theme_phone_display( '', '+19025550100' ) );
			$this->assertSame( '(902) 555-0100', \lafka_theme_phone_display( '(902) 555-0100', '+19025550100' ) );
		}

		/**
		 * @return array<string, array{0:string}>
		 */
		public static function provide_fallback_partials(): array {
			return array(
				'utility bar'   => array( 'partials/editorial-utility-bar.php' ),
				'visit section' => array( 'partials/editorial-visit.php' ),
				'contact NAP'   => array( 'partials/editorial-contact-nap.php' ),
			);
		}

		#[DataProvider( 'provide_fallback_partials' )]
		public function test_partials_show_formatted_text_and_keep_e164_href( string $partial ): void {
			self::with_plugin_formatter();
			$GLOBALS['lafka_test_restaurant_info'] = array(
				'phone_e164'    => '+19025550100',
				'phone_display' => '',
			);

			ob_start();
			require dirname( __DIR__, 2 ) . '/' . $partial;
			$html = (string) ob_get_clean();

			$this->assertStringContainsString( '<a href="tel:+19025550100">(902) 555-0100</a>', $html );
			$this->assertStringNotContainsString( '>+19025550100<', $html, 'Raw E.164 must not be the visible text.' );
		}
	}
}
