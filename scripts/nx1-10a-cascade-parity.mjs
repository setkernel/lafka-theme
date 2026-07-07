/* lafka-theme/scripts/nx1-10a-cascade-parity.mjs
 *
 * NX1-10a cascade-parity verifier. Proves that splitting the monolith into
 * style.css + styles/legacy-*.css preserved the monolith's EFFECTIVE cascade:
 * for every (media-context, exact-selector, property) triple, the winning
 * declaration resolved over the split sheets (style.css first, then the legacy
 * sheets in their enqueue order) must equal the winner resolved over the
 * git-recovered monolith (964f19a:style.css).
 *
 * This is the rerunnable half of the verifier's empirical probe. It exits
 * non-zero on ANY mismatch, so it doubles as a CI-style gate. The monolith is
 * read from git (no working-tree copy needed):
 *
 *   node scripts/nx1-10a-cascade-parity.mjs
 *   node scripts/nx1-10a-cascade-parity.mjs --monolith path/to/monolith.css
 *
 * The 11 flips the verifier confirmed are asserted by name below; the sweep is
 * exhaustive, so a 12th latent flip would also fail the run.
 *
 * @since lafka-theme 6.21.0 (NX1-10a)
 */
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import {
	extractRecords,
	resolveWinners,
	readFile,
} from './nx1-10a-css-lib.mjs';

const THEME = path.dirname( path.dirname( fileURLToPath( import.meta.url ) ) );

// The legacy sheets in ENQUEUE order (incl/system/core-functions.php): blog,
// shortcodes, forum, events. That is the source order the split cascade sees
// after style.css.
const LEGACY_SHEETS = [ 'legacy-blog', 'legacy-shortcodes', 'legacy-forum', 'legacy-events' ];

// The verifier's 11 confirmed flips: (selector, property) that must resolve to
// the monolith winner after the fix. Asserted explicitly so a regression on any
// one is named, not just counted.
const CONFIRMED_FLIPS = [
	// [ exact-normalised-selector, property, verifier's shorthand ]
	[ '.tribe-events-schedule .tribe-events-cost', 'background-color', 'events cost badge (#fafafa on near-white — functional regression)' ],
	[ '.blog-post-meta.post-meta-top .count_comments a', 'background-color', 'comment-count badge' ],
	[ 'div.widget_categories ul li a:hover', 'color', 'widget_categories link hover' ],
	[ 'div.widget_archive ul li a:hover', 'color', 'widget_archive link hover' ],
	[ '.lafka-foodmenu-categories ul li a.is-checked::before', 'background-color', 'foodmenu active category marker' ],
	[ '.lafka_content_slider.lafka_content_slider_light_nav .owl-nav .owl-next', 'color', 'content_slider light-nav owl next arrow' ],
	[ '.lafka_content_slider.lafka_content_slider_light_nav .owl-nav .owl-prev', 'color', 'content_slider light-nav owl prev arrow' ],
	[ '.foodmenu-unit-info a.foodmenu-lightbox-link', 'background-color', 'foodmenu lightbox link' ],
	[ '.blog-post-meta span.sticky_post', 'background-color', 'sticky-post badge' ],
	[ '.lafka-related-blog-posts > h4', 'margin-bottom', 'related-posts heading margin (80px->30px)' ],
	[ '.lafka-related-blog-posts div.post.blog-post.lafka-post-no-image .lafka_post_data_holder h2.heading-title::before', 'color', 'related-posts no-image heading marker' ],
];

function getMonolith( ref = '964f19a:style.css' ) {
	const arg = process.argv.indexOf( '--monolith' );
	if ( arg !== -1 && process.argv[ arg + 1 ] ) {
		return readFile( process.argv[ arg + 1 ] );
	}
	return execFileSync( 'git', [ 'show', ref ], { cwd: THEME, maxBuffer: 64 * 1024 * 1024 } ).toString( 'utf8' );
}

export function computeParity() {
	const monolithCss = getMonolith();
	const mono = extractRecords( monolithCss, 'monolith', 0 );
	const monoWinners = resolveWinners( mono.records );

	// Split: style.css first, then legacy sheets in enqueue order.
	let order = 0;
	const splitRecords = [];
	const style = extractRecords( readFile( path.join( THEME, 'style.css' ) ), 'style', order );
	splitRecords.push( ...style.records );
	order = style.nextOrder;
	for ( const sheet of LEGACY_SHEETS ) {
		const r = extractRecords( readFile( path.join( THEME, 'styles', `${ sheet }.css` ) ), sheet, order );
		splitRecords.push( ...r.records );
		order = r.nextOrder;
	}
	const splitWinners = resolveWinners( splitRecords );

	const mismatches = [];
	const missing = [];
	for ( const [ k, mw ] of monoWinners ) {
		const sw = splitWinners.get( k );
		if ( ! sw ) {
			missing.push( { key: k, mono: mw } );
			continue;
		}
		if ( sw.value !== mw.value || sw.important !== mw.important ) {
			mismatches.push( { key: k, mono: mw, split: sw } );
		}
	}
	return { monoWinners, splitWinners, mismatches, missing };
}

function fmt( k ) {
	const [ media, selector, property ] = k.split( '||' );
	return ( media ? `@media ${ media } ` : '' ) + `{ ${ selector } } ${ property }`;
}

function main() {
	const { monoWinners, splitWinners, mismatches, missing } = computeParity();
	console.log( `[parity] monolith keys: ${ monoWinners.size }  split keys: ${ splitWinners.size }` );

	// Named check of the 11 confirmed flips.
	let namedFail = 0;
	console.log( '\n[parity] the verifier\'s 11 confirmed flips:' );
	for ( const [ sel, prop, label ] of CONFIRMED_FLIPS ) {
		const k = '||' + sel + '||' + prop;
		const mw = monoWinners.get( k );
		const sw = splitWinners.get( k );
		const ok = mw && sw && sw.value === mw.value && sw.important === mw.important;
		if ( ! ok ) {
			namedFail++;
		}
		console.log(
			`  ${ ok ? 'OK  ' : 'FAIL' }  ${ label }\n         ${ sel } { ${ prop } }  ` +
			`mono=${ mw ? mw.value + ( mw.important ? ' !important' : '' ) : '(none)' }  ` +
			`split=${ sw ? sw.value + ( sw.important ? ' !important' : '' ) : '(none)' }`
		);
	}

	if ( missing.length ) {
		console.log( `\n[parity] ${ missing.length } key(s) present in monolith but MISSING from split (a winner was deleted!):` );
		for ( const m of missing.slice( 0, 40 ) ) {
			console.log( `  ${ fmt( m.key ) }  mono=${ m.mono.value }` );
		}
	}
	if ( mismatches.length ) {
		console.log( `\n[parity] ${ mismatches.length } winner mismatch(es):` );
		for ( const m of mismatches.slice( 0, 80 ) ) {
			console.log(
				`  ${ fmt( m.key ) }\n      mono : ${ m.mono.value }${ m.mono.important ? ' !important' : '' } (order ${ m.mono.order }, ${ m.mono.source })` +
				`\n      split: ${ m.split.value }${ m.split.important ? ' !important' : '' } (order ${ m.split.order }, ${ m.split.source })`
			);
		}
	}

	const bad = mismatches.length + missing.length + namedFail;
	if ( bad === 0 ) {
		console.log( '\n[parity] PASS — split cascade matches the monolith for every (media,selector,property).' );
		process.exit( 0 );
	}
	console.log( `\n[parity] FAIL — ${ mismatches.length } mismatch(es), ${ missing.length } missing, ${ namedFail } named-flip failure(s).` );
	process.exit( 1 );
}

if ( import.meta.url === `file://${ process.argv[ 1 ] }` ) {
	main();
}
