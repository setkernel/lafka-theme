// @ts-check
const { defineConfig } = require( '@playwright/test' );

/**
 * Playwright config for the NX1-02 VISUAL PARITY harness (tests/visual/).
 *
 * DELIBERATELY SEPARATE from playwright.config.js (the e2e conversion suite) so
 * this NEVER runs in CI: the e2e job invokes the default config (testDir
 * ./tests/e2e); this config (testDir ./tests/visual) is only ever run by the
 * `npm run test:visual:nx1-02` script a developer runs locally. Its goldens are
 * LOCAL and UNTRACKED (see .gitignore) — the NX1-02 migration gate, regenerated
 * only on an intentional, reviewed visual change.
 *
 * Target: LAFKA_E2E_BASE_URL (default http://localhost:8890 — the umbrella
 * wp-env dev site). global-setup reseeds for determinism; global-teardown
 * restores the env's blocks-checkout baseline.
 *
 * Single-worker, non-parallel: the store's checkout mode is global state the
 * spec toggles (classic for cart/checkout), so workers must not race it.
 *
 * @since lafka-theme 6.21.0 (NX1-02 harness)
 */
module.exports = defineConfig( {
	testDir: './tests/visual',
	globalSetup: require.resolve( './tests/visual/support/global-setup.js' ),
	globalTeardown: require.resolve( './tests/visual/support/global-teardown.js' ),

	// Goldens + diffs live UNDER untracked dirs (gitignored). Regenerate with
	// `npm run test:visual:nx1-02 -- --update-snapshots`.
	snapshotPathTemplate: '{testDir}/__screenshots__/{testFileName}/{arg}{ext}',
	outputDir: './tests/visual/.output',

	timeout: 120 * 1000,
	expect: {
		timeout: 15 * 1000,
		// Freeze CSS animations; allow a tiny sub-pixel AA tolerance so text
		// rendering jitter across runs can't flake the gate.
		toHaveScreenshot: {
			animations: 'disabled',
			maxDiffPixelRatio: 0.01,
		},
	},

	// Shared global store state → no cross-spec parallelism.
	fullyParallel: false,
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: 0,
	reporter: process.env.CI
		? 'line'
		: [ [ 'html', { outputFolder: 'tests/visual/.report', open: 'never' } ] ],

	use: {
		baseURL: process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890',
		screenshot: 'off',
		video: 'off',
		trace: 'off',
	},
} );
