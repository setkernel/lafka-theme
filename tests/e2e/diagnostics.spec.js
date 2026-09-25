/* lafka-theme/tests/e2e/diagnostics.spec.js
 *
 * GX1 Diagnostics, end to end against the seeded store (lafka-plugin 10.2+):
 *   1. Store API responses carry the per-request `X-Lafka-Request-Id` header.
 *   2. A refusal is recorded through `lafka_checkout_blocked`: with the store
 *      forced closed (+ add-to-cart disabled), a Store API add-to-cart is
 *      rejected with `lafka_store_closed` and today's `store_closed` counter
 *      in `lafka_log_checkout_stats` goes up.
 *   3. Lafka → Diagnostics: "Send test error" records an incident that is
 *      listed on the Incidents tab, and the Checkout failures tab lists the
 *      `store_closed` refusal from step 2.
 *
 * SERIAL + self-restoring: it closes the store (global state) and reopens it
 * in afterAll, like store-closed.spec.js.
 *
 * @since lafka-theme 7.2.0 (GX1)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED, forceStoreClosed, restoreStoreOpen } = require( './support/store' );
const { wpCli } = require( './support/wp-cli' );

test.describe.configure( { mode: 'serial' } );

/**
 * Today's counters from the option (keys are site-local dates; take the newest).
 *
 * @return {Object<string, number>} reason → count.
 */
function todaysCounters() {
	let raw = '';
	try {
		raw = wpCli( [ 'option', 'get', 'lafka_log_checkout_stats', '--format=json' ] );
	} catch {
		// Option not written yet.
		return {};
	}
	const stats = JSON.parse( raw || '{}' );
	const days = Object.keys( stats ).sort();
	return days.length ? stats[ days[ days.length - 1 ] ] : {};
}

test.describe( 'Diagnostics (GX1)', () => {
	test.afterAll( () => {
		restoreStoreOpen();
	} );

	test( 'Store API responses carry X-Lafka-Request-Id', async ( { request } ) => {
		const res = await request.get( '/wp-json/wc/store/v1/cart' );

		expect( res.ok() ).toBeTruthy();
		expect( res.headers()[ 'x-lafka-request-id' ] ).toMatch( /^[a-f0-9]{16}$/ );
	} );

	test( 'a closed-store add-to-cart is recorded as store_closed', async ( { request } ) => {
		const before = todaysCounters().store_closed || 0;
		forceStoreClosed();

		const cart = await request.get( '/wp-json/wc/store/v1/cart' );
		const nonce = cart.headers().nonce;
		const productId = Number(
			wpCli( [ 'post', 'list', '--post_type=product', `--name=${ SEED.simpleSlug }`, '--field=ID' ] ).trim()
		);

		const res = await request.post( '/wp-json/wc/store/v1/cart/add-item', {
			headers: { Nonce: nonce },
			data: { id: productId, quantity: 1 },
		} );

		expect( res.status() ).toBe( 409 );
		expect( ( await res.json() ).code ).toBe( 'lafka_store_closed' );
		expect( todaysCounters().store_closed || 0 ).toBeGreaterThan( before );
	} );

	test( 'Lafka → Diagnostics lists a test incident and the checkout refusal', async ( { page } ) => {
		await page.goto( '/wp-login.php' );
		await page.fill( '#user_login', 'admin' );
		await page.fill( '#user_pass', 'password' );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );

		await page.goto( '/wp-admin/admin.php?page=lafka-diagnostics' );
		await expect( page.getByRole( 'heading', { name: 'Lafka Diagnostics' } ) ).toBeVisible();

		await page.getByRole( 'link', { name: 'Send test error' } ).click();
		await expect( page.locator( '.notice-success' ) ).toContainText( 'Test incident recorded' );
		await expect( page.locator( 'table.widefat' ) ).toContainText( 'Diagnostics test incident' );

		await page.goto( '/wp-admin/admin.php?page=lafka-diagnostics&tab=checkout' );
		await expect( page.locator( 'table.widefat' ).first() ).toContainText( 'store_closed' );
	} );
} );
