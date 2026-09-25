<?php
declare(strict_types=1);

namespace Lafka\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Body-flag classes must never be styled as bare element classes.
 *
 * lafka_layout_body_classes() adds flag classes to <body>:
 * `lafka-layout-{surface}-{layout}`, `lafka-motif-{motif}` and
 * `lafka-has-counter-bar`. A stylesheet rule whose selector is just one of
 * those classes (e.g. `.lafka-motif-check { display: none; }`, written for a
 * decorative element that reused the flag name) also matches <body> itself.
 * In GX4 that rule hid the whole Peppery page. Unit tests render markup, not
 * CSS, so only a real browser saw it.
 *
 * Contract: in the theme's own stylesheets, a flag class may appear only
 * qualified by `body` (`body.lafka-motif-check …`) or as the ancestor part of
 * a descendant selector. It must never be the whole compound selector, and
 * never the subject (last compound) of a selector unless it is `body.`-qualified.
 */
final class BodyFlagClassCollisionTest extends TestCase {

	/**
	 * Flag classes the theme puts on <body>: every surface × layout from
	 * lafka_layout_surfaces() / the variant whitelist, every non-"none" motif,
	 * and the counter-bar flag. Add new layout or motif values here.
	 */
	private const FLAG = '/^\.(lafka-layout-(header|home|menu|footer|drawer)-(classic|counter)|lafka-motif-(check)|lafka-has-counter-bar)$/';

	public function test_no_stylesheet_targets_a_body_flag_class_as_an_element(): void {
		$root      = dirname( __DIR__, 2 );
		$offenders = array();
		$files     = array_merge( glob( $root . '/styles/*.css' ) ?: array(), array( $root . '/style.css' ) );

		foreach ( $files as $file ) {
			if ( str_ends_with( $file, '.min.css' ) || ! is_readable( $file ) ) {
				continue;
			}
			$css = (string) file_get_contents( $file );
			$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
			// Every selector list that opens a declaration block.
			preg_match_all( '/([^{}@;]+)\{/', $css, $m );
			foreach ( $m[1] as $list ) {
				foreach ( explode( ',', $list ) as $selector ) {
					$selector = trim( $selector );
					if ( '' === $selector ) {
						continue;
					}
					// The subject compound = the last whitespace/combinator-separated part.
					$parts   = preg_split( '/\s*[>+~]\s*|\s+/', $selector );
					$subject = (string) end( $parts );
					// Strip pseudo-classes/elements and attribute selectors from the subject.
					$bare = (string) preg_replace( '/(::?[a-z-]+(\([^)]*\))?|\[[^\]]*\])/i', '', $subject );
					if ( preg_match( self::FLAG, $bare ) ) {
						$offenders[] = basename( $file ) . ': ' . $selector;
					}
				}
			}
		}

		$this->assertSame(
			array(),
			$offenders,
			"These selectors style a <body> flag class as an element (they also match <body>):\n" . implode( "\n", $offenders )
		);
	}
}
