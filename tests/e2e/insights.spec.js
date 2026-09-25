/* lafka-theme/tests/e2e/insights.spec.js
 *
 * Lafka Insights (lafka-plugin GX2) end to end on the seeded demo store:
 *
 *   - every page sends exactly ONE beacon to POST /wp-json/lafka/v1/i, on
 *     pagehide, and the endpoint answers 204;
 *   - the money path is recorded server-side (add to cart → cart → checkout)
 *     on the same cookieless visit the page beacons describe;
 *   - a cross-origin POST is refused.
 *
 * SERIAL and self-restoring: it switches the plugin's `insights` module on for
 * this file (which installs the tables through the plugin's own option hook)
 * and back off afterwards, and empties today's Insights rows so its counts are
 * deterministic. Visit ids come from IP + browser family (no cookie), so the
 * tests' separate browser contexts still land on one visit row.
 *
 * Needs a lafka-plugin build that ships the Insights module (GX2); it is
 * deliberately NOT tagged @smoke so the PR smoke job (which may run against the
 * last released plugin) is not coupled to an unreleased plugin feature.
 *
 * @since lafka-theme 7.2.0 (GX2)
 */
const { test, expect } = require( '@playwright/test' );
const { SEED } = require( './support/store' );
const { wpCli } = require( './support/wp-cli' );

const BEACON = '/wp-json/lafka/v1/i';

test.describe.configure( { mode: 'serial' } );

/**
 * Flip the plugin's insights module flag in the `lafka` option.
 *
 * @param {'enabled'|'disabled'} state Flag value.
 */
function setInsights( state ) {
	wpCli( [ 'eval', `$o=(array)get_option("lafka");$o["insights"]="${ state }";update_option("lafka",$o);` ] );
}

/** Empty both Insights tables (test isolation). */
function clearInsights() {
	wpCli( [
		'eval',
		'global $wpdb; $wpdb->query("DELETE FROM {$wpdb->prefix}lafka_insights_sessions"); $wpdb->query("DELETE FROM {$wpdb->prefix}lafka_insights_daily");',
	] );
}

/** Today's visit rows. @return {Array<{stages:string,source_type:string,pageviews:string}>} */
function visitsToday() {
	return JSON.parse(
		wpCli( [
			'eval',
			'global $wpdb; echo wp_json_encode($wpdb->get_results($wpdb->prepare("SELECT stages, source_type, pageviews FROM {$wpdb->prefix}lafka_insights_sessions WHERE day = %s", wp_date("Y-m-d")), ARRAY_A));',
		] ) || '[]'
	);
}

/** Post id of a seeded product by slug. */
function productId( slug ) {
	return wpCli( [ 'eval', `$p=get_page_by_path("${ slug }",OBJECT,"product"); echo $p ? $p->ID : 0;` ] );
}

test.describe( 'Insights (first-party funnel analytics)', () => {
	test.beforeAll( () => {
		setInsights( 'enabled' );
		clearInsights();
	} );

	test.afterAll( () => {
		setInsights( 'disabled' );
	} );

	test( 'each page sends one beacon on pagehide and the endpoint answers 204', async ( { page } ) => {
		const beacons = [];
		page.on( 'request', ( req ) => {
			if ( req.method() === 'POST' && req.url().includes( BEACON ) ) {
				beacons.push( req );
			}
		} );

		await page.goto( '/menu/' );
		const response = page.waitForResponse( ( res ) => res.url().includes( BEACON ) );
		await page.goto( `/product/${ SEED.pizzaSlug }/` );
		expect( ( await response ).status() ).toBe( 204 );

		expect( beacons ).toHaveLength( 1 );
		const body = JSON.parse( beacons[ 0 ].postData() || '{}' );
		expect( body.v ).toBe( 1 );
		expect( body.p ).toBe( '/menu/' );
		expect( [ 'm', 't', 'd' ] ).toContain( body.d );

		await expect.poll( () => visitsToday().length ).toBeGreaterThan( 0 );
	} );

	test( 'add to cart → cart → checkout is recorded server-side on the same visit', async ( { page } ) => {
		const id = productId( SEED.simpleSlug );
		expect( Number( id ) ).toBeGreaterThan( 0 );

		await page.goto( `/?add-to-cart=${ id }` );
		await page.goto( '/cart/' );
		await page.goto( '/checkout/' );

		// visit 1 | add 8 | cart 16 | checkout 32
		await expect
			.poll( () => visitsToday().some( ( v ) => ( Number( v.stages ) & 57 ) === 57 ) )
			.toBe( true );
	} );

	test( 'a cross-origin beacon is refused', async ( { request } ) => {
		const res = await request.post( BEACON, {
			headers: { Origin: 'https://evil.example', 'Content-Type': 'text/plain' },
			data: '{"v":1}',
		} );
		expect( res.status() ).toBe( 403 );
	} );
} );
