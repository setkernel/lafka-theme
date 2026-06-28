<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Audit 2026-06-28 (f064) regression lock: the web-push subscribe prompt used to
 * hardcode z-index: 1080, bypassing the documented z-index scale in
 * lafka-tokens.css. A raw literal silently desyncs from the scale if the band is
 * ever re-spaced. The prompt must instead DERIVE its stacking from the shared
 * promo band token (--lafka-z-toast) so re-spacing propagates automatically, and
 * must stay below the review banner (toast - 10) and exit toast (toast).
 */
final class PushPromptZIndexTokenTest extends TestCase {
	private string $css;

	protected function setUp(): void {
		parent::setUp();
		$this->css = file_get_contents( dirname( __DIR__, 2 ) . '/styles/lafka-push-prompt.css' );
	}

	public function test_push_prompt_z_index_is_derived_from_the_toast_token(): void {
		$this->assertStringContainsString(
			'z-index: calc(var(--lafka-z-toast, 1100) - 20);',
			$this->css,
			'lafka-push-prompt.css must derive z-index from the shared promo band token (--lafka-z-toast), not a magic number.'
		);
	}

	public function test_push_prompt_drops_the_hardcoded_z_index_literal(): void {
		$this->assertStringNotContainsString(
			'z-index: 1080;',
			$this->css,
			'lafka-push-prompt.css must not carry the old hardcoded z-index: 1080 literal.'
		);
	}
}
