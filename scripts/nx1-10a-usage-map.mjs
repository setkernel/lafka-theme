/* lafka-theme/scripts/nx1-10a-usage-map.mjs
 *
 * NX1-10a usage-map tooling. Builds the live-selector evidence that drives the
 * monolith teardown: for every seeded surface (the 6 NX1-02 handoff pages + the
 * 4 NX1-10a legacy surfaces) it measures TWO things against the running wp-env:
 *
 *   1. MONOLITH CONTRIBUTION — disables the `lafka-style` (style.css) sheet in
 *      the live DOM and counts how many rendered elements change computed style,
 *      with the top changed properties. A handoff page that barely changes when
 *      the monolith is disabled is already effectively monolith-free (safe to
 *      stop loading it there); a legacy page that changes a lot genuinely needs
 *      the extracted legacy-*.css.
 *
 *   2. BUCKET LIVENESS — probes a set of monolith selector buckets (blog /
 *      forum / events / shortcode / widgets / chrome / product-cart / generic)
 *      via querySelectorAll and reports which buckets have DOM on each surface.
 *      Buckets with zero DOM on the handoff pages are safe to move behind a
 *      legacy-surface enqueue gate.
 *
 * Not a test/gate — a one-shot evidence generator. Run against the umbrella
 * wp-env (LAFKA_E2E_BASE_URL, default http://localhost:8890) after it is up:
 *   node scripts/nx1-10a-usage-map.mjs
 * Writes a JSON + text summary to the path in USAGE_OUT (default: stdout only).
 *
 * @since lafka-theme 6.21.0 (NX1-10a)
 */
import { chromium } from '@playwright/test';
import { createRequire } from 'node:module';
import fs from 'node:fs';

const require = createRequire( import.meta.url );
const { prepareStore, useClassicCartCheckout, SEED } = require( '../tests/e2e/support/store.js' );
const { seedBlog, blogPostPath, BLOG } = require( '../tests/e2e/support/blog.js' );
const { wpCli } = require( '../tests/e2e/support/wp-cli.js' );

const BASE = process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890';
const OUT = process.env.USAGE_OUT || '';

// DOM probes for each monolith selector bucket. A bucket is "live" on a surface
// if any probe matches ≥1 element. Deliberately over-broad so nothing live is
// missed.
const BUCKETS = {
	blog: [
		'.blog-post', '.lafka_post_data_holder', '.blog-post-meta', '.blog-post-excerpt',
		'.content_holder', '.lafka-category-posts', '.lafka-defined-excerpt', '.post-unit-holder',
		'.foodmenu-unit-info', '.lafka-related-blog-posts', '.lafka_title_holder',
	],
	comments: [
		'#comments', '.commentlist', '.comment-body', '#respond', '.comment-form',
		'.comment-author', '.comment-awaiting-moderation',
	],
	widgets: [
		'.widget', '.widget_categories', '.widget_archive', '.widget_nav_menu',
		'.widget_recent_entries', '.widget_recent_comments', '.sidebar', '.lafka_latest_projects_widget',
	],
	forum: [ '#bbpress-forums', '.bbp-forum-title', '.bbp-topic-title', '[class*="bbp-"]', '.bbp_widget_login' ],
	events: [ '#tribe-events', '.tribe-events', '[class*="tribe-events"]', '.tribe-common' ],
	shortcode: [
		'.vc_row', '[class*="vc_"]', '.wpb_wrapper', '[class*="wpb_"]', '.lafka_button',
		'.lafka_teaser', '.lafka_icon', '.lafka-countdown', '.lafka_flexslider', '.slideshow',
	],
	chrome_header: [ '#header', '.lafka-header', '#top-bar', '.top-bar', '#menu', '.main-menu', '.lafka-mega-menu', '[class*="mega-menu"]' ],
	chrome_footer: [ '#footer', '.lafka-footer', '#footer-widgets' ],
	product_cart: [ '.products', 'li.product', '.woocommerce-cart-form', '.cart_totals', '#products-wrapper', '.single_add_to_cart_button' ],
	generic: [ '#content', '#main', '.fixed', '.box', '.button', '.heading-title', 'blockquote' ],
};

// Surfaces to crawl. `prep` runs once before the surface if it needs extra state.
function surfaces() {
	return [
		{ id: 'home', kind: 'handoff', url: '/' },
		{ id: 'menu', kind: 'handoff', url: '/menu/' },
		{ id: 'pdp-simple', kind: 'handoff', url: `/product/${ SEED.simpleSlug }/` },
		{ id: 'pdp-variable', kind: 'handoff', url: `/product/${ SEED.pizzaSlug }/` },
		{ id: 'cart', kind: 'handoff', url: '/cart/', needsCart: true },
		{ id: 'checkout', kind: 'handoff', url: '/checkout/', needsCart: true },
		{ id: 'blog-index', kind: 'legacy', url: `/${ BLOG.blogPageSlug }/` },
		{ id: 'blog-category', kind: 'legacy', url: `/category/${ BLOG.categorySlug }/` },
		{ id: 'blog-search', kind: 'legacy', url: `/?s=${ encodeURIComponent( BLOG.searchTerm ) }` },
		{ id: 'single-post', kind: 'legacy', url: blogPostPath( BLOG.posts.longform ) },
		// Non-handoff, non-blog surfaces — the "not covered by any golden" risk
		// zone for legacy chrome/product/cart. Evidence only.
		{ id: 'plain-page', kind: 'other', url: '/sample-page/' },
		{ id: 'shop-archive', kind: 'other', url: '/shop/' },
		{ id: '404', kind: 'other', url: '/this-page-does-not-exist-xyz/' },
	];
}

async function seedCart( page, productId ) {
	await page.goto( BASE + '/' );
	await page.evaluate( async ( id ) => {
		const probe = await fetch( '/wp-json/wc/store/v1/cart', { headers: { Accept: 'application/json' } } );
		const nonce = probe.headers.get( 'Nonce' );
		await fetch( '/wp-json/wc/store/v1/cart/add-item', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', Nonce: nonce || '' },
			body: JSON.stringify( { id, quantity: 1 } ),
		} );
	}, productId );
}

// In-page: snapshot computed style of every element under <body>. NB:
// getComputedStyle(el).cssText returns "" in Chromium, so we enumerate the
// declaration by index and rebuild a prop→value string ourselves.
const SNAPSHOT_FN = () => {
	const els = Array.from( document.querySelectorAll( 'body *' ) );
	return els.map( ( el ) => {
		const cs = window.getComputedStyle( el );
		const parts = [];
		for ( let i = 0; i < cs.length; i++ ) {
			const p = cs[ i ];
			parts.push( p + ':' + cs.getPropertyValue( p ) );
		}
		return {
			tag: el.tagName.toLowerCase(),
			id: el.id || '',
			cls: ( el.getAttribute( 'class' ) || '' ).slice( 0, 60 ),
			css: parts.join( ';' ),
		};
	} );
};

// Props excluded from the "real" diff: the :root design tokens (kept in
// style.css) inherit into every element, and a handful of reset-inherited /
// box-derived props are pure noise for judging legacy-surface contribution.
const NOISE_PROPS = new Set( [
	'text-rendering', '-webkit-font-smoothing', '-moz-osx-font-smoothing',
	'-webkit-text-size-adjust', 'text-size-adjust', 'outline-width',
	'outline-color', 'outline-style', 'perspective-origin', 'transform-origin',
	'-webkit-locale', '-webkit-tap-highlight-color',
] );
function isNoiseProp( p ) {
	return p.startsWith( '--' ) || NOISE_PROPS.has( p );
}

function diffSnapshots( before, after ) {
	const n = Math.min( before.length, after.length );
	let changed = 0;
	const propCounts = {};
	const samples = [];
	for ( let i = 0; i < n; i++ ) {
		if ( before[ i ].css === after[ i ].css ) {
			continue;
		}
		// Parse the two prop→value blobs and diff, ignoring noise props.
		const pb = parseCss( before[ i ].css );
		const pa = parseCss( after[ i ].css );
		const props = new Set( [ ...Object.keys( pb ), ...Object.keys( pa ) ] );
		const changedProps = [];
		for ( const p of props ) {
			if ( isNoiseProp( p ) ) {
				continue;
			}
			if ( pb[ p ] !== pa[ p ] ) {
				changedProps.push( p );
				propCounts[ p ] = ( propCounts[ p ] || 0 ) + 1;
			}
		}
		if ( changedProps.length === 0 ) {
			continue; // only noise props changed on this element → ignore.
		}
		changed++;
		if ( samples.length < 40 ) {
			samples.push( {
				el: `${ before[ i ].tag }${ before[ i ].id ? '#' + before[ i ].id : '' }${ before[ i ].cls ? '.' + before[ i ].cls.replace( /\s+/g, '.' ) : '' }`,
				props: changedProps.slice( 0, 8 ),
			} );
		}
	}
	const topProps = Object.entries( propCounts ).sort( ( a, b ) => b[ 1 ] - a[ 1 ] ).slice( 0, 15 );
	return { total: n, changed, topProps, samples };
}

function parseCss( cssText ) {
	const map = {};
	for ( const decl of cssText.split( ';' ) ) {
		const idx = decl.indexOf( ':' );
		if ( idx === -1 ) {
			continue;
		}
		map[ decl.slice( 0, idx ).trim() ] = decl.slice( idx + 1 ).trim();
	}
	return map;
}

async function run() {
	console.log( `[usage-map] preparing store + blog against ${ BASE }` );
	prepareStore();
	useClassicCartCheckout();
	seedBlog();
	const productId = Number(
		wpCli( [ 'eval', `$p=get_page_by_path("${ SEED.simpleSlug }",OBJECT,"product");echo $p?(int)$p->ID:0;` ] )
	);

	const browser = await chromium.launch();
	const context = await browser.newContext();
	const page = await context.newPage();
	const report = { base: BASE, generatedAt: new Date().toISOString(), surfaces: [] };

	for ( const s of surfaces() ) {
		if ( s.needsCart ) {
			await seedCart( page, productId );
		}
		await page.goto( BASE + s.url, { waitUntil: 'networkidle' } ).catch( () => {} );

		// Confirm the monolith is actually enqueued here + capture its href.
		const monolith = await page.evaluate( () => {
			const link = document.getElementById( 'lafka-style-css' )
				|| Array.from( document.querySelectorAll( 'link[rel="stylesheet"]' ) )
					.find( ( l ) => /\/style\.css(\?|$)/.test( l.href ) );
			return link ? { present: true, href: link.href } : { present: false };
		} );

		const before = await page.evaluate( SNAPSHOT_FN );
		await page.evaluate( () => {
			const link = document.getElementById( 'lafka-style-css' )
				|| Array.from( document.querySelectorAll( 'link[rel="stylesheet"]' ) )
					.find( ( l ) => /\/style\.css(\?|$)/.test( l.href ) );
			if ( link ) {
				link.disabled = true;
			}
		} );
		// Force a style recalc.
		await page.evaluate( () => document.body.offsetHeight );
		const after = await page.evaluate( SNAPSHOT_FN );
		const diff = diffSnapshots( before, after );

		const liveBuckets = await page.evaluate( ( buckets ) => {
			const out = {};
			for ( const [ name, sels ] of Object.entries( buckets ) ) {
				let count = 0;
				for ( const sel of sels ) {
					try {
						count += document.querySelectorAll( sel ).length;
					} catch {
						count += 0; // invalid selector in this engine — skip.
					}
				}
				out[ name ] = count;
			}
			return out;
		}, BUCKETS );

		report.surfaces.push( { id: s.id, kind: s.kind, url: s.url, monolith, diff, liveBuckets } );
		console.log(
			`\n== ${ s.id } (${ s.kind }) ${ s.url }\n` +
			`   monolith: ${ monolith.present ? 'loaded' : 'ABSENT' }  |  elements: ${ diff.total }  changed-on-disable: ${ diff.changed }\n` +
			`   top changed props: ${ diff.topProps.map( ( p ) => `${ p[ 0 ] }(${ p[ 1 ] })` ).join( ', ' ) || 'none' }\n` +
			`   live buckets: ${ Object.entries( liveBuckets ).filter( ( b ) => b[ 1 ] > 0 ).map( ( b ) => `${ b[ 0 ] }=${ b[ 1 ] }` ).join( ', ' ) }`
		);
	}

	await browser.close();

	if ( OUT ) {
		fs.writeFileSync( OUT, JSON.stringify( report, null, 2 ) );
		console.log( `\n[usage-map] wrote ${ OUT }` );
	}
	return report;
}

run().catch( ( err ) => {
	console.error( err );
	process.exit( 1 );
} );
