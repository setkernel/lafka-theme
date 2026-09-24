<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * OSS-integrity regression lock for the editorial social-proof strip.
 *
 * Audit f036: the editorial social-proof strip defaulted its star rating to 5
 * (customizer-editorial.php) and always printed the stars row whenever the
 * section rendered. An operator who filled in only a stats line (e.g.
 * "153 reviews") without consciously setting a star value would publish a
 * fabricated 5-star rating they never entered.
 *
 * The fix makes the rating explicit-only: the default is 0, the Customizer
 * number input accepts 0 (advertised as "0 = hide"), the partial clamps to
 * 0–5, and the stars row only renders when $stars > 0. The $quote/$stats
 * fields continue to gate overall section visibility.
 */
final class EditorialSocialProofHonestyTest extends TestCase {
	private string $partial;
	private string $customizer;

	protected function setUp(): void {
		$root             = dirname( __DIR__, 2 );
		$this->partial    = file_get_contents( $root . '/partials/editorial-social-proof.php' );
		$this->customizer = file_get_contents( $root . '/incl/customizer-editorial.php' );
	}

	public function test_customizer_star_default_is_zero_not_five(): void {
		$this->assertMatchesRegularExpression(
			"/'lafka_editorial_home_proof_stars',\s*array\(\s*'default'\s*=>\s*0\b/",
			$this->customizer,
			'The proof_stars setting must default to 0 so no rating is published unless the operator sets one.'
		);
		$this->assertDoesNotMatchRegularExpression(
			"/'lafka_editorial_home_proof_stars',\s*array\(\s*'default'\s*=>\s*5\b/",
			$this->customizer,
			'The proof_stars setting must not default to a fabricated 5-star rating.'
		);
	}

	public function test_partial_reads_star_with_zero_default(): void {
		$this->assertStringContainsString(
			"get_theme_mod( 'lafka_editorial_home_proof_stars', 0 )",
			$this->partial,
			'The partial must read the star rating with a 0 default, matching the Customizer.'
		);
		$this->assertStringNotContainsString(
			"get_theme_mod( 'lafka_editorial_home_proof_stars', 5 )",
			$this->partial,
			'The partial must not fall back to a fabricated 5-star default.'
		);
	}

	public function test_partial_gates_star_row_on_positive_rating(): void {
		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*\$stars\s*>\s*0\s*\)\s*:/',
			$this->partial,
			'The stars row markup must only render when the operator deliberately set a rating ( $stars > 0 ).'
		);
	}
}
