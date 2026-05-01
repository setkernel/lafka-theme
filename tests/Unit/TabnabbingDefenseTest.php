<?php
/**
 * TabnabbingDefenseTest — locks down the v5.15.2 fix.
 *
 * <a target="_blank"> without rel="noopener noreferrer" lets the opened
 * page access window.opener and replace the original tab with a
 * phishing clone (tab-nabbing). Same defence pattern shipped in
 * lafka-plugin v9.7.20 for the share-links function.
 *
 * Source-grep based — the templates are PHP-mixed (attribute values
 * built inline via <?php echo esc_attr(...) ?>) so a regex tag-parser
 * trips on the embedded `?>`. Instead we walk lines containing
 * `target="_blank"` and assert each carries `rel="noopener noreferrer"`
 * on the same line.
 *
 * @package Lafka\Tests\Unit
 * @since   5.15.2
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TabnabbingDefenseTest extends TestCase {

	/**
	 * @dataProvider templatesProvider
	 */
	public function test_target_blank_links_carry_noopener_noreferrer( string $relative_path ): void {
		$path = dirname( __DIR__, 2 ) . '/' . $relative_path;
		$lines = file( $path, FILE_IGNORE_NEW_LINES );
		$this->assertNotFalse( $lines, "Could not read {$relative_path}" );

		$found_target_blank = 0;
		foreach ( $lines as $line_index => $line ) {
			if ( false === strpos( $line, 'target="_blank"' ) ) {
				continue;
			}
			++$found_target_blank;
			$this->assertStringContainsString(
				'rel="noopener noreferrer"',
				$line,
				sprintf(
					"%s:%d uses target=\"_blank\" without rel=\"noopener noreferrer\":\n  %s",
					$relative_path,
					$line_index + 1,
					trim( $line )
				)
			);
		}

		$this->assertGreaterThan(
			0,
			$found_target_blank,
			"Expected at least one target=\"_blank\" anchor in {$relative_path} (test fixture stale)."
		);
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public function templatesProvider(): array {
		return array(
			'social-profiles partial'   => array( 'partials/social-profiles.php' ),
			'foodmenu single template'  => array( 'single-lafka-foodmenu.php' ),
		);
	}
}
