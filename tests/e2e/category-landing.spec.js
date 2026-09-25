/* lafka-theme/tests/e2e/category-landing.spec.js
 *
 * GX3 category landing page on the seeded store:
 *   - the term description renders as a `.lafka-menu__intro` block (real
 *     paragraphs, no <p> nested inside the lead <p>);
 *   - product-card images are responsive (srcset + sizes + width/height) with a
 *     non-empty alt, the first card loads eagerly with fetchpriority=high, and
 *     cards past the first row are lazy.
 *
 * Mutates ONE term description (the seeded Pizzas category) and restores the
 * original in afterAll. SERIAL: shares the single-worker backend.
 *
 * @since lafka-theme 7.2.0 (GX3)
 */
const { test, expect } = require( '@playwright/test' );
const { wpCli } = require( './support/wp-cli' );
const { useLayouts, restoreLayouts } = require( './support/store' );

const CAT_SLUG = 'pizzas';
const INTRO =
	'Stone-baked in small batches every day.\n\nEvery pie is cut into eight slices.';

let originalDescription = '';
let categoryUrl = '';

test.describe.configure( { mode: 'serial' } );

test.describe( 'Category landing content + card images', () => {
	test.beforeAll( () => {
		// The classic card grid (GX4: Peppery defaults to counter rows).
		useLayouts( 'classic' );
		originalDescription = wpCli( [
			'term',
			'get',
			'product_cat',
			CAT_SLUG,
			'--by=slug',
			'--field=description',
		] );
		wpCli( [
			'term',
			'update',
			'product_cat',
			CAT_SLUG,
			'--by=slug',
			`--description=${ INTRO }`,
		] );
		categoryUrl = wpCli( [
			'eval',
			`echo get_term_link( '${ CAT_SLUG }', 'product_cat' );`,
		] );
	} );

	test.afterAll( () => {
		restoreLayouts();
		wpCli( [
			'term',
			'update',
			'product_cat',
			CAT_SLUG,
			'--by=slug',
			`--description=${ originalDescription }`,
		] );
	} );

	test( 'term description renders as an intro block above the grid', async ( {
		page,
	} ) => {
		await page.goto( categoryUrl );

		const intro = page.locator( '.lafka-menu__header .lafka-menu__intro' );
		await expect( intro ).toBeVisible();
		await expect( intro.locator( 'p' ) ).toHaveCount( 2 );
		await expect( intro ).toContainText( 'Stone-baked in small batches' );
		// The intro is never wrapped in the one-line lead paragraph.
		await expect( page.locator( 'p.lafka-menu__lead p' ) ).toHaveCount( 0 );
	} );

	test( 'card images are responsive, labelled and prioritised', async ( {
		page,
	} ) => {
		await page.goto( categoryUrl );

		const imgs = page.locator( '.lafka-menu__grid img.lafka-favs__img' );
		const count = await imgs.count();
		expect( count ).toBeGreaterThan( 0 );

		const first = imgs.first();
		await expect( first ).toHaveAttribute( 'srcset', /\S+/ );
		await expect( first ).toHaveAttribute( 'sizes', /\S+/ );
		await expect( first ).toHaveAttribute( 'width', /^\d+$/ );
		await expect( first ).toHaveAttribute( 'height', /^\d+$/ );
		await expect( first ).toHaveAttribute( 'alt', /\S+/ );
		await expect( first ).toHaveAttribute( 'loading', 'eager' );
		await expect( first ).toHaveAttribute( 'fetchpriority', 'high' );

		// Only one card competes for the LCP slot.
		await expect(
			page.locator( '.lafka-menu__grid img[fetchpriority="high"]' )
		).toHaveCount( 1 );

		// Anything past the first row (4 cards) is lazy.
		if ( count > 4 ) {
			await expect( imgs.nth( 4 ) ).toHaveAttribute( 'loading', 'lazy' );
		}
	} );
} );
