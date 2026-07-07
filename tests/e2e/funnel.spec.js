/* lafka-theme/tests/e2e/funnel.spec.js
 *
 * The conversion money-path, end to end, against the seeded demo store
 * (`wp lafka seed-demo`, NX1-09a): menu browse → PDP with a variation + addon →
 * cart drawer → cart page → classic COD checkout → order received. This is the
 * @smoke test the CI job runs on every PR.
 *
 * A few focused PDP regressions (CTA gating, live price, mobile sticky CTA) ride
 * along as non-smoke tests — they replace the retired peppery-specific
 * pdp-flow.spec.js with assertions keyed to the deterministic seeded fixture.
 *
 * Selectors are stable classes / data-attributes from the theme partials and
 * the seeded fixture's own slugs + option ids (never database ids). Asserted
 * text is read from the seeded fixture (product names, prices), not from
 * translatable UI chrome, so an i18n string change can't flake the suite.
 *
 * @since lafka-theme 6.20.0 (NX1-09b)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );

const PDP_URL = `/product/${ SEED.pizzaSlug }/`;

/**
 * Pick a size on the redesigned PDP. The real radio input is visually covered
 * by the chip's inner span, so we click the chip LABEL (clicking the input is
 * pointer-intercepted).
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

test.describe( 'Conversion funnel (seeded demo store)', () => {
	test( '@smoke menu → PDP+addon → drawer → cart → COD checkout → order received', async ( {
		page,
	} ) => {
		await test.step( 'menu renders seeded categories with single category nav', async () => {
			await page.goto( '/menu/' );
			// Single canonical category nav (V3): filter pills present, the
			// duplicate "Jump to" TOC absent.
			await expect( page.locator( '.lafka-menu__cats' ).first() ).toBeVisible();
			await expect( page.locator( '.lafka-menu__toc' ) ).toHaveCount( 0 );
			const chips = page.locator( '.lafka-menu__cat-chip' );
			await expect( chips.filter( { hasText: 'Pizzas' } ) ).toHaveCount( 1 );
			for ( const cat of SEED.categories ) {
				await expect(
					chips.filter( { hasText: cat } )
				).toHaveCount( 1 );
			}
		} );

		await test.step( 'open a seeded product with addons and select size + addon', async () => {
			await page.goto( PDP_URL );
			await expect(
				page.locator( '.lafka-pdp-summary__title' )
			).toContainText( 'Margherita Pizza' );

			const cta = page
				.locator( '.lafka-pdp-summary [data-lafka-add-to-cart]' )
				.first();
			await expect( cta ).toBeDisabled();

			await selectSize( page, 'Medium' );
			// Addon module renders on the seeded pizza (category-assigned group).
			const topping = page.locator(
				`input[value="${ SEED.toppingOptionId }"]`
			);
			await expect( topping ).toHaveCount( 1 );
			// The addon input is custom-styled/overlaid — force the check.
			await topping.check( { force: true } );

			// Size (12.99) + Extra Cheese (1.50) = 14.49 reflected on the CTA.
			await expect( cta ).toBeEnabled();
			await expect( cta ).toContainText( '14.49' );
			await cta.click();
		} );

		await test.step( 'cart drawer opens with the correct line + total', async () => {
			const drawer = page.locator( '.lafka-cart-drawer' );
			await expect( drawer ).toHaveAttribute( 'data-open', 'true', {
				timeout: 6000,
			} );
			await expect( drawer ).toHaveAttribute( 'aria-modal', 'true' );
			await expect(
				drawer.locator( '.lafka-cart-drawer__items li' ).first()
			).toContainText( 'Margherita Pizza' );
			await expect(
				drawer.locator( '.lafka-cart-drawer__subtotal' )
			).toContainText( '14.49' );
		} );

		await test.step( 'proceed to the cart page', async () => {
			await page.goto( '/cart/' );
			await expect(
				page.locator( '.woocommerce-cart-form' )
			).toBeVisible();
			await expect( page.getByText( 'Margherita Pizza' ).first() ).toBeVisible();
			const proceed = page.locator(
				'.wc-proceed-to-checkout a.checkout-button'
			);
			await expect( proceed ).toBeVisible();
		} );

		await test.step( 'place a COD order at classic checkout', async () => {
			await page.goto( '/checkout/' );
			// Robust to whichever renders: classic shortcode is expected here
			// (the block-cart shim / prepareStore() serve it), but assert the
			// classic form is present before driving it.
			const form = page.locator( 'form.checkout' );
			await expect( form ).toBeVisible( { timeout: 15000 } );

			await page.fill( '#billing_first_name', 'Test' );
			await page.fill( '#billing_last_name', 'Buyer' );
			await page.fill( '#billing_address_1', '123 Example St' );
			await page.fill( '#billing_city', 'Example City' );
			await page.fill( '#billing_postcode', '12345' );
			await page.fill( '#billing_phone', '5555550100' );
			await page.fill( '#billing_email', 'buyer@example.com' );
			// Country/state are select2-enhanced; set the underlying <select>
			// value and fire the change WC listens for.
			await page.evaluate( () => {
				const set = ( id, val ) => {
					const s = document.getElementById( id );
					if ( ! s ) {
						return;
					}
					s.value = val;
					s.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					if ( window.jQuery ) {
						window.jQuery( s ).trigger( 'change' );
					}
				};
				set( 'billing_country', 'US' );
				set( 'billing_state', 'CA' );
			} );

			// COD selected + place the order. The COD radio is the only gateway
			// on the seeded store, so WC pre-checks it and hides the input — assert
			// it is present + checked (not visible) and force-select defensively.
			const cod = page.locator( '#payment_method_cod' );
			await expect( cod ).toBeChecked();
			await cod.check( { force: true } );
			await page.locator( '#place_order' ).click();

			await expect( page ).toHaveURL( /order-received/, { timeout: 20000 } );
			await expect(
				page.locator( '.woocommerce-thankyou-order-received' )
			).toContainText( 'order has been received' );
		} );
	} );

	test( 'PDP add-to-cart CTA is gated on choosing a size', async ( {
		page,
	} ) => {
		await page.goto( PDP_URL );
		const cta = page
			.locator( '.lafka-pdp-summary [data-lafka-add-to-cart]' )
			.first();
		await expect( cta ).toBeDisabled();
		await selectSize( page, 'Medium' );
		await expect( cta ).toBeEnabled();
	} );

	test( 'PDP live price updates on size change', async ( { page } ) => {
		await page.goto( PDP_URL );
		const price = page.locator( '[data-lafka-live-price]' ).first();
		await selectSize( page, 'Small' );
		await expect( price ).toContainText( SEED.pizzaPrices.Small );
		await selectSize( page, 'Large' );
		await expect( price ).toContainText( SEED.pizzaPrices.Large );
	} );

	test( 'PDP mobile sticky CTA is visible on small viewports', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.goto( PDP_URL );
		await selectSize( page, 'Medium' );
		const stickyCta = page.locator( '.lafka-pdp-mobile-cta' );
		await expect( stickyCta ).toBeVisible();
	} );

	// NX1-02.dyncss-typography-backgrounds moved the font-enqueue readers
	// (body_font / headings_font / google_subsets) off the legacy Options
	// Framework onto `lafka_<key>` theme_mods. On the shipped defaults all three
	// resolve to Rubik + latin; Rubik is self-hosted (@font-face in style.css) so
	// lafka_typography_enqueue_google_font() strips it and requests NOTHING from
	// the Google Fonts CDN. This guards that the migrated readers keep yielding
	// that Rubik-only result — a migration bug that returned a different (real
	// Google) face would surface as a fonts.googleapis.com request here.
	test( 'default typography self-hosts Rubik — no Google Fonts CDN request', async ( {
		page,
	} ) => {
		const googleFontRequests = [];
		page.on( 'request', ( req ) => {
			if ( req.url().includes( 'fonts.googleapis.com' ) ) {
				googleFontRequests.push( req.url() );
			}
		} );
		await page.goto( '/' );
		await page.waitForLoadState( 'networkidle' );
		expect( googleFontRequests ).toEqual( [] );
		// The enqueue short-circuits before wp_enqueue_style for a Rubik-only
		// request, so there is no lafka-fonts stylesheet link at all.
		await expect( page.locator( 'link#lafka-fonts-css' ) ).toHaveCount( 0 );
	} );
} );
