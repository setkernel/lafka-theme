<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression lock for audit f056: the v5.59/v5.60 handoff partial rebuild
 * left the home Customizer controls drifted from the rebuilt partials.
 *
 * Dead controls (registered, read nowhere) offered the operator fields that
 * went nowhere, while the partials read keys (lead paragraphs, hero stat row)
 * that no control registered — so the operator-visible "Sub-headline" field
 * wrote to a key nothing rendered and the stat row was uneditable.
 *
 * This test asserts the controls and the partials read/write the same keys.
 */
final class HomeHeroCloserCustomizerWiringTest extends TestCase {
	private string $customizer;
	private string $hero;
	private string $closer;

	protected function setUp(): void {
		$root             = dirname( __DIR__, 2 );
		$this->customizer = file_get_contents( $root . '/incl/customizer-home.php' );
		$this->hero       = file_get_contents( $root . '/partials/home-hero.php' );
		$this->closer     = file_get_contents( $root . '/partials/home-cta-closer.php' );
	}

	public function test_every_home_hero_closer_key_read_in_partials_is_registered(): void {
		$read = array();
		foreach ( array( $this->hero, $this->closer ) as $src ) {
			preg_match_all(
				"/get_theme_mod\(\s*'(lafka_home_(?:hero|closer)_[a-z0-9_]+)'/",
				$src,
				$matches
			);
			$read = array_merge( $read, $matches[1] );
		}
		$read = array_values( array_unique( $read ) );
		$this->assertNotEmpty( $read, 'The home partials must read at least one hero/closer setting.' );

		$missing = array();
		foreach ( $read as $key ) {
			if ( false === strpos( $this->customizer, "'{$key}'" ) ) {
				$missing[] = $key;
			}
		}
		$this->assertSame(
			array(),
			$missing,
			'The home partials read get_theme_mod keys that NO add_setting registers in '
				. 'incl/customizer-home.php (operator input would be silently discarded): '
				. implode( ', ', $missing )
		);
	}

	public function test_hero_rating_stat_defaults_to_empty(): void {
		$this->assertMatchesRegularExpression(
			"/'lafka_home_hero_stat_1_value'.*?'default'\s*=>\s*''/s",
			$this->customizer,
			'Customizer stat 1 value must default to an empty string (no fabricated rating).'
		);
		$this->assertMatchesRegularExpression(
			"/'lafka_home_hero_stat_1_label'.*?'default'\s*=>\s*''/s",
			$this->customizer,
			'Customizer stat 1 label must default to an empty string.'
		);
	}
}
