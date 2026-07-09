/* lafka-theme/tests/e2e/customizer-preset.spec.js
 *
 * NX2-04 acceptance: Customizer preset switch updates the preview WITHOUT a
 * reload, publish persists, and the front end renders the published preset.
 * Serial + self-restoring (the active-preset theme_mod is global state; the
 * iron-gate default env state is Peppery/unset).
 *
 * No-reload proof: a sentinel (`window.__lafkaNoReload`) is planted on the
 * preview document's window AFTER the preview is fully ready. A postMessage
 * swap leaves it alone; ANY reload path kills it — a same-frame navigation
 * wipes the window, and WP core's refresh() swaps in a brand-new iframe,
 * detaching our frame handle so the evaluate throws. Both fail the test.
 *
 * Accent assertion targets `--lafka-accent-color:` specifically (not the bare
 * hex): Peppery's accent #dc2626 ALSO appears in Ember's payload as the
 * sale-label colour, so a naive not.toContain('#dc2626') would false-fail.
 *
 * @since lafka-theme 7.1.0 (NX2-04)
 */
const { test, expect } = require( '@playwright/test' );
const { wpCli, bustDynamicCss } = require( './support/wp-cli' );

// presets/ember/preset.json chrome.lafka_accent_color / peppery default accent.
const EMBER_ACCENT = '#f97316';
const PEPPERY_ACCENT = '#dc2626';

test.describe.configure( { mode: 'serial' } );

test.describe( 'NX2-04 Customizer preset switcher', () => {
	test.afterAll( () => {
		wpCli( [ 'eval', 'remove_theme_mod("lafka_active_preset");' ] );
		bustDynamicCss();
	} );

	test( 'switch → instant preview (no reload) → publish → front end', async ( { page } ) => {
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
		const emberRadio = page.locator( '.lafka-preset-grid input[value="ember"]' );
		await expect( emberRadio ).toBeVisible();

		// The preview document's frame. WP names the iframe URL with
		// customize_messenger_channel; page.frames() can race the iframe's
		// attach/navigation, so poll until the SETTLED preview document is
		// there (preset payloads localized + the server-emitted dynamic-css
		// block present). Only then is planting the sentinel meaningful.
		const findPreviewFrame = () =>
			page
				.frames()
				.find( ( f ) => f.url().includes( 'customize_messenger_channel' ) );
		await expect
			.poll(
				() => {
					const f = findPreviewFrame();
					if ( ! f ) {
						return false;
					}
					return f
						.evaluate(
							() =>
								Boolean(
									window.lafkaPresetPreview &&
										window.lafkaPresetPreview.payloads &&
										window.lafkaPresetPreview.payloads.ember &&
										document.getElementById( 'lafka-style-inline-css' )
								)
						)
						.catch( () => false ); // mid-navigation evaluate → not ready yet
				},
				{ timeout: 15000 }
			)
			.toBe( true );
		const frame = findPreviewFrame();

		// Baseline: Peppery (light) — no data-theme, peppery accent in the CSS.
		expect(
			await frame.evaluate( () =>
				document.documentElement.getAttribute( 'data-theme' )
			)
		).toBeNull();

		// Sentinel: survives postMessage updates, dies on iframe reload.
		await frame.evaluate( () => {
			window.__lafkaNoReload = true;
		} );

		// --- Switch to Ember (dark) ---
		await emberRadio.check();

		// Instant restyle: data-theme flips dark without navigation.
		await expect
			.poll(
				() =>
					frame.evaluate( () =>
						document.documentElement.getAttribute( 'data-theme' )
					),
				{ timeout: 3000 }
			)
			.toBe( 'dark' );
		// ← no reload happened (undefined here would mean the window was wiped;
		// a detached-frame throw would mean core swapped in a fresh iframe).
		expect( await frame.evaluate( () => window.__lafkaNoReload ) ).toBe( true );

		// The swapped dynamic-css carries Ember's chrome accent — and the
		// Peppery accent var is gone.
		const inline = await frame.evaluate(
			() => document.getElementById( 'lafka-style-inline-css' ).textContent
		);
		expect( inline ).toContain( `--lafka-accent-color:${ EMBER_ACCENT }` );
		expect( inline ).not.toContain(
			`--lafka-accent-color:${ PEPPERY_ACCENT }`
		);

		// --- Publish ---
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
		expect( frontCss ).toContain( `--lafka-accent-color:${ EMBER_ACCENT }` );
	} );
} );
