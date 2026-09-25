<?php
declare(strict_types=1);

/**
 * incl/system/lafka-script-loading.php: the theme defers only its own
 * allowlisted script handles, through the WP strategy API — never
 * WooCommerce, gateway, checkout or order-attribution scripts, never in the
 * admin, and never over a strategy a script already chose. FlexSlider reuses
 * WooCommerce's `wc-flexslider` and otherwise loads under `lafka-flexslider`,
 * never the generic `flexslider` handle.
 */

namespace {
	if ( ! function_exists( 'wp_script_is' ) ) {
		function wp_script_is( $handle, $status = 'enqueued' ) {
			return isset( $GLOBALS['lafka_test_scripts'][ $handle ] );
		}
	}
	if ( ! function_exists( 'wp_script_add_data' ) ) {
		function wp_script_add_data( $handle, $key, $value ) {
			if ( ! isset( $GLOBALS['lafka_test_scripts'][ $handle ] ) ) {
				return false;
			}
			$GLOBALS['lafka_test_scripts'][ $handle ][ $key ] = $value;
			return true;
		}
	}
	if ( ! function_exists( 'wp_scripts' ) ) {
		function wp_scripts() {
			return new class() {
				public function get_data( $handle, $key ) {
					return $GLOBALS['lafka_test_scripts'][ $handle ][ $key ] ?? false;
				}
			};
		}
	}

	if ( ! function_exists( 'wp_enqueue_script' ) ) {
		function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $args = array() ) {
			if ( ! isset( $GLOBALS['lafka_test_scripts'][ $handle ] ) ) {
				$GLOBALS['lafka_test_scripts'][ $handle ] = array( 'src' => $src, 'args' => $args );
			}
			$GLOBALS['lafka_test_scripts'][ $handle ]['enqueued'] = true;
		}
	}
	if ( ! function_exists( 'lafka_asset_version' ) ) {
		function lafka_asset_version( $relative_path ) {
			return '1';
		}
	}

	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-script-loading.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\DataProvider;
	use PHPUnit\Framework\TestCase;

	final class ScriptLoadingStrategyTest extends TestCase {

		protected function setUp(): void {
			$GLOBALS['lafka_test_scripts'] = array();
		}

		protected function tearDown(): void {
			unset( $GLOBALS['lafka_test_scripts'] );
		}

		/** @param string[] $handles */
		private function register( array $handles ): void {
			foreach ( $handles as $handle ) {
				$GLOBALS['lafka_test_scripts'][ $handle ] = array();
			}
		}

		private function strategy( string $handle ): string {
			return (string) ( $GLOBALS['lafka_test_scripts'][ $handle ]['strategy'] ?? '' );
		}

		public function test_runs_after_every_enqueue_and_again_before_footer_scripts(): void {
			$this->assertSame( 1000, \has_action( 'wp_enqueue_scripts', 'lafka_apply_script_defer_strategy' ) );
			$this->assertSame( 1, \has_action( 'wp_footer', 'lafka_apply_script_defer_strategy' ) );
		}

		public function test_theme_scripts_get_the_defer_strategy(): void {
			$this->register( array( 'lafka-front', 'lafka-cart-drawer', 'lafka-menu-controls', 'lafka-flexslider', 'owl-carousel' ) );

			\lafka_apply_script_defer_strategy();

			foreach ( array( 'lafka-front', 'lafka-cart-drawer', 'lafka-menu-controls', 'lafka-flexslider', 'owl-carousel' ) as $handle ) {
				$this->assertSame( 'defer', $this->strategy( $handle ), $handle );
			}
		}

		/** @return array<string, array{0:string}> */
		public static function foreignHandles(): array {
			return array(
				'jquery'              => array( 'jquery' ),
				'wc-checkout'         => array( 'wc-checkout' ),
				'wc-cart-fragments'   => array( 'wc-cart-fragments' ),
				'wc-order-attribution' => array( 'wc-order-attribution' ),
				'wc-settings (blocks)' => array( 'wc-settings' ),
				'stripe gateway'      => array( 'wc-stripe-upe-classic' ),
				'square gateway'      => array( 'wc-square' ),
				'paypal payments'     => array( 'ppcp-smart-button' ),
				'plugin maps loader'  => array( 'lafka-google-maps' ),
				'plugin shipping'     => array( 'lafka-shipping-areas-handle-shipping' ),
			);
		}

		#[DataProvider( 'foreignHandles' )]
		public function test_non_theme_scripts_are_never_touched( string $handle ): void {
			$this->register( array( $handle ) );

			\lafka_apply_script_defer_strategy();

			$this->assertSame( '', $this->strategy( $handle ) );
		}

		public function test_unregistered_handles_are_skipped(): void {
			\lafka_apply_script_defer_strategy();

			$this->assertSame( array(), $GLOBALS['lafka_test_scripts'] );
		}

		public function test_an_existing_strategy_is_kept(): void {
			$this->register( array( 'typed' ) );
			$GLOBALS['lafka_test_scripts']['typed']['strategy'] = 'async';

			\lafka_apply_script_defer_strategy();

			$this->assertSame( 'async', $this->strategy( 'typed' ) );
		}

		public function test_admin_is_left_alone(): void {
			$GLOBALS['lafka_test_is_admin'] = true;
			$this->register( array( 'lafka-front' ) );

			\lafka_apply_script_defer_strategy();

			$this->assertSame( '', $this->strategy( 'lafka-front' ) );
		}

		public function test_allowlist_is_filterable(): void {
			$this->register( array( 'lafka-front', 'my-child-theme-js' ) );
			\add_filter(
				'lafka_deferred_script_handles',
				static fn( array $handles ) => array_merge( array_diff( $handles, array( 'lafka-front' ) ), array( 'my-child-theme-js', 42 ) )
			);

			\lafka_apply_script_defer_strategy();

			$this->assertSame( '', $this->strategy( 'lafka-front' ) );
			$this->assertSame( 'defer', $this->strategy( 'my-child-theme-js' ) );
		}

		public function test_flexslider_reuses_woocommerces_registration(): void {
			$this->register( array( 'wc-flexslider', 'flexslider' ) );

			$this->assertSame( 'wc-flexslider', \lafka_enqueue_flexslider( array( 'strategy' => 'defer' ) ) );
			$this->assertTrue( $GLOBALS['lafka_test_scripts']['wc-flexslider']['enqueued'] ?? false );
			$this->assertArrayNotHasKey( 'lafka-flexslider', $GLOBALS['lafka_test_scripts'] );
			$this->assertArrayNotHasKey( 'enqueued', $GLOBALS['lafka_test_scripts']['flexslider'], 'The generic alias is not used.' );
		}

		public function test_flexslider_falls_back_to_the_bundled_copy_under_the_theme_handle(): void {
			$this->register( array( 'flexslider' ) ); // Some other plugin's "flexslider".

			$this->assertSame( 'lafka-flexslider', \lafka_enqueue_flexslider( array( 'strategy' => 'defer' ) ) );
			$this->assertStringEndsWith( '/js/flex/jquery.flexslider-min.js', $GLOBALS['lafka_test_scripts']['lafka-flexslider']['src'] );
			$this->assertArrayNotHasKey( 'enqueued', $GLOBALS['lafka_test_scripts']['flexslider'] );
		}
	}
}
