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

	protected function setUp(): void {
		$root          = dirname( __DIR__, 2 );
		$this->hero    = file_get_contents( $root . '/partials/home-hero.php' );
		$this->reviews = file_get_contents( $root . '/partials/home-reviews.php' );
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
}
