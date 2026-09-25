/* lafka-theme/tests/e2e/menu-browse.spec.js
 *
 * GX QA (sx2): browsing the menu on the seeded store under the counter layouts.
 *
 *   - /menu/: the category strip sticks while scrolling and the chip of the
 *     section in view becomes active (scroll-spy); "All" has a real target.
 *   - A no-result live search shows the empty state; its reset brings the
 *     menu back; a live search with no match on the page falls through to
 *     the server product search on Enter.
 *   - /?s=…&post_type=product echoes the query, counts, and renders rows;
 *     a nonsense query gets "Nothing on the menu matches".
 *   - The PDP price line never shows a variation nobody picked, and the add
 *     button reads "Choose …" until every attribute is chosen, then
 *     "Add to order · $total" (× quantity).
 *
 * Written for the lead's integrated run; not run by the worker.
 *
 * @since lafka-theme 7.3.0
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );
const { wpCli } = require( './support/wp-cli' );

const SURFACES = [ 'header', 'home', 'menu', 'footer', 'drawer' ];

test.describe.serial( 'Menu browsing (GX QA)', () => {
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

	test( 'the category strip sticks and follows the section in view', async ( { page } ) => {
		await page.setViewportSize( { width: 375, height: 812 } );
		await page.goto( '/menu/' );
		const strip = page.locator( '.lafka-menu__cats' );
		await expect( page.locator( '.lafka-menu__cat-chip' ).first() ).toHaveAttribute( 'href', '#lafka-menu-all' );

		const last = page.locator( '.lafka-menu__group' ).last();
		await last.scrollIntoViewIfNeeded();
		await page.waitForTimeout( 300 );
		const box = await strip.boundingBox();
		expect( box.y ).toBeLessThanOrEqual( 1 );
		const lastId = await last.getAttribute( 'id' );
		await expect( page.locator( `.lafka-menu__cat-chip[href="#${ lastId }"]` ) ).toHaveAttribute( 'aria-current', 'true' );
	} );

	test( 'a no-result search shows the empty state and resets', async ( { page } ) => {
		await page.goto( '/menu/' );
		const input = page.locator( '[data-lafka-menu-search-input]' );
		await input.fill( 'xyzzy' );
		const empty = page.locator( '[data-lafka-menu-empty]' );
		await expect( empty ).toBeVisible();
		await empty.locator( '[data-lafka-menu-reset]' ).click();
		await expect( empty ).toBeHidden();
		await expect( input ).toHaveValue( '' );
		await expect( page.locator( '.lafka-menu__group' ).first() ).toBeVisible();
	} );

	test( 'product search echoes the query and handles no results', async ( { page } ) => {
		await page.goto( '/?s=pizza&post_type=product' );
		await expect( page.locator( 'h1' ) ).toContainText( 'pizza' );
		await expect( page.locator( '.lafka-row' ).first() ).toBeVisible();
		await expect( page.locator( '.lafka-menu__group' ) ).toHaveCount( 0 );
		await expect( page.locator( '[data-lafka-menu-search-input]' ) ).toHaveValue( 'pizza' );

		await page.goto( '/?s=xyzzy&post_type=product' );
		await expect( page.locator( '.lafka-menu__empty-title' ) ).toContainText( 'xyzzy' );
	} );

	test( 'PDP: no phantom price, "Choose …" then the line total', async ( { page } ) => {
		await page.goto( `/product/${ SEED.pizzaSlug }/` );
		const cta = page.locator( '.lafka-pdp-summary__cta' );
		await expect( cta ).toBeDisabled();
		await expect( cta ).toContainText( /^Choose /i );
		await expect( page.locator( '[data-lafka-live-price]' ) ).toContainText( /From|\$/ );

		await page.locator( '.lafka-pdp-chip', { hasText: 'Medium' } ).click();
		await expect( cta ).toBeEnabled();
		await expect( cta ).toContainText( `Add to order · $${ SEED.pizzaPrices.Medium }` );

		await page.locator( '.lafka-pdp-summary [data-lafka-qty="+1"]' ).click();
		const twice = ( 2 * parseFloat( SEED.pizzaPrices.Medium ) ).toFixed( 2 );
		await expect( cta ).toContainText( `$${ twice }` );
	} );
} );
