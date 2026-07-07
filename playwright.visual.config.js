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
	// The NX2-07 dark-preset goldens run under playwright.visual.dark.config.js
	// (midnight active); they must NOT run in this Peppery pass.
	testIgnore: '**/nx2-dark.spec.js',
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
		//
		// NX1-10a: a bare maxDiffPixelRatio: 0.01 let the cascade-inversion badge
		// flip slip through — a badge-sized colour change (a few hundred px) is
		// far under 1% of a full-page screenshot. We now ALSO cap the ABSOLUTE
		// differing-pixel count. Playwright takes Math.min() of the two limits
		// (coreBundle.js: maxDiffPixels = min(maxDiffPixels, ratio*w*h)), so with
		// both set the 50-pixel cap is the effective gate on every page large
		// enough to matter — a badge-sized colour flip can never pass again. The
		// ratio is retained only as a belt-and-braces ceiling on tiny images.
		toHaveScreenshot: {
			animations: 'disabled',
			maxDiffPixels: 50,
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
