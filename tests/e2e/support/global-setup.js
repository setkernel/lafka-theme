/* lafka-theme/tests/e2e/support/global-setup.js
 *
 * Playwright global setup: fail fast if the target isn't reachable, then bring
 * the seeded store to a deterministic, anonymously-orderable state (see
 * support/store.js for the four prerequisites).
 *
 * This never STARTS wp-env — the umbrella stack (or the CI job) owns that
 * lifecycle. If the target is down we stop with an actionable message rather
 * than letting every spec time out against a dead host.
 *
 * @since lafka-theme 6.20.0 (NX1-09b)
 */
const { BASE_URL, prepareStore } = require( './store' );

module.exports = async function globalSetup() {
	// 1. Reachability — fail with a clear, actionable message.
	let ok = false;
	try {
		const controller = new AbortController();
		const timer = setTimeout( () => controller.abort(), 10000 );
		const res = await fetch( BASE_URL + '/', {
			signal: controller.signal,
			redirect: 'manual',
		} );
		clearTimeout( timer );
		// Any HTTP answer (200/redirect/even 404) means the server is up.
		ok = res.status > 0;
	} catch {
		ok = false;
	}
	if ( ! ok ) {
		throw new Error(
			'\n[e2e] Cannot reach ' +
				BASE_URL +
				'.\n' +
				'      Start wp-env at the umbrella root first ' +
				'(`npx wp-env start`), or point the suite elsewhere with ' +
				'LAFKA_E2E_BASE_URL=<url>.\n'
		);
	}

	// 2. Deterministic, orderable store state (re-seed + coming-soon off + COD +
	//    product_addons + classic cart/checkout). Throws with a clear message if
	//    the CLI container can't be found.
	prepareStore();

	console.log( '[e2e] store prepared against ' + BASE_URL );
};
