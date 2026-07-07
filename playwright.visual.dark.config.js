// @ts-check
const { defineConfig } = require( '@playwright/test' );

/**
 * Playwright config for the NX2-07 DARK-PRESET visual gate (tests/visual/
 * nx2-dark.spec.js). A SEPARATE config from playwright.visual.config.js so the
 * dark goldens and the 30 Peppery goldens never run in the same pass:
 *   - the Peppery config (playwright.visual.config.js) testIgnore's this spec;
 *   - this config testMatch'es ONLY nx2-dark.spec.js.
 *
 * Like the Peppery gate this NEVER runs in CI (its goldens are LOCAL + UNTRACKED)
 * — only via `npm run test:visual:nx2-dark`. It reuses the SAME globalSetup /
 * globalTeardown (seeded store + static-front-page + blocks-baseline restore);
 * the spec itself toggles the active-preset theme_mod to midnight and back.
 *
 * Goldens: tests/visual/__screenshots__/nx2-dark.spec.js/ (shared snapshot root
 * with the Peppery goldens, disambiguated by testFileName). Output/report dirs
 * are dark-specific so a dark run never clobbers a Peppery run's artefacts.
 *
 * @since lafka-theme 7.1.0 (NX2-07)
 */
module.exports = defineConfig( {
	testDir: './tests/visual',
	testMatch: '**/nx2-dark.spec.js',
	globalSetup: require.resolve( './tests/visual/support/global-setup.js' ),
	globalTeardown: require.resolve( './tests/visual/support/global-teardown.js' ),

	snapshotPathTemplate: '{testDir}/__screenshots__/{testFileName}/{arg}{ext}',
	outputDir: './tests/visual/.output-dark',

	timeout: 120 * 1000,
	expect: {
		timeout: 15 * 1000,
		// Same tolerance contract as the Peppery gate: freeze animations, allow a
		// tiny AA jitter, and cap the absolute differing-pixel count at 50 so a
		// small colour regression can't slip under a ratio-only gate.
		toHaveScreenshot: {
			animations: 'disabled',
			maxDiffPixels: 50,
			maxDiffPixelRatio: 0.01,
		},
	},

	fullyParallel: false,
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: 0,
	reporter: process.env.CI
		? 'line'
		: [ [ 'html', { outputFolder: 'tests/visual/.report-dark', open: 'never' } ] ],

	use: {
		baseURL: process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890',
		screenshot: 'off',
		video: 'off',
		trace: 'off',
	},
} );
