// @ts-check
const { defineConfig } = require( '@playwright/test' );

/**
 * Playwright config for the local visual and rendered-contrast gates
 * (tests/visual/). Kept separate from playwright.config.js (the e2e suite CI
 * runs) because the goldens are LOCAL and UNTRACKED (see .gitignore): they are
 * captured on a developer's wp-env and regenerated only on an intentional,
 * reviewed visual change.
 *
 * Projects (run one at a time via the npm scripts):
 *   - peppery  — default-preset full-page goldens at 375/768/1280: the handoff
 *                pages (nx1-02.spec.js) and the legacy blog surfaces
 *                (nx1-10a.spec.js). `npm run test:visual`
 *   - dark     — Midnight (dark preset) goldens on home, menu, PDP and cart
 *                (nx2-dark.spec.js). `npm run test:visual:dark`
 *   - contrast — rendered text/CTA WCAG contrast for every registered preset
 *                (nx2-contrast.spec.js). No goldens, so it can never bless a
 *                regression. `npm run test:contrast`
 * Add `-- --update-snapshots` to (re)capture goldens.
 *
 * Target: LAFKA_E2E_BASE_URL (default http://localhost:8890 — the theme's
 * wp-env). global-setup reseeds the store; global-teardown restores the
 * blocks-checkout baseline. The dark and contrast specs switch the active
 * preset and restore it, so everything runs single-worker.
 *
 * @since lafka-theme 6.21.0 (NX1-02 harness); projects since 7.1.0
 */
module.exports = defineConfig( {
	testDir: './tests/visual',
	globalSetup: require.resolve( './tests/visual/support/global-setup.js' ),
	globalTeardown: require.resolve( './tests/visual/support/global-teardown.js' ),

	snapshotPathTemplate: '{testDir}/__screenshots__/{testFileName}/{arg}{ext}',
	outputDir: './tests/visual/.output',

	timeout: 120 * 1000,
	expect: {
		timeout: 15 * 1000,
		// Freeze animations and allow a tiny anti-aliasing tolerance. Playwright
		// applies the stricter of the two limits, so on a full-page shot the
		// 50-pixel cap is the effective gate: a badge-sized colour flip (a few
		// hundred pixels, far under 1%) still fails.
		toHaveScreenshot: {
			animations: 'disabled',
			maxDiffPixels: 50,
			maxDiffPixelRatio: 0.01,
		},
	},

	// Shared store state (checkout mode, active preset) → no parallelism.
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

	projects: [
		{ name: 'peppery', testMatch: [ '**/nx1-02.spec.js', '**/nx1-10a.spec.js' ] },
		{ name: 'dark', testMatch: '**/nx2-dark.spec.js' },
		{ name: 'contrast', testMatch: '**/nx2-contrast.spec.js' },
	],
} );
