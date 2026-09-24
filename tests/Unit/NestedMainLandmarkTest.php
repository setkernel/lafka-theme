<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * One main landmark per page.
 *
 * header.php opens <main id="content" tabindex="-1"> (the skip-link target) and
 * footer.php closes it, so every template renders inside it. Any other template
 * that opens a <main> or sets role="main" nests a second main landmark, which
 * screen readers announce as two "main" regions.
 */
final class NestedMainLandmarkTest extends TestCase {

	public function test_only_header_opens_a_main_landmark(): void {
		$root      = dirname( __DIR__, 2 );
		$offenders = array();
		$dirs      = array( '', '/page_templates', '/partials', '/template-parts', '/woocommerce', '/tribe-events' );
		foreach ( $dirs as $dir ) {
			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root . $dir, \FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $file ) {
				$path = $file->getPathname();
				if ( 'php' !== $file->getExtension() || ( '' === $dir && dirname( $path ) !== $root ) ) {
					continue;
				}
				$code = $this->markup_only( (string) file_get_contents( $path ) );
				if ( preg_match( '/<main[\s>]|role=["\']main["\']/', $code ) ) {
					$offenders[] = substr( $path, strlen( $root ) + 1 );
				}
			}
		}
		$this->assertSame( array( 'header.php' ), $offenders, 'Only header.php may open the <main> landmark.' );
	}

	public function test_header_main_is_the_skip_link_target(): void {
		$header = (string) file_get_contents( dirname( __DIR__, 2 ) . '/header.php' );
		$this->assertStringContainsString( 'href="#content"', $header );
		$this->assertSame( 1, preg_match_all( '/<main id="content" tabindex="-1">/', $header ) );
	}

	/**
	 * Strip PHP comments so prose that mentions <main> in a docblock is ignored.
	 */
	private function markup_only( string $source ): string {
		$out = '';
		foreach ( token_get_all( $source ) as $token ) {
			if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$out .= is_array( $token ) ? $token[1] : $token;
		}
		return $out;
	}
}
