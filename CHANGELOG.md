# Changelog

All notable changes to lafka-theme are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/); versions follow the repo's
semver (see the Releases section of CONTRIBUTING.md). Older history lives in
git tags + GitHub Releases.

## [Unreleased]

### Added
- **Diagnostics (GX1)**: `lafka_theme_log()` logs through the Lafka plugin's
  `lafka_log` action (WooCommerce logs, source `lafka-theme`, scrubbed) with no
  hard dependency on the plugin; without a listener it writes to the PHP error
  log only when `WP_DEBUG` is on (filter `lafka_theme_log_fallback`).

### Changed
- The GitHub updater and the preset engine log through `lafka_theme_log()`
  instead of `error_log()`; updater failures are warnings (listed on
  Lafka → Diagnostics), routine updater lines are info.

## [7.1.0] — 2026-09-24

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
- **Default copy is cuisine-neutral** — the fallback hero headline and lead,
  footer blurb, empty-cart lead and how-it-works steps no longer name the
  reference restaurant's menu (pizza, poutine, donair, dough, pies). Every
  string is still a Customizer setting; existing operator copy is untouched.
- The "Blank page" template is renamed **Content only**: it has rendered the
  normal header and footer since 5.55 and only omits the title, breadcrumb and
  hero. The file name is unchanged, so assigned pages keep it.
- `--lafka-color-accent-500` and the menu-highlight fallback are emitted as
  `var(--lafka-accent-color)`, so a live Customizer accent change reaches them.
- WooCommerce overrides reconciled with 10.9: `cart/cart.php` (10.8.0),
  `content-product.php` (9.4.0, now fires `woocommerce_shop_loop_item_title`),
  `archive-product.php` (8.6.0, prints store notices), `cart/cart-empty.php`
  (7.0.1, honours `woocommerce_return_to_shop_redirect` / `_text`),
  `single-product.php` (1.6.4).
- Theme tags use the WordPress.org list: `e-commerce, food-and-drink` replace
  `woocommerce, restaurant, food`.
- `wpml-config.xml` registers the migrated Customizer copy under
  `theme_mods_lafka` / `theme_mods_lafka-child`; plugin-owned keys moved out.
- All first-party scripts, including `lafka-front`, `lafka-libs-config`,
  `lafka-price-slider` and `lafka-dialog`, are minified by `npm run build`; the
  hand-tuned committed `.min` files are gone (`lafka-dialog.min.js` stays
  committed for the plugin, and CI checks it matches the build).
- `languages/lafka.pot` regenerated from the current code with a GPL header
  (`npm run i18n:pot`).

### Fixed
- The Customizer preview served stale saved-value dynamic CSS instead of the
  unsaved values being edited.
- Mobile-nav grouped-categories toggle wiring; home free-delivery claims now
  derive from the threshold setting.
- `legacy-shortcodes.css` now loads on foodmenu pages (the loader checked the
  wrong post-type slug).
- CloudZoom loads where its markup renders (foodmenu singles on the cloud
  gallery, `[lafka_cloudzoom_gallery]`) instead of on every product page,
  which also fixes the TypeError on those pages.
- Shop-card sale countdowns start (wrong selector, and the library only
  loaded on product pages); the countdown library and its locale file load
  only where a countdown renders or the store-closed countdown can show.
- The product category/tag "title background image" picker opens the media
  modal (its script handle was never enqueued on those screens).
- Tools → Lafka Maintenance is reachable again with lafka-plugin 10 (class
  name clash), and the GitHub updater's notices and links point at it.
- Exactly one `<main>` landmark per page: the page, post, 404, contact and
  editorial templates no longer nest a second one.
- The cart page's backorder notice only shows for lines actually on backorder.
- Variation-in-listings prices include default add-on options again (the
  theme looked for a class alias the plugin removed in 8.18.0).
- The cart remove-link hover used a hard-coded brand red; it now uses the
  error tokens. The remove × itself uses the muted-text token instead of
  `#999`, and the rendered-contrast gate now checks it in every preset.
- The `[lafka_foodmenu]` light scheme's weight badge was grey on a dark
  section (2.7:1); it now follows the title colour (11.6:1).
- Menu items that share a `menu_order` no longer reshuffle between requests
  on /menu/ and the grouped shop view: title breaks the tie, as in
  WooCommerce's default catalog order.

### Removed
- The add-to-cart sound (`lafka_add_to_cart_sound`, the `<audio>` element and
  the 352 KB `image/cart_add.wav`): it could never play.
- The nav-menus.php mega-menu editor (label, colour, icon, image, mega/column
  fields), its save hook, `LafkaFrontWalker`, the admin mega-menu JS/CSS and
  the fontIconPicker library. Nothing rendered these since the handoff header;
  menu-item meta already saved is left in place.
- ~600 CSS selectors for the pre-handoff header, mega menu, mini-cart module
  and mobile drawer, and the matching JS in `lafka-front.js` (cart module,
  mega-menu sizing, drawer tabs, sticky-header init, search/account holders)
  plus the unused `lafka_map_config` block in `lafka-libs-config.js` and its
  images.
- Options-Framework rules in `lafka-admin.css` (27.7 KB → 7.6 KB).
- CSS/JS for the collapsible pre-header, top-bar menu, old footer menu and
  stretched header/footer widths (no markup renders them).
- 29 `dynamic-css.php` custom properties nothing reads (main-menu, top-bar,
  pre-header, logo/menu typography, copyright-bar colours, …); the preset
  chrome whitelist goes from 55 to 34 keys and four presets drop their
  main-menu colours.
- Customizer settings with no remaining front-end effect: sticky header,
  cart-on-add, top header (+ mobile), uppercase menu, header cart/wishlist
  toggles, logo point, header/footer width, submenu scheme, main-menu /
  top-bar / pre-header / copyright-bar colours, main-menu / top-menu /
  text-logo typography, home reviews eyebrow and source. The Main Menu
  Colors section is gone; stored values are left in place. The body classes
  those settings (and a few others) added without any CSS consumer are no
  longer printed.
- `jquery.mb.YTPlayer` (no stated licence; its initialiser targeted markup no
  template renders).
- Constants `LAFKA_BACKGROUNDS_PATH`, `LAFKA_IS_VC`, `LAFKA_IS_ENVATO_MARKET`;
  the `$lafka_is_blank` global; `lafka_remove_page_template()` and its WP All
  Import hook, which deleted page templates on every import.
- Deprecated (kept for one cycle): `lafka_build_mobile_menu_items_wrap()`
  (returns an empty string) and `lafka_is_text_logo()`.

### Performance
- style.css 279.6 KB → 217.9 KB (gzip 46.1 → 37.4 KB); always-on first-party
  CSS+JS on the front page 705.7 KB → 638.5 KB. Product pages also drop
  CloudZoom; non-English sites no longer load a countdown locale everywhere.
- The shipped theme is ~0.8 MB smaller (the sound file, YTPlayer,
  fontIconPicker, map images, dead CSS/JS).
- Unit suite: 747 → 362 tests, now in one process (~1 s instead of 25–45 s):
  shared store-backed WordPress shims replace per-file shims and process
  isolation, and implementation-pinning tests were removed or replaced by
  render tests. PHPCS runs in parallel with a result cache, ESLint and
  Stylelint use content-hash caches (all restored in CI), and the pre-push
  hook runs its four gates concurrently.
- The three local visual configs are one `playwright.visual.config.js` with
  `peppery`, `dark` and `contrast` projects; `test:visual:nx2-dark` is now
  `test:visual:dark`.

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
