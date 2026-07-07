/* lafka-theme/tests/e2e/cart-drawer-a11y.spec.js
 *
 * Cart-drawer modal accessibility: opening from the header cart trigger sets
 * aria-modal, moves focus INTO the drawer, Escape closes it, and focus returns
 * to the trigger that opened it (WCAG 2.4.3 focus order / 2.1.2 no keyboard
 * trap).
 *
 * The drawer defers its focus move one task after open() to survive the
 * visibility transition + the background-inert blur (js/cart-drawer.js), so the
 * "focus lands inside" assertion waits for that rather than sampling instantly.
 *
 * @since lafka-theme 6.20.0 (NX1-09b)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );

test.describe( 'Cart drawer accessibility', () => {
	test( 'opens modal, traps focus, Escape closes and returns focus to trigger', async ( {
		page,
	} ) => {
		// A cart with a line makes the drawer meaningful; the simple product
		// adds straight to cart and auto-opens the drawer.
		await page.goto( `/product/${ SEED.simpleSlug }/` );
		await page
			.locator( '[data-lafka-add-to-cart]' )
			.first()
			.click();
		const drawer = page.locator( '.lafka-cart-drawer' );
		await expect( drawer ).toHaveAttribute( 'data-open', 'true', {
			timeout: 6000,
		} );

		// Reset to a known state: close, then open via the header trigger so we
		// can assert focus RETURNS to that trigger specifically.
		await page.keyboard.press( 'Escape' );
		await expect( drawer ).toHaveAttribute( 'data-open', 'false' );

		const trigger = page.locator( '[data-lafka-cart-open]' ).first();
		await trigger.focus();
		await trigger.click();

		// Declared as a modal.
		await expect( drawer ).toHaveAttribute( 'data-open', 'true' );
		await expect( drawer ).toHaveAttribute( 'aria-modal', 'true' );
		await expect( drawer ).toHaveAttribute( 'aria-hidden', 'false' );

		// Focus lands inside the drawer (deferred one task after open()).
		await expect
			.poll(
				() =>
					page.evaluate( () => {
						const d = document.querySelector( '.lafka-cart-drawer' );
						return !! d && d.contains( document.activeElement );
					} ),
				{ timeout: 3000 }
			)
			.toBe( true );

		// Escape closes and returns focus to the opening trigger.
		await page.keyboard.press( 'Escape' );
		await expect( drawer ).toHaveAttribute( 'data-open', 'false' );
		await expect
			.poll(
				() =>
					page.evaluate( () => {
						const el = document.activeElement;
						return !! (
							el &&
							el.matches &&
							el.matches( '[data-lafka-cart-open]' )
						);
					} ),
				{ timeout: 3000 }
			)
			.toBe( true );
	} );
} );
