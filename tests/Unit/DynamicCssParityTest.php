<?php
declare(strict_types=1);

/**
 * NX1-02 DYNAMIC-CSS BYTE-PARITY GATE (build this BEFORE any legacy-option migration).
 *
 * WHAT IT LOCKS
 * -------------
 * styles/dynamic-css.php reads the legacy Options Framework layer (via
 * lafka_get_option()) to emit the :root{} design-token block on every page.
 * NX1-02 retires that layer: each slice re-points a reader at its new home
 * (Customizer theme_mods / plugin options) and must NOT change a single emitted
 * byte. This test is the regression gate that proves that.
 *
 * HOW THE CONTRACT WORKS (read before touching this file)
 * -------------------------------------------------------
 *  1. tests/fixtures/dynamic-css-fixture.php is a COMPLETE, deliberately
 *     non-default snapshot of every option key dynamic-css.php consumes. Values
 *     are all unique so a swapped/dropped/mis-homed key shows up as a diff.
 *  2. The global-namespace shims below answer BOTH lafka_get_option() AND
 *     get_theme_mod() from that SAME fixture. So whether a reader still calls the
 *     legacy helper (pre-migration) or has been re-pointed at a theme_mod
 *     (post-slice), it receives the identical value — and the emitted CSS stays
 *     byte-identical to the golden. Byte parity therefore == lossless rewiring.
 *  3. tests/fixtures/dynamic-css-expected.css is the golden captured on the
 *     PRE-migration HEAD. The test asserts lafka_dynamic_css_build() === golden.
 *
 * A migration mistake (reads the wrong key, forgets to migrate a default, drops
 * a token) makes a reader return something other than the fixture value, the
 * emitted CSS diverges, and this test fails on the offending slice.
 *
 * REGENERATE THE GOLDEN (only on an INTENTIONAL dynamic-css contract change):
 *   LAFKA_UPDATE_DYNCSS_GOLDEN=1 vendor/bin/phpunit --filter DynamicCssParityTest
 * then review the diff and commit tests/fixtures/dynamic-css-expected.css.
 *
 * ISOLATION
 * ---------
 * The WP shims (esc_attr / esc_url / add_action / wp_get_attachment_image_url /
 * lafka_get_option / get_theme_mod) live in the GLOBAL namespace — that is where
 * the procedural builder resolves its calls. Sibling test files define some of
 * the same shims, so this class runs in a SEPARATE PROCESS with global state
 * discarded, guaranteeing THESE fixture-backed shims win.
 *
 * @package Lafka\Tests
 */

namespace {

	// Shared bootstrap defines ABSPATH; guard for isolated runs.
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	// The single source both readers answer from (see contract note #2).
	$GLOBALS['lafka_dyncss_fixture'] = require dirname( __DIR__ ) . '/fixtures/dynamic-css-fixture.php';

	if ( ! function_exists( 'lafka_dyncss_fixture_get' ) ) {
		/**
		 * Resolve a key from the committed parity fixture, mirroring the
		 * "return the stored value or the caller default" contract shared by
		 * lafka_get_option() and get_theme_mod().
		 */
		function lafka_dyncss_fixture_get( $name, $default = false ) {
			$fx = isset( $GLOBALS['lafka_dyncss_fixture'] ) ? $GLOBALS['lafka_dyncss_fixture'] : array();
			return array_key_exists( $name, $fx ) ? $fx[ $name ] : $default;
		}
	}

	// ---- The two readers under migration — SAME answer, by design. ----------
	if ( ! function_exists( 'lafka_get_option' ) ) {
		function lafka_get_option( $name, $default = false ) {
			return lafka_dyncss_fixture_get( $name, $default );
		}
	}
	if ( ! function_exists( 'get_theme_mod' ) ) {
		function get_theme_mod( $name, $default = false ) {
			return lafka_dyncss_fixture_get( $name, $default );
		}
	}

	// ---- WordPress shims the builder calls. ---------------------------------
	// esc_attr/esc_url are identity: every fixture value is clean ASCII, so real
	// WP escaping is a no-op on them. The golden is self-consistent regardless —
	// this gate proves reader REWIRING, not escaping behaviour.
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( $url, $protocols = null, $context = 'display' ) {
			return $url;
		}
	}
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook = '', $callback = null, $priority = 10, $args = 1 ) {
			return true;
		}
	}
	if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
		function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail', $icon = false ) {
			// Deterministic per id so the image branches emit stable URLs.
			return 'https://example.test/wp-content/uploads/lafka-fixture-' . (int) $attachment_id . '.jpg';
		}
	}

	// Define the builder under test. Output-buffer the require: the file emits a
	// stray "\n" between its two <?php blocks that would otherwise leak.
	if ( ! function_exists( 'lafka_dynamic_css_build' ) ) {
		ob_start();
		require dirname( __DIR__, 2 ) . '/styles/dynamic-css.php';
		ob_end_clean();
	}
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\Attributes\PreserveGlobalState;
	use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
	use PHPUnit\Framework\TestCase;

	#[RunTestsInSeparateProcesses]
	#[PreserveGlobalState( false )]
	final class DynamicCssParityTest extends TestCase {

		private function fixture(): array {
			return (array) ( $GLOBALS['lafka_dyncss_fixture'] ?? array() );
		}

		/**
		 * Every design-token key styles/dynamic-css.php reads must appear in the
		 * fixture, so a future dynamic-css edit that consumes a NEW key can't
		 * silently escape the parity gate — it fails here until the fixture (and
		 * golden) are extended.
		 *
		 * NX1-02.dyncss-typography-backgrounds retired the last lafka_get_option
		 * read here: the builder now reads exclusively from `lafka_<key>`
		 * theme_mods, so this guard tracks the get_theme_mod( 'lafka_*' ) reads.
		 * (The migration is complete only when the legacy grep below stays empty.)
		 */
		public function test_fixture_covers_every_consumed_key(): void {
			$src = (string) file_get_contents( dirname( __DIR__, 2 ) . '/styles/dynamic-css.php' );

			// Milestone guard: no legacy Options-Framework read may remain here.
			preg_match_all( "/lafka_get_option\(\s*'([a-z0-9_]+)'/", $src, $legacy );
			$this->assertSame(
				array(),
				array_values( array_unique( $legacy[1] ) ),
				'styles/dynamic-css.php still calls lafka_get_option() — NX1-02 requires ZERO legacy reads here.'
			);

			preg_match_all( "/get_theme_mod\(\s*'(lafka_[a-z0-9_]+)'/", $src, $m );
			$keys = array_values( array_unique( $m[1] ) );
			// The heading loop reads get_theme_mod( 'lafka_h' . $i . '_font' ); the
			// regex captures the literal 'lafka_h' — expand it to lafka_h1_font..h6.
			$keys = array_values( array_diff( $keys, array( 'lafka_h' ) ) );
			for ( $i = 1; $i <= 6; $i++ ) {
				$keys[] = 'lafka_h' . $i . '_font';
			}

			$fixture = $this->fixture();
			$this->assertNotEmpty( $fixture, 'Parity fixture failed to load.' );
			foreach ( $keys as $key ) {
				$this->assertArrayHasKey(
					$key,
					$fixture,
					"dynamic-css.php reads '{$key}' but the parity fixture does not define it. "
						. 'Extend tests/fixtures/dynamic-css-fixture.php (and regenerate the golden).'
				);
			}
		}

		/**
		 * The dual-answer contract: for every fixture key, lafka_get_option() and
		 * get_theme_mod() must return the identical value. This is what makes a
		 * mid-migration reader swap (legacy helper -> theme_mod) a no-op on output.
		 */
		public function test_both_readers_answer_identically(): void {
			foreach ( array_keys( $this->fixture() ) as $key ) {
				$this->assertSame(
					\lafka_get_option( $key ),
					\get_theme_mod( $key ),
					"Reader disagreement on '{$key}' would let a migration slice change output undetected."
				);
			}
		}

		/**
		 * The gate itself: emitted CSS must be byte-identical to the golden
		 * captured on the pre-migration HEAD.
		 */
		public function test_emitted_css_is_byte_identical_to_golden(): void {
			$this->assertTrue(
				function_exists( 'lafka_dynamic_css_build' ),
				'styles/dynamic-css.php did not define lafka_dynamic_css_build().'
			);

			$actual = \lafka_dynamic_css_build();
			$golden = dirname( __DIR__ ) . '/fixtures/dynamic-css-expected.css';

			if ( getenv( 'LAFKA_UPDATE_DYNCSS_GOLDEN' ) === '1' ) {
				file_put_contents( $golden, $actual );
			}

			$this->assertFileExists(
				$golden,
				'Golden missing. Generate it once with '
					. 'LAFKA_UPDATE_DYNCSS_GOLDEN=1 vendor/bin/phpunit --filter DynamicCssParityTest.'
			);
			$this->assertSame(
				(string) file_get_contents( $golden ),
				$actual,
				'Emitted dynamic-css diverged from the golden: a NX1-02 slice re-pointed a '
					. 'reader lossily (wrong/dropped/mis-homed key or default). Diff the two to find it.'
			);
		}

		/**
		 * Determinism guard: two consecutive builds are identical (no time/random
		 * leakage), so a clean re-run of the gate is stable.
		 */
		public function test_build_is_deterministic(): void {
			$this->assertSame( \lafka_dynamic_css_build(), \lafka_dynamic_css_build() );
		}
	}
}
