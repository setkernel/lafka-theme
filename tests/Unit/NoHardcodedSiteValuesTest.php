<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * lafka-theme is public OSS: operator values (brand colours, business name,
 * address) come from the Customizer, filters or --lafka-* tokens, never from
 * literals in the shipped CSS.
 */
final class NoHardcodedSiteValuesTest extends TestCase {

	/** The reference restaurant's retired brand red; colours come from tokens. */
	private const BANNED = array( '#c62828' );

	public function test_first_party_stylesheets_carry_no_operator_brand_literals(): void {
		$root  = dirname( __DIR__, 2 );
		$files = array_merge( array( $root . '/style.css' ), glob( $root . '/styles/*.css' ) ?: array() );
		$hits  = array();
		foreach ( $files as $file ) {
			if ( str_ends_with( $file, '.min.css' ) ) {
				continue;
			}
			$css = strtolower( (string) file_get_contents( $file ) );
			foreach ( self::BANNED as $literal ) {
				if ( str_contains( $css, $literal ) ) {
					$hits[] = basename( $file ) . ': ' . $literal;
				}
			}
		}
		$this->assertSame( array(), $hits );
	}
}
