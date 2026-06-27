// @ts-check
const { defineConfig, devices } = require( '@playwright/test' );

/**
 * Playwright config for the conversion-path e2e (tests/e2e/pdp-flow.spec.js).
 *
 * Audit 2026-06-27 #1: the suite guarding the revenue path (variation →
 * live price → add-to-cart → cart drawer → mobile sticky CTA → upsell)
 * existed but ran nowhere — no dependency, no config, no script. This wires
 * it so `npm run test:e2e` runs it against a wp-env instance.
 *
 * The workspace-root .wp-env.json maps the plugin + theme + child + WC 10.7
 * at testsPort 8891, which matches the spec's default BASE_URL. Bring it up
 * with `npx wp-env start` (and seed demo content) before running.
 *
 * Override the target with BASE_URL=… for staging/prod smoke runs.
 */
module.exports = defineConfig( {
	testDir: './tests/e2e',
	timeout: 30 * 1000,
	expect: { timeout: 5 * 1000 },
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? 'github' : 'list',
	use: {
		baseURL: process.env.BASE_URL || 'http://localhost:8891',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	projects: [
		{ name: 'chromium', use: { ...devices['Desktop Chrome'] } },
	],
} );
