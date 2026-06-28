<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
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

	/**
	 * Setting IDs the rebuilt partials read must each be registered by the
	 * Customizer, or operator edits are silently discarded.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function registered_key_provider(): array {
		return array(
			'hero lead'         => array( 'lafka_home_hero_lead' ),
			'hero stat 1 value' => array( 'lafka_home_hero_stat_1_value' ),
			'hero stat 1 label' => array( 'lafka_home_hero_stat_1_label' ),
			'hero stat 2 value' => array( 'lafka_home_hero_stat_2_value' ),
			'hero stat 2 label' => array( 'lafka_home_hero_stat_2_label' ),
			'hero stat 3 value' => array( 'lafka_home_hero_stat_3_value' ),
			'hero stat 3 label' => array( 'lafka_home_hero_stat_3_label' ),
			'closer lead'       => array( 'lafka_home_closer_lead' ),
		);
	}

	#[DataProvider( 'registered_key_provider' )]
	public function test_partial_keys_are_registered_settings( string $key ): void {
		$this->assertStringContainsString(
			"'{$key}'",
			$this->customizer,
			"Customizer must register the setting {$key} (a rebuilt partial reads it)."
		);
	}

	/**
	 * Orphaned v5.46 controls the rebuilt partials no longer read must be
	 * gone, so the panel stops offering dead fields (the renamed *_subhead
	 * keys must not linger either, or operator edits land where nothing
	 * renders).
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function dead_key_provider(): array {
		return array(
			'hero eyebrow'             => array( 'lafka_home_hero_eyebrow' ),
			'hero subhead (renamed)'   => array( 'lafka_home_hero_subhead' ),
			'hero secondary cta label' => array( 'lafka_home_hero_secondary_cta_label' ),
			'hero secondary cta url'   => array( 'lafka_home_hero_secondary_cta_url' ),
			'closer eyebrow'           => array( 'lafka_home_closer_eyebrow' ),
			'closer subhead (renamed)' => array( 'lafka_home_closer_subhead' ),
		);
	}

	#[DataProvider( 'dead_key_provider' )]
	public function test_dead_controls_removed( string $key ): void {
		$this->assertStringNotContainsString(
			"'{$key}'",
			$this->customizer,
			"Customizer must not register the orphaned control {$key} (no partial reads it)."
		);
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

	public function test_hero_lead_customizer_default_matches_partial_fallback(): void {
		$this->assertMatchesRegularExpression(
			"/get_theme_mod\(\s*'lafka_home_hero_lead',\s*\R?\s*__\(\s*'Fresh dough,/s",
			$this->hero,
			'Hero partial lead fallback baseline.'
		);
		$this->assertMatchesRegularExpression(
			"/'lafka_home_hero_lead'.*?'default'\s*=>\s*__\(\s*'Fresh dough,/s",
			$this->customizer,
			'Customizer hero lead default must match the partial fallback so preview == render.'
		);
	}

	public function test_closer_lead_customizer_default_matches_partial_fallback(): void {
		$this->assertStringContainsString(
			"'Pickup or delivery. Ready in about 25 minutes.'",
			$this->closer,
			'Closer partial lead fallback baseline.'
		);
		$this->assertMatchesRegularExpression(
			"/'lafka_home_closer_lead'.*?'default'\s*=>\s*__\(\s*'Pickup or delivery\. Ready in about 25 minutes\.'/s",
			$this->customizer,
			'Customizer closer lead default must match the partial fallback so preview == render.'
		);
	}

	public function test_status_pill_gated_on_show_status_toggle(): void {
		$this->assertMatchesRegularExpression(
			"/get_theme_mod\(\s*'lafka_home_hero_show_status',\s*true\s*\)/",
			$this->hero,
			'Hero partial must gate the status pill on the lafka_home_hero_show_status toggle (default true).'
		);
	}

	public function test_overlay_toggle_adds_media_modifier_class(): void {
		$this->assertStringContainsString(
			"get_theme_mod( 'lafka_home_hero_overlay'",
			$this->hero,
			'Hero partial must read the overlay toggle.'
		);
		$this->assertStringContainsString(
			'lafka-hero__media--overlay',
			$this->hero,
			'Hero partial must add the overlay modifier class when the overlay mod is on.'
		);
	}
}
