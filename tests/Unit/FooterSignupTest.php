<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock for the footer email-signup default.
 *
 * Audit 2026-06-27 #4: footer.php shipped a real <form> posting to
 * admin-ajax.php?action=lafka_footer_subscribe with NO server handler and NO
 * JS — submitting did a full-page reload to admin-ajax (0/"") and the promised
 * "$5 off / check your inbox for your $5-off code" had no fulfilment.
 *
 * Fix: the default form is only emitted when an integration actually wires the
 * handler (has_action on the AJAX action); otherwise nothing renders. The
 * lafka_footer_signup_html filter still lets a provider supply its own form.
 * The unfulfilled "$5 off" promise is removed.
 */
final class FooterSignupTest extends TestCase {

	private string $footer;

	protected function setUp(): void {
		$this->footer = file_get_contents( dirname( __DIR__, 2 ) . '/footer.php' );
	}

	public function test_default_form_gated_on_a_real_handler(): void {
		$this->assertMatchesRegularExpression(
			"/has_action\(\s*['\"]wp_ajax_nopriv_lafka_footer_subscribe['\"]\s*\)/",
			$this->footer,
			'The default signup form must only render when a subscribe handler is actually registered.'
		);
	}

	public function test_filter_escape_hatch_preserved(): void {
		$this->assertMatchesRegularExpression(
			"/apply_filters\(\s*['\"]lafka_footer_signup_html['\"]/",
			$this->footer,
			'Integrators must still be able to supply their own signup form via the filter.'
		);
	}

	public function test_no_unfulfilled_discount_promise(): void {
		$this->assertStringNotContainsString( '$5 off', $this->footer, 'Remove the unfulfilled "$5 off" promise.' );
		$this->assertStringNotContainsString( '$5-off code', $this->footer, 'Remove the unfulfilled "$5-off code" promise.' );
	}
}
