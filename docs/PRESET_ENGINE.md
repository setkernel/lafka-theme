# Lafka Preset Engine — Architecture Reference (NX2)

> Current reference for the preset engine shipped in NX2 (theme 7.1.0): engine (NX2-01),
> contrast gates (NX2-02/07), font pool (NX2-03), Customizer switcher (NX2-04) and the ten
> built-in presets (NX2-05/08). The original design-panel write-up, build sequence and
> rejected alternatives live in git history.

## 0. Why this shape (the one-fact foundation)

`styles/dynamic-css.php` emits only **two canonical `--lafka-*` tokens** from operator
input — `--lafka-color-accent-500` and `--lafka-color-brand-500` — plus the legacy
*chrome* theme_mods (53 appearance keys). The other 186 of the 188 custom properties
declared in `styles/lafka-tokens.css` have **no operator feed at all**. That split is the
whole design: a preset's output goes through two layers, each with a
*trivial, independent* "operator always wins" proof.

Peppery becomes **preset #1, the default, and a provable no-op**: it overrides nothing,
so the engine emits nothing for it → byte-identical `dynamic-css` + pixel-identical
30 visual goldens.

## 1. Terminology (PIN THIS — the panel inverted "Channel 1/2"; do not reuse those labels)

- **Base layer** — `styles/lafka-tokens.css` `:root{}`, the static file declaring 188 unique
  `--lafka-*` custom properties (plus the dark scaffold, §6).
- **Preset-token layer (PTL)** — an inline `:root{}` (or `:root[data-theme="dark"]{}`) block
  carrying a preset's overrides for the 88 tokens in `LAFKA_PRESET_TOKEN_WHITELIST`, all with
  **no** operator feed (surfaces, borders, text, semantics, radii, shadows, motion, type
  scale/family). Emitted
  on a dedicated dependency-ordered handle. **`accent-500`, `brand-500`, `accent-text` are
  FORBIDDEN here** (operator-fed or derived).
- **Operator layer** — `dynamic-css.php`'s existing `:root{}` inline on `lafka-style`
  (accent/brand + chrome theme_mods). Always prints last → always wins.
- **theme_mod-default layer (TML)** — the mechanism by which a preset supplies the
  *default* value for the 34 keys in `LAFKA_PRESET_CHROME_WHITELIST` (`lafka_accent_color`,
  `lafka_brand_color` + 53 appearance theme_mods) that `dynamic-css` emits, via one guarded
  helper. Operator-set theme_mods beat it by `get_theme_mod()` semantics.

Cascade: **Base < PTL < Operator**, with accent/brand/chrome flowing Base-literal ←
preset-default (TML) ← operator-value. Both routes make the operator the final winner.

## 2. File layout & PHP surface (new)

```
lafka-theme/
  presets/
    peppery/preset.json          # preset #1, DEFAULT, identity (empty overrides)
    peppery/preview.jpg          # NX2-04 620×465 switcher thumbnail (one preview.jpg per preset dir)
    midnight/ ember/             # dark presets
    verde/ koyo/ terracotta/ azzurro/ brioche/ saffron/ fjord/  # light presets (10 total, NX2-05/08)
    __fixtures__/lowcontrast/preset.json   # NX2-02: must FAIL the contrast gate
  incl/presets/
    class-lafka-preset.php            # value object: reads one preset.json, typed accessors
    class-lafka-presets.php           # registry: discovery, cache, lafka_presets filter, active()
    lafka-preset-tokens.php           # LAFKA_PRESET_TOKEN_WHITELIST (88) + LAFKA_PRESET_CHROME_WHITELIST (34) + LAFKA_PRESET_CRITICAL_KEYS (pure-data constants)
    lafka-preset-emit.php             # PTL builder + pool @font-face emitter + enqueue wiring + data-theme + category-emoji feed + lafka_preset_default()
    lafka-preset-fonts.php            # NX2-03 LAFKA_FONT_POOL: 8-family OFL registry (pure data)
    class-lafka-color-contrast.php    # NX2-02 WCAG ratio helper
    lafka-preset-customizer.php       # NX2-04 Customizer switcher: section/setting/control reg, preview payloads, controls+preview enqueues, font preload
    class-lafka-customize-preset-control.php  # NX2-04 radio-image grid control (preview.jpg thumb + accent/brand swatch fallback)
  assets/customizer/
    lafka-preset-preview.js           # NX2-04 preview-iframe swap script (zero client-side style math)
  docs/PRESET_ENGINE.md               # this file
```

Public function surface (all `function_exists`-guarded, `lafka_` prefixed):
- `lafka_presets()` → `Lafka_Presets` registry (singleton-ish, cached).
- `lafka_active_preset()` → `Lafka_Preset` for the active slug (falls back to `peppery`).
- `lafka_preset_default( string $key, $fallback )` → active preset's chrome default for
  `$key`, else `$fallback`. **Untyped** return (must route composite typography arrays like
  `lafka_h1_font`, not just scalars).
- `lafka_get_active_preset_slug()` → `get_theme_mod( 'lafka_active_preset', 'peppery' )`.
- `lafka_preset_preview_payloads()` → `array<slug, {label,description,dark,ptl,fonts,dynamicCss}>`
  for the NX2-04 live preview: per preset, the three swap-ready CSS strings built by the REAL
  emitters (each slug forced through `lafka_active_preset_slug` @999); static-memoized per request.
- `lafka_sanitize_preset_slug( mixed ): string` → registry-checked slug sanitizer (unknown →
  `peppery`); the `sanitize_callback` for the `lafka_active_preset` Customizer setting.
- Filters: `lafka_presets` (register/modify the discovered set), `lafka_active_preset_slug`
  (override the resolved slug; the preview builder forces it @999), `lafka_font_pool`
  (extend/modify the font pool), and `lafka_category_emoji` (applied in
  `partials/home-categories.php`; the engine hooks it @10 to feed the active preset's map).
  The whitelists are constants, not filterable.

## 3. preset.json schema

```jsonc
{
  "slug": "midnight",              // == directory name; sanitize_key; no banned substrings
  "schema": 1,
  "label": "Midnight",
  "description": "Late-night diner — neon on black.",
  "dark": true,                    // default false
  "extends": null,                 // slug|null — deep-merge base for child/3rd-party deltas
  "tokens": {                      // PTL overrides; every key MUST be in LAFKA_PRESET_TOKEN_WHITELIST
    "--lafka-color-surface-page": "#0a0a0a",
    "--lafka-color-text-primary": "#fafafa"
    // FORBIDDEN: --lafka-color-accent-500/-brand-500/-accent-text, spacing/gap, z-index,
    //           container/gutter/header-h, tap-target, legacy var()-alias tokens
  },
  "chrome": {                      // TML defaults; keys MUST be in LAFKA_PRESET_CHROME_WHITELIST
    "lafka_accent_color": "#22d3ee",
    "lafka_brand_color": "#a3e635"
    // whitelist = lafka_accent_color, lafka_brand_color + the 53 appearance theme_mods
    // (lafka_legacy_migrate_map() destinations) dynamic-css emits — 34 keys
  },
  "fonts": {                       // source "base" (Rubik/Fraunces, already in static CSS — emits
                                   // nothing) | "pool" (a LAFKA_FONT_POOL family — @font-face inline)
    "body":    { "family": "Rubik",    "source": "base" },
    "display": { "family": "Fraunces", "source": "base" }
  },
  "category_emoji": {},            // feeds lafka_category_emoji; empty = hardcoded default
  "variants": {},                  // GX4: live, keys/values in LAFKA_PRESET_VARIANT_WHITELIST —
                                   // header/home/menu/footer/drawer_layout ∈ classic|counter,
                                   // motif ∈ none|check. lafka_preset_variant() feeds the DEFAULT
                                   // of the lafka_<surface>_layout Customizer selects (operator wins).
  "contrast_exceptions": []        // audited AA waivers, e.g. ["text-muted-on-surface"]
}
```

**Whitelists are pure-data PHP array constants** in `lafka-preset-tokens.php`, read by BOTH
the emitter and the validator, mirroring the `lafka_legacy_migrate_map()` idiom. An
out-of-whitelist key fails a unit test AND is dropped at emit time (never reaches CSS).
`LAFKA_PRESET_CRITICAL_KEYS` names the above-fold subset (no per-preset `critical` field) —
reserved for NX2-04.1; not yet consumed (only `PresetSchemaTest` checks it is a subset of
the token whitelist).

## 4. Emission — the three layers, dependency-enforced

1. **Base** — `lafka-tokens.css` `:root{}` (the file), enqueued as today on the
   `lafka-tokens` handle.
2. **PTL** — register an **inline-only handle**: `wp_register_style( 'lafka-preset', false,
   ['lafka-tokens'], $ver )`, then `wp_add_inline_style( 'lafka-preset', $ptl_css )`. `src=false`
   means **no extra HTTP request**; the `deps=['lafka-tokens']` **dependency edge** forces it
   to print *after* base (grafted from dedicated-emitter — robust, not print-order luck).
   Add `'lafka-preset'` to `lafka-style`'s deps so the operator inline still prints last.
   - Light preset: `:root{ … }` (specificity 0,1,0) — beats base by source order.
   - Dark preset: `:root[data-theme="dark"]{ … }` (0,2,0) — supersedes the scaffold token-for-token.
   - **Peppery emits an empty PTL** → nothing meaningful printed → byte-identical.
   - PTL string is built per request from the transient-cached registry (see
     `Lafka_Presets` discovery cache) — a cheap string-concat over ~10-40 already-parsed
     entries, so no dedicated PTL-string cache is kept.
   - **Fonts** ride a second inline-only handle, `lafka-preset-fonts`, carrying the
     `@font-face` rules (`font-display: swap`) for the active preset's `source:"pool"`
     families only; base-only presets (Peppery) leave it empty.
3. **Operator** — `dynamic-css.php`'s existing `:root{}` inline on `lafka-style`. Unchanged
   in structure; §5 wraps its default args.

**Operator-wins proof.** PTL tokens have zero operator feed (dynamic-css emits none of
them; accent/brand/accent-text are forbidden from PTL), so nothing can out-rank a PTL
declaration except the operator's own layer, which prints last. Chrome/accent/brand flow
through §5 as `get_theme_mod()` *defaults*, so any stored operator value wins by definition.
A preset switch writes **only** `lafka_active_preset` — never a chrome theme_mod — so operator
customizations survive a switch untouched.

### Customizer live preview (NX2-04)

The switcher previews **server-emitted CSS — zero client-side style math**.
`lafka_preset_preview_payloads()` runs the real emitters once per preset (PTL, fonts,
`lafka_dynamic_css_build()`) and localizes the three swap-ready strings; on selection
`assets/customizer/lafka-preset-preview.js` swaps the three live `<style>` blocks —
`lafka-preset-inline-css`, `lafka-preset-fonts-inline-css`, `lafka-style-inline-css` —
and flips `html[data-theme]`. Accent/brand bind straight to `--lafka-*` vars via a per-color
postMessage transport; hero copy + announce bar use selective-refresh partials.

- **Preview cache bypass.** Inside `is_customize_preview()`, `lafka_dynamic_css_build()`
  REBUILDS and never reads/writes the mtime cache; otherwise a stale saved-value cache serves
  the previous palette in the preview iframe (the real defect fixed en route).
- **WP preview-pin trap (durable lesson).** In a live customize session
  `WP_Customize_Setting::_preview_filter` pins every registered flat theme_mod to the value
  captured with the *active* preset — so a forced-slug rebuild reads that preset's chrome for
  every key, collapsing all ten `dynamicCss` payloads into clones of the active one. Unit/CLI
  builds have no customize manager, so no unit gate could see it; only the e2e caught it. The
  payload builder suspends those pins for the build (exception-safe repin in `finally`; posted
  changeset values still win via a `post_value()` override). `tests/e2e/customizer-preset.spec.js`
  is the regression gate.

## 5. theme_mod-default layer (29 call sites in dynamic-css.php)

Every chrome read in `dynamic-css.php` is wrapped — 29 `lafka_preset_default()` call sites
covering all 34 whitelisted keys (the six `lafka_h1_font`…`lafka_h6_font` arrays share one
looped call). Each wraps `get_theme_mod( 'lafka_x', <literal> )` in `dynamic-css.php` as:
```php
get_theme_mod( 'lafka_x', function_exists( 'lafka_preset_default' )
    ? lafka_preset_default( 'lafka_x', <literal> ) : <literal> );
```
- `lafka_preset_default` returns the active preset's `chrome['lafka_x']` if set, else the
  literal. **Peppery.chrome is empty** → returns the literal → `DynamicCssParityTest`
  (whose fixture overrides every key, so the default path never fires) stays byte-green,
  AND `PresetDefaultsGoldenTest` (§9, all-unset render) proves the default path too.
- The `function_exists` guard means the isolated PHPUnit process (no theme bootstrap) falls
  back to the literal — the grep pattern `get_theme_mod('lafka_*'` at `DynamicCssParityTest`
  is preserved (first arg stays a literal string).
- Untyped return routes composite typography arrays (`lafka_h1_font{}`), closing the
  dark-heading gap the minimal-integration blueprint had.

**Do NOT use site-wide `theme_mod_{key}` filters** (the theme.json-native approach) — they
fire for every reader of the key across the whole site (unpredictable blast radius). The
narrow default-arg wrap is contained to the emission site.

## 6. Dark mode (set attribute + emit dark tokens; scaffold accent removed)

A `dark:true` preset (Midnight, Ember):
1. **Sets the attribute** — `lafka_preset_language_attributes()` adds `data-theme="dark"` to
   `<html>` via the `language_attributes` filter (`header.php` emits
   `<html <?php language_attributes(); ?>>`). Activates the `:root[data-theme="dark"]`
   scaffold + `color-scheme`. Nothing else sets the attribute (no `prefers-color-scheme`).
2. **Emits the delta** — its PTL block under `:root[data-theme="dark"]` fills every token the
   scaffold misses (accent-700, full brand ramp, the four semantics) and prints after the
   scaffold (dep on `lafka-tokens`) so it wins token-for-token.
3. **accent-text direction** — the emitter appends a dark `@supports (color-mix)` block that
   *lightens* accent-text (the base derivation darkens, wrong on dark surfaces), with a static
   fallback.
4. **Scaffold accent removed.** The dark scaffold in `lafka-tokens.css` declares no
   `--lafka-color-accent-500` / `-600`: at (0,2,0) they would out-rank the operator's (0,1,0)
   `dynamic-css` accent, silently ignoring an operator override in dark mode. Dark accent
   comes from `chrome.lafka_accent_color` (TML, operator-overridable). Peppery never stamps
   `data-theme`, so this is zero-impact on the 30 Peppery goldens.

## 7. Storage, switching, reset

- `lafka_active_preset` **theme_mod** (not option), default `peppery` — **per-stylesheet, so
  child-active-safe** (the NX1-02 trap: prod runs the child theme; theme_mods resolve against
  the active stylesheet). Shipped preset **files** live in the **parent**'s `presets/`; when a
  child theme is active its own `presets/` dir is scanned too (a same-slug child preset
  overrides the parent's), and the `lafka_presets` filter can add or modify presets.
- **Caches:**
  - *Registry* — the file-discovered set is cached in a transient (`lafka_presets_<md5>`,
    1 day) keyed by every `preset.json` path + mtime, so adding, editing or removing a
    preset file busts it.
  - *dynamic-css* — cache key `lafka_dyncss_v<lafka_dynamic_css_version>_t<theme version>_<locale>_p<active slug>`.
    The active slug makes a preset switch cache-correct by construction; saving the
    `lafka` option or `theme_mods_<stylesheet>` bumps `lafka_dynamic_css_version`; inside
    `is_customize_preview()` it always rebuilds and never reads/writes the cache.
  - The PTL and pool `@font-face` strings are rebuilt per request from the cached registry
    (no cache of their own).
- **Reset to preset** — NOT YET BUILT — planned as `remove_theme_mod()` on **only**
  `array_keys($preset->chrome())` (+ the accent/brand/font operator keys), so unset keys fall
  back to the **ACTIVE** preset's defaults (not Peppery's), reusing the NX1-02 sentinel idiom
  and never touching secrets/KDS/functional keys.

## 8. Extensibility

`lafka_presets()` discovers `presets/*/preset.json` in parent (+ child), validates each
definition, applies the `lafka_presets` filter (child/3rd-party registration), and caches the
file-discovered set in a transient (§7). A file-discovered preset with any unknown/typo'd
token or chrome key fails `validate()` and is skipped (logged under `WP_DEBUG`); presets
injected through the filter bypass that check, so the emitter also drops any non-whitelisted
token (logged).

## 9. Tests & gates

- **Iron gate (must stay green for Peppery):** the 30 visual goldens
  (`npm run test:visual`, local/untracked) + `DynamicCssParityTest` — both
  byte/pixel-identical because Peppery emits nothing.
- **`PresetDefaultsGoldenTest`** — renders `dynamic-css` with **all theme_mods unset**, per
  preset, byte-compared to `tests/fixtures/preset-defaults-<slug>.css`. Closes the
  `DynamicCssParityTest` blind spot (its fixture overrides everything, so the default path
  never fires).
- **`PresetCascadeTest`** — DOM-free resolver proving `Base < PTL < Operator` and
  operator-wins in code (not prose), for a light and a dark preset.
- **`PresetSchemaTest`** — every shipped preset validates; every `tokens` key ∈ token
  whitelist; every `chrome` key ∈ chrome whitelist; no structural/a11y/legacy-alias keys.
- **`PresetContrastTest` (NX2-02)** — for every registered preset, resolves the effective
  palette (base ⊕ PTL ⊕ chrome ⊕ derived accent-text) and computes WCAG ratios via
  `Lafka_Color_Contrast` for the critical pairs (body-text/surface, accent-text/surface,
  button-text/accent-500, focus-ring, badges), failing any pair < AA **except** audited
  `contrast_exceptions`, which may never drop below the AA-large floor (3.0). The one shipped
  waiver is Peppery's `text-muted-on-surface` (`#71717a` on `surface-muted` `#f4f4f5`,
  4.40:1). The `__fixtures__/lowcontrast` preset **must fail** (proven via a data provider
  that expects failure, so the suite stays green while proving the gate has teeth).
- **Rendered contrast gate (NX2-07/08)** — `npm run test:contrast` measures real rendered
  text/CTA contrast for every registered preset on home, menu, PDP and cart (local, not in
  CI); `npm run test:visual:dark` holds the dark-preset goldens.
- **`PresetEnqueueOrderTest`** — asserts the `lafka-preset` handle sits between
  `lafka-tokens` and `lafka-style` in the dependency graph.
- **Switcher** — `PresetSwitcherWiringTest`, `PresetPreviewPayloadTest`,
  `PresetPreviewEnqueueTest` (unit) + `tests/e2e/customizer-preset.spec.js` (§4).
- Standard gates each commit: `composer test`, `composer phpcs`, `npm run lint`.

## 10. Worked examples

**`presets/peppery/preset.json` (identity — emits nothing):**
```json
{ "slug": "peppery", "schema": 1, "label": "Peppery",
  "description": "Pizza & poutine — the Lafka default.", "dark": false, "extends": null,
  "tokens": {}, "chrome": {},
  "fonts": { "body": {"family":"Rubik","source":"base"}, "display": {"family":"Fraunces","source":"base"} },
  "category_emoji": {}, "variants": {}, "contrast_exceptions": ["text-muted-on-surface"] }
```
With `active_preset=peppery` and no operator overrides: PTL empty, chrome defaults = literals,
no `data-theme`, base fonts already enqueued → **byte-identical dynamic-css + pixel-identical
goldens**. This is the acceptance proof for NX2-01.

**`presets/midnight/preset.json` (dark, exercises every path):** `dark:true`, `tokens` sets the
dark surface/border/text ramp under the scoped selector, `chrome` sets
`lafka_accent_color` (cyan) + `lafka_brand_color` (lime); base fonts. Activating it: sets
`data-theme=dark`, emits the dark PTL, accent flows through TML (operator-overridable), and
`PresetContrastTest` enforces AA on the dark palette.

## 11. Deferred / open

- **critical.css preset-awareness** (first-paint flash on non-default presets + the
  `#ffca3c`/`#fccc4c` menu-bg drift) → **NX2-04.1**, the intended consumer of
  `LAFKA_PRESET_CRITICAL_KEYS`. `critical.css` is pixel-critical for Peppery, so it stays
  untouched until then.
- **Dedicated `styles/presets/<slug>.css` + `build-presets.mjs` generator** → only if/when
  browser-caching 10 presets justifies it; the inline-only handle (§4) needs no generator and
  costs no request (only one preset is active at a time).
- **theme.json style-variation shadow** (`styles/<slug>.json` generated from preset SSOT so the
  Site-Editor picker gets NX2-06 nearly free) → **NX2-06**; must also manage the picker↔
  `lafka_active_preset` desync (hide the implicit Default / guard divergence).
- **theme.json stale button literals** (`#c43d0d`, `#ffffff`) that don't track accent → NX2-06
  generator work (editor-only, cosmetic).
