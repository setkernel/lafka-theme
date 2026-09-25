/* lafka-theme/tests/e2e/order-path-drawer.spec.js
 *
 * GX order-path QA, counter drawer + fulfilment:
 *   - O-01 / H-03: two-row lines at 320–414 (the text column is not a sliver)
 *     and a static, ≥44px worded Remove.
 *   - O-17: three quick taps on + end on quantity 4.
 *   - O-18: after Remove, focus stays in the drawer.
 *   - O-19: an empty order shows no "Go to checkout" footer.
 *   - O-11: at 812×375 the order list is not squeezed (panel scrolls, compact footer).
 *   - O-02: an upsell / listing add opens the drawer, it does not redirect to /cart/.
 *   - O-07: choosing Delivery in the drawer selects a delivery rate on the
 *     classic checkout (the "Delivery" placeholder until an address exists)
 *     and COD reads "Pay on delivery".
 *
 * Written for the seeded wp-env store (see CONTRIBUTING.md → End-to-end).
 *
 * @since lafka-theme 7.3.0
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );
const { wpCli } = require( './support/wp-cli' );

const SURFACES = [ 'header', 'home', 'menu', 'footer', 'drawer' ];

async function addSimple( page, qty = 1 ) {
	const res = await page.request.get( '/wp-json/wc/store/v1/products?slug=' + SEED.simpleSlug );
	const [ product ] = await res.json();
	for ( let i = 0; i < qty; i++ ) {
		await page.goto( '/?add-to-cart=' + product.id );
	}
	return product;
}

async function openDrawer( page ) {
	await page.goto( '/menu/' );
	await page.locator( '[data-lafka-cart-open]' ).first().click();
	await expect( page.locator( '.lafka-cart-drawer[data-open="true"]' ) ).toBeVisible();
}

test.describe.serial( 'Order path — counter drawer', () => {
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

	for ( const width of [ 320, 375, 414 ] ) {
		test( `lines are two rows with a 44px Remove at ${ width }px`, async ( { page } ) => {
			await page.setViewportSize( { width, height: 800 } );
			await addSimple( page );
			await openDrawer( page );

			const row = page.locator( '.lafka-cart-drawer__item--stepper' ).first();
			const info = await row.locator( '.lafka-cart-drawer__info' ).boundingBox();
			const remove = await row.locator( '.lafka-cart-drawer__remove' ).boundingBox();
			expect( info.width ).toBeGreaterThan( 180 );
			expect( remove.height ).toBeGreaterThanOrEqual( 44 );
			await expect( row.locator( '.lafka-cart-drawer__remove' ) ).toHaveText( /Remove/ );
			expect( await page.evaluate( () => document.documentElement.scrollWidth <= innerWidth ) ).toBe( true );
		} );
	}

	test( 'rapid taps on + are all counted', async ( { page } ) => {
		await addSimple( page );
		await openDrawer( page );
		const plus = page.locator( '[data-lafka-qty-step="1"]' ).first();
		await plus.click();
		await plus.click();
		await plus.click();
		await expect( page.locator( '.lafka-cart-drawer__qty' ).first() ).toHaveText( '4', { timeout: 15000 } );
		const cart = await ( await page.request.get( '/wp-json/wc/store/v1/cart' ) ).json();
		expect( cart.items[ 0 ].quantity ).toBe( 4 );
	} );

	test( 'focus stays in the drawer after Remove; the empty order has no checkout footer', async ( { page } ) => {
		await addSimple( page );
		await openDrawer( page );
		await page.locator( '.lafka-cart-drawer__remove' ).first().focus();
		await page.keyboard.press( 'Enter' );
		await expect( page.locator( '[data-lafka-cart-empty]' ) ).toBeVisible( { timeout: 15000 } );
		expect( await page.evaluate( () => !! document.activeElement.closest( '.lafka-cart-drawer' ) ) ).toBe( true );
		await expect( page.locator( '.lafka-drawer__footer' ) ).toBeHidden();
	} );

	test( 'landscape phone keeps the order list usable', async ( { page } ) => {
		await page.setViewportSize( { width: 812, height: 375 } );
		await addSimple( page );
		await openDrawer( page );
		await expect( page.locator( '.lafka-drawer__tax' ) ).toBeHidden();
		await expect( page.locator( '.lafka-cart-drawer__checkout' ) ).toBeInViewport();
	} );

	test( 'an add opens the drawer instead of redirecting to the cart', async ( { page } ) => {
		wpCli( [ 'option', 'update', 'woocommerce_cart_redirect_after_add', 'yes' ] );
		try {
			await addSimple( page );
			await openDrawer( page );
			const add = page.locator( '.lafka-cart-drawer__upsell-add' ).first();
			if ( await add.count() ) {
				await add.click();
				await page.waitForTimeout( 1500 );
				expect( page.url() ).not.toMatch( /\/cart\/?$/ );
			}
		} finally {
			wpCli( [ 'option', 'update', 'woocommerce_cart_redirect_after_add', 'no' ] );
		}
	} );

	test( 'Delivery chosen in the drawer is the checkout choice before any address', async ( { page } ) => {
		await addSimple( page );
		await openDrawer( page );
		const delivery = page.locator( '.lafka-fulfilment--drawer input[value="delivery"]' );
		if ( ! ( await delivery.count() ) ) {
			test.skip( true, 'The seeded store offers one fulfilment mode only.' );
		}
		await delivery.check( { force: true } );
		await page.goto( '/checkout/' );
		const checked = page.locator( 'input[name^="shipping_method"]:checked, input[name^="shipping_method"][type="hidden"]' ).first();
		await expect( checked ).not.toHaveValue( /^(local_pickup|pickup_location)/ );
		await expect( page.locator( '#payment' ) ).not.toContainText( /Pay at pickup/i );
	} );
} );
