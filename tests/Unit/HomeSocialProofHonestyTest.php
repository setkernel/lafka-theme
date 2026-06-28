<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * OSS-integrity regression lock for home-page social proof.
 *
 * Audit 2026-06-27 #5: the home hero defaulted its rating stat to
 * "4.8 / 1,200+ reviews" and the reviews section defaulted to a visible
 * 4.8 average over 500 reviews plus three invented testimonials. Because the
 * product ships "defaults are the product" (operators are not expected to
 * customize), every fresh install publicly displayed fabricated ratings and
 * fake testimonials.
 *
 * The fix: no fabricated social proof in defaults. The hero rating stat and
 * each review render only when the operator supplies real data (Customizer
 * fields or the `lafka_home_reviews` filter); otherwise the markup is omitted.
 */
final class HomeSocialProofHonestyTest extends TestCase {

	private string $hero;
	private string $reviews;
	private string $customizer;

	protected function setUp(): void {
		$root             = dirname( __DIR__, 2 );
		$this->hero       = file_get_contents( $root . '/partials/home-hero.php' );
		$this->reviews    = file_get_contents( $root . '/partials/home-reviews.php' );
		$this->customizer = file_get_contents( $root . '/incl/customizer-home.php' );
	}

	public function test_hero_does_not_default_a_fake_rating(): void {
		$this->assertDoesNotMatchRegularExpression(
			"/lafka_home_hero_stat_1_value['\"]\s*,\s*['\"]4\.8['\"]/",
			$this->hero,
			'Hero must not default the rating stat to a fabricated "4.8".'
		);
		$this->assertDoesNotMatchRegularExpression(
			"/1,200\+\s*reviews/",
			$this->hero,
			'Hero must not default to a fabricated "1,200+ reviews" count.'
		);
	}

	public function test_hero_renders_first_stat_conditionally(): void {
		$this->assertMatchesRegularExpression(
			"/''\s*!==\s*\\\$lafka_hero_stat_1_value/",
			$this->hero,
			'Hero must only render the rating stat when the operator provides a value.'
		);
	}

	public function test_reviews_do_not_default_fake_aggregate(): void {
		$this->assertDoesNotMatchRegularExpression(
			"/lafka_home_reviews_avg['\"]\s*,\s*4\.8/",
			$this->reviews,
			'Reviews must not default the average to a fabricated 4.8.'
		);
		$this->assertDoesNotMatchRegularExpression(
			"/lafka_home_reviews_count['\"]\s*,\s*500/",
			$this->reviews,
			'Reviews must not default the count to a fabricated 500.'
		);
	}

	public function test_reviews_have_no_fabricated_testimonials(): void {
		foreach ( array( 'Hot, fresh', 'poutine is the real deal', 'Family favourite' ) as $needle ) {
			$this->assertStringNotContainsString(
				$needle,
				$this->reviews,
				"Reviews must not ship the fabricated testimonial: \"$needle\"."
			);
		}
	}

	public function test_reviews_drop_empty_entries_and_bail_when_none(): void {
		$this->assertStringContainsString(
			'array_filter',
			$this->reviews,
			'Reviews must filter out entries with no real quote text.'
		);
		$this->assertMatchesRegularExpression(
			"/if\s*\(\s*empty\(\s*\\\$lafka_reviews\s*\)\s*\)\s*\{?\s*return;/",
			$this->reviews,
			'Reviews must return early (render nothing) when there are no real reviews.'
		);
	}

	public function test_reviews_rating_row_gated_on_real_count(): void {
		$this->assertMatchesRegularExpression(
			"/\\\$lafka_rev_count\s*>\s*0/",
			$this->reviews,
			'The aggregate rating row must only render when there is a real review count.'
		);
	}

	/**
	 * Audit f009: the v6.13.0 honesty rewrite left the partial reading
	 * setting IDs (lafka_home_reviews_avg, lafka_home_review_N_author/_date,
	 * etc.) that no add_setting() registers, so every operator-entered review
	 * was silently discarded. This locks the render keys to the Customizer
	 * keys: every get_theme_mod() key the partial reads for the Reviews
	 * section must be a registered setting in section `lafka_home_reviews`.
	 */
	public function test_reviews_partial_keys_are_all_registered_settings(): void {
		$read       = $this->reviews_partial_keys();
		$registered = $this->registered_review_setting_ids();

		$this->assertNotEmpty( $read, 'The reviews partial must read at least one review setting.' );

		$missing = array_values( array_diff( $read, $registered ) );
		$this->assertSame(
			array(),
			$missing,
			'The reviews partial reads get_theme_mod keys that NO add_setting registers in section '
				. 'lafka_home_reviews (operator input would be silently discarded): '
				. implode( ', ', $missing )
		);
	}

	public function test_reviews_partial_reads_the_canonical_per_review_keys(): void {
		foreach ( array( 1, 2, 3 ) as $n ) {
			foreach ( array( 'quote', 'name', 'source', 'stars' ) as $suffix ) {
				$this->assertStringContainsString(
					"get_theme_mod( 'lafka_home_reviews_{$n}_{$suffix}'",
					$this->reviews,
					"Reviews partial must read the registered per-review key lafka_home_reviews_{$n}_{$suffix}."
				);
			}
		}
	}

	public function test_reviews_partial_reads_registered_aggregate_rating_key(): void {
		$this->assertStringContainsString(
			"get_theme_mod( 'lafka_home_reviews_rating'",
			$this->reviews,
			'Reviews partial must read the registered aggregate key lafka_home_reviews_rating.'
		);
		$this->assertStringNotContainsString(
			'lafka_home_reviews_avg',
			$this->reviews,
			'Reviews partial must not read the unregistered aggregate key lafka_home_reviews_avg.'
		);
	}

	public function test_reviews_partial_drives_stars_off_data_not_hardcoded(): void {
		$this->assertStringNotContainsString(
			'★★★★★',
			$this->reviews,
			'Reviews partial must not hardcode a 5-star row; emit str_repeat() from the rating/per-review stars.'
		);
		$this->assertStringContainsString(
			"str_repeat( '★'",
			$this->reviews,
			'Reviews partial must build the star row with str_repeat() from real data.'
		);
	}

	public function test_reviews_headline_default_matches_customizer(): void {
		$this->assertMatchesRegularExpression(
			"/get_theme_mod\(\s*'lafka_home_reviews_headline',\s*__\(\s*'What our neighbors say'/",
			$this->reviews,
			'Reviews partial headline fallback must match the Customizer default so preview and render agree.'
		);
		$this->assertMatchesRegularExpression(
			"/'lafka_home_reviews_headline'.*?'default'\s*=>\s*__\(\s*'What our neighbors say'/s",
			$this->customizer,
			'Customizer headline default must match the partial fallback.'
		);
		$this->assertStringNotContainsString(
			'People keep coming back.',
			$this->reviews,
			'Reviews partial must not keep its old divergent headline fallback.'
		);
	}

	/**
	 * Every get_theme_mod() key the reviews partial reads (covering both the
	 * correct plural `lafka_home_reviews_*` form and the broken singular
	 * `lafka_home_review_*` form, so a regression is caught either way).
	 *
	 * @return string[]
	 */
	private function reviews_partial_keys(): array {
		preg_match_all(
			"/get_theme_mod\(\s*'(lafka_home_review[a-z0-9_]*)'/",
			$this->reviews,
			$matches
		);

		return array_values( array_unique( $matches[1] ) );
	}

	/**
	 * Setting IDs registered by add_setting() for section lafka_home_reviews,
	 * reconstructed from the Customizer source (literal IDs + the per-review
	 * IDs that are built with the `{$lafka_r}` loop counter, expanded 1–3).
	 *
	 * @return string[]
	 */
	private function registered_review_setting_ids(): array {
		$ids = array();

		// Literal setting IDs: the `visible` flag and the reviews fields-array keys.
		preg_match_all( "/'(lafka_home_reviews_[a-z]+)'/", $this->customizer, $literal );
		foreach ( $literal[1] as $id ) {
			$ids[ $id ] = true;
		}

		// Per-review IDs registered via "lafka_home_reviews_{$lafka_r}_<suffix>".
		preg_match_all( '/lafka_home_reviews_\{\$lafka_r\}_([a-z]+)/', $this->customizer, $per_review );
		foreach ( array( 1, 2, 3 ) as $n ) {
			foreach ( array_unique( $per_review[1] ) as $suffix ) {
				$ids[ "lafka_home_reviews_{$n}_{$suffix}" ] = true;
			}
		}

		return array_keys( $ids );
	}
}
