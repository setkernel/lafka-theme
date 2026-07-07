<?php
/**
 * LegacyOptionShimScanTest — NX1-02 shim-deprecation coverage.
 *
 * After the framework retirement, lafka_get_option() is a DEPRECATED one-cycle
 * back-compat shim: for a MAPPED appearance key it reads the migrated
 * `lafka_<key>` theme_mod and fires a WP_DEBUG deprecation notice. Every
 * FIRST-PARTY theme reader of a mapped key was re-pointed at get_theme_mod() by
 * the NX1-02 slices, so no theme source may still call lafka_get_option() for a
 * mapped key — otherwise it would emit a self-inflicted deprecation notice and
 * silently depend on the shim it is meant to retire.
 *
 * This guard tokenises every theme PHP file (so it ignores mapped-key mentions
 * inside comments/docblocks) and fails if any `lafka_get_option( 'mapped_key' )`
 * call survives outside the shim itself and the migration map.
 *
 * @package Lafka\Tests\Unit
 * @since   lafka-theme 7.0.0 (NX1-02 framework retirement)
 */

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}
	// The migration map is the single source of "which keys are mapped".
	require_once dirname( __DIR__, 2 ) . '/incl/system/lafka-legacy-migrate.php';
}

namespace Lafka\Tests\Unit {

	use PHPUnit\Framework\TestCase;

	final class LegacyOptionShimScanTest extends TestCase {

		/**
		 * Files that are ALLOWED to reference lafka_get_option + mapped keys:
		 * the shim definition and the migration map itself.
		 *
		 * @return string[] Absolute paths.
		 */
		private function excluded(): array {
			$root = dirname( __DIR__, 2 );
			return array(
				$root . '/incl/system/core-functions.php',        // the shim.
				$root . '/incl/system/lafka-legacy-migrate.php',  // the map.
			);
		}

		/**
		 * Extract every `lafka_get_option( '<literal>' )` first-argument literal
		 * from PHP source using the tokenizer, so comments never register as calls.
		 *
		 * @return string[] The literal keys passed as the first argument.
		 */
		private function called_keys( string $src ): array {
			$tokens = token_get_all( $src );
			$count  = count( $tokens );
			$keys   = array();

			for ( $i = 0; $i < $count; $i++ ) {
				$tok = $tokens[ $i ];
				if ( ! is_array( $tok ) || T_STRING !== $tok[0] || 'lafka_get_option' !== $tok[1] ) {
					continue;
				}
				// Next significant token must be '('.
				$j = $this->next_significant( $tokens, $i + 1, $count );
				if ( $j >= $count || '(' !== $tokens[ $j ] ) {
					continue;
				}
				// First argument must be a single-quoted / double-quoted literal.
				$k = $this->next_significant( $tokens, $j + 1, $count );
				if ( $k < $count && is_array( $tokens[ $k ] ) && T_CONSTANT_ENCAPSED_STRING === $tokens[ $k ][0] ) {
					$keys[] = trim( $tokens[ $k ][1], "'\"" );
				}
			}
			return $keys;
		}

		private function next_significant( array $tokens, int $from, int $count ): int {
			for ( $i = $from; $i < $count; $i++ ) {
				if ( is_array( $tokens[ $i ] ) && in_array( $tokens[ $i ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
					continue;
				}
				return $i;
			}
			return $count;
		}

		public function test_no_theme_file_reads_a_mapped_key_via_shim(): void {
			$mapped   = \lafka_legacy_migrate_map();
			$excluded = $this->excluded();
			$root     = dirname( __DIR__, 2 );

			$violations = array();

			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $it as $file ) {
				if ( 'php' !== strtolower( $file->getExtension() ) ) {
					continue;
				}
				$path = $file->getPathname();
				if ( in_array( $path, $excluded, true ) ) {
					continue;
				}
				if ( str_contains( $path, '/tests/' ) || str_contains( $path, '/vendor/' ) || str_contains( $path, '/node_modules/' ) ) {
					continue;
				}

				foreach ( $this->called_keys( (string) file_get_contents( $path ) ) as $key ) {
					if ( isset( $mapped[ $key ] ) ) {
						$violations[] = sprintf( '%s reads mapped key %s (use get_theme_mod(\'%s\'))', str_replace( $root . '/', '', $path ), $key, $mapped[ $key ] );
					}
				}
			}

			$this->assertSame(
				array(),
				$violations,
				"First-party theme code still reads a migrated key through the deprecated lafka_get_option() shim:\n" . implode( "\n", $violations )
			);
		}

		/**
		 * Sanity: the tokenizer scanner actually detects a mapped-key call (so a
		 * future regression can't pass simply because the scanner is broken).
		 */
		public function test_scanner_detects_a_mapped_key_call(): void {
			$sample = "<?php \$x = lafka_get_option( 'accent_color' ); // lafka_get_option('links_color') in a comment does not count\n";
			$found  = $this->called_keys( $sample );
			$this->assertContains( 'accent_color', $found );
			$this->assertNotContains( 'links_color', $found, 'A commented mapped-key reference must NOT be flagged.' );
		}
	}
}
