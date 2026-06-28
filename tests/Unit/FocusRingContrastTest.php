<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * C-A11Y-Audit-2026-06-27 (f018): WCAG 1.4.11 focus-ring regression lock.
 *
 * The keyboard focus indicator on every interactive control on the
 * conversion path (header CTA / cart icon / menu fulfilment tabs / dietary
 * chips / cart + mobile-nav close buttons) is driven by a single design
 * token, --lafka-shadow-focus. The old value was a 35%-alpha red ring:
 *
 *     --lafka-shadow-focus: 0 0 0 3px rgba(220, 38, 38, 0.35);
 *
 * which composited to ~#f3b4b4 on white (~1.5:1) and, on the #dc2626
 * "Order now" CTA, was red-on-red (~1:1) — far below the 3:1 required for
 * non-text contrast. The fix makes the ring opaque + offset:
 *
 *     --lafka-shadow-focus:
 *         0 0 0 2px var(--lafka-color-surface-page, #fff),
 *         0 0 0 4px var(--lafka-color-accent-700);
 *
 * (white spacer + solid #991b1b = 7.41:1 on white; the spacer guarantees
 * separation on the red CTA and the ink-black active chip too).
 *
 * These assertions lock the token value and verify the fix actually
 * propagates by confirming each conversion-path component still reads the
 * token (so a future refactor that inlines a per-component ring can't
 * silently reintroduce the invisible variant).
 */
final class FocusRingContrastTest extends TestCase {

	private string $tokens_css;

	protected function setUp(): void {
		parent::setUp();
		$this->tokens_css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-tokens.css' );
	}

	/** Isolate the --lafka-shadow-focus declaration value (no comments). */
	private function focus_token_value(): string {
		// Match the property up to its terminating semicolon. The value may
		// span multiple lines (comma-separated shadow layers).
		preg_match(
			'/--lafka-shadow-focus\s*:\s*([^;]+);/s',
			$this->tokens_css,
			$m
		);
		return isset( $m[1] ) ? trim( $m[1] ) : '';
	}

	/** The token must exist and be a single canonical declaration. */
	public function test_focus_token_declared_once(): void {
		$count = preg_match_all( '/--lafka-shadow-focus\s*:/', $this->tokens_css );
		$this->assertSame(
			1,
			$count,
			'--lafka-shadow-focus must be declared exactly once (single source of truth)'
		);
	}

	/** The token must NOT use the old translucent red ring. */
	public function test_focus_token_not_translucent_red(): void {
		$value = $this->focus_token_value();
		$this->assertNotEmpty( $value, '--lafka-shadow-focus declaration not found in lafka-tokens.css' );

		$this->assertDoesNotMatchRegularExpression(
			'/rgba\(\s*220\s*,\s*38\s*,\s*38\s*,\s*0?\.35\s*\)/',
			$value,
			'--lafka-shadow-focus must not use rgba(220,38,38,.35) (~1.5:1 on white, ~1:1 on the CTA) — C-A11Y-Audit-2026-06-27'
		);

		// Defence-in-depth: a focus ring expressed purely in alpha can never
		// guarantee 3:1, so reject any sub-1.0 alpha rgba/rgb/hsl layer.
		$this->assertDoesNotMatchRegularExpression(
			'/(?:rgba|hsla)\([^)]*,\s*0?\.\d+\s*\)/',
			$value,
			'--lafka-shadow-focus must be opaque (no fractional alpha) — C-A11Y-Audit-2026-06-27'
		);
	}

	/** The token must use the opaque, offset, accent-700-based ring. */
	public function test_focus_token_is_opaque_offset_ring(): void {
		$value = $this->focus_token_value();

		// A surface-coloured spacer ring (separation on same-colour surfaces).
		$this->assertMatchesRegularExpression(
			'/var\(\s*--lafka-color-surface-page\b/',
			$value,
			'--lafka-shadow-focus must include a --lafka-color-surface-page spacer ring — C-A11Y-Audit-2026-06-27'
		);

		// A solid high-contrast accent-700 ring (7.41:1 on white).
		$this->assertMatchesRegularExpression(
			'/var\(\s*--lafka-color-accent-700\b/',
			$value,
			'--lafka-shadow-focus must include a solid --lafka-color-accent-700 ring (7.41:1 on white) — C-A11Y-Audit-2026-06-27'
		);

		// Two comma-separated shadow layers => the ring is offset, not flush.
		$this->assertStringContainsString(
			',',
			$value,
			'--lafka-shadow-focus must layer a spacer + ring so it is offset, not flush against a same-colour surface — C-A11Y-Audit-2026-06-27'
		);
	}

	/** accent-700 must remain a dark, AAA-on-white red for the ring to hold. */
	public function test_accent_700_token_is_dark_red(): void {
		$this->assertMatchesRegularExpression(
			'/--lafka-color-accent-700\s*:\s*#991b1b\b/i',
			$this->tokens_css,
			'--lafka-color-accent-700 must remain #991b1b (7.41:1 on white) so the focus ring clears WCAG 1.4.11 — C-A11Y-Audit-2026-06-27'
		);
	}

	/** The tokens file must carry the audit provenance comment. */
	public function test_tokens_css_audit_comment_present(): void {
		$this->assertStringContainsString(
			'C-A11Y-Audit-2026-06-27',
			$this->tokens_css,
			'lafka-tokens.css must carry the C-A11Y-Audit-2026-06-27 (f018) provenance comment'
		);
	}

	/**
	 * Every conversion-path component must still read the shared token, so
	 * the opaque ring actually reaches each control.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function conversion_path_components(): array {
		return array(
			'header chrome' => array( 'lafka-header-chrome.css' ),
			'cart drawer'   => array( 'lafka-cart-drawer.css' ),
			'menu archive'  => array( 'lafka-menu-archive.css' ),
			'mobile nav'    => array( 'lafka-mobile-nav.css' ),
		);
	}

	#[DataProvider('conversion_path_components')]
	public function test_component_reads_shared_focus_token( string $file ): void {
		$css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/' . $file );
		$this->assertIsString( $css, "Could not read styles/{$file}" );

		$this->assertStringContainsString(
			'var(--lafka-shadow-focus)',
			$css,
			"styles/{$file} must drive its focus ring from var(--lafka-shadow-focus) so the opaque ring propagates — C-A11Y-Audit-2026-06-27"
		);
	}
}
