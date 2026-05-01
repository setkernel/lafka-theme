<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProductCardEnqueueTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
	}

	public function test_css_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/styles/product-card.css' );
	}

	public function test_css_defines_load_bearing_classes(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/product-card.css' );
		foreach ( array( '.lafka-product-card', '.lafka-product-card__img-wrap', '.lafka-product-card__body', '.lafka-product-card__title', '.lafka-product-card__price' ) as $cls ) {
			$this->assertStringContainsString( $cls, $css, "Missing CSS class {$cls}" );
		}
	}

	public function test_css_grid_columns_for_mobile_image_left(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/product-card.css' );
		// Mobile baseline: 92px image + 1fr body column.
		$this->assertMatchesRegularExpression(
			'/grid-template-columns:\s*92px\s+1fr/',
			$css,
			'mobile card must use 92px image-left + 1fr body grid'
		);
	}

	public function test_enqueue_handle_present(): void {
		$this->assertStringContainsString( "'lafka-product-card'", $this->src );
		$this->assertStringContainsString( 'styles/product-card.css', $this->src );
	}

	public function test_enqueue_gated_to_archive_contexts(): void {
		// Must NOT load on PDP, cart, checkout, account pages.
		// Should load on shop, product taxonomies, all-products page.
		$this->assertStringContainsString( 'is_shop()', $this->src );
		$this->assertStringContainsString( 'is_product_taxonomy()', $this->src );
	}

	public function test_enqueue_depends_on_lafka_style(): void {
		// Card CSS overrides base styles, must load after lafka-style.
		$this->assertMatchesRegularExpression(
			"/wp_enqueue_style\(\s*['\"]lafka-product-card['\"][\s\S]*?array\(\s*['\"]lafka-style['\"]/",
			$this->src,
			'product-card CSS must depend on lafka-style for cascade order'
		);
	}

	public function test_version_pinned_to_parent(): void {
		// Cache-busting must use parent's version, matching the established
		// idiom at incl/system/core-functions.php:1049 and the editorial enqueue.
		$this->assertStringContainsString( "wp_get_theme( get_template() )->get( 'Version' )", $this->src );
	}
}
