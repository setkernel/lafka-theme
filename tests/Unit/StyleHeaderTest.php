<?php
/**
 * Smoke test that locks in the style.css header block.
 *
 * Catches accidental version drift, removed Author/Text Domain lines, and
 * unparseable header blocks. Replaces the historical "open the ZIP and eyeball
 * the version" release-checklist item.
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StyleHeaderTest extends TestCase {

	private const STYLE_PATH = __DIR__ . '/../../style.css';

	public function test_style_css_exists(): void {
		self::assertFileExists( self::STYLE_PATH );
	}

	public function test_required_headers_are_present(): void {
		$header_block = $this->read_header_block();

		self::assertMatchesRegularExpression( '/Theme Name:\s*Lafka\s*$/m', $header_block );
		self::assertMatchesRegularExpression( '/Version:\s*\d+\.\d+\.\d+\s*$/m', $header_block );
		self::assertMatchesRegularExpression( '/Text Domain:\s*lafka\s*$/m', $header_block );
		self::assertMatchesRegularExpression( '/License:\s*GNU General Public License/m', $header_block );
	}

	private function read_header_block(): string {
		$contents = file_get_contents( self::STYLE_PATH );
		self::assertNotFalse( $contents, 'style.css unreadable' );

		// WordPress only parses the first 8 KiB of theme metadata.
		return substr( $contents, 0, 8192 );
	}
}
