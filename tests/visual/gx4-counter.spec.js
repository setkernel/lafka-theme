/* lafka-theme/tests/visual/gx4-counter.spec.js
 *
 * GX4 "The counter" visual goldens — Peppery's default surfaces at
 * 375 / 768 / 1280: home, /menu/, a category archive, a variable PDP, the size
 * chooser open, and the order drawer open with three lines.
 *
 * Project `counter` in playwright.visual.config.js:
 *   npx playwright test --config playwright.visual.config.js --project=counter
 *   (add `-- --update-snapshots` for the first capture)
 * Goldens are LOCAL + UNTRACKED like every visual project.
 *
 * The layouts are pinned to "counter" through theme_mods (operator layer) so
 * the spec is independent of which preset is active, and restored afterwards.
 * Clock-driven text (the open/closed status) is masked.
 *
 * @since lafka-theme 7.2.0 (GX4)
 */
const { test, expect } = require( '@playwright/test' );
const { BREAKPOINTS, HEIGHTS, stabilize, shootAllBreakpoints } = require( './support/capture' );
const { SEED, useLayouts, restoreLayouts } = require( '../e2e/support/store' );
const { wpCli, bustDynamicCss } = require( '../e2e/support/wp-cli' );

const MASK_SELECTORS = [ '[data-lafka-open-status]', '[data-lafka-status]', '.lafka-related-carousel', '.lafka-pdp-upsell__grid' ];

/** Empty the cart, then add the given product ids through the Store API. */
async function setCart( page, ids ) {
	await page.goto( '/' );
	const ok = await page.evaluate( async ( list ) => {
		const probe = await fetch( '/wp-json/wc/store/v1/cart', { headers: { Accept: 'application/json' } } );
		const nonce = probe.headers.get( 'Nonce' ) || '';
		const cart = await probe.json();
		for ( const item of cart.items || [] ) {
			await fetch( '/wp-json/wc/store/v1/cart/remove-item', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', Nonce: nonce },
				body: JSON.stringify( { key: item.key } ),
			} );
		}
		for ( const id of list ) {
			const res = await fetch( '/wp-json/wc/store/v1/cart/add-item', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', Nonce: nonce },
				body: JSON.stringify( { id, quantity: 1 } ),
			} );
			if ( ! res.ok ) {
				return false;
			}
		}
		return true;
	}, ids );
	expect( ok, 'Store API cart seeding failed' ).toBeTruthy();
}

/** Viewport (not full-page) shots of an overlay at every breakpoint. */
async function shootOverlay( page, name, open ) {
	const mask = MASK_SELECTORS.map( ( sel ) => page.locator( sel ) );
	for ( const width of BREAKPOINTS ) {
		await page.setViewportSize( { width, height: HEIGHTS[ width ] } );
		await page.reload();
		await stabilize( page );
		await open();
		await expect( page ).toHaveScreenshot( `${ name }-${ width }.png`, { mask, animations: 'disabled' } );
		await page.keyboard.press( 'Escape' );
	}
}

test.describe.configure( { mode: 'serial' } );

test.describe( 'GX4 counter goldens', () => {
	test.beforeAll( () => {
		useLayouts( 'counter' );
		wpCli( [ 'theme', 'mod', 'set', 'lafka_motif', 'check' ] );
		bustDynamicCss();
	} );

	test.afterAll( () => {
		restoreLayouts();
		wpCli( [ 'theme', 'mod', 'remove', 'lafka_motif' ] );
	} );

	test( 'home', async ( { page } ) => {
		await page.goto( '/' );
		await expect( page.locator( '.lafka-counter-hero' ) ).toBeVisible();
		await shootAllBreakpoints( page, 'counter-home', MASK_SELECTORS );
	} );

	test( 'menu', async ( { page } ) => {
		await page.goto( '/menu/' );
		await expect( page.locator( '.lafka-row' ).first() ).toBeVisible();
		await shootAllBreakpoints( page, 'counter-menu', MASK_SELECTORS );
	} );

	test( 'category archive', async ( { page } ) => {
		const url = wpCli( [ 'eval', "echo get_term_link( 'pizzas', 'product_cat' );" ] );
		await page.goto( url );
		await expect( page.locator( '.lafka-row' ).first() ).toBeVisible();
		await shootAllBreakpoints( page, 'counter-archive', MASK_SELECTORS );
	} );

	test( 'pdp (variable)', async ( { page } ) => {
		await page.goto( `/product/${ SEED.pizzaSlug }/` );
		await expect( page.locator( '.lafka-pdp-summary__title' ) ).toContainText( 'Margherita Pizza' );
		await shootAllBreakpoints( page, 'counter-pdp', MASK_SELECTORS );
	} );

	test( 'size chooser open', async ( { page } ) => {
		await page.goto( '/menu/' );
		await shootOverlay( page, 'counter-chooser', async () => {
			await page.locator( '.lafka-row button[data-lafka-add-mode="chooser"]' ).first().click();
			await expect( page.locator( '#lafka-chooser' ) ).toBeVisible();
		} );
	} );

	test( 'order drawer with three lines', async ( { page } ) => {
		const ids = wpCli( [
			'eval',
			'$ids=array();foreach(wc_get_products(array("status"=>"publish","type"=>"simple","limit"=>3,"orderby"=>"title","order"=>"ASC")) as $p){$ids[]=$p->get_id();}echo implode(",",$ids);',
		] )
			.split( ',' )
			.map( Number )
			.filter( Boolean );
		expect( ids.length ).toBe( 3 );
		await setCart( page, ids );
		await page.goto( '/menu/' );
		await shootOverlay( page, 'counter-drawer', async () => {
			await page.locator( '[data-lafka-cart-open]' ).first().click();
			await expect( page.locator( '.lafka-cart-drawer' ) ).toHaveAttribute( 'data-open', 'true' );
		} );
		await setCart( page, [] );
	} );
} );
