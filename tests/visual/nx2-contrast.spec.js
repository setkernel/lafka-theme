/* lafka-theme/tests/visual/nx2-contrast.spec.js
 *
 * ============================================================================
 * NX2-07 RENDERED TEXT-CONTRAST GATE — README FOR AGENTS (read before editing)
 * ============================================================================
 * A screenshot golden can BAKE a broken state: if a dark preset ships text
 * that is dark-on-dark, `--update-snapshots` happily records the unreadable
 * pixels as "correct" and the gate goes green forever. That is exactly how the
 * first NX2-07 dark goldens encoded a batch of dark-on-dark text/CTA inversions.
 *
 * This gate CANNOT bake that bug. It activates the dark preset (midnight), loads
 * home + menu + a PDP + the cart, and for a CURATED list of critical selectors
 * computes the *rendered* WCAG contrast ratio from getComputedStyle (text colour
 * over its first opaque ancestor background, alpha-composited) and FAILS if any
 * pair falls under AA (>= 4.5 for body text, >= 3.0 for large text / UI such as
 * primary CTAs). There is no snapshot to update — the only way to make it green
 * is to make the text actually readable.
 *
 * The curated list is the six inversions NX2-07's adversarial verify caught plus
 * the load-bearing surrounding text (hero heading, header nav, footer, card
 * price, section headings, primary CTAs) so a future preset regression is caught
 * where a customer would actually read.
 *
 * ISOLATION: run by its OWN config (playwright.contrast.config.js,
 * `npm run test:contrast`) and testIgnore'd by playwright.visual.config.js, so
 * the 30 Peppery goldens never trip over its midnight activation and vice-versa.
 * Like nx2-dark it toggles lafka_active_preset -> midnight in beforeAll and
 * REMOVES it in afterAll (leaving it on would poison a later Peppery run).
 *
 * @since lafka-theme 7.1.0 (NX2-07 dark text/CTA inversion remediation)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED, useClassicCartCheckout } = require( '../e2e/support/store' );
const { wpCli, bustDynamicCss } = require( '../e2e/support/wp-cli' );
const { stabilize } = require( './support/capture' );

/**
 * In-page WCAG contrast probe. Returns, per selector, the computed text colour,
 * the alpha-composited effective background (first opaque ancestor over white),
 * and the WCAG 2.x contrast ratio. Kept as a plain function so it serialises
 * into page.evaluate. Handles rgb()/rgba() and color(srgb …) (what color-mix()
 * resolves to) and composites translucent text over its background.
 *
 * @param {Array<{sel:string,min:number,label:string}>} entries
 * @return {Array<object>}
 */
function probeContrast( entries ) {
	function parse( c ) {
		if ( ! c ) {
			return null;
		}
		c = c.trim();
		let m = c.match( /rgba?\(([^)]+)\)/ );
		if ( m ) {
			const p = m[ 1 ].split( /[ ,/]+/ ).map( Number );
			return { r: p[ 0 ], g: p[ 1 ], b: p[ 2 ], a: p[ 3 ] === undefined ? 1 : p[ 3 ] };
		}
		m = c.match( /color\(srgb\s+([^)]+)\)/ );
		if ( m ) {
			const p = m[ 1 ].split( /[ /]+/ ).map( Number );
			return { r: p[ 0 ] * 255, g: p[ 1 ] * 255, b: p[ 2 ] * 255, a: p[ 3 ] === undefined ? 1 : p[ 3 ] };
		}
		return null;
	}
	function over( fg, bg ) {
		const a = fg.a;
		return {
			r: fg.r * a + bg.r * ( 1 - a ),
			g: fg.g * a + bg.g * ( 1 - a ),
			b: fg.b * a + bg.b * ( 1 - a ),
			a: 1,
		};
	}
	function effBg( el ) {
		const stack = [];
		let node = el;
		while ( node && node.nodeType === 1 ) {
			stack.push( node );
			node = node.parentElement;
		}
		let base = { r: 255, g: 255, b: 255, a: 1 };
		for ( let i = stack.length - 1; i >= 0; i-- ) {
			const bc = parse( getComputedStyle( stack[ i ] ).backgroundColor );
			if ( bc && bc.a > 0 ) {
				base = over( bc, base );
			}
		}
		return base;
	}
	function lum( c ) {
		const f = ( x ) => {
			x /= 255;
			return x <= 0.03928 ? x / 12.92 : Math.pow( ( x + 0.055 ) / 1.055, 2.4 );
		};
		return 0.2126 * f( c.r ) + 0.7152 * f( c.g ) + 0.0722 * f( c.b );
	}
	function ratio( fg, bg ) {
		const a = lum( fg );
		const b = lum( bg );
		const hi = Math.max( a, b );
		const lo = Math.min( a, b );
		return ( hi + 0.05 ) / ( lo + 0.05 );
	}
	return entries.map( ( e ) => {
		const el = document.querySelector( e.sel );
		if ( ! el ) {
			return { ...e, found: false };
		}
		const cs = getComputedStyle( el );
		const fg = parse( cs.color );
		const bg = effBg( el );
		const fgc = fg && fg.a < 1 ? over( fg, bg ) : fg;
		return {
			...e,
			found: true,
			color: cs.color,
			effbg: `rgb(${ Math.round( bg.r ) },${ Math.round( bg.g ) },${ Math.round( bg.b ) })`,
			ratio: fgc ? +ratio( fgc, bg ).toFixed( 2 ) : null,
		};
	} );
}

/**
 * Evaluate the probe on the current page and assert every entry is present and
 * meets its minimum ratio. A missing curated selector fails loudly (a page-
 * structure regression is as much a signal as a contrast one).
 *
 * @param {import('@playwright/test').Page} page
 * @param {Array<{sel:string,min:number,label:string}>} entries
 */
async function assertContrast( page, entries ) {
	// Settle first: several styled surfaces (e.g. the announce bar) load their
	// CSS DEFERRED (non-critical), and a WordPress global-styles fallback
	// (`a:where(){color:var(--wp--preset--color--accent)}`, a fixed red) paints
	// links until it applies. Probing before the deferred stylesheet lands reads
	// that fallback instead of the theme's colour — a false failure. stabilize()
	// waits for fonts + a full-height scroll + network idle so every stylesheet
	// has applied (same settle the goldens use).
	await stabilize( page );
	const results = await page.evaluate( probeContrast, entries );
	for ( const r of results ) {
		expect( r.found, `[${ r.label }] selector not found: ${ r.sel }` ).toBeTruthy();
		expect(
			r.ratio,
			`[${ r.label }] ${ r.sel } — ${ r.color } on ${ r.effbg } = ${ r.ratio }:1 (needs >= ${ r.min }:1)`
		).toBeGreaterThanOrEqual( r.min );
	}
}

// AA thresholds: 4.5 for normal text, 3.0 for large text / UI (primary CTAs).
const T = { text: 4.5, ui: 3.0 };

// Serial: the active-preset theme_mod + checkout mode are global store state.
test.describe.configure( { mode: 'serial' } );

test.describe( 'NX2-07 midnight rendered text-contrast (>= AA)', () => {
	test.beforeAll( () => {
		wpCli( [ 'eval', 'set_theme_mod("lafka_active_preset","midnight");' ] );
		bustDynamicCss();
	} );

	test.afterAll( () => {
		// MANDATORY: restore the Peppery default so a later Peppery gate is clean.
		wpCli( [ 'eval', 'remove_theme_mod("lafka_active_preset");' ] );
	} );

	test( 'home', async ( { page } ) => {
		await page.goto( '/' );
		await expect( page.locator( '.lafka-announce-bar' ).first() ).toBeVisible();
		await assertContrast( page, [
			{ sel: '.lafka-announce-bar__status', min: T.text, label: 'announce status' },
			{ sel: '.lafka-announce-bar__phone', min: T.text, label: 'announce phone' },
			{ sel: '.lafka-direct__heading', min: T.text, label: 'direct heading' },
			{ sel: '.lafka-direct__point', min: T.text, label: 'direct point' },
			{ sel: '.lafka-visit__cta--primary', min: T.ui, label: 'visit CTA (primary)' },
			{ sel: '.lafka-hero__headline', min: T.text, label: 'hero heading' },
			// The header control glyph sits directly on the header chrome, so it
			// guards the white-header regression (defect 7): if the header bg
			// flips back to white, this near-white icon collapses to ~1:1. (The
			// text logo is a WCAG-exempt logotype and renders in the brand accent,
			// so it is intentionally NOT asserted here.)
			{ sel: '.lafka-header__search', min: T.ui, label: 'header control' },
			{ sel: '.lafka-footer__col-title', min: T.text, label: 'footer heading' },
			{ sel: '.lafka-footer__copyright', min: T.text, label: 'footer copyright' },
		] );
	} );

	test( 'menu', async ( { page } ) => {
		await page.goto( '/menu/' );
		await expect( page.locator( '.lafka-menu__tab.is-active' ).first() ).toBeVisible();
		await assertContrast( page, [
			{ sel: '.lafka-menu__tab.is-active .lafka-menu__tab-label', min: T.text, label: 'active tab label' },
			{ sel: '.lafka-menu__tab.is-active .lafka-menu__tab-meta', min: T.text, label: 'active tab meta' },
		] );
	} );

	test( 'pdp (margherita-pizza)', async ( { page } ) => {
		await page.goto( `/product/${ SEED.pizzaSlug }/` );
		await expect( page.locator( '.lafka-pdp-summary__title' ) ).toContainText( 'Margherita Pizza' );
		await assertContrast( page, [
			{ sel: '.lafka-pdp-summary__title', min: T.text, label: 'pdp title' },
			{ sel: '.lafka-pdp-upsell__heading', min: T.text, label: 'pdp upsell heading' },
			{ sel: '.lafka-product-card__title', min: T.text, label: 'product card title' },
			{ sel: '.lafka-product-card__price', min: T.text, label: 'product card price' },
		] );
	} );

	test.describe( 'classic cart', () => {
		let productId;

		test.beforeAll( () => {
			useClassicCartCheckout();
			productId = Number(
				wpCli( [
					'eval',
					'$p=get_page_by_path("' + SEED.simpleSlug + '",OBJECT,"product");echo $p?(int)$p->ID:0;',
				] )
			);
			expect( productId, `could not resolve product id for ${ SEED.simpleSlug }` ).toBeGreaterThan( 0 );
		} );

		test( 'cart (classic)', async ( { page } ) => {
			// Seed the context cart via the Store API (GET-for-Nonce -> POST add-item).
			await page.goto( '/' );
			const seeded = await page.evaluate( async ( id ) => {
				const probe = await fetch( '/wp-json/wc/store/v1/cart', { headers: { Accept: 'application/json' } } );
				const nonce = probe.headers.get( 'Nonce' );
				const res = await fetch( '/wp-json/wc/store/v1/cart/add-item', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', Nonce: nonce || '' },
					body: JSON.stringify( { id, quantity: 1 } ),
				} );
				return res.ok;
			}, productId );
			expect( seeded, 'Store API add-item failed' ).toBeTruthy();

			await page.goto( '/cart/' );
			await expect( page.locator( '.woocommerce-cart-form' ) ).toBeVisible( { timeout: 15000 } );
			await assertContrast( page, [
				{ sel: '.cart_totals h2', min: T.text, label: 'cart totals heading' },
				{ sel: '.cart_totals .order-total .amount', min: T.text, label: 'cart order total' },
				{ sel: '.wc-proceed-to-checkout .checkout-button', min: T.ui, label: 'checkout CTA' },
			] );
		} );
	} );
} );
