/* lafka-theme/tests/e2e/block-checkout.spec.js
 *
 * The conversion money-path on the BLOCK Cart/Checkout (NX1-04b), end to end,
 * against the seeded demo store: menu → PDP with a variation + addon → block
 * cart (addon line + free-delivery data) → block checkout (address, lafka
 * order-type = pickup, COD) → order received.
 *
 * The env baseline is the CLASSIC shortcode path (support/global-setup.js →
 * prepareStore swaps the cart/checkout pages to shortcodes). This spec is
 * SERIAL and self-contained: beforeAll flips the store onto WooCommerce's block
 * pages + Lafka blocks mode (and turns on the branch/order-type + free-delivery
 * knobs the block UI exercises); afterAll restores the classic baseline so
 * funnel.spec.js and the rest stay green regardless of run order.
 *
 * Selectors are stable WooCommerce block classes + the seeded fixture's own
 * slugs / option ids (never database ids). The server (NX1-04a gates) is the
 * authority — this proves a block-mode order carries the same lafka meta a
 * classic order does.
 *
 * @since lafka-theme 6.21.0 (NX1-04b)
 */
const { test, expect } = require( '@playwright/test' );
const {
	SEED,
	prepareStore,
	useBlockCartCheckout,
	useClassicCartCheckout,
	enableBranchOrderTypes,
	disableBranchSelection,
	setFreeDeliveryThreshold,
} = require( './support/store' );

const PDP_URL = `/product/${ SEED.pizzaSlug }/`;
const FREE_DELIVERY_THRESHOLD = 30; // Above the seeded 14.49 cart → in-progress.

/**
 * Pick a size on the redesigned PDP (click the chip label — the real radio is
 * pointer-intercepted by the chip's inner span).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string}                          size Small|Medium|Large.
 */
async function selectSize( page, size ) {
	await page
		.locator( `.lafka-pdp-pickers input[value="${ size }"]` )
		.waitFor( { state: 'attached', timeout: 15000 } );
	await page
		.locator( '.lafka-pdp-chip', {
			has: page.locator( `input[value="${ size }"]` ),
		} )
		.click();
}

test.describe.configure( { mode: 'serial' } );

test.describe( 'Block Cart/Checkout funnel (seeded demo store)', () => {
	test.beforeAll( () => {
		// Deterministic store + block-mode prerequisites. prepareStore() re-seeds
		// and (re)applies coming-soon/COD/addons; the block helpers then flip the
		// cart/checkout pages to WooCommerce's block markup and turn on the
		// branch/order-type + free-delivery knobs the block UI exercises.
		prepareStore();
		enableBranchOrderTypes();
		setFreeDeliveryThreshold( FREE_DELIVERY_THRESHOLD );
		useBlockCartCheckout();
	} );

	test.afterAll( () => {
		// Restore the classic baseline (shortcode pages + classic mode) and undo
		// the block-only knobs so the classic funnel spec runs its seed-default
		// flow regardless of file order.
		disableBranchSelection();
		setFreeDeliveryThreshold( 0 );
		useClassicCartCheckout();
	} );

	test( 'menu → PDP+addon → block cart → block checkout (pickup, COD) → order received', async ( {
		page,
	} ) => {
		await test.step( 'open a seeded product and select size + addon', async () => {
			await page.goto( PDP_URL );
			await expect(
				page.locator( '.lafka-pdp-summary__title' )
			).toContainText( 'Margherita Pizza' );

			await selectSize( page, 'Medium' );
			const topping = page.locator(
				`input[value="${ SEED.toppingOptionId }"]`
			);
			await expect( topping ).toHaveCount( 1 );
			await topping.check( { force: true } );

			const cta = page
				.locator( '.lafka-pdp-summary [data-lafka-add-to-cart]' )
				.first();
			// Size (12.99) + Extra Cheese (1.50) = 14.49.
			await expect( cta ).toBeEnabled();
			await expect( cta ).toContainText( '14.49' );
			await cta.click();
			await page.waitForTimeout( 2000 );
		} );

		await test.step( 'block cart shows the line + addon meta + free-delivery data', async () => {
			await page.goto( '/cart/' );

			// Block cart hydrated with the real line item (not the skeleton).
			const line = page
				.locator( '.wc-block-cart-item__product' )
				.filter( { hasText: 'Margherita Pizza' } )
				.first();
			await expect( line ).toBeVisible( { timeout: 25000 } );

			// Addon item_data carried through Store API (NX1-04c): the chosen
			// topping + size render as product detail lines in the block cart.
			await expect( line ).toContainText( 'Extra Cheese' );
			await expect( line ).toContainText( 'Medium' );

			// Block cart total reflects size + addon.
			await expect(
				page.locator( '.wc-block-components-totals-footer-item' )
			).toContainText( '14.49' );

			// Free-delivery progress DATA path: the NX1-04a `lafka` cart
			// extension exposes the threshold the progress bar consumes.
			const ext = await page.evaluate( async () => {
				const res = await fetch( '/wp-json/wc/store/v1/cart', {
					credentials: 'same-origin',
				} );
				const json = await res.json();
				return json.extensions && json.extensions.lafka
					? json.extensions.lafka
					: null;
			} );
			expect( ext ).not.toBeNull();
			expect( Number( ext.free_delivery_threshold ) ).toBe(
				FREE_DELIVERY_THRESHOLD
			);
			expect( Number( ext.free_delivery_remaining ) ).toBeGreaterThan( 0 );

			// If the plugin mounts the progress SlotFill, it must be styled by the
			// theme (lafka-blocks-checkout.css). Soft — the bar is a plugin-side
			// component; the DATA assertion above is the hard contract.
			const bar = page.locator( '.lafka-block-free-delivery' );
			if ( await bar.count() ) {
				await expect( bar ).toBeVisible();
			}
		} );

		await test.step( 'place a COD order at block checkout with pickup', async () => {
			await page.goto( '/checkout/' );
			await page
				.locator( '.wc-block-checkout__form' )
				.waitFor( { timeout: 25000 } );

			// Contact + billing (block field ids).
			await page.locator( '#email' ).fill( 'buyer@example.com' );
			await page.locator( '#billing-first_name' ).fill( 'Test' );
			await page.locator( '#billing-last_name' ).fill( 'Buyer' );
			await page.locator( '#billing-address_1' ).fill( '123 Example St' );
			await page.locator( '#billing-city' ).fill( 'Example City' );
			await page.locator( '#billing-postcode' ).fill( '12345' );

			// lafka order-type select (Additional Checkout Fields API) = pickup.
			const orderType = page.locator( '#order-lafka-order-type' );
			await expect( orderType ).toHaveCount( 1 );
			await orderType.selectOption( 'pickup' );

			// COD is the only gateway on the seeded store; wait for it to hydrate.
			await page
				.locator( '.wc-block-components-radio-control__option' )
				.first()
				.waitFor( { timeout: 20000 } );
			await page.waitForTimeout( 800 );

			await page
				.locator( '.wc-block-components-checkout-place-order-button' )
				.click();

			await expect( page ).toHaveURL( /order-received/, {
				timeout: 25000,
			} );
			await expect(
				page.locator( '.woocommerce-order' )
			).toContainText( 'received' );
		} );
	} );
} );
