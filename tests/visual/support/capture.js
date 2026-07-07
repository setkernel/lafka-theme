/* lafka-theme/tests/visual/support/capture.js
 *
 * Shared full-page capture helpers for the visual harness. Extracted so the
 * NX1-10a monolith-teardown spec (tests/visual/nx1-10a.spec.js) can reuse the
 * exact settle-and-shoot behaviour the NX1-02 parity gate established, without
 * editing the pristine NX1-02 spec. Same three product breakpoints, same font +
 * lazy-image settling, same frozen animations.
 *
 * @since lafka-theme 6.21.0 (NX1-10a monolith teardown safety net)
 */
const { expect } = require( '@playwright/test' );

/** The three product breakpoints every visual ship is verified at. */
const BREAKPOINTS = [ 375, 768, 1280 ];
const HEIGHTS = { 375: 812, 768: 1024, 1280: 900 };

/**
 * Settle the page so a full-page capture is deterministic: wait for web fonts,
 * force any lazy-loaded imagery to fetch by scrolling the full height, then
 * return to the top and let the network go idle.
 *
 * @param {import('@playwright/test').Page} page
 */
async function stabilize( page ) {
	await page.evaluate( async () => {
		if ( document.fonts && document.fonts.ready ) {
			await document.fonts.ready;
		}
		await new Promise( ( resolve ) => {
			const total = document.body.scrollHeight;
			let y = 0;
			const step = () => {
				y += Math.max( 400, window.innerHeight );
				window.scrollTo( 0, y );
				if ( y < total ) {
					setTimeout( step, 40 );
				} else {
					window.scrollTo( 0, 0 );
					setTimeout( resolve, 120 );
				}
			};
			step();
		} );
	} );
	await page.waitForLoadState( 'networkidle' ).catch( () => {} );
}

/**
 * Capture one surface at all three breakpoints as `${name}-${width}.png`.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string}                          name       Golden basename.
 * @param {string[]}                        maskSelectors CSS selectors to blank.
 */
async function shootAllBreakpoints( page, name, maskSelectors ) {
	const mask = ( maskSelectors || [] ).map( ( sel ) => page.locator( sel ) );
	for ( const width of BREAKPOINTS ) {
		await page.setViewportSize( { width, height: HEIGHTS[ width ] } );
		await stabilize( page );
		await expect( page ).toHaveScreenshot( `${ name }-${ width }.png`, {
			fullPage: true,
			mask,
			animations: 'disabled',
		} );
	}
}

module.exports = { BREAKPOINTS, HEIGHTS, stabilize, shootAllBreakpoints };
