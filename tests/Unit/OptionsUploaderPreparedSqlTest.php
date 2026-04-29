<?php
/**
 * C-9: Unprepared SQL in options-framework media uploader.
 *
 * The pre-fix query interpolated $_args values directly into a SQL string with
 * double-quoted concatenation:
 *
 *     $query .= ' AND ' . $k . ' = "' . $v . '"';
 *
 * Even though upstream callers tokenize the inputs, the audit asks for the
 * fix to be expressed as `$wpdb->prepare()` with `%s` placeholders, both for
 * clarity and as a future-proofing measure if a refactor ever introduces an
 * untrusted code path.
 *
 * Source-grep lock: assert `$wpdb->prepare(` is used inside the function.
 *
 * @package Lafka\Theme\Tests\Unit
 */

declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OptionsUploaderPreparedSqlTest extends TestCase {

	private function source(): string {
		$path = __DIR__ . '/../../incl/lafka-options-framework/lafka-options-medialibrary-uploader.php';
		$this->assertFileExists( $path );

		return file_get_contents( $path );
	}

	public function test_uses_wpdb_prepare(): void {
		$src = $this->source();

		$this->assertStringContainsString(
			'$wpdb->prepare(',
			$src,
			'Options uploader query must use $wpdb->prepare() instead of string concatenation'
		);
	}

	public function test_uses_placeholder_for_string_values(): void {
		$src = $this->source();

		$this->assertStringContainsString(
			'%s',
			$src,
			'Prepared statement must use %s placeholders for string columns'
		);
	}

	public function test_no_legacy_double_quote_concat_in_query(): void {
		$src = $this->source();

		// The dangerous shape: `$query .= ' AND ' . $k . ' = "' . $v . '"';`
		// Must no longer return.
		$this->assertDoesNotMatchRegularExpression(
			'/\$query\s*\.=\s*\x27 AND \x27\s*\.\s*\$k\s*\.\s*\x27 = "\x27\s*\.\s*\$v/',
			$src,
			'Legacy unprepared concatenation of $k/$v into SQL must not return'
		);
	}

	public function test_column_allowlist_enforced(): void {
		$src = $this->source();

		// Implementation maintains an explicit allowlist of $_args keys, since
		// $wpdb->prepare() can't placeholder a column name.
		$this->assertStringContainsString( "_allowed_columns", $src );
		$this->assertStringContainsString( "'post_type'", $src );
		$this->assertStringContainsString( "'post_name'", $src );
	}
}
