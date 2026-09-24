<?php
declare(strict_types=1);

/**
 * Social proof (incl/template-helpers/social-proof.php) must never show a
 * rating the operator did not enter: a review count on its own renders as a
 * count, with no stars, no "0.0" and no separator.
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/template-helpers/social-proof.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class SocialProofRatingGuardTest extends TestCase {

		private function render(): string {
			ob_start();
			\lafka_social_proof_render();
			return (string) ob_get_clean();
		}

		public function test_nothing_renders_without_a_rating_or_count(): void {
			$this->assertSame( '', $this->render() );
		}

		public function test_count_only_renders_no_stars_or_zero_rating(): void {
			$GLOBALS['lafka_test_theme_mods'] = array( 'lafka_social_proof_count' => 120 );

			$html = $this->render();

			$this->assertStringContainsString( '120 reviews', $html );
			$this->assertStringNotContainsString( 'lafka-social-proof__stars', $html );
			$this->assertStringNotContainsString( '0.0', $html );
			$this->assertStringNotContainsString( 'lafka-social-proof__separator', $html );
			$this->assertStringContainsString( 'aria-label="120 reviews"', $html );
		}

		public function test_rating_and_count_render_stars_value_and_separator(): void {
			$GLOBALS['lafka_test_theme_mods'] = array(
				'lafka_social_proof_rating'   => '4.5',
				'lafka_social_proof_count'    => 1,
				'lafka_social_proof_provider' => 'Google',
			);

			$html = $this->render();

			$this->assertStringContainsString( 'style="width: 90%;"', $html );
			$this->assertStringContainsString( '<span class="lafka-social-proof__rating">4.5</span>', $html );
			$this->assertStringContainsString( 'lafka-social-proof__separator', $html );
			$this->assertStringContainsString( 'aria-label="4.5 out of 5 stars, 1 Google review"', $html );
		}
	}
}
