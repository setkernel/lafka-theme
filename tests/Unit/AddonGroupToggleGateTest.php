<?php
declare(strict_types=1);

/**
 * GX T-19: the plugin's add-on disclosure button renders only where the
 * redesigned PDP styles it; elsewhere add-on groups keep the plain heading.
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/woocommerce/lafka-addon-group-toggle.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class AddonGroupToggleGateTest extends TestCase {

		private function gate( bool $plugin_says ): bool {
			return (bool) \apply_filters( 'lafka_addon_group_toggle', $plugin_says, array( 'name' => 'Toppings' ) );
		}

		public function test_redesigned_pdp_gets_the_button(): void {
			$GLOBALS['lafka_test_is_product'] = true;
			$this->assertTrue( $this->gate( true ) );
		}

		public function test_legacy_pdp_and_other_contexts_keep_the_plain_heading(): void {
			$GLOBALS['lafka_test_is_product']  = true;
			$GLOBALS['lafka_test_pdp_redesign'] = false;
			$this->assertFalse( $this->gate( true ), 'legacy PDP has no button styles' );

			$GLOBALS['lafka_test_is_product']  = false;
			$GLOBALS['lafka_test_pdp_redesign'] = true;
			$this->assertFalse( $this->gate( true ), 'AJAX / modal add-on forms' );
		}

		public function test_never_turns_on_what_the_plugin_left_off(): void {
			$GLOBALS['lafka_test_is_product'] = true;
			$this->assertFalse( $this->gate( false ) );
		}
	}
}
