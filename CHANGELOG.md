# Changelog

All notable changes to lafka-theme are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/); versions follow the repo's
semver (see the Releases section of CONTRIBUTING.md). Older history lives in
git tags + GitHub Releases.

## [Unreleased]

## [7.3.0] — 2026-09-25

Live-site QA sharpening (2026-09-25); pairs with lafka-plugin 10.3.0.

### Fixed
- **No white preloader** on counter layouts (off by default for new installs) and
  **no layout shift**: above-the-fold stylesheets are render-blocking again; only
  off-screen modules load async (measured CLS 0 on home, menu, PDP, cart, checkout
  at 375 and 1280). A CLS-budget e2e spec guards it.
- **Leaner pages**: legacy libraries (Font Awesome, owl, animate, nice-select,
  imagesLoaded, wp-util, flaticon, legacy shortcodes, comment-reply) and wp-emoji are
  skipped on counter pages; image `sizes` match their slots (logo, hero, deals, rows).
- **Menu**: sticky category tabs with scroll-spy; honest empty states for filters and
  search; filter chips only when something matches; no silent per-category caps;
  archives paginate; one category order everywhere; breadcrumbs "Home / Menu / …";
  the shop page and stray /shop/ 301 to /menu/; real product search results.
- **Product page**: gluten-free and other unavailable sizes are disabled and re-priced;
  no phantom default price; "Choose size" → "Add to order · $total" with quantity;
  gallery zoom button; age/ID note (optional, operator categories).
- **Home and header**: the restaurant name is never cut (the status wraps); the short
  name is the distinctive first word; rest-of-menu masonry; aligned price columns;
  deal cards without gaps; hero captions on their own dish; accessible hamburger,
  aria-current, skip link first and visible; landscape-phone bar slimmer; the phone
  order bar never clips its total.
- **Order path**: two-row drawer lines that read at 320px, 44px targets, a usable
  drawer on short screens; cart page stepper, names and options; shipping options as
  cards; inline checkout errors; neutral notices; add-to-cart never redirects to /cart/
  under the counter drawer; Pickup/Delivery drives the shipping radio.
- Legacy `/product-category/…` URLs 301 to the real category archive.

## [7.2.0] — 2026-09-25

Phases GX0 ("stop losing orders"), GX1 (diagnostics) and GX4 ("The counter");
pairs with lafka-plugin.

### Added
- **GX4 "The counter" (design direction C)** — Peppery's new default surfaces,
  available to any preset per surface (Customizer → Lafka Settings → Page
  layouts; preset `variants`): a calm header (order-gate-aware open/closed
  status, Pickup/Delivery, phone, worded Cart, Order online, optional links
  row from the new "Header menu (counter layout)" location); a homepage with
  hero co-stars, today's deals, the two co-star categories and every other
  category in WooCommerce order (per-section limits, jump index), and find-us
  from the single NAP resolver; product rows with size-price columns and a
  worded Add; a 2-tap size chooser on a native `<dialog>`; a sticky mobile
  Call/Order bar; the order drawer (stepper rows via the plugin, "Add a
  little extra?", Pickup or delivery, "Go to checkout — $total"); /menu/ and
  category archives in the same rows; a quiet footer. Customizer → Lafka —
  Home Page → Counter sections. No invented ratings, ETAs or savings: every
  such line renders only from operator / product data.
- **Presets**: variants are live (`LAFKA_PRESET_VARIANT_WHITELIST`,
  `lafka_preset_variant()`); per-role `font_display` (swap|optional) with
  matching preloads; variable-font pool entries; Atkinson Hyperlegible Next
  and Bricolage Grotesque join the pool (10 families).
- **Reset appearance to preset** — Customizer → Design Preset button and
  `wp lafka preset reset [--dry-run] | restore <backup> | backups`: removes
  only the preset engine's appearance overrides, with a backup.
- **Critical CSS** follows the active preset under a counter layout (preset
  above-fold tokens + a var()-only header/hero skeleton).
- **Diagnostics (GX1)**: `lafka_theme_log()` logs through the Lafka plugin's
  `lafka_log` action (WooCommerce logs, source `lafka-theme`, scrubbed) with no
  hard dependency on the plugin; without a listener it writes to the PHP error
  log only when `WP_DEBUG` is on (filter `lafka_theme_log_fallback`).
- **Cart**: the payment trust line names only the enabled WooCommerce
  gateways ("Secure checkout · Credit Card · Cash") in the cart drawer and on
  the classic cart page — no more hard-coded "Apple Pay · Visa · Mastercard".
  Customizer → Lafka — Order Flow → Payment trust line (toggle + text
  override); filters `lafka_payment_trust_line`, `lafka_payment_trust_label_map`.
- **Checkout**: muted styling for the plugin's "enter your street address to
  see the delivery cost" notice (classic + block).

### Changed
- **Peppery ships the counter design**: warm-white palette with ink
  `#1F1B18`, tomato `#B0271D`, leaf green, 17/18 px body, 8 px buttons, the
  checkered band; no contrast waiver. The engine's no-op moved to the test-only
  `presets/__fixtures__/identity` preset. Sites with saved legacy colour/font
  overrides keep them until "Reset appearance to preset" is used.
- Primary CTAs (cart, checkout, PDP, account, 404) read `--lafka-radius-button`
  (pill for every other preset).
- The GitHub updater and the preset engine log through `lafka_theme_log()`
  instead of `error_log()`; updater failures are warnings (listed on
  Lafka → Diagnostics), routine updater lines are info.

### Fixed
- **Mobile**: fixed-bottom bars (sticky cart, PDP add-to-cart, toasts) sit
  above the cookie-consent banner via `--lafka-consent-banner-h`.
- **PDP**: size chips and menu variation rows follow the plugin's order (the
  operator's term order, else cheapest first) instead of database order.
- **PDP**: add-to-cart stays usable while a closed store takes orders ahead.
- **NAP**: phone text is never a raw E.164 number (routed through the
  plugin's formatter; tel: links unchanged).
- **WooCommerce**: related-products args pass an int limit (no more
  "Invalid limit type" log errors on every product page).

### Platform (GX5)
- WooCommerce template overrides reconciled with core: `single-product/product-image.php` (11.1.0 media gallery, native video first item), `cart/cart.php` (11.2.0 item naming), `single-product/meta.php` (11.2.0 category ordering).
- Editor styles load into WordPress 7.1's iframed canvas; the catch-all `defer` filter is replaced by the script strategy API on the theme's own handles; FlexSlider reuses WooCommerce's `wc-flexslider`; `$.trim`/`$.proxy` removed (jQuery 4 ready).
- Tested up to WordPress 7.1 / WooCommerce 11.1; `responsive-embeds` + safe `html5` supports; theme.json schema 7.1; stylelint 17; PHPCompatibility 10 (PHP 8.x checks).

### Fixed
- Counter layout: the checkered band element reused the `<body>` flag class, so its default `display:none` hid the whole page — renamed to `.lafka-motif-band`, locked by `BodyFlagClassCollisionTest`.
- Counter polish from the browser review: header controls never overlap (1024–1440), both hero dishes visible with captions in clear space, every deal renders (legacy product types included via `lafka_listing_extra_product_types`), price columns never break words (2×2 on narrow rows), Bricolage tabular figures for prices, contact shadow for cut-outs vs rounded crop for photos, content-visibility sections keep remembered sizes and jump links land correctly, find-us hours grid, quiet footer from its own menu location (no legacy link walls), one-line brand on phones.
- Tests: the preset registry singleton resets between tests; CI also runs PHPUnit in reverse and seeded random order.

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
