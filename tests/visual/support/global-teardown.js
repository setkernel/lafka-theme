/* lafka-theme/tests/visual/support/global-teardown.js
 *
 * Global teardown for the NX1-02 VISUAL PARITY harness. Restores the wp-env
 * checkout mode to the environment baseline: BLOCKS mode with WooCommerce's own
 * default block Cart/Checkout page content (regenerated via WC_Install, never
 * hardcoded). The spec flips to CLASSIC mode for the cart/checkout goldens
 * (production runs classic); this returns the throwaway env to how it was found.
 *
 * @since lafka-theme 6.21.0 (NX1-02 harness)
 */
const { useBlockCartCheckout } = require( '../../e2e/support/store' );

module.exports = async function globalTeardown() {
	try {
		useBlockCartCheckout();
		console.log( '[visual] restored env to blocks checkout mode' );
	} catch ( err ) {
		// Non-fatal: the env is throwaway. Surface it so a human can re-baseline.
		console.warn(
			'[visual] could not restore blocks checkout mode: ' + err.message
		);
	}
};
