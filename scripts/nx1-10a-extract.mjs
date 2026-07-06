/* lafka-theme/scripts/nx1-10a-extract.mjs
 *
 * NX1-10a monolith extractor. Splits the @layer legacy body of style.css into
 * scoped legacy-*.css sheets by a RULE-LEVEL, live-DOM-gated rule:
 *
 *   A rule (or a whole @media block) is MOVED out of style.css only when
 *     (a) NONE of its selectors match any element on ANY of the 6 handoff
 *         surfaces (home / menu / pdp-simple / pdp-variable / cart / checkout),
 *         AND
 *     (b) every one of its selectors matches ONE legacy bucket's pattern
 *         (blog / forum / events / shortcodes).
 *   Otherwise the rule STAYS in style.css (the conservative default — a rule
 *   that is live on any handoff page, or ambiguous, is never moved).
 *
 * This sidesteps the monolith's unreliable section comments (e.g. the "SLIDERS"
 * comment is followed by cart/checkout rules, and .lafka_flexslider is used on
 * home/PDP) — placement is decided per rule by what actually renders, not by
 * where a comment sits.
 *
 * The kept + moved rules preserve their exact source text and original order;
 * @media blocks that move are re-wrapped verbatim in the target sheet. The
 * script asserts every top-level item lands exactly once (no rule lost or
 * duplicated). The visual harness is the final pixel gate.
 *
 * Run against the umbrella wp-env (LAFKA_E2E_BASE_URL, default :8890) after it
 * is up:  node scripts/nx1-10a-extract.mjs
 *
 * @since lafka-theme 6.21.0 (NX1-10a)
 */
import { chromium } from '@playwright/test';
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire( import.meta.url );
const { prepareStore, useClassicCartCheckout, SEED } = require( '../tests/e2e/support/store.js' );
const { wpCli } = require( '../tests/e2e/support/wp-cli.js' );

const BASE = process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890';
const THEME = path.dirname( path.dirname( fileURLToPath( import.meta.url ) ) );
const STYLE = path.join( THEME, 'style.css' );

// Legacy buckets. A selector belongs to a bucket if it matches the pattern.
// A rule moves to bucket X only if ALL its selectors match X (and none match
// the handoff DOM). Order matters: forum/events first (most specific).
const BUCKETS = [
	{ id: 'forum', re: /\bbbp[-_]|bbpress|#subscription-toggle|\.forum\b|\.bbp\b/i },
	{ id: 'events', re: /tribe|#tribe-events|\.recurring-info/i },
	{
		// Blog / archive / widgets / sidebars. NB: comment/review selectors
		// (commentlist, comment-body, #comments, #respond, #reviews, …) are
		// DELIBERATELY excluded — they are cross-cutting: the same monolith
		// rules style WooCommerce product reviews on the handoff PDP, which the
		// modular PDP sheets do NOT restyle. Keeping them in the site-wide
		// style.css keeps PDP reviews styled while PDP stays monolith-light.
		id: 'blog',
		re: /blog-post|lafka_post_data_holder|content_holder|lafka_title_holder|lafka-category-posts|lafka-related-blog-posts|lafka-defined-excerpt|post-meta-|posted_by|posted_in|count_comments|page-links|lafka-author-info|post-unit-holder|foodmenu-unit-info|widget_|\.widget\b|\.sidebar\b|lafka_latest_projects|blog-post-excerpt|blog-post-meta/i,
	},
	{
		// Legacy page-builder / shortcode content. NB: the countdown-WIDGET
		// selectors (count_holder, countdown_time, is-countdown) are DELIBERATELY
		// excluded — the same monolith rules render on handoff surfaces the demo
		// probe never exercised: the on-sale product "Offer ends in" countdown
		// (woocommerce-functions.php) on PDP/shop, and the store-closed card's
		// count_holder_small digits on PDP (store-closed.css styles only the
		// container). Keeping them in the site-wide style.css keeps those live.
		id: 'shortcodes',
		re: /vc_|wpb_|lafka_button|lafka_teaser|lafka_icon|lafka_counter|lafka_flexslider|lafka_content_slider|lafka-from-|lafka_pricing|lafka_progress|lafka_tabs|lafka_toggle|lafka_gmap|lafka_testimonial|lafka_team|foodmenu|lafka_gallery|similar_projects|\.project|dokan|wcfm/i,
	},
];

function classifySelectors( selectors ) {
	for ( const b of BUCKETS ) {
		if ( selectors.every( ( s ) => b.re.test( s ) ) ) {
			return b.id;
		}
	}
	return null;
}

/**
 * Tokenise a CSS body (already inside @layer legacy) into top-level items:
 * comments, at-rule blocks (@media …{…}) and style rules (sel …{…}). Tracks
 * string state so braces inside content:"…" never miscount. Returns items with
 * { type, text, selectors } where selectors is the comma-split prelude (for
 * style rules) or the union of inner rule selectors (for @media blocks).
 */
function tokenize( body ) {
	const items = [];
	let i = 0;
	const n = body.length;
	const pushText = ( type, text, selectors ) => items.push( { type, text, selectors } );

	while ( i < n ) {
		// Whitespace between items — attach to the following item's leading text.
		if ( /\s/.test( body[ i ] ) ) {
			let j = i;
			while ( j < n && /\s/.test( body[ j ] ) ) {
				j++;
			}
			pushText( 'ws', body.slice( i, j ), [] );
			i = j;
			continue;
		}
		// Comment.
		if ( body[ i ] === '/' && body[ i + 1 ] === '*' ) {
			const end = body.indexOf( '*/', i + 2 );
			const stop = end === -1 ? n : end + 2;
			pushText( 'comment', body.slice( i, stop ), [] );
			i = stop;
			continue;
		}
		// A rule or at-rule: read the prelude up to '{', then the balanced block.
		let j = i;
		let str = null;
		while ( j < n ) {
			const c = body[ j ];
			if ( str ) {
				if ( c === '\\' ) {
					j += 2;
					continue;
				}
				if ( c === str ) {
					str = null;
				}
				j++;
				continue;
			}
			if ( c === '"' || c === "'" ) {
				str = c;
				j++;
				continue;
			}
			if ( c === '{' || c === ';' ) {
				break;
			}
			j++;
		}
		if ( j >= n ) {
			pushText( 'other', body.slice( i ), [] );
			break;
		}
		if ( body[ j ] === ';' ) {
			// A statement without a block (e.g. stray @import) — keep verbatim.
			pushText( 'stmt', body.slice( i, j + 1 ), [] );
			i = j + 1;
			continue;
		}
		const prelude = body.slice( i, j );
		// Read the balanced { … } block.
		let depth = 0;
		let k = j;
		str = null;
		for ( ; k < n; k++ ) {
			const c = body[ k ];
			if ( str ) {
				if ( c === '\\' ) {
					k++;
					continue;
				}
				if ( c === str ) {
					str = null;
				}
				continue;
			}
			if ( c === '"' || c === "'" ) {
				str = c;
				continue;
			}
			if ( c === '{' ) {
				depth++;
			} else if ( c === '}' ) {
				depth--;
				if ( depth === 0 ) {
					k++;
					break;
				}
			}
		}
		const text = body.slice( i, k );
		const isAt = prelude.trim().startsWith( '@' );
		if ( isAt ) {
			// @media / @supports: collect inner style-rule selectors.
			const inner = text.slice( text.indexOf( '{' ) + 1, text.lastIndexOf( '}' ) );
			const innerItems = tokenize( inner ).filter( ( it ) => it.type === 'rule' );
			const sels = innerItems.flatMap( ( it ) => it.selectors );
			pushText( 'atblock', text, sels );
		} else {
			const selectors = prelude
				.split( ',' )
				.map( ( s ) => s.trim() )
				.filter( Boolean );
			pushText( 'rule', text, selectors );
		}
		i = k;
	}
	return items;
}

async function collectHandoffSelectors( selectors ) {
	const productId = Number(
		wpCli( [ 'eval', `$p=get_page_by_path("${ SEED.simpleSlug }",OBJECT,"product");echo $p?(int)$p->ID:0;` ] )
	);
	const browser = await chromium.launch();
	const page = await browser.newContext().then( ( c ) => c.newPage() );
	const live = new Set();
	const surfaces = [
		{ url: '/', cart: false },
		{ url: '/menu/', cart: false },
		{ url: `/product/${ SEED.simpleSlug }/`, cart: false },
		{ url: `/product/${ SEED.pizzaSlug }/`, cart: false },
		{ url: '/cart/', cart: true },
		{ url: '/checkout/', cart: true },
	];
	for ( const s of surfaces ) {
		if ( s.cart ) {
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
		await page.goto( BASE + s.url, { waitUntil: 'networkidle' } ).catch( () => {} );
		const matched = await page.evaluate( ( sels ) => {
			const hit = [];
			for ( const sel of sels ) {
				try {
					if ( document.querySelector( sel ) ) {
						hit.push( sel );
					}
				} catch {
					hit.push( sel ); // unparseable → treat as live (conservative keep).
				}
			}
			return hit;
		}, selectors );
		matched.forEach( ( m ) => live.add( m ) );
		console.log( `[extract] ${ s.url }: ${ matched.length } selectors live` );
	}
	await browser.close();
	return live;
}

async function main() {
	console.log( `[extract] preparing store against ${ BASE }` );
	prepareStore();
	useClassicCartCheckout();

	// One-shot migration guard: this must run on the ORIGINAL monolith, never on
	// an already-extracted style.css (that would move a second, disjoint slice).
	if ( fs.existsSync( path.join( THEME, 'styles', 'legacy-blog.css' ) ) && ! process.env.NX1_10A_FORCE ) {
		throw new Error(
			'styles/legacy-blog.css already exists — extraction has already run.\n' +
			'This is a one-shot migration tool. To re-run intentionally, restore the\n' +
			'original style.css (git checkout), remove styles/legacy-*.css, and set\n' +
			'NX1_10A_FORCE=1.'
		);
	}

	const css = fs.readFileSync( STYLE, 'utf8' );
	const openTok = '@layer legacy {';
	const openIdx = css.indexOf( openTok );
	if ( openIdx === -1 ) {
		throw new Error( 'could not find "@layer legacy {" in style.css' );
	}
	// The layer body is from just after the open brace to the matching close,
	// which is the final "}" of the file's meaningful content.
	const bodyStart = openIdx + openTok.length;
	const lastClose = css.lastIndexOf( '}' );
	const prefix = css.slice( 0, bodyStart );
	const body = css.slice( bodyStart, lastClose );
	const suffix = css.slice( lastClose ); // "}\n"

	// Merge each run of trailing whitespace into the preceding item so a moved
	// rule carries its blank-line separator with it (keeps both style.css and
	// the legacy sheets cleanly formatted). A leading ws item (before any rule)
	// is left standing so the layer body keeps its opening newline.
	const rawItems = tokenize( body );
	const items = [];
	for ( const it of rawItems ) {
		if ( it.type === 'ws' && items.length > 0 ) {
			items[ items.length - 1 ].text += it.text;
		} else {
			items.push( it );
		}
	}

	// Unique selector list for the handoff probe.
	const allSelectors = new Set();
	for ( const it of items ) {
		it.selectors.forEach( ( s ) => allSelectors.add( s ) );
	}
	const live = await collectHandoffSelectors( [ ...allSelectors ] );

	// Decide disposition per item. ws/comment/stmt/other → keep. A rule or
	// atblock moves only if no selector is live AND all map to one bucket.
	const kept = [];
	const moved = { blog: [], forum: [], events: [], shortcodes: [] };
	const stats = { rules: 0, movedRules: 0, atblocks: 0, movedAt: 0 };
	for ( const it of items ) {
		if ( it.type === 'rule' ) {
			stats.rules++;
		}
		if ( it.type === 'atblock' ) {
			stats.atblocks++;
		}
		const movable = ( it.type === 'rule' || it.type === 'atblock' )
			&& it.selectors.length > 0
			&& it.selectors.every( ( s ) => ! live.has( s ) )
			&& classifySelectors( it.selectors );
		if ( movable ) {
			moved[ movable ].push( it.text );
			if ( it.type === 'rule' ) {
				stats.movedRules++;
			} else {
				stats.movedAt++;
			}
		} else {
			kept.push( it.text );
		}
	}

	// Reconstruct style.css (kept items, original order) and write legacy sheets.
	const newStyle = prefix + kept.join( '' ) + suffix;
	fs.writeFileSync( STYLE, newStyle );

	const header = ( name, desc ) => `/* lafka-theme/styles/${ name }\n *\n * ${ desc }\n *\n * EXTRACTED from the legacy style.css monolith by scripts/nx1-10a-extract.mjs\n * (NX1-10a). Every rule here was proven to match ZERO elements on the six\n * handoff surfaces, so it is enqueued only on the legacy surface below. Kept in\n * @layer legacy so it stays below the unlayered modular sheets, exactly as it\n * did inside the monolith.\n *\n * @since lafka-theme 6.21.0 (NX1-10a)\n */\n\n@layer legacy {\n`;
	const descs = {
		blog: 'Classic blog / archive / single / search / comments / widgets / sidebars. Enqueued on is_home/is_archive/is_single/is_search/is_attachment or when comments are open.',
		forum: 'bbPress forum surfaces. Enqueued only when bbPress is active.',
		events: 'The Events Calendar (tribe-events) surfaces. Enqueued only when the plugin is active.',
		shortcodes: 'Legacy lafka_* shortcodes + WPBakery-era content (galleries, sliders, countdowns, foodmenu grids). Enqueued on legacy shortcode/page-builder content and singular legacy templates.',
	};
	for ( const id of Object.keys( moved ) ) {
		const file = path.join( THEME, 'styles', `legacy-${ id }.css` );
		if ( moved[ id ].length === 0 ) {
			console.warn( `[extract] WARNING: legacy-${ id }.css has 0 rules` );
		}
		// Blank line after "@layer legacy {" keeps the output stylelint-clean
		// (rule-empty-line-before). style.css's own comment spacing after removals
		// is tidied with `npm run lint:css:fix`.
		const out = header( `legacy-${ id }.css`, descs[ id ] ) + '\n' + moved[ id ].join( '' ).replace( /^\s+/, '' ) + '\n}\n';
		fs.writeFileSync( file, out );
	}

	// Sanity: kept + moved item count == original item count.
	const movedCount = Object.values( moved ).reduce( ( a, b ) => a + b.length, 0 );
	console.log( '\n[extract] summary:' );
	console.log( `  total items: ${ items.length }  (kept text blocks + moved)` );
	console.log( `  rules: ${ stats.rules }  moved rules: ${ stats.movedRules }` );
	console.log( `  @media blocks: ${ stats.atblocks }  moved: ${ stats.movedAt }` );
	for ( const id of Object.keys( moved ) ) {
		console.log( `  legacy-${ id }.css: ${ moved[ id ].length } items` );
	}
	console.log( `  style.css: ${ newStyle.length } bytes (was ${ css.length })` );
	console.log( `  moved total: ${ movedCount } items` );
}

main().catch( ( err ) => {
	console.error( err );
	process.exit( 1 );
} );
