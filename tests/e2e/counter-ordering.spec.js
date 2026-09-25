/* lafka-theme/tests/e2e/counter-ordering.spec.js
 *
 * GX4 "The counter": the 2-tap order path on the seeded store.
 *
 *   row "Add" (variable) -> <dialog> size chooser -> tap a size -> the RIGHT
 *   variation is in the cart (asserted through the Store API), the drawer
 *   opens, focus comes back to the row's Add after Escape; a simple product
 *   adds in one tap; keyboard-only works; the drawer stepper and the
 *   fulfilment choice carry into checkout.
 *
 * The counter layouts are switched on per run with theme_mods (operator
 * layer) and restored afterwards, so the suite works on any preset.
 *
 * @since lafka-theme 7.2.0 (GX4)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );
const { wpCli } = require( './support/wp-cli' );

const SURFACES = [ 'header', 'home', 'menu', 'footer', 'drawer' ];

async function cartItems( page ) {
	const res = await page.request.get( '/wp-json/wc/store/v1/cart' );
	expect( res.ok() ).toBeTruthy();
	const body = await res.json();
	return body.items || [];
}

async function emptyCart( page ) {
	const res = await page.request.get( '/wp-json/wc/store/v1/cart' );
	const nonce = res.headers().nonce || res.headers()[ 'x-wc-store-api-nonce' ];
	const body = await res.json();
	for ( const item of body.items || [] ) {
		await page.request.post( '/wp-json/wc/store/v1/cart/remove-item', {
			headers: { Nonce: nonce },
			data: { key: item.key },
		} );
	}
}

test.describe.serial( 'Counter ordering @smoke', () => {
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

	test.beforeEach( async ( { page } ) => {
		await page.goto( '/menu/' );
		await emptyCart( page );
		await page.reload();
	} );

	test( 'variable Add opens the chooser and adds the chosen size', async ( { page } ) => {
		const row = page.locator( '.lafka-row', { hasText: /Margherita/i } ).first();
		const add = row.locator( 'button[data-lafka-add-mode="chooser"]' );
		await expect( add ).toBeVisible();
		await expect( row.locator( '.lafka-prices dt' ) ).toHaveText( Object.keys( SEED.pizzaPrices ) );

		await add.click();
		const dialog = page.locator( '#lafka-chooser' );
		await expect( dialog ).toBeVisible();
		await expect( dialog.locator( '[data-lafka-chooser-name]' ) ).toHaveText( /Margherita/i );

		const medium = dialog.getByRole( 'button', { name: new RegExp( `^Medium, .*${ SEED.pizzaPrices.Medium.replace( '.', '\\.' ) }` ) } );
		await medium.click();

		await expect( dialog ).toBeHidden();
		await expect( page.locator( '.lafka-cart-drawer' ) ).toHaveAttribute( 'data-open', 'true', { timeout: 6000 } );

		const items = await cartItems( page );
		expect( items ).toHaveLength( 1 );
		const size = ( items[ 0 ].variation || [] ).find( ( v ) => /size/i.test( v.attribute ) );
		expect( size && size.value ).toMatch( /medium/i );
	} );

	test( 'simple Add adds in one tap', async ( { page } ) => {
		const row = page.locator( '.lafka-row', { hasText: /Garlic Bread/i } ).first();
		await row.locator( 'button[data-lafka-add-mode="direct"]' ).click();
		await expect( page.locator( '.lafka-cart-drawer' ) ).toHaveAttribute( 'data-open', 'true', { timeout: 6000 } );
		expect( await cartItems( page ) ).toHaveLength( 1 );
	} );

	test( 'keyboard only: Enter opens, Escape closes, focus returns to Add', async ( { page } ) => {
		const add = page.locator( '.lafka-row button[data-lafka-add-mode="chooser"]' ).first();
		await add.focus();
		await page.keyboard.press( 'Enter' );
		const dialog = page.locator( '#lafka-chooser' );
		await expect( dialog ).toBeVisible();
		await expect
			.poll( () => page.evaluate( () => document.activeElement && document.activeElement.classList.contains( 'lafka-chooser__option' ) ) )
			.toBe( true );
		await page.keyboard.press( 'Escape' );
		await expect( dialog ).toBeHidden();
		await expect
			.poll( () => page.evaluate( () => document.activeElement && document.activeElement.hasAttribute( 'data-lafka-add' ) ) )
			.toBe( true );
	} );

	test( 'the chooser lists every size with a price and a worded action', async ( { page } ) => {
		await page.locator( '.lafka-row', { hasText: /Margherita/i } ).locator( 'button[data-lafka-add]' ).click();
		const options = page.locator( '#lafka-chooser .lafka-chooser__option' );
		await expect( options ).toHaveCount( Object.keys( SEED.pizzaPrices ).length );
		for ( const [ size, price ] of Object.entries( SEED.pizzaPrices ) ) {
			await expect( page.locator( '#lafka-chooser' ).getByRole( 'button', { name: new RegExp( `^${ size }, .*${ price.replace( '.', '\\.' ) }, add to order$` ) } ) ).toBeVisible();
		}
	} );

	test( 'drawer: stepper, remove, checkout label and pickup into checkout', async ( { page } ) => {
		const row = page.locator( '.lafka-row', { hasText: /Garlic Bread/i } ).first();
		await row.locator( 'button[data-lafka-add]' ).click();
		const drawer = page.locator( '.lafka-cart-drawer--counter' );
		await expect( drawer ).toHaveAttribute( 'data-open', 'true', { timeout: 6000 } );
		const checkout = drawer.locator( '.lafka-drawer__checkout-total' );
		const before = await checkout.textContent();

		// lafka-plugin's quantity stepper (theme support lafka-drawer-stepper).
		await drawer.getByRole( 'button', { name: /^One more / } ).first().click();
		await expect.poll( async () => ( await cartItems( page ) )[ 0 ].quantity ).toBe( 2 );
		await expect( checkout ).not.toHaveText( before || '' );
		await expect( drawer.locator( '.lafka-drawer__summary' ) ).toHaveText( /2 items/ );

		// Pickup chosen in the drawer is the preference checkout preselects.
		await drawer.locator( 'label', { hasText: 'Pickup' } ).click();
		expect( ( await page.context().cookies() ).find( ( c ) => c.name === 'lafka_order_method' )?.value ).toBe( 'pickup' );
		await drawer.locator( '.lafka-cart-drawer__checkout' ).click();
		await expect( page ).toHaveURL( /checkout/ );
		await expect( page.locator( 'input[name^="shipping_method"]:checked' ) ).toHaveValue( /pickup/ );

		// Remove empties the drawer again.
		await page.goto( '/menu/' );
		await page.locator( '[data-lafka-cart-open]' ).first().click();
		await drawer.getByRole( 'button', { name: /^Remove|Remove / } ).first().click();
		await expect.poll( async () => ( await cartItems( page ) ).length ).toBe( 0 );
		await expect( drawer.locator( '.lafka-drawer__checkout-total' ) ).toHaveText( 'Go to checkout' );
	} );

	test( 'no visible text below 14px on the counter menu', async ( { page } ) => {
		const tooSmall = await page.evaluate( () => {
			const out = [];
			const walker = document.createTreeWalker( document.body, NodeFilter.SHOW_TEXT );
			while ( walker.nextNode() ) {
				const node = walker.currentNode;
				const el = node.parentElement;
				if ( ! node.textContent.trim() || ! el || el.closest( '.screen-reader-text, [hidden], [aria-hidden="true"]' ) ) {
					continue;
				}
				const rect = el.getBoundingClientRect();
				const style = getComputedStyle( el );
				if ( rect.width === 0 || style.visibility === 'hidden' || style.display === 'none' ) {
					continue;
				}
				if ( parseFloat( style.fontSize ) < 14 ) {
					out.push( `${ el.className || el.tagName }: ${ style.fontSize }` );
				}
			}
			return out;
		} );
		expect( tooSmall ).toEqual( [] );
	} );
} );
