/* lafka-theme/tests/visual/support/global-setup.js
 *
 * Global setup for the NX1-02 VISUAL PARITY harness (playwright.visual.config.js).
 * Fails fast if the wp-env target is unreachable, then brings the seeded demo
 * store to the same deterministic, anonymously-browsable state the e2e suite
 * uses (re-seed + coming-soon off + COD + product_addons + classic shortcode
 * cart/checkout pages). Determinism here is what makes the goldens reproducible.
 *
 * This never STARTS wp-env — the umbrella stack owns that lifecycle.
 *
 * @since lafka-theme 6.21.0 (NX1-02 harness)
 */
const { BASE_URL, prepareStore } = require( '../../e2e/support/store' );
const { wpCli } = require( '../../e2e/support/wp-cli' );
const { seedBlog } = require( '../../e2e/support/blog' );

/**
 * Pin the home "Customer favourites" grid to a DETERMINISTIC set. Without
 * featured products the section falls back to a best-sellers query ordered by
 * total_sales (0 for every product on a fresh seed) — MySQL returns those tied
 * rows in an unstable order/selection, so the 8 cards (and therefore the grid's
 * total HEIGHT, since card heights vary by description length) differ run to
 * run, shifting the entire lower page. Marking the first 8 products (stable ID
 * order) featured with an explicit menu_order makes the section's primary query
 * (`featured => true, orderby => menu_order ASC`) return the same ordered set
 * every run. Re-applied after each reseed. (Throwaway env; benign demo state.)
 */
function pinFeaturedProducts() {
	wpCli( [
		'eval',
		'$ids=get_posts(array("post_type"=>"product","post_status"=>"publish","numberposts"=>-1,"fields"=>"ids","orderby"=>"ID","order"=>"ASC"));$i=0;foreach($ids as $id){$p=wc_get_product($id);if(!$p){continue;}$f=$i<8;$p->set_featured($f);if($f){$p->set_menu_order($i);}$p->save();$i++;}',
	] );
}

module.exports = async function globalSetup() {
	let ok = false;
	try {
		const controller = new AbortController();
		const timer = setTimeout( () => controller.abort(), 10000 );
		const res = await fetch( BASE_URL + '/', {
			signal: controller.signal,
			redirect: 'manual',
		} );
		clearTimeout( timer );
		ok = res.status > 0;
	} catch {
		ok = false;
	}
	if ( ! ok ) {
		throw new Error(
			'\n[visual] Cannot reach ' +
				BASE_URL +
				'.\n' +
				'      Start wp-env at the umbrella root first ' +
				'(`npx wp-env start`), or point the suite elsewhere with ' +
				'LAFKA_E2E_BASE_URL=<url>.\n'
		);
	}

	// Deterministic, browsable store state (shared with the e2e harness).
	prepareStore();
	// Deterministic home "Customer favourites" grid (stable height → no shift).
	pinFeaturedProducts();
	// Deterministic blog fixture + static-front-page wiring for the NX1-10a
	// legacy-surface goldens (blog index / archive / search / single+comments).
	// Runs after prepareStore so the reused product attachments already exist.
	seedBlog();
	console.log( '[visual] store + blog prepared against ' + BASE_URL );
};
