// @ts-check
const { defineConfig, devices } = require( '@playwright/test' );

/**
 * Playwright config for the conversion-funnel e2e suite (tests/e2e/).
 *
 * Targets the seeded demo store (`wp lafka seed-demo`, NX1-09a) served by the
 * wp-env stack. `global-setup.js` fails fast if the target is unreachable, then
 * brings the store to a deterministic, anonymously-orderable state.
 *
 * Target: LAFKA_E2E_BASE_URL (default http://localhost:8890 — the umbrella
 * wp-env dev site). Bring it up with `npx wp-env start` at the workspace root
 * before running.
 *
 * The suite runs SINGLE-WORKER: every spec shares one WordPress backend, and
 * store-level state (open/closed, coming-soon, gateways) is global — parallel
 * workers would race it. Per-test browser contexts still isolate cart/cookies.
 *
 * @since lafka-theme 5.12.0
 * @since 6.20.0 (NX1-09b) seeded-store target, global setup, single-worker,
 *                @smoke subset, CI-friendly reporter.
 */
module.exports = defineConfig( {
	testDir: './tests/e2e',
	globalSetup: require.resolve( './tests/e2e/support/global-setup.js' ),
	timeout: 45 * 1000,
	expect: { timeout: 7 * 1000 },
	// Shared mutable backend → no cross-spec parallelism.
	fullyParallel: false,
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: 1,
	// HTML report locally (nice to browse); plain line output in CI logs.
	reporter: process.env.CI ? 'line' : 'html',
	use: {
		baseURL: process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'off',
	},
	projects: [
		{ name: 'chromium', use: { ...devices[ 'Desktop Chrome' ] } },
	],
} );
