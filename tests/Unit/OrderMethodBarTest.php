<?php
declare(strict_types=1);

/**
 * The PDP order-method bar (partials/order-method-bar.php) tells a customer
 * when a closed store reopens, using lafka-plugin's order-hours API.
 */

namespace {
	if ( ! function_exists( 'lafka_pdp_is_store_open' ) ) {
		function lafka_pdp_is_store_open() {
			return \Lafka_Order_Hours::$shop_open;
		}
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class OrderMethodBarTest extends TestCase {

		protected function setUp(): void {
			\lafka_test_use_classic_layouts(); // The counter header replaces this bar (GX4).
			$GLOBALS['lafka_test_restaurant_info'] = array(
				'address_short' => '1 Example St',
				'city'          => 'Example City',
				'region'        => 'EX',
				'hours'         => array(),
			);
		}

		private function render(): string {
			ob_start();
			require dirname( __DIR__, 2 ) . '/partials/order-method-bar.php';
			return (string) ob_get_clean();
		}

		public function test_closed_store_shows_when_it_reopens(): void {
			\Lafka_Order_Hours::$shop_open       = false;
			\Lafka_Order_Hours::$next_open_human = 'tomorrow at 11:00';

			$html = $this->render();

			$this->assertStringContainsString( 'Closed', $html );
			$this->assertStringContainsString( 'Opens tomorrow at 11:00', $html );
		}

		public function test_open_store_does_not_say_closed(): void {
			$html = $this->render();

			$this->assertStringContainsString( 'Open', $html );
			$this->assertStringNotContainsString( 'Closed', $html );
		}
	}
}
