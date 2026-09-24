#!/usr/bin/env node
/**
 * NX2-03 — reproducible OFL font-pool sourcing.
 *
 * Copies the LATIN + LATIN-EXT woff2 subsets (weights 400/600/700, whatever each
 * family ships) plus the OFL LICENSE for the six *pool* families out of the
 * dev-only @fontsource/* packages into assets/fonts/<dir>/, renamed to the
 * repo's existing `<Prefix>-<weight>[.-ext].woff2` idiom (matches Rubik-400.woff2
 * / Fraunces-600.woff2). Also drops the LICENSE for the already-self-hosted base
 * family Rubik (Fraunces already carries OFL.txt) so all eight pool families have
 * an on-disk licence.
 *
 * Rubik + Fraunces woff2 are LEFT UNTOUCHED (they are `source:"base"`, already
 * self-hosted, and any byte change would break the Peppery goldens).
 *
 * Idempotent: re-running overwrites the copied files with identical bytes.
 * Run:  node scripts/nx2-03-sync-fonts.mjs
 */

import { mkdirSync, copyFileSync, existsSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const ROOT = dirname( dirname( fileURLToPath( import.meta.url ) ) );
const SRC = join( ROOT, 'node_modules', '@fontsource' );
const DEST = join( ROOT, 'assets', 'fonts' );

// Pool families to source in full (latin + latin-ext, all listed weights).
const POOL = [
	{ pkg: 'inter', dir: 'inter', prefix: 'Inter', weights: [ 400, 600, 700 ], ext: true },
	{ pkg: 'archivo', dir: 'archivo', prefix: 'Archivo', weights: [ 400, 600, 700 ], ext: true },
	{ pkg: 'lora', dir: 'lora', prefix: 'Lora', weights: [ 400, 600, 700 ], ext: true },
	{ pkg: 'manrope', dir: 'manrope', prefix: 'Manrope', weights: [ 400, 600, 700 ], ext: true },
	{ pkg: 'space-grotesk', dir: 'space-grotesk', prefix: 'SpaceGrotesk', weights: [ 400, 600, 700 ], ext: true },
	{ pkg: 'dm-serif-display', dir: 'dm-serif-display', prefix: 'DMSerifDisplay', weights: [ 400 ], ext: true },
];

let added = 0;

function copy( from, to ) {
	copyFileSync( from, to );
	added += statSync( to ).size;
	console.log( `  ${ to.replace( ROOT + '/', '' ) }  (${ statSync( to ).size } B)` );
}

for ( const f of POOL ) {
	const destDir = join( DEST, f.dir );
	mkdirSync( destDir, { recursive: true } );
	const filesDir = join( SRC, f.pkg, 'files' );

	for ( const w of f.weights ) {
		const latin = join( filesDir, `${ f.pkg }-latin-${ w }-normal.woff2` );
		if ( ! existsSync( latin ) ) {
			throw new Error( `missing ${ latin } — run: npm i -D @fontsource/${ f.pkg }` );
		}
		copy( latin, join( destDir, `${ f.prefix }-${ w }.woff2` ) );

		if ( f.ext ) {
			const ext = join( filesDir, `${ f.pkg }-latin-ext-${ w }-normal.woff2` );
			if ( existsSync( ext ) ) {
				copy( ext, join( destDir, `${ f.prefix }-${ w }-ext.woff2` ) );
			}
		}
	}

	copy( join( SRC, f.pkg, 'LICENSE' ), join( destDir, 'LICENSE' ) );
}

// Base family Rubik: licence only (woff2 already self-hosted, do NOT touch).
copy( join( SRC, 'rubik', 'LICENSE' ), join( DEST, 'rubik', 'LICENSE' ) );

console.log( `\nTotal added/overwritten: ${ added } bytes (${ ( added / 1024 ).toFixed( 1 ) } KB).` );
