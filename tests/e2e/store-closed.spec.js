/* lafka-theme/tests/e2e/store-closed.spec.js
 *
 * Order-hours closure gate on the PDP. Forces the shop closed (order-hours
 * force-override + disable-add-to-cart) and asserts the redesigned PDP swaps its
 * buy box for the plugin's closed-store card and offers no way to add to cart,
 * then restores the open state.
 *
 * SERIAL by design and self-restoring: it mutates GLOBAL store state (the shop's
 * open/closed status), so it must not run while the funnel spec expects an open
 * store. The suite also runs single-worker (see playwright.config.js), but the
 * afterAll restore is the belt-and-suspenders guarantee regardless of order.
 *
 * The asserted text is the plugin's exact default closed-store title
 * ("Closed right now", incl/order-hours/Lafka_Order_Hours.php) — the seeded
 * fixture sets no operator override message.
 *
 * @since lafka-theme 6.20.0 (NX1-09b)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED, forceStoreClosed, restoreStoreOpen } = require( './support/store' );

const PDP_URL = `/product/${ SEED.pizzaSlug }/`;

test.describe.configure( { mode: 'serial' } );

test.describe( 'Store-closed gate (PDP)', () => {
	test.beforeAll( () => {
		forceStoreClosed();
	} );

	test.afterAll( () => {
		restoreStoreOpen();
	} );

	test( 'closed store blocks add-to-cart and shows the closed-store card', async ( {
		page,
	} ) => {
		await page.goto( PDP_URL );

		// Buy box replaced by the closed-store card…
		const card = page.locator( '.lafka-store-closed-card' );
		await expect( card ).toBeVisible();
		await expect(
			card.locator( '.lafka-store-closed-card__title' )
		).toContainText( 'Closed right now' );

		// …and there is no add-to-cart control to submit.
		await expect(
			page.locator( '[data-lafka-add-to-cart]' )
		).toHaveCount( 0 );
		await expect( page.locator( 'form.variations_form' ) ).toHaveCount( 0 );
	} );

	test( 'reopening the store restores the add-to-cart buy box', async ( {
		page,
	} ) => {
		// Prove the gate is reversible (and leave the store open for any
		// following work). This runs after the closed assertions above.
		restoreStoreOpen();
		await page.goto( PDP_URL );
		await expect(
			page.locator( '.lafka-pdp-summary [data-lafka-add-to-cart]' ).first()
		).toBeVisible();
		await expect( page.locator( '.lafka-store-closed-card' ) ).toHaveCount(
			0
		);
	} );
} );
