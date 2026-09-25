/* lafka-theme/tests/e2e/addon-group-toggle.spec.js
 *
 * GX T-19: an add-on group heading is a heading that CONTAINS a disclosure
 * button (lafka-plugin renders it for themes declaring
 * `lafka-addon-group-toggle`), never an <h3 role="button">. The button's
 * aria-expanded follows the collapsed state, Enter / Space work natively, and
 * a click anywhere on the heading bar (caret, summary) still toggles.
 *
 * @since lafka-theme 7.3.0 (GX T-19)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );

test.describe( 'Add-on group toggle @smoke', () => {
	test( 'heading holds a real disclosure button', async ( { page } ) => {
		await page.goto( `/product/${ SEED.pizzaSlug }/` );

		const group = page.locator( '.product-addon' ).filter( { has: page.locator( '.lafka-addon-toggle' ) } ).first();
		const heading = group.locator( 'h3.addon-name' );
		const button = heading.getByRole( 'button' );
		const firstOption = group.locator( 'p.form-row' ).first();

		await expect( heading ).not.toHaveAttribute( 'role', /.*/ );
		await expect( heading ).not.toHaveAttribute( 'tabindex', /.*/ );
		await expect( button ).toHaveAttribute( 'aria-expanded', 'true' );
		const controls = await button.getAttribute( 'aria-controls' );
		await expect( page.locator( `#${ controls }` ) ).toHaveCount( 1 );
		await expect( firstOption ).toBeVisible();

		await button.click();
		await expect( button ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( group ).toHaveAttribute( 'data-collapsed', 'true' );
		await expect( firstOption ).toBeHidden();

		await button.focus();
		await page.keyboard.press( 'Enter' );
		await expect( button ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( firstOption ).toBeVisible();

		await page.keyboard.press( 'Space' );
		await expect( button ).toHaveAttribute( 'aria-expanded', 'false' );

		// The rest of the bar (caret at the right edge) is still a hit area.
		const box = await heading.boundingBox();
		await page.mouse.click( box.x + box.width - 3, box.y + box.height / 2 );
		await expect( button ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( group ).toHaveAttribute( 'data-collapsed', 'false' );
	} );
} );
