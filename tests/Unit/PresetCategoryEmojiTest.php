<?php
declare(strict_types=1);

/**
 * NX2 proof that the active preset's `category_emoji` map is WIRED into the
 * `lafka_category_emoji` filter (partials/home-categories.php:102), so each
 * preset can theme the "What are you craving?" category-tile glyphs. Exercises
 * the REAL registry + the REAL shipped presets (ember has a map; peppery and
 * midnight ship empty maps and must pass the incoming glyph through untouched).
 *
 * @package Lafka\Tests
 */

namespace {
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-tokens.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-preset.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/class-lafka-presets.php';
	require_once dirname( __DIR__, 2 ) . '/incl/presets/lafka-preset-emit.php';

	// Hooks registered when the preset code was loaded (the category-emoji filter).
	$GLOBALS['lafka_test_include_filters'] = $GLOBALS['lafka_test_filters'] ?? array();
}

namespace Lafka\Tests\Unit {
	use PHPUnit\Framework\TestCase;

	final class PresetCategoryEmojiTest extends TestCase {
		protected function setUp(): void {
			$GLOBALS['lafka_test_filters']    = array();
			$GLOBALS['lafka_test_theme_mods'] = array();
			\Lafka_Presets::reset();
		}

		/** A product_cat term is just a slug + name to the filter callback. */
		private function term( string $slug, string $name = '' ): \stdClass {
			$term       = new \stdClass();
			$term->slug = $slug;
			$term->name = '' !== $name ? $name : ucfirst( $slug );
			return $term;
		}

		/** The callback is actually registered on the filter at include time. */
		public function test_callback_is_registered_on_the_filter(): void {
			$registered = $GLOBALS['lafka_test_include_filters']['lafka_category_emoji'][10] ?? array();
			$this->assertContains( 'lafka_preset_category_emoji', $registered, 'lafka-preset-emit.php must register lafka_preset_category_emoji on lafka_category_emoji' );
		}

		/** (a) Preset WITH a map: exact slug hit returns the preset glyph. */
		public function test_active_map_returns_preset_glyph_for_exact_slug(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'ember';
			$this->assertSame(
				'🍔',
				\lafka_preset_category_emoji( '🍕', $this->term( 'pizzas', 'Pizzas' ) ),
				'ember maps the pizzas slug to its burger glyph'
			);
			$this->assertSame(
				'🥤',
				\lafka_preset_category_emoji( '🍕', $this->term( 'drinks', 'Drinks' ) ),
				'ember maps the drinks slug to its cup glyph'
			);
		}

		/** Fuzzy fallback: the term slug CONTAINS a map key (mirrors the partial). */
		public function test_fuzzy_slug_contains_map_key(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'ember';
			$this->assertSame(
				'🥗',
				\lafka_preset_category_emoji( '🍕', $this->term( 'fresh-salads', 'Fresh Salads' ) ),
				'salads is a substring of the fresh-salads slug'
			);
		}

		/** Fuzzy fallback: the term NAME contains a map key (case-insensitive). */
		public function test_fuzzy_name_contains_map_key(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'ember';
			$this->assertSame(
				'🥤',
				\lafka_preset_category_emoji( '🍕', $this->term( 'cold-bev', 'Cold Drinks' ) ),
				'the drinks key appears in the term name'
			);
		}

		/** (b) Peppery ships an EMPTY map -> the incoming glyph passes through. */
		public function test_peppery_empty_map_returns_emoji_unchanged(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'peppery';
			$this->assertSame(
				'🍕',
				\lafka_preset_category_emoji( '🍕', $this->term( 'pizzas', 'Pizzas' ) ),
				'peppery has no category_emoji map, so the default glyph is byte-identical'
			);
		}

		/** (c) A slug absent from a non-empty map falls back to the passed glyph. */
		public function test_unknown_slug_falls_back_to_passed_emoji(): void {
			$GLOBALS['lafka_test_theme_mods']['lafka_active_preset'] = 'ember';
			$this->assertSame(
				'🍞',
				\lafka_preset_category_emoji( '🍞', $this->term( 'ramen', 'Ramen' ) ),
				'ember has no ramen key, so the incoming default glyph is preserved'
			);
		}
	}
}
