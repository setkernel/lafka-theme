# Changelog

All notable changes to lafka-theme are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/); versions follow the repo's
semver (see the Releases section of CONTRIBUTING.md). Older history lives in
git tags + GitHub Releases.

## [Unreleased]

Phase NX2 ("10 designs in one theme"), targeting 7.1.0.

### Added
- **10 built-in design presets** — pure-data `presets/<slug>/preset.json`
  (Peppery default, Midnight, Ember, Verde, Koyo, Terracotta, Azzurro,
  Brioche, Saffron, Fjord), including **two dark presets** (Midnight, Ember).
  Peppery is a provable no-op (byte-identical dynamic CSS, pixel-identical
  goldens); operator Customizer values always win over a preset's defaults.
  Architecture: `docs/PRESET_ENGINE.md`.
- **Customizer preset switcher with live preview** — a "Design Preset"
  thumbnail grid at the top of Site Settings; switching swaps the
  server-emitted CSS in the preview iframe with no reload; accent/brand
  colours preview instantly and hero copy + announce bar use selective
  refresh.
- **8-family OFL font pool** (Rubik, Fraunces, Inter, Archivo, Lora, Manrope,
  Space Grotesk, DM Serif Display), self-hosted; only the active preset's two
  families load.
- **Contrast gates** — every preset's effective palette is WCAG-AA checked in
  PHPUnit (`PresetContrastTest`), plus a rendered text/CTA contrast suite
  (`npm run test:contrast`) across home, menu, product and cart.
- Per-preset category emoji via the `lafka_category_emoji` filter.

### Changed
- **Surface tokenization + dark-mode completion** — remaining hard-coded
  chrome surfaces (header glass, footer, announce bar, panels) now read
  `--lafka-*` tokens, so dark presets render dark end-to-end; dark mode is
  driven by `dark: true` presets stamping `data-theme="dark"`.

### Fixed
- The Customizer preview served stale saved-value dynamic CSS instead of the
  unsaved values being edited.
- Mobile-nav grouped-categories toggle wiring; home free-delivery claims now
  derive from the threshold setting.

### Lean pass
- Release zip now ships the GPL `LICENSE` and every OFL font licence, and
  excludes Playwright configs and the fixture preset.
- Removed dead weight: 53 unreferenced vendored/legacy assets, dead
  templates/partials and the 259KB Font Awesome data class, dead legacy admin
  code, and 20 dead critical-CSS header rules (−3.5KB inlined on every page).
- Legacy demo importer (store/demo, ~9.5MB) retired — unreachable since NX1-02
  and broken since plugin 9.7.17; its global WordPress-Importer post-ID filter
  could overwrite unrelated posts.
- Dev tooling: unused Brain Monkey / PHPUnit polyfills dropped; PHPCS / WPCS
  updated past published advisories; applied one-shot NX1-10a scripts removed;
  the theme's wp-env now boots the full e2e stack (WP 7.0 / PHP 8.4 / WC 10.9.1
  + plugin).

## [7.0.0] — 2026-07-07

Phase NX1 ("Platform & Configurability Foundation") release. See
`ROADMAP_2026-07-05.md` at the umbrella repo for the full program.

### Breaking / Changed
- **Legacy Options Framework retired.** All consumed theme options migrated
  to Customizer `theme_mods` (`lafka_*`) via a one-time, idempotent upgrade
  migration (162-key map; runs automatically on update; plugin-owned keys in
  the `lafka` option array are untouched). The old Theme Options admin panel
  is removed; `lafka_get_option()` remains for one major cycle as a
  deprecated delegating shim. Migration verified pixel-identical via a
  dynamic-CSS byte-parity fixture and 375/768/1280 visual goldens.
- **style.css monolith retired**: 349KB → 280KB on every page; live legacy
  styling for blog/forum/events/legacy-shortcode surfaces extracted to
  conditionally-loaded `styles/legacy-*.css` (cascade parity proven against
  the original monolith and locked by `CascadeParityTest`).
- theme.json editor presets are now generated from the `--lafka-*` token SSOT
  (`npm run build:theme-json`) — editor and front end finally agree.
- Release zips exclude dev-only files (647 → 547 files).

### Added
- **Block Cart/Checkout skin**: token-only styling for WooCommerce block
  cart/checkout and the plugin's checkout components (order type, branch,
  timeslot, free-delivery progress), loaded only on block pages.
- **Playwright e2e suite** (funnel placing real COD orders on both checkout
  modes, store-closed gate, cart-drawer a11y) + a non-blocking CI smoke job.
- Minified-asset pipeline (`npm run build`) with a runtime `.min` switch when
  `SCRIPT_DEBUG` is off, wired into release packaging; asset-budget ratchet.
- Local visual-regression harness (30 goldens across handoff + blog surfaces,
  ≤50-pixel diff budget) and a dynamic-CSS byte-parity gate.
- wp.org-format `readme.txt`.

### Fixed
- Variable products could never resolve a variation from the redesigned PDP
  pickers (case-sensitive attribute matching) — add-to-cart was permanently
  disabled for them.
- Script-defer optimization broke every WooCommerce block page (empty block
  cart; checkout could not mount).
- Block CTA anchors inherited the prose-link styling (dark-red underlined
  "Proceed to Checkout" on the accent pill).
- `/menu/` no longer renders two category navigations (jump-links gated
  behind `lafka_menu_show_jump_links`, default off).
- Footer contact email prefers the configured business email and never leaks
  `host:port` derivations.
- Cart-drawer focus handling; cascade parity on legacy surfaces (11
  declarations that silently changed color/spacing after the monolith split).

### Removed
- Order-notification poller (business logic moved to lafka-plugin).
- 48 dead legacy options and the orphaned social-profiles partial.

### Compatibility
- Requires WP 6.6+ / PHP 8.1+ (tested to WP 7.0); WooCommerce integration
  tested to WC 10.9. Companion plugin lafka-plugin ≥ 10.0.0 recommended.
