/* lafka-theme/tests/e2e/cls-budget.spec.js
 *
 * GX T-02: Cumulative Layout Shift budget for the order path.
 *
 * Every stylesheet that paints the first viewport is render-blocking and only
 * off-screen modules load async (incl/system/lafka-critical-css.php), and the
 * preloader is off (T-01). This spec locks that in: home, /menu/, a PDP, the
 * cart and checkout must each stay at CLS <= 0.02 at 375 and 1280 under the
 * conditions that used to expose the regressions — every stylesheet answered
 * late (as on a phone connection) and a 4x slower CPU on mobile.
 *
 * CLS is summed from `layout-shift` PerformanceObserver entries without
 * recent input, from navigation until 2.5 s after `load`. On failure the
 * message lists the worst shift sources.
 *
 * @since lafka-theme 7.3.0 (GX T-02)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );
const { wpCli } = require( './support/wp-cli' );

const BUDGET = 0.02;
const SURFACES = [ 'header', 'home', 'menu', 'footer', 'drawer' ];
const STYLESHEET_DELAY_MS = 400;
const PAGES = [
	{ name: 'home', path: '/' },
	{ name: 'menu', path: '/menu/' },
	{ name: 'pdp', path: `/product/${ SEED.pizzaSlug }/` },
	{ name: 'cart', path: '/cart/', cart: true },
	{ name: 'checkout', path: '/checkout/', cart: true },
];
const VIEWPORTS = [
	{ width: 375, height: 812, mobile: true },
	{ width: 1280, height: 900, mobile: false },
];

/** Registered before any page script: records every layout shift. */
function observeLayoutShifts() {
	window.__lafkaShifts = [];
	new PerformanceObserver( ( list ) => {
		for ( const entry of list.getEntries() ) {
			if ( entry.hadRecentInput ) {
				continue;
			}
			window.__lafkaShifts.push( {
				value: entry.value,
				at: Math.round( entry.startTime ),
				sources: ( entry.sources || [] ).slice( 0, 3 ).map( ( s ) => {
					const n = s.node;
					if ( ! n ) {
						return '?';
					}
					const cls = typeof n.className === 'string' && n.className ? '.' + n.className.trim().split( /\s+/ )[ 0 ] : '';
					return `${ n.nodeName }${ n.id ? '#' + n.id : '' }${ cls } y${ Math.round( s.previousRect.y ) }>${ Math.round( s.currentRect.y ) }`;
				} ),
			} );
		}
	} ).observe( { type: 'layout-shift', buffered: true } );
}

async function fillCart( page ) {
	const res = await page.request.post( '/?wc-ajax=add_to_cart', {
		form: { product_id: await simpleProductId(), quantity: '1' },
	} );
	expect( res.ok() ).toBeTruthy();
}

let simpleId = null;
async function simpleProductId() {
	if ( null === simpleId ) {
		simpleId = wpCli( [ 'post', 'list', '--post_type=product', `--name=${ SEED.simpleSlug }`, '--field=ID' ] ).trim();
	}
	return simpleId;
}

test.describe( 'CLS budget @smoke', () => {
	test.beforeAll( () => {
		for ( const surface of SURFACES ) {
			wpCli( [ 'theme', 'mod', 'set', `lafka_${ surface }_layout`, 'counter' ] );
		}
	} );

	test.afterAll( () => {
		for ( const surface of SURFACES ) {
			wpCli( [ 'theme', 'mod', 'remove', `lafka_${ surface }_layout` ] );
		}
	} );

	for ( const vp of VIEWPORTS ) {
		for ( const target of PAGES ) {
			test( `${ target.name } @ ${ vp.width }px stays within CLS ${ BUDGET }`, async ( { browser } ) => {
				const context = await browser.newContext( {
					viewport: { width: vp.width, height: vp.height },
					isMobile: vp.mobile,
					hasTouch: vp.mobile,
				} );
				await context.addInitScript( observeLayoutShifts );
				// Late stylesheets: exactly the condition an async (print-media)
				// sheet that styles the first viewport turns into a shift.
				await context.route( '**/*.css*', async ( route ) => {
					await new Promise( ( r ) => setTimeout( r, STYLESHEET_DELAY_MS ) );
					await route.continue();
				} );
				const page = await context.newPage();
				if ( vp.mobile ) {
					const cdp = await context.newCDPSession( page );
					await cdp.send( 'Emulation.setCPUThrottlingRate', { rate: 4 } );
				}
				if ( target.cart ) {
					await fillCart( page );
				}

				await page.goto( target.path, { waitUntil: 'load' } );
				await page.waitForTimeout( 2500 );

				const shifts = await page.evaluate( () => window.__lafkaShifts );
				const cls = shifts.reduce( ( sum, s ) => sum + s.value, 0 );
				const worst = [ ...shifts ].sort( ( a, b ) => b.value - a.value ).slice( 0, 4 )
					.map( ( s ) => `${ s.value.toFixed( 4 ) } @${ s.at }ms ${ s.sources.join( ' ; ' ) }` ).join( '\n' );

				expect( cls, `CLS ${ cls.toFixed( 4 ) } on ${ target.path } at ${ vp.width }px. Worst shifts:\n${ worst }` ).toBeLessThanOrEqual( BUDGET );
				await context.close();
			} );
		}
	}
} );
