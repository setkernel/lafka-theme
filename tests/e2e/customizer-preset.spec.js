/* lafka-theme/tests/e2e/customizer-preset.spec.js
 *
 * NX2-04 acceptance: Customizer preset switch updates the preview WITHOUT a
 * reload, publish persists, and the front end renders the published preset.
 * Serial + self-restoring (the active-preset + accent theme_mods are global
 * state; the iron-gate default env state is Peppery/unset).
 *
 * Two swap rounds:
 *   1. Chrome-only: switch Peppery → Ember, assert the swapped dynamic-css
 *      carries Ember's chrome accent (this exact assertion caught the
 *      customize-preview theme_mod pinning bug the payload builder fixes).
 *   2. Posted-value-wins: set an UNSAVED accent (#123456) via the controls
 *      JS API, deliberately refresh the preview (the one on-purpose reload —
 *      the refreshed request POSTs `customized`, so the payload rebuild hits
 *      the unpin override branch), then swap presets again and assert the
 *      posted accent beats every preset's chrome default in the payloads.
 *
 * No-reload proof: a sentinel (`window.__lafkaNoReload`) is planted on the
 * preview document's window AFTER the preview is fully ready. A postMessage
 * swap leaves it alone; ANY reload path kills it — a same-frame navigation
 * wipes the window, and WP core's refresh() swaps in a brand-new iframe,
 * detaching our frame handle so the evaluate throws. Both fail the test.
 *
 * Accent assertions target `--lafka-accent-color:` specifically (not the bare
 * hex): Peppery's accent #dc2626 ALSO appears in Ember's payload as the
 * sale-label colour, and Ember's accent hex doubles as its menu-hover colour,
 * so bare-hex contains/not-contains checks would false-fail.
 *
 * @since lafka-theme 7.1.0 (NX2-04)
 */
const { test, expect } = require( '@playwright/test' );
const { wpCli, bustDynamicCss } = require( './support/wp-cli' );

// presets/ember/preset.json chrome.lafka_accent_color / peppery default accent.
const EMBER_ACCENT = '#f97316';
const PEPPERY_ACCENT = '#dc2626';
// Ember chrome.lafka_main_menu_links_hover_color (same hex as its accent) —
// proves preset chrome still lands alongside a posted accent override.
const EMBER_MENU_HOVER = '#f97316';
// Arbitrary unsaved operator override posted through the changeset.
const POSTED_ACCENT = '#123456';

/**
 * Resolve the SETTLED customize-preview frame: the messenger-channel frame
 * whose document satisfies `readyPredicate`. page.frames() races iframe
 * attach/navigation (and during a refresh old + new frames coexist), so scan
 * every candidate until one passes; mid-navigation evaluate throws count as
 * "not ready yet".
 *
 * @param {import('@playwright/test').Page} page
 * @param {Function}                        readyPredicate In-page predicate.
 * @param {*}                               [arg]          Serialized argument.
 * @return {Promise<import('@playwright/test').Frame>} The settled frame.
 */
async function settledPreviewFrame( page, readyPredicate, arg ) {
	let settled = null;
	await expect
		.poll(
			async () => {
				const frames = page
					.frames()
					.filter( ( f ) =>
						f.url().includes( 'customize_messenger_channel' )
					);
				for ( const f of frames ) {
					const ready = await f
						.evaluate( readyPredicate, arg )
						.catch( () => false );
					if ( ready ) {
						settled = f;
						return true;
					}
				}
				return false;
			},
			{ timeout: 15000 }
		)
		.toBe( true );
	return settled;
}

/**
 * Plant the no-reload sentinel, swap to a preset via its radio, wait for the
 * expected data-theme, then assert the sentinel survived (= no reload) and
 * return the swapped dynamic-css text.
 *
 * @param {import('@playwright/test').Page}  page
 * @param {import('@playwright/test').Frame} frame     Settled preview frame.
 * @param {string}                           slug      Preset slug to select.
 * @param {string|null}                      dataTheme Expected html[data-theme].
 * @return {Promise<string>} #lafka-style-inline-css textContent after the swap.
 */
async function swapPresetNoReload( page, frame, slug, dataTheme ) {
	await frame.evaluate( () => {
		window.__lafkaNoReload = true;
	} );
	await page.check( `.lafka-preset-grid input[value="${ slug }"]` );
	await expect
		.poll(
			() =>
				frame.evaluate( () =>
					document.documentElement.getAttribute( 'data-theme' )
				),
			{ timeout: 3000 }
		)
		.toBe( dataTheme );
	// ← no reload happened (undefined here would mean the window was wiped;
	// a detached-frame throw would mean core swapped in a fresh iframe).
	expect( await frame.evaluate( () => window.__lafkaNoReload ) ).toBe( true );
	return frame.evaluate(
		() => document.getElementById( 'lafka-style-inline-css' ).textContent
	);
}

test.describe.configure( { mode: 'serial' } );

test.describe( 'NX2-04 Customizer preset switcher', () => {
	test.afterAll( () => {
		// Publish persists BOTH the preset and the posted accent override —
		// restore the iron-gate default env state (Peppery, no overrides).
		wpCli( [
			'eval',
			'remove_theme_mod("lafka_active_preset"); remove_theme_mod("lafka_accent_color");',
		] );
		bustDynamicCss();
	} );

	test( 'switch → instant preview (no reload) → posted value wins → publish → front end', async ( { page } ) => {
		// --- Log in ---
		await page.goto( '/wp-login.php' );
		await page.fill( '#user_login', 'admin' );
		await page.fill( '#user_pass', 'password' );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );

		// --- Open the Customizer, drill into the Design Preset section ---
		await page.goto( '/wp-admin/customize.php?autofocus[section]=lafka_design_preset' );
		const preview = page.frameLocator( 'iframe[title="Site Preview"]' );
		await expect( preview.locator( 'body' ) ).toBeVisible();
		// Controls pane ready: autofocus expanded the section, Ember card shown.
		await expect(
			page.locator( '.lafka-preset-grid input[value="ember"]' )
		).toBeVisible();

		// Settled preview: preset payloads localized + the server-emitted
		// dynamic-css block present. Only then is the sentinel meaningful.
		const frame = await settledPreviewFrame( page, () =>
			Boolean(
				window.lafkaPresetPreview &&
					window.lafkaPresetPreview.payloads &&
					window.lafkaPresetPreview.payloads.ember &&
					document.getElementById( 'lafka-style-inline-css' )
			)
		);

		// Baseline: Peppery (light) — no data-theme.
		expect(
			await frame.evaluate( () =>
				document.documentElement.getAttribute( 'data-theme' )
			)
		).toBeNull();

		// --- Round 1: switch to Ember (dark), chrome accent from the preset ---
		const emberCss = await swapPresetNoReload( page, frame, 'ember', 'dark' );
		expect( emberCss ).toContain( `--lafka-accent-color:${ EMBER_ACCENT }` );
		expect( emberCss ).not.toContain(
			`--lafka-accent-color:${ PEPPERY_ACCENT }`
		);

		// --- Round 2: an UNSAVED accent override beats every preset's chrome ---
		// Post the value through the customize JS API (no color-picker UI) and
		// refresh the preview ON PURPOSE: the refreshed request POSTs the
		// dirty `customized` values, so the payload rebuild exercises the
		// posted-value-wins branch of the theme_mod unpinning.
		await page.evaluate( ( accent ) => {
			window.wp.customize( 'lafka_accent_color' ).set( accent );
			window.wp.customize.previewer.refresh();
		}, POSTED_ACCENT );

		// Settle the NEW frame: only a payload set rebuilt WITH the posted
		// accent satisfies this (the pre-refresh frame's payloads carry the
		// ember chrome accent, so it can never match).
		const frame2 = await settledPreviewFrame(
			page,
			( accent ) =>
				Boolean(
					window.lafkaPresetPreview &&
						window.lafkaPresetPreview.payloads &&
						window.lafkaPresetPreview.payloads.ember &&
						window.lafkaPresetPreview.payloads.ember.dynamicCss.indexOf(
							'--lafka-accent-color:' + accent
						) !== -1
				),
			POSTED_ACCENT
		);

		// Refreshed baseline: ember is the dirty preset → dark server render.
		expect(
			await frame2.evaluate( () =>
				document.documentElement.getAttribute( 'data-theme' )
			)
		).toBe( 'dark' );

		// Swap to Peppery: posted accent beats Peppery's #dc2626 default.
		const pepperyCss = await swapPresetNoReload(
			page,
			frame2,
			'peppery',
			null
		);
		expect( pepperyCss ).toContain(
			`--lafka-accent-color:${ POSTED_ACCENT }`
		);
		expect( pepperyCss ).not.toContain(
			`--lafka-accent-color:${ PEPPERY_ACCENT }`
		);
		expect( pepperyCss ).not.toContain(
			`--lafka-accent-color:${ EMBER_ACCENT }`
		);

		// Swap back to Ember: posted accent beats Ember's chrome accent while
		// the REST of Ember's chrome still lands (menu hover keeps its hex).
		const emberCss2 = await swapPresetNoReload(
			page,
			frame2,
			'ember',
			'dark'
		);
		expect( emberCss2 ).toContain(
			`--lafka-accent-color:${ POSTED_ACCENT }`
		);
		expect( emberCss2 ).not.toContain(
			`--lafka-accent-color:${ EMBER_ACCENT }`
		);
		expect( emberCss2 ).toContain(
			`--lafka-menu-link-hover-color:${ EMBER_MENU_HOVER }`
		);

		// --- Publish (persists ember + the posted accent; afterAll restores) ---
		const save = page.locator( '#save' );
		await expect( save ).toBeEnabled();
		await save.click();
		await expect( save ).toHaveAttribute( 'value', /Published/i, {
			timeout: 10000,
		} );

		// --- Front end renders the published preset (fresh dynamic-css) ---
		bustDynamicCss();
		await page.goto( '/' );
		await expect( page.locator( 'html' ) ).toHaveAttribute(
			'data-theme',
			'dark'
		);
		// <style> text never renders, so assert on textContent, not innerText.
		const frontCss = await page.evaluate(
			() =>
				( document.getElementById( 'lafka-style-inline-css' ) || {} )
					.textContent || ''
		);
		// Published operator override wins over the published preset's chrome
		// accent, and the preset's remaining chrome renders server-side.
		expect( frontCss ).toContain(
			`--lafka-accent-color:${ POSTED_ACCENT }`
		);
		expect( frontCss ).toContain(
			`--lafka-menu-link-hover-color:${ EMBER_MENU_HOVER }`
		);
	} );
} );
