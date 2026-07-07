/* lafka-theme/tests/visual/nx2-dark.spec.js
 *
 * ============================================================================
 * NX2-07 DARK-PRESET VISUAL GATE — README FOR AGENTS (read before editing)
 * ============================================================================
 * The dark-mode counterpart of the NX1-02 parity gate. NX2-07 tokenised the
 * theme's core SURFACES (body, cards, sticky chrome, footer, PDP panels) onto
 * --lafka-color-surface-* tokens and completed the :root[data-theme="dark"]
 * scaffold, so activating a dark preset (midnight) now renders a COHERENT
 * dark-on-dark page instead of light-text-on-white surfaces. This suite pins
 * that: it screenshots the seeded store with active_preset=midnight at
 * 375/768/1280 for home + menu + a PDP + the classic cart.
 *
 * ISOLATION FROM THE PEPPERY GATE (critical): this file is DELIBERATELY run by
 * its OWN config (playwright.visual.dark.config.js, `npm run test:visual:nx2-dark`)
 * and is testIgnore'd by playwright.visual.config.js, so the 30 PEPPERY goldens
 * (test:visual:nx1-02) never try to render these dark shots and vice-versa. The
 * dark goldens live under tests/visual/__screenshots__/nx2-dark.spec.js/ and are
 * LOCAL + UNTRACKED (gitignored), machine-specific — same contract as nx1-02.
 * Capture the baseline ONLY after tokenisation makes midnight render dark:
 *   npm run test:visual:nx2-dark -- --update-snapshots
 *
 * PRESET LIFECYCLE: beforeAll sets the lafka_active_preset theme_mod to
 * "midnight"; afterAll REMOVES it (back to the Peppery default). The env is a
 * throwaway wp-env — but leaving it on midnight would poison a subsequent
 * Peppery gate run, so the unset is mandatory.
 *
 * DETERMINISM: identical to nx1-02 — dynamic regions masked, fonts + lazy
 * images settled, animations frozen; cart captured in CLASSIC mode.
 *
 * @since lafka-theme 7.1.0 (NX2-07 surface tokenisation + dark completion)
 */
const { test, expect } = require( '@playwright/test' );
const { shootAllBreakpoints } = require( './support/capture' );
const { SEED, useClassicCartCheckout } = require( '../e2e/support/store' );
const { wpCli } = require( '../e2e/support/wp-cli' );

// Same non-deterministic regions the Peppery gate blanks (see nx1-02.spec.js).
const MASK_SELECTORS = [
	'[data-lafka-status]',
	'.lafka-favs__grid',
	'.lafka-related-carousel',
	'.lafka-pdp-upsell__grid',
];

/**
 * Seed the context cart with one simple product via the Store API (the proven
 * GET-for-Nonce → POST /cart/add-item pattern, shared with nx1-02).
 *
 * @param {import('@playwright/test').Page} page
 * @param {number}                          productId
 */
async function seedCart( page, productId ) {
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

// Serial: the active-preset theme_mod + checkout mode are global store state.
test.describe.configure( { mode: 'serial' } );

test.describe( 'NX2-07 midnight dark goldens', () => {
	test.beforeAll( () => {
		// Activate the dark preset for every capture in this file.
		wpCli( [ 'eval', 'set_theme_mod("lafka_active_preset","midnight");' ] );
	} );

	test.afterAll( () => {
		// MANDATORY: restore the Peppery default so a later Peppery gate is clean.
		wpCli( [ 'eval', 'remove_theme_mod("lafka_active_preset");' ] );
	} );

	test( 'home', async ( { page } ) => {
		await page.goto( '/' );
		await shootAllBreakpoints( page, 'midnight-home', MASK_SELECTORS );
	} );

	test( 'menu', async ( { page } ) => {
		await page.goto( '/menu/' );
		await expect(
			page.locator( '.lafka-menu__cats' ).first()
		).toBeVisible();
		await shootAllBreakpoints( page, 'midnight-menu', MASK_SELECTORS );
	} );

	test( 'pdp-variable (margherita-pizza)', async ( { page } ) => {
		await page.goto( `/product/${ SEED.pizzaSlug }/` );
		await expect(
			page.locator( '.lafka-pdp-summary__title' )
		).toContainText( 'Margherita Pizza' );
		await shootAllBreakpoints( page, 'midnight-pdp', MASK_SELECTORS );
	} );

	test.describe( 'classic cart', () => {
		let productId;

		test.beforeAll( () => {
			useClassicCartCheckout();
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
			await shootAllBreakpoints( page, 'midnight-cart', MASK_SELECTORS );
		} );
	} );
} );
