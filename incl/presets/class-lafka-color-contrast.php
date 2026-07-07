<?php
/**
 * NX2-02 WCAG colour-contrast helper.
 *
 * A tiny, pure (WordPress-free) implementation of the WCAG 2.x relative-luminance
 * and contrast-ratio maths. No such helper existed in the repo — the theme's prior
 * contrast locks (ColorContrastTest, ContrastFixesTest, FocusRingContrastTest) only
 * assert literal hex values in CSS, they never COMPUTE a ratio. The preset engine
 * needs a real ratio so `PresetContrastTest` can gate every preset's EFFECTIVE
 * palette (base tokens (+) PTL (+) chrome) against WCAG AA. See docs/PRESET_ENGINE.md §9.
 *
 * Reference: https://www.w3.org/TR/WCAG21/#dfn-relative-luminance and #dfn-contrast-ratio.
 * Verified against a known pair: #767676 on #fff resolves to ~4.54:1 (the WCAG AA
 * boundary for normal text) — locked by PresetContrastTest::test_known_pair_ratio().
 *
 * @package Lafka
 * @since   7.1.0 (NX2-02)
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Lafka_Color_Contrast' ) ) {

	/**
	 * Stateless WCAG contrast maths. All methods are static + pure.
	 */
	class Lafka_Color_Contrast {

		/** WCAG AA minimum for normal-size text (1.4.3). */
		const AA_NORMAL = 4.5;

		/** WCAG AA minimum for large text (1.4.3) and non-text/UI contrast (1.4.11). */
		const AA_LARGE = 3.0;

		/**
		 * Parse a CSS colour into an [r, g, b] triple of 0-255 ints, or null when
		 * unparseable. Understands `#rgb`, `#rrggbb`, and `rgb()/rgba()` (any alpha
		 * is IGNORED — the colour is treated as opaque, which is correct for the
		 * solid-hex tokens the contrast gate resolves; translucent scrim/overlay
		 * tokens are never fed to it).
		 *
		 * @param string $color
		 * @return array{0:int,1:int,2:int}|null
		 */
		public static function parse( string $color ): ?array {
			$color = strtolower( trim( $color ) );

			if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $color, $m ) ) {
				$hex = $m[1];
				if ( 3 === strlen( $hex ) ) {
					$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
				}
				return array(
					(int) hexdec( substr( $hex, 0, 2 ) ),
					(int) hexdec( substr( $hex, 2, 2 ) ),
					(int) hexdec( substr( $hex, 4, 2 ) ),
				);
			}

			if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})\s*[,\s]\s*(\d{1,3})/', $color, $m ) ) {
				return array(
					min( 255, (int) $m[1] ),
					min( 255, (int) $m[2] ),
					min( 255, (int) $m[3] ),
				);
			}

			return null;
		}

		/**
		 * Linearise a single 0-1 sRGB channel per the WCAG relative-luminance
		 * definition (the 0.03928 knee).
		 *
		 * @param float $channel 0-1 sRGB value.
		 */
		private static function linearise( float $channel ): float {
			return $channel <= 0.03928
				? $channel / 12.92
				: pow( ( $channel + 0.055 ) / 1.055, 2.4 );
		}

		/**
		 * WCAG relative luminance (0.0 – 1.0) of a colour (hex/rgb string or an
		 * [r,g,b] triple).
		 *
		 * @param string|array{0:int,1:int,2:int} $color
		 * @throws \InvalidArgumentException When the colour cannot be parsed.
		 */
		public static function relative_luminance( $color ): float {
			$rgb = is_array( $color ) ? array_values( $color ) : self::parse( (string) $color );
			if ( null === $rgb || 3 !== count( $rgb ) ) {
				// Static message (no interpolated input) — this helper is
				// WordPress-free, so esc_html() is not available to satisfy the
				// output-escaping sniff, and a parse failure here is a programming
				// error (a non-colour reached the contrast maths), not user output.
				throw new \InvalidArgumentException( 'Lafka_Color_Contrast: unparseable colour value.' );
			}
			$lr = self::linearise( ( (int) $rgb[0] ) / 255 );
			$lg = self::linearise( ( (int) $rgb[1] ) / 255 );
			$lb = self::linearise( ( (int) $rgb[2] ) / 255 );
			return 0.2126 * $lr + 0.7152 * $lg + 0.0722 * $lb;
		}

		/**
		 * WCAG contrast ratio (1.0 – 21.0) between two colours. Symmetric — the
		 * lighter colour is always the numerator.
		 *
		 * @param string|array{0:int,1:int,2:int} $a
		 * @param string|array{0:int,1:int,2:int} $b
		 */
		public static function ratio( $a, $b ): float {
			$la    = self::relative_luminance( $a );
			$lb    = self::relative_luminance( $b );
			$light = max( $la, $lb );
			$dark  = min( $la, $lb );
			return ( $light + 0.05 ) / ( $dark + 0.05 );
		}

		/**
		 * True when the foreground/background pair meets (or exceeds) a minimum
		 * ratio. Comparison is on the ratio rounded to 2 decimals — the same
		 * precision WCAG tooling reports — so a value that displays as "4.50"
		 * counts as passing.
		 *
		 * @param string|array{0:int,1:int,2:int} $fg
		 * @param string|array{0:int,1:int,2:int} $bg
		 * @param float                           $min Minimum ratio (default AA normal text).
		 */
		public static function meets( $fg, $bg, float $min = self::AA_NORMAL ): bool {
			return round( self::ratio( $fg, $bg ), 2 ) >= $min;
		}
	}
}
