<?php
/**
 * NX2-03 preset FONT POOL — curated 8-family OFL registry (pure-data).
 *
 * The preset engine's font layer mirrors the pure-data whitelist idiom of
 * incl/presets/lafka-preset-tokens.php: a single constant that BOTH the emitter
 * (lafka_preset_font_face_css() in lafka-preset-emit.php) and the disk/enqueue
 * test (tests/Unit/SelfHostedFontsTest.php) read.
 *
 * A preset's `fonts{ body, display }` block names a `family` and a `source`:
 *   - source "base" — Rubik / Fraunces, already @font-face'd in the static CSS
 *     (style.css + styles/critical.css for Rubik, styles/lafka-tokens.css +
 *     styles/editorial.css for Fraunces). The engine emits NOTHING for these,
 *     so Peppery stays byte-identical + pixel-identical.
 *   - source "pool" — one of the six added OFL families below. The engine emits
 *     the family's @font-face declarations inline (no extra HTTP request for the
 *     CSS) so ONLY the active preset's two families are ever fetched.
 *
 * Files are self-hosted under assets/fonts/<dir>/, sourced reproducibly from the
 * dev-only @fontsource/* packages via scripts/nx2-03-sync-fonts.mjs (latin +
 * latin-ext woff2, weights 400/600/700 where shipped; DM Serif Display is 400
 * only). Total added woff2 ≈ 528 KB (well under the 1.5 MB pool budget). Each
 * family carries its OFL licence on disk (Fraunces keeps its historic OFL.txt;
 * every other family ships the @fontsource LICENSE).
 *
 * @package Lafka
 * @since   7.2.0 (NX2-03)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared Google-Fonts subset unicode-ranges. Verified IDENTICAL across every
 * @fontsource family in the pool at sourcing time, so they live once here rather
 * than per-entry. The `latin` range covers ASCII + common punctuation/symbols;
 * `latin-ext` adds the accented European glyphs (the pool is sold as an OSS
 * bundle to non-English restaurants, so latin-ext is shipped too).
 */
if ( ! defined( 'LAFKA_FONT_RANGE_LATIN' ) ) {
	define(
		'LAFKA_FONT_RANGE_LATIN',
		'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD'
	);
}
if ( ! defined( 'LAFKA_FONT_RANGE_LATIN_EXT' ) ) {
	define(
		'LAFKA_FONT_RANGE_LATIN_EXT',
		'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF'
	);
}

/**
 * The curated font pool: slug => definition.
 *
 * Fields per entry:
 *   - family   : the CSS `font-family` name a preset's fonts{} block references.
 *   - source   : "base" (already in static CSS — engine emits nothing) or "pool".
 *   - dir      : sub-directory under assets/fonts/.
 *   - category : "sans" | "serif" — selects the shared fallback stack.
 *   - fallback : the full CSS font stack (family + system fallbacks), for preset
 *                authors to drop verbatim into --lafka-font-family-body/-display.
 *   - license  : the on-disk licence filename inside the family dir.
 *   - weights  : weight(int) => [ 'latin' => file, 'latin-ext' => file|null ].
 *                The base families are latin-only (no -ext), matching their
 *                historic single-file-per-weight self-hosting.
 *
 * The two BASE families are included so the registry is the single source of
 * truth for "which families are on disk" (SelfHostedFontsTest reads this), even
 * though the emitter skips them.
 */
if ( ! defined( 'LAFKA_FONT_POOL' ) ) {
	define(
		'LAFKA_FONT_POOL',
		array(

			// ---- BASE families (already self-hosted; engine emits nothing) -----
			'rubik'            => array(
				'family'   => 'Rubik',
				'source'   => 'base',
				'dir'      => 'rubik',
				'category' => 'sans',
				'fallback' => '"Rubik", system-ui, -apple-system, "Segoe UI", roboto, "Helvetica Neue", arial, sans-serif',
				'license'  => 'LICENSE',
				'weights'  => array(
					400 => array( 'latin' => 'Rubik-400.woff2' ),
					600 => array( 'latin' => 'Rubik-600.woff2' ),
					700 => array( 'latin' => 'Rubik-700.woff2' ),
				),
			),
			'fraunces'         => array(
				'family'   => 'Fraunces',
				'source'   => 'base',
				'dir'      => 'fraunces',
				'category' => 'serif',
				'fallback' => '"Fraunces", "Iowan Old Style", "Palatino Linotype", "Book Antiqua", palatino, georgia, serif',
				'license'  => 'OFL.txt',
				'weights'  => array(
					400 => array( 'latin' => 'Fraunces-400.woff2' ),
					600 => array( 'latin' => 'Fraunces-600.woff2' ),
					800 => array( 'latin' => 'Fraunces-800.woff2' ),
				),
			),

			// ---- POOL families (conditional per-preset @font-face) -------------
			'inter'            => array(
				'family'   => 'Inter',
				'source'   => 'pool',
				'dir'      => 'inter',
				'category' => 'sans',
				'fallback' => '"Inter", system-ui, -apple-system, "Segoe UI", roboto, "Helvetica Neue", arial, sans-serif',
				'license'  => 'LICENSE',
				'weights'  => array(
					400 => array(
						'latin' => 'Inter-400.woff2',
						'latin-ext' => 'Inter-400-ext.woff2',
					),
					600 => array(
						'latin' => 'Inter-600.woff2',
						'latin-ext' => 'Inter-600-ext.woff2',
					),
					700 => array(
						'latin' => 'Inter-700.woff2',
						'latin-ext' => 'Inter-700-ext.woff2',
					),
				),
			),
			'archivo'          => array(
				'family'   => 'Archivo',
				'source'   => 'pool',
				'dir'      => 'archivo',
				'category' => 'sans',
				'fallback' => '"Archivo", system-ui, -apple-system, "Segoe UI", roboto, "Helvetica Neue", arial, sans-serif',
				'license'  => 'LICENSE',
				'weights'  => array(
					400 => array(
						'latin' => 'Archivo-400.woff2',
						'latin-ext' => 'Archivo-400-ext.woff2',
					),
					600 => array(
						'latin' => 'Archivo-600.woff2',
						'latin-ext' => 'Archivo-600-ext.woff2',
					),
					700 => array(
						'latin' => 'Archivo-700.woff2',
						'latin-ext' => 'Archivo-700-ext.woff2',
					),
				),
			),
			'lora'             => array(
				'family'   => 'Lora',
				'source'   => 'pool',
				'dir'      => 'lora',
				'category' => 'serif',
				'fallback' => '"Lora", "Iowan Old Style", "Palatino Linotype", "Book Antiqua", palatino, georgia, serif',
				'license'  => 'LICENSE',
				'weights'  => array(
					400 => array(
						'latin' => 'Lora-400.woff2',
						'latin-ext' => 'Lora-400-ext.woff2',
					),
					600 => array(
						'latin' => 'Lora-600.woff2',
						'latin-ext' => 'Lora-600-ext.woff2',
					),
					700 => array(
						'latin' => 'Lora-700.woff2',
						'latin-ext' => 'Lora-700-ext.woff2',
					),
				),
			),
			'manrope'          => array(
				'family'   => 'Manrope',
				'source'   => 'pool',
				'dir'      => 'manrope',
				'category' => 'sans',
				'fallback' => '"Manrope", system-ui, -apple-system, "Segoe UI", roboto, "Helvetica Neue", arial, sans-serif',
				'license'  => 'LICENSE',
				'weights'  => array(
					400 => array(
						'latin' => 'Manrope-400.woff2',
						'latin-ext' => 'Manrope-400-ext.woff2',
					),
					600 => array(
						'latin' => 'Manrope-600.woff2',
						'latin-ext' => 'Manrope-600-ext.woff2',
					),
					700 => array(
						'latin' => 'Manrope-700.woff2',
						'latin-ext' => 'Manrope-700-ext.woff2',
					),
				),
			),
			'space-grotesk'    => array(
				'family'   => 'Space Grotesk',
				'source'   => 'pool',
				'dir'      => 'space-grotesk',
				'category' => 'sans',
				'fallback' => '"Space Grotesk", system-ui, -apple-system, "Segoe UI", roboto, "Helvetica Neue", arial, sans-serif',
				'license'  => 'LICENSE',
				'weights'  => array(
					400 => array(
						'latin' => 'SpaceGrotesk-400.woff2',
						'latin-ext' => 'SpaceGrotesk-400-ext.woff2',
					),
					600 => array(
						'latin' => 'SpaceGrotesk-600.woff2',
						'latin-ext' => 'SpaceGrotesk-600-ext.woff2',
					),
					700 => array(
						'latin' => 'SpaceGrotesk-700.woff2',
						'latin-ext' => 'SpaceGrotesk-700-ext.woff2',
					),
				),
			),
			'dm-serif-display' => array(
				'family'   => 'DM Serif Display',
				'source'   => 'pool',
				'dir'      => 'dm-serif-display',
				'category' => 'serif',
				'fallback' => '"DM Serif Display", "Iowan Old Style", "Palatino Linotype", "Book Antiqua", palatino, georgia, serif',
				'license'  => 'LICENSE',
				'weights'  => array(
					400 => array(
						'latin' => 'DMSerifDisplay-400.woff2',
						'latin-ext' => 'DMSerifDisplay-400-ext.woff2',
					),
				),
			),
		)
	);
}
