# Changelog

All notable changes to lafka-theme are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/); versions follow the repo's
semver (see `npm version` SSOT in CONTRIBUTING.md). Older history lives in
git tags + GitHub Releases.

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
