/* lafka-theme/tests/e2e/consent-banner-sticky-bars.spec.js
 *
 * Mobile fixed-bottom bars must sit ABOVE lafka-plugin's cookie-consent banner.
 *
 * The banner is fixed to the viewport bottom (z-index 99998); on phones it used
 * to cover the PDP's sticky "Add to cart" bar and the global sticky cart bar.
 * Contract: while the banner is visible the plugin sets
 * `--lafka-consent-banner-h` on <html> to the banner's rendered height and adds
 * `html.lafka-consent-open`; on dismiss it resets the property to 0px and drops
 * the class. The theme offsets every fixed-bottom bar by that property
 * (CSS lock: tests/Unit/ConsentBannerOffsetCssTest.php).
 *
 * The banner only renders when an analytics destination is configured, so the
 * spec sets a placeholder GA4 id for its duration (restored afterwards) and
 * aborts the tag-manager request so the run stays hermetic. Each test gets a
 * fresh browser context → no stored consent → the banner shows.
 *
 * SERIAL + self-restoring: it mutates a store-level theme_mod.
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );
const { wpCli } = require( './support/wp-cli' );

const GA4_MOD = 'lafka_ga4_measurement_id';
let previousGa4 = '';

test.describe.configure( { mode: 'serial' } );

test.use( { viewport: { width: 375, height: 812 }, hasTouch: true } );

/**
 * Visible consent banner + the bar under test, both laid out.
 *
 * @param {import('@playwright/test').Page} page
 * @return {Promise<import('@playwright/test').Locator>} The banner locator.
 */
async function expectBannerOpen( page ) {
	const banner = page.locator( '#lafka-consent-banner' );
	await expect( banner ).toBeVisible();
	await expect( page.locator( 'html' ) ).toHaveClass( /\blafka-consent-open\b/ );
	// The plugin publishes the banner's real height.
	await expect
		.poll( () =>
			page.evaluate( () =>
				parseFloat(
					getComputedStyle( document.documentElement ).getPropertyValue(
						'--lafka-consent-banner-h'
					)
				) || 0
			)
		)
		.toBeGreaterThan( 0 );
	return banner;
}

/**
 * Assert `bar` ends at or above the banner's top edge (0.5px sub-pixel slack).
 *
 * @param {import('@playwright/test').Locator} bar
 * @param {import('@playwright/test').Locator} banner
 */
async function expectBarAboveBanner( bar, banner ) {
	await expect
		.poll( async () => {
			const b = await bar.boundingBox();
			const c = await banner.boundingBox();
			return b && c ? c.y - ( b.y + b.height ) : -Infinity;
		} )
		.toBeGreaterThanOrEqual( -0.5 );
}

test.describe( 'Consent banner vs mobile sticky bars (375px)', () => {
	test.beforeAll( () => {
		try {
			previousGa4 = wpCli( [ 'theme', 'mod', 'get', GA4_MOD ] );
		} catch {
			previousGa4 = '';
		}
		wpCli( [ 'theme', 'mod', 'set', GA4_MOD, 'G-E2ECONSENT1' ] );
	} );

	test.afterAll( () => {
		if ( previousGa4 ) {
			wpCli( [ 'theme', 'mod', 'set', GA4_MOD, previousGa4 ] );
		} else {
			wpCli( [ 'theme', 'mod', 'remove', GA4_MOD ] );
		}
	} );

	test.beforeEach( async ( { page } ) => {
		await page.route( /googletagmanager\.com|google-analytics\.com/, ( route ) =>
			route.abort()
		);
	} );

	test( 'PDP sticky add-to-cart bar sits above the banner, then drops back on dismiss', async ( {
		page,
	} ) => {
		await page.goto( `/product/${ SEED.simpleSlug }/` );
		const banner = await expectBannerOpen( page );

		const bar = page.locator( '.lafka-pdp-mobile-cta' ).first();
		await expect( bar ).toBeVisible();
		await expectBarAboveBanner( bar, banner );

		// The CTA stays a full tap target and is actually hittable (not under
		// the banner): a trial click fails if another element would receive it.
		const cta = bar.locator( '[data-lafka-add-to-cart]' );
		const ctaBox = await cta.boundingBox();
		expect( ctaBox.height ).toBeGreaterThanOrEqual( 44 );
		await cta.click( { trial: true } );

		// Dismiss → contract resets → bar returns to the viewport bottom.
		await page.locator( '[data-lafka-consent="reject"]' ).click();
		await expect( banner ).toBeHidden();
		await expect( page.locator( 'html' ) ).not.toHaveClass( /\blafka-consent-open\b/ );
		const viewport = page.viewportSize();
		await expect
			.poll( async () => {
				const b = await bar.boundingBox();
				return b ? Math.round( b.y + b.height ) : 0;
			} )
			.toBe( viewport.height );
	} );

	test( 'global sticky cart bar sits above the banner', async ( { page } ) => {
		// Put a line in the cart (simple product adds straight from the PDP).
		// At 375px the in-summary button is hidden; the mobile bar's CTA is the
		// one to tap — and it is only tappable because it clears the banner.
		await page.goto( `/product/${ SEED.simpleSlug }/` );
		await page
			.locator( '.lafka-pdp-mobile-cta [data-lafka-add-to-cart]' )
			.first()
			.click();
		await expect( page.locator( '.lafka-cart-drawer' ) ).toHaveAttribute(
			'data-open',
			'true',
			{ timeout: 6000 }
		);

		// Any non-PDP, non-cart page shows the sticky cart bar.
		await page.goto( '/' );
		const banner = await expectBannerOpen( page );
		const bar = page.locator( '[data-lafka-sticky-cart]' );
		await expect( bar ).toBeVisible();
		await expectBarAboveBanner( bar, banner );
		await bar.locator( '.lafka-sticky-cart__link' ).click( { trial: true } );
	} );
} );
