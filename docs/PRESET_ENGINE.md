# Lafka Preset Engine — Architecture Blueprint (NX2-01 / NX2-02)

> Synthesized 2026-07-07 from a 3-architect / 3-judge design panel (minimal-integration
> vs dedicated-emitter vs theme.json-native). **Base = dedicated-emitter** (won 2 of 3
> lenses, tied the third), grafted with the minimal-integration inline mechanism and the
> theme.json-native editor-shadow idea. This file is the durable SSOT for the engine; the
> build implements it. Roadmap: `ROADMAP_2026-07-05.md` items NX2-01 (engine) + NX2-02
> (contrast gate).

## 0. Why this shape (the one-fact foundation)

`styles/dynamic-css.php` emits only **two canonical `--lafka-*` tokens** from operator
input — `--lafka-color-accent-500` and `--lafka-color-brand-500` — plus ~55 legacy
*chrome* theme_mods. The other ~200 `--lafka-*` tokens have **no operator feed at all**.
That split is the whole design: a preset's output goes through two layers, each with a
*trivial, independent* "operator always wins" proof.

Peppery becomes **preset #1, the default, and a provable no-op**: it overrides nothing,
so the engine emits nothing for it → byte-identical `dynamic-css` + pixel-identical
30 visual goldens.

## 1. Terminology (PIN THIS — the panel inverted "Channel 1/2"; do not reuse those labels)

- **Base layer** — `styles/lafka-tokens.css` `:root{}`, the static 212-token file. Unchanged
  except the 2-line dark-scaffold accent deletion (§6).
- **Preset-token layer (PTL)** — an inline `:root{}` (or `:root[data-theme="dark"]{}`) block
  carrying a preset's overrides for the ~200 tokens that have **no** operator feed
  (surfaces, borders, text, semantics, radii, shadows, motion, type scale/family). Emitted
  on a dedicated dependency-ordered handle. **`accent-500`, `brand-500`, `accent-text` are
  FORBIDDEN here** (operator-fed or derived).
- **Operator layer** — `dynamic-css.php`'s existing `:root{}` inline on `lafka-style`
  (accent/brand + chrome theme_mods). Always prints last → always wins.
- **theme_mod-default layer (TML)** — the mechanism by which a preset supplies the
  *default* value for the ~57 keys `dynamic-css` emits from theme_mods, via one guarded
  helper. Operator-set theme_mods beat it by `get_theme_mod()` semantics.

Cascade: **Base < PTL < Operator**, with accent/brand/chrome flowing Base-literal ←
preset-default (TML) ← operator-value. Both routes make the operator the final winner.

## 2. File layout & PHP surface (new)

```
lafka-theme/
  presets/
    peppery/preset.json          # preset #1, DEFAULT, identity (empty overrides)
    midnight/ ember/             # dark presets
    verde/ koyo/ terracotta/ azzurro/ brioche/ saffron/ fjord/  # light presets (10 total, NX2-05/08)
    __fixtures__/lowcontrast/preset.json   # NX2-02: must FAIL the contrast gate
  incl/presets/
    class-lafka-preset.php            # value object: reads one preset.json, typed accessors
    class-lafka-presets.php           # registry: discovery, cache, lafka_presets filter, active()
    lafka-preset-tokens.php           # LAFKA_PRESET_TOKEN_WHITELIST + LAFKA_PRESET_CHROME_WHITELIST + LAFKA_PRESET_CRITICAL_KEYS (pure-data constants)
    lafka-preset-emit.php             # PTL builder + enqueue wiring + data-theme + lafka_preset_default()
    lafka-preset-fonts.php            # NX2-03 8-family OFL font registry + per-preset enqueue
    class-lafka-color-contrast.php    # NX2-02 WCAG ratio helper
  docs/PRESET_ENGINE.md               # this file
```

Public function surface (all `function_exists`-guarded, `lafka_` prefixed):
- `lafka_presets()` → `Lafka_Presets` registry (singleton-ish, cached).
- `lafka_active_preset()` → `Lafka_Preset` for the active slug (falls back to `peppery`).
- `lafka_preset_default( string $key, $fallback )` → active preset's chrome default for
  `$key`, else `$fallback`. **Untyped** return (must route composite typography arrays like
  `lafka_h1_font`, not just scalars).
- `lafka_get_active_preset_slug()` → `get_theme_mod( 'lafka_active_preset', 'peppery' )`.
- Filters: `lafka_presets` (register/modify the discovered set), `lafka_active_preset_slug`,
  `lafka_preset_token_whitelist`, `lafka_category_emoji` (existing; preset feeds it).

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
    // whitelist = lafka_accent_color, lafka_brand_color + the ~55 appearance theme_mods
    // that are lafka_legacy_migrate_map() destinations dynamic-css emits
  },
  "fonts": {                       // NX2-03 interface; engine wave uses source:"base" only
    "body":    { "family": "Rubik",    "source": "base" },
    "display": { "family": "Fraunces", "source": "base" }
  },
  "category_emoji": {},            // feeds lafka_category_emoji; empty = hardcoded default
  "variants": {},                  // flat map -> body classes lafka-variant--k-v (inert this wave)
  "contrast_exceptions": []        // audited AA waivers, e.g. ["text-muted-on-surface"]
}
```

**Whitelists are pure-data PHP array constants** in `lafka-preset-tokens.php`, read by BOTH
the emitter and the validator, mirroring the `lafka_legacy_migrate_map()` idiom. An
out-of-whitelist key fails a unit test AND is dropped at emit time (never reaches CSS).
`LAFKA_PRESET_CRITICAL_KEYS` is the single source for the above-fold subset (no per-preset
`critical` field).

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
   - Build the PTL string once, cache under a key folding `active_slug + preset-file-mtime`.
3. **Operator** — `dynamic-css.php`'s existing `:root{}` inline on `lafka-style`. Unchanged
   in structure; §5 wraps its default args.

**Operator-wins proof.** PTL tokens have zero operator feed (dynamic-css emits none of
them; accent/brand/accent-text are forbidden from PTL), so nothing can out-rank a PTL
declaration except the operator's own layer, which prints last. Chrome/accent/brand flow
through §5 as `get_theme_mod()` *defaults*, so any stored operator value wins by definition.
A preset switch writes **only** `lafka_active_preset` — never a chrome theme_mod — so operator
customizations survive a switch untouched.

## 5. theme_mod-default layer (the ~57 wraps in dynamic-css.php)

Wrap each `get_theme_mod( 'lafka_x', <literal> )` in `dynamic-css.php` as:
```php
get_theme_mod( 'lafka_x', function_exists( 'lafka_preset_default' )
    ? lafka_preset_default( 'lafka_x', <literal> ) : <literal> );
```
- `lafka_preset_default` returns the active preset's `chrome['lafka_x']` if set, else the
  literal. **Peppery.chrome is empty** → returns the literal → `DynamicCssParityTest`
  (whose fixture overrides every key, so the default path never fires) stays byte-green,
  AND the new `PresetDefaultsGoldenTest` (§9, all-unset render) proves the default path too.
- The `function_exists` guard means the isolated PHPUnit process (no theme bootstrap) falls
  back to the literal — the grep pattern `get_theme_mod('lafka_*'` at `DynamicCssParityTest`
  is preserved (first arg stays a literal string).
- Untyped return routes composite typography arrays (`lafka_h1_font{}`), closing the
  dark-heading gap the minimal-integration blueprint had.

**Do NOT use site-wide `theme_mod_{key}` filters** (the theme.json-native approach) — they
fire for every reader of the key across the whole site (unpredictable blast radius). The
narrow default-arg wrap is contained to the emission site.

## 6. Dark mode (BOTH: set attribute + emit dark tokens + REQUIRED scaffold fix)

A `dark:true` preset:
1. **Sets the attribute** — adds `data-theme="dark"` to `<html>` via a `language_attributes`
   filter (no setter exists today; `header.php` emits `<html <?php language_attributes(); ?>>`).
   Activates the existing `:root[data-theme="dark"]` scaffold + `color-scheme`.
2. **Emits the delta** — its PTL block under `:root[data-theme="dark"]` fills every token the
   scaffold misses (accent-700, full brand ramp, the four semantics) and prints after the
   scaffold (dep on `lafka-tokens`) so it wins token-for-token.
3. **accent-text direction** — the emitter appends a dark `@supports (color-mix)` block that
   *lightens* accent-text (the base derivation darkens, wrong on dark surfaces), with a static
   fallback.
4. **REQUIRED SCAFFOLD FIX (non-negotiable, all 3 judges):** delete the two
   `--lafka-color-accent-500` / `--lafka-color-accent-600` lines from the
   `:root[data-theme="dark"]` scaffold in `lafka-tokens.css` (~lines 421-422). At (0,2,0)
   they out-rank the operator's (0,1,0) `dynamic-css` accent — an operator override is
   silently ignored in dark mode. Dark accent instead comes from `chrome.lafka_accent_color`
   (TML, operator-overridable). **Peppery never stamps `data-theme`, so deleting these lines
   is provably zero-impact on the 30 goldens** — verify goldens stay green after the deletion.

## 7. Storage, switching, reset

- `lafka_active_preset` **theme_mod** (not option), default `peppery` — **per-stylesheet, so
  child-active-safe** (the NX1-02 trap: prod runs the child theme; theme_mods resolve against
  the active stylesheet). Preset **files** always resolve from the **parent** (child may add
  presets via the `lafka_presets` filter).
- **Cache-bust on switch:** writing `lafka_active_preset` mutates `theme_mods_<stylesheet>`,
  firing the existing `lafka_dynamic_css` bust hook; the PTL's mtime-keyed cache is flushed on
  `switch_theme` / `customize_save_after` / preset-file change. Add the active slug to the
  dynamic-css cache key.
- **Reset to preset** = `remove_theme_mod()` on **only** `array_keys($preset->chrome())` (+ the
  accent/brand/font operator keys), so unset keys fall back to the **ACTIVE** preset's defaults
  (not Peppery's). Reuse the NX1-02 sentinel idiom. Never touches secrets/KDS/functional keys.

## 8. Extensibility

`lafka_presets()` discovers `presets/*/preset.json` in parent (+ child), skips malformed,
applies the `lafka_presets` filter (child/3rd-party registration), and caches in a transient
keyed by a directory-mtime fingerprint. Unknown/typo'd token keys are validated against the
whitelist constants at load (logged in `WP_DEBUG`, dropped at emit).

## 9. Test & gating plan (reuse the existing harnesses)

- **IRON GATE (unchanged, must stay green for Peppery):** the 30 visual goldens
  (`npm run test:visual:nx1-02`) + `DynamicCssParityTest` — both byte/pixel-identical because
  Peppery emits nothing.
- **NEW `PresetDefaultsGoldenTest`** — renders `dynamic-css` with **all theme_mods unset**, per
  active preset, byte-compared to a golden. Closes the documented `DynamicCssParityTest` blind
  spot (its fixture overrides everything, so the default path never fires). **Capture the
  Peppery golden against pre-engine HEAD** so a wrong extraction is caught.
- **NEW `PresetCascadeTest`** — DOM-free resolver proving `Base < PTL < Operator` and
  operator-wins in code (not prose), for a light and a dark preset.
- **NEW `PresetSchemaTest`** — every shipped preset validates; every `tokens` key ∈ token
  whitelist; every `chrome` key ∈ chrome whitelist; no structural/a11y/legacy-alias keys.
- **NEW `PresetContrastTest` (NX2-02)** — for every registered preset, resolve its effective
  palette (base ⊕ PTL ⊕ chrome) and compute WCAG ratios via `Lafka_Color_Contrast` for the
  critical pairs the theme already tests (body-text/surface, accent-text/surface,
  button-text/accent-500, focus-ring, badges), failing any pair < AA **except** audited
  `contrast_exceptions` (Peppery's `text-muted` 4.48 is grandfathered). Includes a
  dark-surface audit (the existing `FocusRingContrastTest` only checks light hex). The
  `__fixtures__/lowcontrast` preset **must fail** this assertion (proven via a data provider
  that expects failure, so the suite stays green while proving the gate has teeth).
- **NEW `PresetEnqueueOrderTest`** — asserts the `lafka-preset` handle sits between
  `lafka-tokens` and `lafka-style` in the dependency graph.
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

## 11. Deferred (explicitly out of the engine wave — keep Peppery footprint at zero)

- **critical.css preset-awareness** (first-paint flash on non-default presets + the
  `#ffca3c`/`#fccc4c` menu-bg drift) → **NX2-04.1**, when the switcher UI makes presets
  operator-facing. Touching `critical.css` (a pixel-critical file) for a benefit that doesn't
  matter until presets are selectable is deferred to minimize Peppery risk this wave.
- **Dedicated `styles/presets/<slug>.css` + `build-presets.mjs` generator** → only if/when
  browser-caching 10 presets justifies it; the inline-only handle (§4) needs no generator and
  costs no request (only one preset is active at a time).
- **theme.json style-variation shadow** (`styles/<slug>.json` generated from preset SSOT so the
  Site-Editor picker gets NX2-06 nearly free) → **NX2-06**; must also manage the picker↔
  `lafka_active_preset` desync (hide the implicit Default / guard divergence).
- **theme.json stale button literals** (`#c43d0d`, `#ffffff`) that don't track accent → NX2-06
  generator work (editor-only, cosmetic).
- **8-font pool + conditional per-preset loading + header.php preload** → **NX2-03** (engine
  wave uses `source:"base"` for both presets, touching no font enqueue).

## 12. Build sequence (each step lands on a green gate)

1. Whitelist constants + `Lafka_Preset` VO + `Lafka_Presets` registry + `lafka_active_preset`
   theme_mod (default peppery). Ship `peppery` (empty) + `PresetSchemaTest`. Gate: all green,
   goldens untouched (nothing emits yet).
2. PTL inline-only handle + dependency edge + `PresetEnqueueOrderTest` + `PresetCascadeTest`.
   Gate: goldens pixel-identical (Peppery PTL empty).
3. `lafka_preset_default` + the ~57 dynamic-css default wraps + `PresetDefaultsGoldenTest`
   (capture Peppery golden on pre-change HEAD FIRST). Gate: `DynamicCssParityTest` +
   defaults-golden byte-green.
4. Dark path: `data-theme` filter + scaffold accent-line deletion + ship `midnight`. Gate:
   goldens still green (Peppery unaffected); live wp-env switch to midnight changes `:root`.
5. NX2-02: `Lafka_Color_Contrast` + `PresetContrastTest` + `__fixtures__/lowcontrast` (must
   fail) + peppery/midnight pass. Gate: all green.
6. Commit this doc as `docs/PRESET_ENGINE.md`; wire `docs/PRESET_ENGINE.md` into DESIGN_SYSTEM.md's
   entry-points table.

## 13. Why not the others

- **Minimal-integration** (score ~50-55): cleanest small diff, but inline print-order cascade
  (less robust than a dependency edge), a 4th SSOT home if Peppery restates chrome, string-typed
  default helper can't route composite typography arrays, no `extends`. We grafted its
  inline-emission (as an inline-only *dependency-ordered* handle) and its `PresetCascadeTest`.
- **theme.json-native** (score ~45-54): best editor alignment and lowest Peppery-byte risk by
  non-modification, BUT a **confirmed operator-override-clobber in dark mode** (it refuses to
  delete the scaffold accent lines, so scaffold (0,2,0) beats operator (0,1,0)) and a site-wide
  `theme_mod_{key}` filter blast radius, plus a Styles-picker↔theme_mod dual-control desync on
  child-active prod. We graft its editor-variation idea as a **generated shadow** in NX2-06
  (SSOT stays the PHP-read `preset.json`, never theme.json) and explicitly fix the dark bug.
