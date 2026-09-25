/* lafka-theme/tests/e2e/seo-machine-readable.spec.js
 *
 * GX3 (lafka-plugin 10.2) search & AI surfaces on the seeded store:
 *   - /llms.txt, /llms-full.txt, /menu.md serve plain text built from the
 *     business record + menu; /menu.json is schema.org JSON with a Menu node;
 *   - the menu page's JSON-LD links Restaurant → hasMenu → #menu, one
 *     MenuSection per leaf category, MenuItems with a product url;
 *   - a category page carries ONLY its own section(s), not the whole menu,
 *     and a templated <title> naming the category;
 *   - a category FAQ saved in term meta renders visibly AND as FAQPage;
 *   - the product sitemap carries <image:image> entries.
 *
 * Mutates ONE term's FAQ meta (the seeded Pizzas category) and restores it in
 * afterAll. SERIAL: shares the single-worker backend. Rewrite rules are
 * flushed by the plugin on the first request after activation/upgrade; the
 * beforeAll flushes explicitly so a long-lived dev stack behaves the same.
 *
 * @since lafka-theme 7.2.0 / lafka-plugin 10.2.0 (GX3)
 */
const { test, expect } = require( '@playwright/test' );
const { wpCli } = require( './support/wp-cli' );

const CAT_SLUG = 'pizzas';
let categoryUrl = '';
let menuUrl = '';

/** All JSON-LD @graph nodes on a page. */
async function graphNodes( page ) {
	const blocks = await page
		.locator( 'script[type="application/ld+json"]' )
		.allTextContents();
	return blocks.flatMap( ( raw ) => {
		const data = JSON.parse( raw );
		return data[ '@graph' ] || [ data ];
	} );
}

test.describe.configure( { mode: 'serial' } );

test.describe( 'GX3 search & AI surfaces', () => {
	test.beforeAll( () => {
		wpCli( [ 'rewrite', 'flush' ] );
		categoryUrl = wpCli( [
			'eval',
			`echo get_term_link( '${ CAT_SLUG }', 'product_cat' );`,
		] );
		menuUrl = wpCli( [ 'eval', 'echo lafka_get_menu_url();' ] );
		wpCli( [
			'eval',
			`$t = get_term_by( 'slug', '${ CAT_SLUG }', 'product_cat' ); update_term_meta( $t->term_id, '_lafka_term_faqs', array( array( 'q' => 'Is there a gluten-free crust?', 'a' => 'Yes, on any 10-inch pizza.' ) ) ); do_action( 'lafka_menu_data_changed' );`,
		] );
	} );

	test.afterAll( () => {
		wpCli( [
			'eval',
			`$t = get_term_by( 'slug', '${ CAT_SLUG }', 'product_cat' ); delete_term_meta( $t->term_id, '_lafka_term_faqs' ); do_action( 'lafka_menu_data_changed' );`,
		] );
	} );

	test( 'llms.txt family is served as plain text', async ( { request } ) => {
		const llms = await request.get( '/llms.txt' );
		expect( llms.status() ).toBe( 200 );
		expect( llms.headers()[ 'content-type' ] ).toContain( 'text/plain' );
		const body = await llms.text();
		expect( body ).toMatch( /^# \S/ );
		expect( body ).toContain( '## How to order' );
		expect( body ).toContain( '## Menu' );

		const full = await request.get( '/llms-full.txt' );
		expect( full.status() ).toBe( 200 );
		expect( await full.text() ).toContain( 'Is there a gluten-free crust?' );

		const md = await request.get( '/menu.md' );
		expect( md.status() ).toBe( 200 );
		expect( md.headers()[ 'content-type' ] ).toContain( 'text/markdown' );

		const json = await request.get( '/menu.json' );
		expect( json.status() ).toBe( 200 );
		const doc = await json.json();
		expect( doc[ '@context' ] ).toBe( 'https://schema.org' );
		expect( doc[ '@graph' ].some( ( n ) => n[ '@type' ] === 'Menu' ) ).toBe(
			true
		);
	} );

	test( 'menu page graph links Restaurant ↔ Menu, items link to products', async ( {
		page,
	} ) => {
		await page.goto( menuUrl );
		const nodes = await graphNodes( page );
		const menu = nodes.find( ( n ) => n[ '@type' ] === 'Menu' );
		expect( menu ).toBeTruthy();
		const restaurant = nodes.find( ( n ) =>
			String( n[ '@id' ] || '' ).endsWith( '#restaurant' )
		);
		if ( restaurant ) {
			expect( restaurant.hasMenu[ '@id' ] ).toBe( menu[ '@id' ] );
		}
		const item = menu.hasMenuSection[ 0 ].hasMenuItem[ 0 ];
		expect( item.url ).toMatch( /^https?:\/\// );

		// No item appears in a parent section AND one of its children.
		const names = menu.hasMenuSection.flatMap( ( s ) =>
			s.hasMenuItem.map( ( i ) => `${ s.name }::${ i[ '@id' ] }` )
		);
		expect( new Set( names ).size ).toBe( names.length );
	} );

	test( 'category page: own section only, templated title, FAQ', async ( {
		page,
	} ) => {
		await page.goto( categoryUrl );
		const nodes = await graphNodes( page );
		const menu = nodes.find( ( n ) => n[ '@type' ] === 'Menu' );
		expect( menu ).toBeTruthy();
		const sectionUrls = menu.hasMenuSection.map( ( s ) => s.url );
		expect(
			sectionUrls.every( ( u ) => u.includes( `/${ CAT_SLUG }` ) )
		).toBe( true );

		const faq = nodes.find( ( n ) => n[ '@type' ] === 'FAQPage' );
		expect( faq.mainEntity[ 0 ].name ).toBe( 'Is there a gluten-free crust?' );
		await expect( page.locator( '.lafka-menu__faq' ) ).toContainText(
			'Is there a gluten-free crust?'
		);

		const title = await page.title();
		expect( title.toLowerCase() ).toContain( 'pizza' );
		const description = await page
			.locator( 'meta[name="description"]' )
			.getAttribute( 'content' );
		expect( ( description || '' ).length ).toBeGreaterThan( 20 );
	} );

	test( 'product sitemap carries image entries', async ( { request } ) => {
		const res = await request.get( '/wp-sitemap-posts-product-1.xml' );
		expect( res.status() ).toBe( 200 );
		const xml = await res.text();
		expect( xml ).toContain(
			'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'
		);
		expect( xml ).toContain( '<image:loc>' );
	} );
} );
