<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock (audit f035): the PDP social-proof widget must NOT show a
 * "0.0" rating (plus an empty star bar) when the operator configures only a
 * review count and no rating.
 *
 * Before the fix lafka_social_proof_get_data() coerced a blank rating to 0.0,
 * and the renderer gated the rating label on `'' !== (string) $data['rating']`
 * which is true for the string "0" — so a count-only configuration printed a
 * zero rating that reads as broken. The fix decouples "has a rating" from the
 * numeric value via an explicit `has_rating` flag and gates the stars, the
 * rating label and the separator on it.
 *
 * Pure source-scan (no WP runtime) so it is immune to the shared-process stub
 * collisions that other behavioural render tests in this suite rely on.
 */
final class SocialProofRatingGuardTest extends TestCase {

	private string $src;

	protected function setUp(): void {
		parent::setUp();
		$this->src = file_get_contents(
			dirname( __DIR__, 2 ) . '/incl/template-helpers/social-proof.php'
		);
	}

	public function test_data_array_exposes_an_explicit_has_rating_flag(): void {
		$this->assertMatchesRegularExpression(
			"/'has_rating'\s*=>\s*''\s*!==\s*\\\$rating/",
			$this->src,
			'get_data() must set has_rating from whether a rating string was supplied, not from the float value.'
		);
	}

	public function test_get_data_still_keeps_a_float_rating_for_the_bar_width(): void {
		$this->assertMatchesRegularExpression(
			"/'rating'\s*=>\s*''\s*===\s*\\\$rating\s*\?\s*0\.0\s*:\s*\(float\)\s*\\\$rating/",
			$this->src,
			'get_data() must preserve the documented float contract (blank -> 0.0) for the star-bar width math.'
		);
	}

	public function test_rating_label_is_gated_on_has_rating_not_on_the_raw_value(): void {
		$this->assertMatchesRegularExpression(
			"/\\\$rating_label\s*=\s*\\\$has_rating\s*\?\s*number_format_i18n\(\s*\\\$rating,\s*1\s*\)\s*:\s*''/",
			$this->src,
			'The rating label must derive from has_rating.'
		);
		$this->assertStringNotContainsString(
			"'' !== (string) \$data['rating']",
			$this->src,
			'The old guard that printed "0.0" for a string "0" rating must be gone.'
		);
	}

	public function test_stars_block_is_gated_on_has_rating(): void {
		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*\$has_rating\s*\)\s*:\s*\?>.*?lafka-social-proof__stars.*?lafka-social-proof__rating.*?endif/s',
			$this->src,
			'Both the star bar and the numeric rating span must render only when has_rating is true.'
		);
	}

	public function test_separator_is_gated_on_has_rating(): void {
		$this->assertMatchesRegularExpression(
			'/if\s*\(\s*\$has_rating\s*\)\s*:\s*\?>\s*<span class="lafka-social-proof__separator"/s',
			$this->src,
			'The leading separator dot must not render for a count-only configuration.'
		);
	}

	public function test_docblock_documents_the_has_rating_typedef(): void {
		$this->assertMatchesRegularExpression(
			'/has_rating:\s*bool/',
			$this->src,
			'The get_data() return typedef must document the has_rating flag.'
		);
	}
}
