// @ts-check
const { defineConfig } = require( '@playwright/test' );

/**
 * Playwright config for the NX2-07 RENDERED TEXT-CONTRAST gate
 * (tests/visual/nx2-contrast.spec.js). A SEPARATE config so it never runs in the
 * same pass as the 30 Peppery goldens (playwright.visual.config.js testIgnore's
 * it) nor the dark goldens: this config testMatch'es ONLY nx2-contrast.spec.js.
 *
 * Unlike the golden configs this has NO snapshots — it asserts computed WCAG
 * contrast ratios directly, so it can never bake a dark-on-dark bug the way an
 * `--update-snapshots` screenshot can. It reuses the same globalSetup /
 * globalTeardown (seeded store + baseline restore); the spec toggles the
 * active-preset theme_mod to midnight and back.
 *
 * Runs locally via `npm run test:contrast` (and is CI-safe — no local goldens).
 *
 * @since lafka-theme 7.1.0 (NX2-07)
 */
module.exports = defineConfig( {
	testDir: './tests/visual',
	testMatch: '**/nx2-contrast.spec.js',
	globalSetup: require.resolve( './tests/visual/support/global-setup.js' ),
	globalTeardown: require.resolve( './tests/visual/support/global-teardown.js' ),

	outputDir: './tests/visual/.output-contrast',

	timeout: 120 * 1000,
	expect: { timeout: 15 * 1000 },

	fullyParallel: false,
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: 0,
	reporter: process.env.CI
		? 'line'
		: [ [ 'html', { outputFolder: 'tests/visual/.report-contrast', open: 'never' } ] ],

	use: {
		baseURL: process.env.LAFKA_E2E_BASE_URL || 'http://localhost:8890',
		screenshot: 'off',
		video: 'off',
		trace: 'off',
	},
} );
