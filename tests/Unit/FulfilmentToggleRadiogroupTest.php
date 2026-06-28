<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * f055 regression lock: the pickup/delivery fulfilment switch must not pose as
 * a tablist. It reveals no panels, so it is a role="radiogroup" of role="radio"
 * buttons (aria-checked), with a roving tabindex and arrow-key navigation —
 * matching the announced semantics to the actual widget (WCAG 4.1.2 / 2.1.1).
 */
final class FulfilmentToggleRadiogroupTest extends TestCase {

	private function partial_source(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/partials/menu-controls.php' );
	}

	private function js_source(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/js/lafka-menu-controls.js' );
	}

	public function test_container_is_a_named_radiogroup(): void {
		$src = $this->partial_source();
		$this->assertMatchesRegularExpression(
			'/class="lafka-menu__tabs"\s+role="radiogroup"\s+aria-label=/',
			$src,
			'The fulfilment container must be a role="radiogroup" with an accessible name.'
		);
	}

	public function test_no_tab_semantics_remain(): void {
		$src = $this->partial_source();
		$this->assertStringNotContainsString( 'role="tablist"', $src, 'role="tablist" must be removed.' );
		$this->assertStringNotContainsString( 'role="tab"', $src, 'role="tab" must be removed.' );
		$this->assertStringNotContainsString( 'aria-selected', $src, 'aria-selected belongs to tabs, not radios.' );
	}

	public function test_each_button_is_a_radio_with_aria_checked(): void {
		$src = $this->partial_source();
		// Two fulfilment buttons, each role="radio" with an aria-checked state.
		$this->assertSame( 2, substr_count( $src, 'role="radio"' ), 'Both fulfilment buttons must be role="radio".' );
		$this->assertStringContainsString( 'aria-checked="true"', $src, 'The active button must be aria-checked="true".' );
		$this->assertStringContainsString( 'aria-checked="false"', $src, 'The inactive button must be aria-checked="false".' );
	}

	public function test_buttons_carry_roving_tabindex(): void {
		$src = $this->partial_source();
		// Active radio is in the tab sequence (0); inactive is removed (-1).
		$this->assertMatchesRegularExpression(
			'/role="radio"\s+aria-checked="true"\s+tabindex="0"/',
			$src,
			'The checked radio must have tabindex="0".'
		);
		$this->assertMatchesRegularExpression(
			'/role="radio"\s+aria-checked="false"\s+tabindex="-1"/',
			$src,
			'The unchecked radio must have tabindex="-1".'
		);
	}

	public function test_js_manages_aria_checked_not_aria_selected(): void {
		$js = $this->js_source();
		$this->assertStringContainsString( "setAttribute( 'aria-checked'", $js, 'JS must drive aria-checked.' );
		$this->assertStringNotContainsString( "aria-selected", $js, 'JS must not set tab-only aria-selected.' );
	}

	public function test_js_manages_roving_tabindex(): void {
		$js = $this->js_source();
		$this->assertStringContainsString( "setAttribute( 'tabindex'", $js, 'JS must manage a roving tabindex.' );
	}

	public function test_js_handles_arrow_key_navigation(): void {
		$js = $this->js_source();
		foreach ( array( 'ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'keydown' ) as $needle ) {
			$this->assertStringContainsString( $needle, $js, "JS must handle $needle for radiogroup navigation." );
		}
	}
}
