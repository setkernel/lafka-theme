<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StoreClosedCssTest extends TestCase {
	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents( dirname( __DIR__, 2 ) . '/functions.php' );
	}

	public function test_css_file_exists(): void {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/styles/store-closed.css' );
	}

	public function test_css_defines_load_bearing_classes(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/store-closed.css' );
		foreach ( array(
			'.lafka-store-closed-card',
			'.lafka-store-closed-card__title',
			'.lafka-store-closed-card__subtitle',
		) as $cls ) {
			$this->assertStringContainsString( $cls, $css, "Missing CSS class {$cls}" );
		}
	}

	public function test_css_disables_pdp_add_to_cart_when_body_has_closed_class(): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/store-closed.css' );
		$this->assertMatchesRegularExpression(
			'/body\.lafka-store-closed[^{]*\.single_add_to_cart_button/',
			$css,
			'CSS must disable .single_add_to_cart_button when body has lafka-store-closed class'
		);
		$this->assertStringContainsString( 'pointer-events: none', $css );
		$this->assertStringContainsString( 'opacity:', $css );
	}

	public function test_css_uses_calm_amber_not_panic_red(): void {
		// No #c62828 (Pepperypizza brand red) leak. The card should use an
		// amber/warm background, not a red one.
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/store-closed.css' );
		$this->assertStringNotContainsString(
			'#c62828',
			$css,
			'must not contain Pepperypizza brand red — public OSS theme'
		);
	}

	public function test_css_uses_var_for_brand_accent(): void {
		// Border accent reads the dedicated warning token --lafka-color-warning-500
		// (defined in lafka-tokens.css, enqueued first so no fallback is needed).
		// This gives the closed-store card its calm amber emphasis from the SSOT
		// rather than the old orphaned/undefined --lafka-primary name.
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/store-closed.css' );
		$this->assertMatchesRegularExpression(
			'/var\(\s*--lafka-color-warning-500\s*\)/',
			$css,
			'border accent must use var(--lafka-color-warning-500)'
		);
		$this->assertStringNotContainsString(
			'--lafka-primary',
			$css,
			'the orphaned/undefined --lafka-primary token must not reappear'
		);
	}

	public function test_enqueue_handle_present(): void {
		$this->assertStringContainsString( "'lafka-store-closed'", $this->src );
		$this->assertStringContainsString( 'styles/store-closed.css', $this->src );
	}

	public function test_enqueue_depends_on_lafka_style(): void {
		$this->assertMatchesRegularExpression(
			"/wp_enqueue_style\(\s*['\"]lafka-store-closed['\"][\s\S]*?array\(\s*['\"]lafka-style['\"]/",
			$this->src,
			'store-closed CSS must depend on lafka-style for cascade order'
		);
	}

	public function test_enqueue_version_pinned_to_parent(): void {
		// Same idiom as editorial + product-card enqueues.
		$this->assertStringContainsString( "wp_get_theme( get_template() )->get( 'Version' )", $this->src );
	}
}
