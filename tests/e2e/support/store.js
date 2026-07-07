/* lafka-theme/tests/e2e/support/store.js
 *
 * Shared constants + store-preparation helpers for the e2e suite, all keyed to
 * the deterministic content produced by `wp lafka seed-demo` (NX1-09a). Slugs,
 * addon option ids and prices below are the fixture's stable values — never
 * database ids, which are not stable across a wipe/reseed.
 *
 * The prepare helpers encode the FOUR store-level prerequisites the seeded
 * wp-env needs before the conversion funnel is exercisable by an anonymous
 * browser. The seeder alone is not enough:
 *   1. woocommerce_coming_soon = no — WooCommerce 9.1+ defaults a fresh store to
 *      "coming soon" (store-pages-only), which serves anonymous visitors a
 *      placeholder for /product/, /cart/ and /checkout/. Without this the whole
 *      funnel is invisible to Playwright.
 *   2. COD enabled — the only offline gateway, so the smoke order can complete
 *      with no payment integration.
 *   3. product_addons flag PERSISTED in the `lafka` option — the addon module's
 *      load gate runs at plugin-include time (before `init`) and reads the
 *      persisted value; the seeder only writes order_hours + shipping_areas, so
 *      the addon engine never loads on the front end and the seeded pizzas'
 *      addons render nowhere until the flag is written.
 *   4. Classic cart/checkout shortcodes — this install ships the WC block
 *      Cart/Checkout pages; the plugin's block-cart shim swaps them to the
 *      classic shortcodes Lafka supports, but only on an admin request. We
 *      apply the same idempotent swap up front so COD checkout is deterministic.
 *
 * @since lafka-theme 6.20.0 (NX1-09b)
 */
const { wpCli } = require( './wp-cli' );

const BASE_URL = process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890';

/** Seeded content the specs address by stable slug / fixture value. */
const SEED = {
	// A variable pizza (Size: Small/Medium/Large) carrying the two seeded addon
	// groups — the one product that exercises variation + addon + drawer at once.
	pizzaSlug: 'margherita-pizza',
	pizzaPrices: { Small: '9.99', Medium: '12.99', Large: '15.99' },
	// Flat-per-option checkbox addon; +1.50 on top of the chosen size.
	toppingOptionId: 'demo-topping-cheese',
	toppingDelta: 1.5,
	// A simple product for cart-only / a11y flows.
	simpleSlug: 'garlic-bread',
	categories: [ 'Pizzas', 'Sides', 'Salads', 'Drinks' ],
};

/**
 * Bring the seeded store to a deterministic, anonymously-orderable state.
 * Idempotent; safe to run before every suite.
 */
function prepareStore() {
	// Deterministic content first — this also re-enables order_hours +
	// shipping_areas and the always-open schedule.
	wpCli( [ 'lafka', 'seed-demo' ] );
	// (1) Take the store out of WooCommerce "coming soon" mode.
	wpCli( [ 'option', 'update', 'woocommerce_coming_soon', 'no' ] );
	// (2) Cash on delivery for the offline smoke order.
	wpCli( [
		'wc',
		'payment_gateway',
		'update',
		'cod',
		'--enabled=true',
		'--user=admin',
	] );
	// (3) Persist the product_addons flag so the addon engine loads front-end.
	wpCli( [
		'eval',
		'$o=(array)get_option("lafka");$o["product_addons"]="enabled";update_option("lafka",$o);',
	] );
	// (4) Serve classic cart/checkout shortcodes (only swaps unedited block pages).
	wpCli( [
		'eval',
		'foreach(array("woocommerce_cart_page_id"=>"[woocommerce_cart]","woocommerce_checkout_page_id"=>"[woocommerce_checkout]") as $opt=>$sc){$pid=(int)get_option($opt);if(!$pid)continue;$p=get_post($pid);if($p && false!==strpos($p->post_content,"wp:woocommerce/")){wp_update_post(array("ID"=>$pid,"post_content"=>$sc));}}',
	] );
}

/**
 * Put the store on the BLOCK Cart/Checkout path for the block-checkout spec:
 * regenerate WooCommerce's own default block markup into the cart + checkout
 * pages (via WC_Install's protected generators, so we never hardcode the long
 * block comment markup) and flip Lafka to blocks checkout mode. The env default
 * baseline is the CLASSIC shortcode path (support/store.js prepareStore swaps to
 * shortcodes), so this must run in the block spec's beforeAll and be undone in
 * its afterAll (useClassicCartCheckout) to keep the classic funnel spec green.
 */
function useBlockCartCheckout() {
	wpCli( [
		'eval',
		'foreach(array("woocommerce_cart_page_id"=>"get_cart_block_content","woocommerce_checkout_page_id"=>"get_checkout_block_content") as $opt=>$fn){$pid=(int)get_option($opt);if(!$pid)continue;$m=new ReflectionMethod("WC_Install",$fn);$m->setAccessible(true);wp_update_post(array("ID"=>$pid,"post_content"=>$m->invoke(null)));}',
	] );
	wpCli( [ 'option', 'update', 'lafka_checkout_mode', 'blocks' ] );
}

/**
 * Restore the CLASSIC shortcode Cart/Checkout pages + classic mode — the
 * global-setup baseline the classic funnel spec depends on. Idempotent.
 */
function useClassicCartCheckout() {
	wpCli( [
		'eval',
		'foreach(array("woocommerce_cart_page_id"=>"[woocommerce_cart]","woocommerce_checkout_page_id"=>"[woocommerce_checkout]") as $opt=>$sc){$pid=(int)get_option($opt);if(!$pid)continue;wp_update_post(array("ID"=>$pid,"post_content"=>$sc));}',
	] );
	wpCli( [ 'option', 'update', 'lafka_checkout_mode', 'classic' ] );
}

/**
 * Turn ON the branch-selection modal and allow BOTH order types on every seeded
 * branch, so the block checkout's lafka order_type select renders and a `pickup`
 * selection passes the NX1-04a server gate. The demo seed ships a single
 * delivery-only branch with the modal off, so both must be flipped for the
 * block spec's pickup path.
 */
function enableBranchOrderTypes() {
	wpCli( [
		'eval',
		'$o=(array)get_option("lafka_shipping_areas_branches");$o["enable_branch_selection_modal"]=1;update_option("lafka_shipping_areas_branches",$o);foreach(get_terms(array("taxonomy"=>"lafka_branch_location","hide_empty"=>false)) as $t){update_term_meta($t->term_id,"lafka_branch_order_type","delivery_pickup");}',
	] );
}

/**
 * Set the free-delivery threshold option (0 = off) that the NX1-04a `lafka` cart
 * extension exposes as free_delivery_threshold / free_delivery_remaining — the
 * data the block cart's free-delivery progress reads. A value above the seeded
 * cart total surfaces an in-progress bar.
 *
 * @param {number} amount Threshold in store currency (0 clears it).
 */
function setFreeDeliveryThreshold( amount ) {
	wpCli( [ 'option', 'update', 'lafka_free_delivery_threshold', String( amount ) ] );
}

/**
 * Undo enableBranchOrderTypes(): turn the branch-selection modal back OFF so the
 * classic funnel spec runs the seed-default (single-branch, no modal) flow. A
 * re-seed would also reset the branch meta; this surgical unset is enough for
 * the classic path (which does not read the branch caps).
 */
function disableBranchSelection() {
	wpCli( [
		'eval',
		'$o=(array)get_option("lafka_shipping_areas_branches");unset($o["enable_branch_selection_modal"]);update_option("lafka_shipping_areas_branches",$o);',
	] );
}

/**
 * Force the shop CLOSED and hide the add-to-cart form (order-hours override +
 * disable-add-to-cart). Used by the store-closed spec.
 */
function forceStoreClosed() {
	wpCli( [
		'eval',
		'$o=(array)get_option("lafka_order_hours_options");$o["lafka_order_hours_force_override_check"]=1;$o["lafka_order_hours_force_override_status"]="";$o["lafka_order_hours_disable_add_to_cart"]=1;update_option("lafka_order_hours_options",$o);',
	] );
}

/**
 * Undo forceStoreClosed(): clear the override and re-open. Belt-and-suspenders
 * — a re-seed would also reset these, but the surgical unset leaves the other
 * prerequisites (coming-soon/COD/addons/pages) untouched.
 */
function restoreStoreOpen() {
	wpCli( [
		'eval',
		'$o=(array)get_option("lafka_order_hours_options");$o["lafka_order_hours_force_override_check"]=0;$o["lafka_order_hours_force_override_status"]="";unset($o["lafka_order_hours_disable_add_to_cart"]);update_option("lafka_order_hours_options",$o);',
	] );
}

module.exports = {
	BASE_URL,
	SEED,
	prepareStore,
	forceStoreClosed,
	restoreStoreOpen,
	useBlockCartCheckout,
	useClassicCartCheckout,
	enableBranchOrderTypes,
	disableBranchSelection,
	setFreeDeliveryThreshold,
};
