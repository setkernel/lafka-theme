/* lafka-theme/tests/visual/nx1-02.spec.js
 *
 * ============================================================================
 * NX1-02 VISUAL PARITY GATE — README FOR AGENTS (read before running/editing)
 * ============================================================================
 * This full-page screenshot suite is the human-visible half of the NX1-02
 * regression gate (the machine half is tests/Unit/DynamicCssParityTest.php).
 * NX1-02 retires the legacy Options Framework and re-points every design-token
 * reader at the Customizer/plugin layer. An UPGRADED install must render
 * PIXEL-IDENTICAL before and after each migration slice. This suite proves it.
 *
 * THE GOLDENS ARE LOCAL. They are written under tests/visual/__screenshots__/
 * (gitignored) and are NOT committed, NOT run in CI, and machine-specific.
 * Regenerate them ONLY intentionally — on a clean pre-slice HEAD to establish
 * the baseline, and after a slice ONLY once you have eyeballed the diff and
 * confirmed the visual change is expected (for a lossless migration there should
 * be NONE).
 *
 * HOW SLICE AGENTS USE IT
 *   1. On the pre-migration HEAD, once:
 *        npm run test:visual:nx1-02 -- --update-snapshots
 *      (writes the golden baseline)
 *   2. After each migration slice:
 *        npm run test:visual:nx1-02
 *      A green run == the slice changed no rendered pixels. A red run == the
 *      slice moved/dropped a token; inspect tests/visual/.report before shipping.
 *
 * REQUIREMENTS: the umbrella wp-env must be up at LAFKA_E2E_BASE_URL
 * (default http://localhost:8890). global-setup reseeds the demo store for
 * determinism and global-teardown restores the env's blocks-checkout baseline.
 *
 * DETERMINISM: dynamic regions (the announce-bar open/closed + until-time badge)
 * are MASKED; fonts + lazy images are settled before every capture; CSS
 * animations are frozen by config. Cart/checkout are captured in CLASSIC mode
 * (production's mode, and the parity that matters most) — the suite flips to
 * classic around them and the teardown restores blocks.
 *
 * @since lafka-theme 6.21.0 (NX1-02 harness)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED, useClassicCartCheckout } = require( '../e2e/support/store' );
const { wpCli } = require( '../e2e/support/wp-cli' );

// The three product breakpoints every visual ship is verified at (375/768/1280).
const BREAKPOINTS = [ 375, 768, 1280 ];
const HEIGHTS = { 375: 812, 768: 1024, 1280: 900 };

// Non-deterministic regions to blank out so the goldens are reproducible:
//   - [data-lafka-status]   the announce-bar open/closed dot + "Open until
//     HH:MM" label (the only clock-driven text on these surfaces);
//   - .lafka-favs__grid     the home "Customer favourites" grid. With no
//     featured products on a fresh seed it falls back to best-sellers ordered
//     by total_sales, which is 0 for every product → MySQL returns the tied
//     rows in an unstable order/selection run to run. The grid's box (fixed 8
//     items) is stable, so masking its content keeps the section chrome and
//     layout under the gate while removing the content churn.
//   - .lafka-related-carousel  the PDP "Other … You'll Love" related products,
//     which WooCommerce orders with `orderby => rand` — different every load.
//   - .lafka-pdp-upsell__grid  the PDP "Make it a meal" row. Slots beyond a
//     product's explicit upsells are filled from a `orderby => date` fallback
//     whose tied seed dates make the extra card(s) unstable. Fixed card count
//     → stable height; only the content churns, so masking it suffices.
// Locators that match zero elements on a given page are a no-op mask.
const MASK_SELECTORS = [
	'[data-lafka-status]',
	'.lafka-favs__grid',
	'.lafka-related-carousel',
	'.lafka-pdp-upsell__grid',
];

/**
 * Settle the page so a full-page capture is deterministic: wait for web fonts,
 * force any lazy-loaded imagery to fetch by scrolling the full height, then
 * return to the top and let the network go idle.
 *
 * @param {import('@playwright/test').Page} page
 */
async function stabilize( page ) {
	await page.evaluate( async () => {
		if ( document.fonts && document.fonts.ready ) {
			await document.fonts.ready;
		}
		await new Promise( ( resolve ) => {
			const total = document.body.scrollHeight;
			let y = 0;
			const step = () => {
				y += Math.max( 400, window.innerHeight );
				window.scrollTo( 0, y );
				if ( y < total ) {
					setTimeout( step, 40 );
				} else {
					window.scrollTo( 0, 0 );
					setTimeout( resolve, 120 );
				}
			};
			step();
		} );
	} );
	await page.waitForLoadState( 'networkidle' ).catch( () => {} );
}

/**
 * Capture one surface at all three breakpoints as `${name}-${width}.png`.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string}                          name Golden basename (surface id).
 */
async function shootAllBreakpoints( page, name ) {
	const mask = MASK_SELECTORS.map( ( sel ) => page.locator( sel ) );
	for ( const width of BREAKPOINTS ) {
		await page.setViewportSize( { width, height: HEIGHTS[ width ] } );
		await stabilize( page );
		await expect( page ).toHaveScreenshot( `${ name }-${ width }.png`, {
			fullPage: true,
			mask,
			animations: 'disabled',
		} );
	}
}

/**
 * Seed the browser context's WooCommerce cart with one simple product using the
 * Store API (the proven script pattern: GET the cart to read the Nonce response
 * header, then POST it back to /cart/add-item). Cookies from the same context
 * carry the session to the subsequent classic cart/checkout render.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number}                          productId
 */
async function seedCart( page, productId ) {
	// Establish a same-origin document so fetch() rides the store cookies.
	await page.goto( '/' );
	const result = await page.evaluate( async ( id ) => {
		const probe = await fetch( '/wp-json/wc/store/v1/cart', {
			headers: { Accept: 'application/json' },
		} );
		const nonce = probe.headers.get( 'Nonce' );
		const res = await fetch( '/wp-json/wc/store/v1/cart/add-item', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', Nonce: nonce || '' },
			body: JSON.stringify( { id, quantity: 1 } ),
		} );
		return { ok: res.ok, status: res.status, body: await res.text() };
	}, productId );
	expect(
		result.ok,
		`Store API add-item failed (${ result.status }): ${ result.body }`
	).toBeTruthy();
}

// Global store state (checkout mode) is toggled by this suite → run serial.
test.describe.configure( { mode: 'serial' } );

test.describe( 'NX1-02 visual goldens', () => {
	test( 'home', async ( { page } ) => {
		await page.goto( '/' );
		await shootAllBreakpoints( page, 'home' );
	} );

	test( 'menu', async ( { page } ) => {
		await page.goto( '/menu/' );
		await expect(
			page.locator( '.lafka-menu__cats' ).first()
		).toBeVisible();
		await shootAllBreakpoints( page, 'menu' );
	} );

	test( 'pdp-simple (garlic-bread)', async ( { page } ) => {
		await page.goto( `/product/${ SEED.simpleSlug }/` );
		await shootAllBreakpoints( page, 'pdp-simple' );
	} );

	test( 'pdp-variable (margherita-pizza)', async ( { page } ) => {
		await page.goto( `/product/${ SEED.pizzaSlug }/` );
		await expect(
			page.locator( '.lafka-pdp-summary__title' )
		).toContainText( 'Margherita Pizza' );
		await shootAllBreakpoints( page, 'pdp-variable' );
	} );

	test.describe( 'classic cart + checkout (production mode)', () => {
		let productId;

		test.beforeAll( () => {
			// Flip the store to CLASSIC shortcode cart/checkout for these goldens.
			// global-teardown restores the blocks baseline for the whole suite.
			useClassicCartCheckout();
			// Resolve by slug via get_page_by_path — wc_get_products() silently
			// ignores a `slug` arg and would return an arbitrary product.
			productId = Number(
				wpCli( [
					'eval',
					'$p=get_page_by_path("' +
						SEED.simpleSlug +
						'",OBJECT,"product");echo $p?(int)$p->ID:0;',
				] )
			);
			expect(
				productId,
				`could not resolve product id for ${ SEED.simpleSlug }`
			).toBeGreaterThan( 0 );
		} );

		test( 'cart (classic)', async ( { page } ) => {
			await seedCart( page, productId );
			await page.goto( '/cart/' );
			await expect(
				page.locator( '.woocommerce-cart-form' )
			).toBeVisible( { timeout: 15000 } );
			await shootAllBreakpoints( page, 'cart-classic' );
		} );

		test( 'checkout (classic)', async ( { page } ) => {
			await seedCart( page, productId );
			await page.goto( '/checkout/' );
			await expect( page.locator( 'form.checkout' ) ).toBeVisible( {
				timeout: 15000,
			} );
			await shootAllBreakpoints( page, 'checkout-classic' );
		} );
	} );
} );
