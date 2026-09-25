/* lafka-theme/tests/e2e/order-path-polish.spec.js
 *
 * Order-path polish (GX QA, worker sx1): the behaviour the unit tests cannot
 * reach because it lives in CSS / browser JS.
 *
 *  - H-13  the skip link is the first Tab stop and visible when focused.
 *  - O-31  a required field left empty says so in words, wired with
 *          aria-describedby; a 2-digit phone is flagged.
 *  - O-16  a /cart/ quantity change updates the cart by itself (no visible
 *          "Update cart" button with JavaScript on).
 *  - O-06  the shipping options are full-width cards (the row spans the
 *          totals table) at 375.
 *  - O-09  the /cart/ stepper is one 44px box inside its card at 320.
 *
 * Runs against the seeded demo store (support/store.js), classic checkout.
 * Asserted text is the theme's own English defaults; the selectors are the
 * stable classes the templates print.
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );

/** Put the seeded simple product in the cart through its PDP button. */
async function addSimpleProduct( page ) {
	await page.goto( `/product/${ SEED.simpleSlug }/` );
	await page.locator( '[data-lafka-add-to-cart]' ).first().click();
	await expect( page.locator( '.lafka-cart-drawer' ) ).toHaveAttribute( 'data-open', 'true', { timeout: 6000 } );
}

test.describe( 'Order path polish', () => {
	test( 'skip link is the first Tab stop and visible on focus', async ( { page } ) => {
		await page.goto( '/' );
		await page.keyboard.press( 'Tab' );

		const link = page.locator( 'a.skip-link' );
		await expect( link ).toBeFocused();
		const box = await link.boundingBox();
		expect( box ).not.toBeNull();
		expect( box.y ).toBeGreaterThanOrEqual( 0 );
		expect( box.width ).toBeGreaterThan( 40 );
		expect( box.height ).toBeGreaterThan( 20 );
	} );

	test( 'checkout: worded inline errors on blur, short phone flagged', async ( { page } ) => {
		await addSimpleProduct( page );
		await page.goto( '/checkout/' );

		await page.locator( '#billing_first_name' ).focus();
		await page.locator( '#billing_last_name' ).focus();
		const firstMessage = page.locator( '#billing_first_name_field .checkout-inline-error-message' );
		await expect( firstMessage ).toHaveText( /is required\./ );
		await expect( page.locator( '#billing_first_name' ) ).toHaveAttribute( 'aria-describedby', 'billing_first_name_description' );
		await expect( page.locator( '#billing_first_name' ) ).toHaveAttribute( 'aria-invalid', 'true' );

		await page.locator( '#billing_phone' ).fill( '12' );
		await page.locator( '#billing_email' ).focus();
		await expect( page.locator( '#billing_phone_field' ) ).toHaveClass( /woocommerce-invalid-phone/ );
		await expect( page.locator( '#billing_phone_field .checkout-inline-error-message' ) ).toHaveText( 'Enter a valid phone number.' );

		// Fixing the field removes the message (WooCommerce's own clean-up).
		await page.locator( '#billing_first_name' ).fill( 'Ada' );
		await page.locator( '#billing_last_name' ).focus();
		await expect( firstMessage ).toHaveCount( 0 );
	} );

	test( 'cart: a quantity change updates the cart by itself', async ( { page } ) => {
		await addSimpleProduct( page );
		await page.goto( '/cart/' );

		await expect( page.locator( 'button[name="update_cart"]' ) ).toBeHidden();
		const subtotal = page.locator( '.cart_totals .cart-subtotal td' );
		const before = await subtotal.innerText();

		await page.locator( '.lafka-cart-item__qty .lafka-qty-plus' ).first().click();
		await expect.poll( () => subtotal.innerText(), { timeout: 8000 } ).not.toBe( before );
		await expect( page.locator( '.lafka-cart-item__qty input.qty' ).first() ).toHaveValue( '2' );
	} );

	test( 'shipping options are full-width cards at 375', async ( { page } ) => {
		await page.setViewportSize( { width: 375, height: 812 } );
		await addSimpleProduct( page );
		await page.goto( '/cart/' );

		const row = page.locator( '.cart_totals tr.lafka-shipping-totals > td' );
		await expect( row ).toHaveAttribute( 'colspan', '2' );
		const table = await page.locator( '.cart_totals table.shop_table' ).boundingBox();
		const card = await page.locator( '.lafka-shipping-choice' ).first().boundingBox();
		expect( card.width ).toBeGreaterThan( table.width * 0.9 );
		expect( card.height ).toBeGreaterThanOrEqual( 48 );
		const label = page.locator( '.lafka-shipping-choice > label' ).first();
		expect( await label.evaluate( ( el ) => parseFloat( getComputedStyle( el ).fontSize ) ) ).toBeGreaterThanOrEqual( 15 );
		expect( await label.evaluate( ( el ) => getComputedStyle( el ).textTransform ) ).toBe( 'none' );
	} );

	test( 'cart stepper is one 44px box inside the card at 320', async ( { page } ) => {
		await page.setViewportSize( { width: 320, height: 720 } );
		await addSimpleProduct( page );
		await page.goto( '/cart/' );

		const box = await page.locator( '.lafka-cart-item__qty div.quantity' ).first().boundingBox();
		const card = await page.locator( '.lafka-cart-item' ).first().boundingBox();
		expect( box.height ).toBeGreaterThanOrEqual( 44 );
		expect( box.x ).toBeGreaterThanOrEqual( card.x );
		expect( box.x + box.width ).toBeLessThanOrEqual( card.x + card.width + 0.5 );
		for ( const selector of [ '.lafka-qty-minus', '.lafka-qty-plus' ] ) {
			const button = await page.locator( `.lafka-cart-item__qty ${ selector }` ).first().boundingBox();
			expect( button.width ).toBeGreaterThanOrEqual( 44 );
			expect( button.x ).toBeGreaterThanOrEqual( box.x - 0.5 );
			expect( button.x + button.width ).toBeLessThanOrEqual( box.x + box.width + 0.5 );
		}
		expect( await page.evaluate( () => document.documentElement.scrollWidth ) ).toBeLessThanOrEqual( 320 );
	} );
} );
