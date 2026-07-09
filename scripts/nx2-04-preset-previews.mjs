#!/usr/bin/env node
/**
 * NX2-04 — generate presets/<slug>/preview.jpg for the Customizer switcher.
 *
 * For each discovered preset: point lafka_active_preset at it (wp-cli into
 * the umbrella wp-env), bust the dynamic-css cache, screenshot the home page
 * hero region at 1240×930 and save a 620-wide JPEG. Restores the theme_mod
 * and cache when done — run against http://localhost:8890 only.
 *
 * Usage: npm run previews:presets [-- --only=ember,koyo]
 */
import { chromium } from '@playwright/test';
import { readdirSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire( import.meta.url );
const { wpCli, bustDynamicCss } = require( '../tests/e2e/support/wp-cli.js' );

const ROOT = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const BASE_URL = process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890';

const onlyArg = process.argv.find( ( a ) => a.startsWith( '--only=' ) );
const only = onlyArg ? onlyArg.slice( 7 ).split( ',' ) : null;

const slugs = readdirSync( join( ROOT, 'presets' ), { withFileTypes: true } )
	.filter( ( d ) => d.isDirectory() && ! d.name.startsWith( '__' ) )
	.map( ( d ) => d.name )
	.filter( ( slug ) => existsSync( join( ROOT, 'presets', slug, 'preset.json' ) ) )
	.filter( ( slug ) => ! only || only.includes( slug ) );

const browser = await chromium.launch();
// Lay the page out at 1240 CSS px (desktop) but emit 620 device px so the
// committed JPEG is the 620-wide 4:3 thumbnail the Customizer control expects.
const page = await browser.newPage( {
	viewport: { width: 1240, height: 930 },
	deviceScaleFactor: 0.5,
} );

try {
	for ( const slug of slugs ) {
		wpCli( [ 'eval', `set_theme_mod("lafka_active_preset","${ slug }");` ] );
		bustDynamicCss();
		await page.goto( `${ BASE_URL }/`, { waitUntil: 'networkidle' } );
		// Fonts finish after networkidle occasionally; settle briefly.
		await page.evaluate( () => document.fonts.ready );
		const out = join( ROOT, 'presets', slug, 'preview.jpg' );
		await page.screenshot( {
			path: out,
			type: 'jpeg',
			quality: 80,
			clip: { x: 0, y: 0, width: 1240, height: 930 },
			scale: 'device',
		} );
		console.log( `✓ ${ slug } -> presets/${ slug }/preview.jpg` );
	}
} finally {
	wpCli( [ 'eval', 'remove_theme_mod("lafka_active_preset");' ] );
	bustDynamicCss();
	await browser.close();
}
console.log( `previews:presets: ${ slugs.length } thumbnail(s) written.` );
