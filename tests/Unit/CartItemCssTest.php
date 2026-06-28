<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CartItemCssTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
	}

	public function test_css_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/styles/cart-item.css' );
	}

	public function test_css_defines_load_bearing_classes(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/cart-item.css' );
		foreach ( array(
			'.lafka-cart',
			'.lafka-cart-item',
			'.lafka-cart-item__img-wrap',
			'.lafka-cart-item__body',
			'.lafka-cart-item__title',
			'.lafka-cart-item__meta',
			'.lafka-cart-item__bottom',
			'.lafka-cart-item__price',
			'.lafka-cart-item__qty',
			'.lafka-cart-item__remove',
		) as $cls ) {
			$this->assertStringContainsString( $cls, $css, "Missing CSS class {$cls}" );
		}
	}

	public function test_css_grid_uses_80px_image_column(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/cart-item.css' );
		$this->assertMatchesRegularExpression(
			'/grid-template-columns:\s*80px\s+1fr/',
			$css,
			'cart item card must use 80px image-left + 1fr body grid'
		);
	}

	public function test_css_uses_css_var_for_brand_accent(): void {
		// Cart line price is design-system INK, read from the SSOT token
		// --lafka-color-text-primary (defined in lafka-tokens.css, enqueued
		// first so no fallback is needed). Per the pixel-perfect handoff the
		// line price is ink, NOT accent — so this must not use an accent token
		// nor the old orphaned/undefined --lafka-primary name.
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/cart-item.css' );
		$this->assertMatchesRegularExpression(
			'/color:\s*var\(\s*--lafka-color-text-primary\s*\)/',
			$css,
			'price color must use var(--lafka-color-text-primary)'
		);
		$this->assertStringNotContainsString(
			'--lafka-primary',
			$css,
			'the orphaned/undefined --lafka-primary token must not reappear'
		);
	}

	public function test_enqueue_handle_present(): void {
		$this->assertStringContainsString( "'lafka-cart-item'", $this->src );
		$this->assertStringContainsString( 'styles/cart-item.css', $this->src );
	}

	public function test_enqueue_gated_to_cart_page(): void {
		// CSS only loads on the cart page — not PDP, not checkout, not menu.
		// Look for the is_cart() check inside the enqueue closure.
		$this->assertMatchesRegularExpression(
			"/['\"]lafka-cart-item['\"][\s\S]{0,500}is_cart\(\)|is_cart\(\)[\s\S]{0,500}['\"]lafka-cart-item['\"]/",
			$this->src,
			'cart-item CSS enqueue must be gated to is_cart()'
		);
	}

	public function test_enqueue_depends_on_lafka_style(): void {
		$this->assertMatchesRegularExpression(
			"/wp_enqueue_style\(\s*['\"]lafka-cart-item['\"][\s\S]*?array\(\s*['\"]lafka-style['\"]/",
			$this->src,
			'cart-item CSS must depend on lafka-style for cascade order'
		);
	}

	public function test_enqueue_version_pinned_to_parent(): void {
		// Cache-busting via parent's version, matching established idiom.
		$this->assertStringContainsString( "wp_get_theme( get_template() )->get( 'Version' )", $this->src );
	}
}
