<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PdpEnqueueTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
	}

	public function test_pdp_assets_resolve_against_template_not_stylesheet(): void {
		// PDP enqueue must use get_template_directory() not get_stylesheet_directory()
		// so the parent's assets load even when a child theme is active. This is
		// the entire raison d'être of A3 — without this assertion a future careless
		// copy-paste from child could silently break parent-only operators.
		$this->assertStringContainsString( 'get_template_directory_uri()', $this->src );
		$this->assertStringNotContainsString(
			'get_stylesheet_directory_uri()',
			$this->src,
			'Parent theme PDP enqueue must not use get_stylesheet_directory_uri() — that resolves to active child theme and breaks parent-only operators.'
		);
		$this->assertStringNotContainsString(
			'get_stylesheet_directory()',
			$this->src,
			'Parent theme PDP enqueue must not use get_stylesheet_directory() — that resolves to active child theme and breaks parent-only operators.'
		);
	}
}
