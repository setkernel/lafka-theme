<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OrderMethodBarOpensTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/partials/order-method-bar.php' );
	}

	public function test_partial_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/partials/order-method-bar.php' );
	}

	public function test_partial_uses_class_exists_guard_for_plugin_helper(): void {
		// Must gate on class_exists so the partial works without lafka-plugin.
		$this->assertStringContainsString( "class_exists( 'Lafka_Order_Hours' )", $this->src );
	}

	public function test_partial_calls_format_next_open_time_human(): void {
		$this->assertStringContainsString( 'format_next_open_time_human', $this->src );
	}

	public function test_partial_calls_get_next_opening_time(): void {
		$this->assertStringContainsString( 'get_next_opening_time', $this->src );
	}

	public function test_partial_uses_opens_translatable_string(): void {
		$this->assertMatchesRegularExpression(
			"/__\(\s*['\"]Opens %s['\"]\s*,\s*['\"]lafka['\"]/",
			$this->src
		);
	}

	public function test_existing_closed_label_still_present(): void {
		// The existing "Closed" label must stay as the prefix; the "Opens" line
		// is appended, not replaced.
		$this->assertMatchesRegularExpression(
			"/['\"]Closed['\"]/",
			$this->src
		);
	}
}
