# Lafka Design System

The one reference for the theme's look: design tokens, the preset engine that
re-skins them, the per-surface layout resolver, and the stylesheet entry points.
Edit it in the same change as the code it describes.

## Principles

1. **Mobile-first.** Breakpoints at 600, 768, 1024 and 1280 px.
2. **Token-driven.** No hex literals, no magic spacing, no inline styles outside
   tokenised custom properties.
3. **WCAG AA minimum** for body text (4.5:1). AAA is the target for prose and
   form fields.
4. **One way to do it.** If two rules produce the same result, delete the older.
5. **Conversion before decoration.** Every decision ladders to order completion.

## Architecture

Appearance comes from three layers. Later layers win.

| Layer | Source | Holds |
|-------|--------|-------|
| Base | `styles/lafka-tokens.css` `:root` | Every `--lafka-*` custom property, with the default values, plus the dark scaffold. |
| Preset-token layer (PTL) | inline CSS from the active preset's `tokens` | Overrides for the tokens in `LAFKA_PRESET_TOKEN_WHITELIST` (surfaces, borders, text, semantic colours, radii, shadows, motion, type scale and families). |
| Operator layer | `styles/dynamic-css.php` inline CSS | `--lafka-color-accent-500`, `--lafka-color-brand-500` and the Customizer "chrome" theme_mods. Prints last. |

The operator always wins. PTL tokens have no operator feed, so only the operator
layer can out-rank them. Accent, brand and chrome values reach the operator layer
through `get_theme_mod()` with the preset's value as the default, so a stored
operator value beats the preset by definition. `--lafka-color-accent-500`,
`--lafka-color-brand-500` and `--lafka-color-accent-text` are forbidden in a
preset's `tokens`.

A preset with no `tokens` emits nothing.

### Where theme settings live

Every appearance and behaviour setting the theme owns is a Customizer
`theme_mod` named `lafka_<key>`. `styles/dynamic-css.php` turns them into
`--lafka-*` properties; each reader carries its shipped default inline, so a
fresh install renders the defaults.

To add a setting:

- Register the control in `incl/class-lafka-customizer-bridge.php` (or a sibling
  `incl/customizer-*.php`). Read it with `get_theme_mod( 'lafka_<key>', <default> )`.
  `lafka_get_option()` is a deprecated shim; do not call it.
- To carry a value over from the retired options panel, add the legacy key to
  `lafka_legacy_migrate_map()` in `incl/system/lafka-legacy-migrate.php` and bump
  `LAFKA_LEGACY_MIGRATION_VERSION`.
- The `lafka` option array is plugin-owned storage (module flags and shared
  functional keys). The theme never writes it. Its defaults are in
  `incl/system/lafka-option-defaults.php`.

## Colour tokens

All colours are CSS custom properties in `styles/lafka-tokens.css`. The values
below are the base defaults. Presets override most of them (see Presets).

### Brand (base ramp)

| Token | Hex | Role |
|-------|-----|------|
| `--lafka-color-brand-50` | `#fff7ed` | softest tint, hero backgrounds |
| `--lafka-color-brand-100` | `#ffedd5` | section-fill banners |
| `--lafka-color-brand-300` | `#fdba74` | hover overlays |
| `--lafka-color-brand-500` | `#f59e0b` | brand fill (operator-fed) |
| `--lafka-color-brand-600` | `#d97706` | active and pressed states |
| `--lafka-color-brand-700` | `#b45309` | text on brand tints (AA 4.73:1 on `brand-50`) |
| `--lafka-color-brand-900` | `#451a03` | display text on brand fills |

### Accent (calls to action)

| Token | Hex | Role |
|-------|-----|------|
| `--lafka-color-accent-50` | `#fef2f2` | error-state and warm-panel surface |
| `--lafka-color-accent-500` | `#dc2626` | primary CTA fill (operator-fed) |
| `--lafka-color-accent-600` | `#b91c1c` | CTA hover and pressed |
| `--lafka-color-accent-700` | `#991b1b` | CTA text on light surfaces, focus ring |
| `--lafka-color-accent-contrast` | `#fff` | text on accent fills (AA 4.83:1 on `accent-500`) |
| `--lafka-color-accent-text` | derived | accent used as text (see below) |

### Neutrals

| Token | Hex | Role |
|-------|-----|------|
| `--lafka-color-text-primary` | `#18181b` | body, headings (AAA 17.7:1) |
| `--lafka-color-text-secondary` | `#3f3f46` | meta, captions (AAA 10.4:1) |
| `--lafka-color-text-muted` | `#71717a` | hints, disabled (AA 4.83:1 on page) |
| `--lafka-color-text-inverse` | `#fff` | text on dark surfaces |
| `--lafka-color-surface-page` | `#fff` | page background |
| `--lafka-color-surface-raised` | `#fff` | card fill |
| `--lafka-color-surface-sunken` | `#fafafa` | inset and form-field background |
| `--lafka-color-surface-muted` | `#f4f4f5` | dividers, chip rest |
| `--lafka-color-border-subtle` | `#e4e4e7` | card divider |
| `--lafka-color-border-default` | `#d4d4d8` | form field rest |
| `--lafka-color-border-strong` | `#a1a1aa` | form field hover |
| `--lafka-color-border-focus` | `var(--lafka-color-accent-500)` | focus border |

### Semantic

Success `#047857`, error `#b91c1c`, warning `#b45309`, info `#1d4ed8`
(`--lafka-color-<name>-500`), each with a `-50` tint. All pass AA on white.

### Accent as text

An operator can pick any brand red, and some fail AA as text on white (for
example `#f2002d` gives 4.36:1). `--lafka-color-accent-text` is the accent
darkened for use as text (eyebrows, prices, links):

```css
--lafka-color-accent-text: var(--lafka-color-accent-600); /* fallback */

@supports (color: color-mix(in srgb, red 50%, white)) {
  :root {
    --lafka-color-accent-text:
      color-mix(in srgb, var(--lafka-color-accent-500) 85%, #000);
  }
}
```

Use `accent-text` for accent as text. Use `accent-500` for backgrounds with white
text. An operator's own accent override is not contrast-checked.

### Forbidden

- Pure black `#000`. Use `text-primary`.
- Pure red `#ff0000`.
- Any hex outside the token files.

## Typography

Every preset uses two families, a body and a display face, self-hosted as WOFF2
under `assets/fonts/`. There is no remote font request.

- **Base families** (in `styles/lafka-tokens.css`): Rubik (body) and Fraunces
  (display), `source: "base"`.
- **Font pool** (`incl/presets/lafka-preset-fonts.php`, `LAFKA_FONT_POOL`): ten
  OFL families on disk, listed there with weights, subsets and licence files:
  Archivo, Atkinson Hyperlegible Next, Bricolage Grotesque, DM Serif Display,
  Fraunces, Inter, Lora, Manrope, Rubik, Space Grotesk. Fraunces and Rubik are
  the two base families; the rest are `source: "pool"`.
- A preset names its two families in `fonts`. The engine emits `@font-face` only
  for the active preset's pool families. Entries can be variable fonts (one file
  per subset plus a weight range). The `lafka_font_pool` filter adds families.
- `font_display` per role is `swap` (default) or `optional`. A preset with
  `optional` preloads its latin display and body files and skips the static
  Fraunces preloads, so the first view has no font layout shift.
- Archivo has no 800 weight; presets using it pin display weight to 700.

### Type scale (mobile first)

| Token | Mobile | Desktop (768 px and up) | Weight |
|-------|--------|-------------------------|--------|
| `--lafka-font-size-display` | `2.5rem` | `clamp(2.5rem, 4vw + 1rem, 4.5rem)` (`-display-desk`) | 800 |
| `--lafka-font-size-h1` | `clamp(2.5rem, 5vw, 3.5rem)` | same | 800 |
| `--lafka-font-size-h2` | `1.5rem` | `2rem` (`-h2-desk`) | 600 |
| `--lafka-font-size-h3` | `1.25rem` | `1.5rem` (`-h3-desk`) | 700 |
| `--lafka-font-size-h4` | `1.125rem` | `1.25rem` (`-h4-desk`) | 700 |
| `--lafka-font-size-body-lg` | `1.0625rem` | `1.125rem` (`-body-lg-desk`) | 400 |
| `--lafka-font-size-body` | `1rem` | `1rem` | 400 |
| `--lafka-font-size-body-sm` | `0.9375rem` | `0.9375rem` | 400 |
| `--lafka-font-size-caption` | `0.8125rem` | `0.8125rem` | 500 |

`--lafka-font-size-body-desk` defaults to the body size. Only the counter layout
reads it, at 768 px and up. Line heights: display 1.1, headings 1.15, body 1.5,
small 1.4.

### Forbidden

- Script, handwritten or decorative fonts. Identity comes from photography,
  colour and the serif display face.
- Font sizes outside the token table, and visible text below 14 px.

## Spacing, radii, elevation, motion

**Spacing** (8 px base): `--lafka-space-1` 4, `-2` 8, `-3` 12, `-4` 16, `-5` 20,
`-6` 24, `-8` 32, `-10` 40, `-12` 48, `-16` 64, `-20` 80 px. Use the named
tokens, not raw px.

**Radii:** `xs` 2 px, `sm` 6 px, `md` 10 px (fields, small buttons), `lg` 16 px
(cards, modals), `xl` 24 px, `pill` 999 px. `--lafka-radius-button` defaults to
pill; presets and the counter layout re-point it.

**Elevation:** `--lafka-shadow-0` (none) to `--lafka-shadow-4` (modal).
`--lafka-shadow-focus` is the focus indicator: a 2 px `surface-page` spacer then a
4 px solid `accent-700` ring (8.31:1 on white; the spacer keeps it visible on
accent and ink fills, WCAG 1.4.11). Every focusable control reads this token.

**Motion:** `--lafka-motion-duration-fast` 120 ms, `-base` 200 ms, `-slow` 320 ms;
`--lafka-motion-ease-out` `cubic-bezier(0.2, 0.8, 0.4, 1)`,
`--lafka-motion-ease-in-out` `cubic-bezier(0.4, 0, 0.2, 1)`. Under
`prefers-reduced-motion: reduce` the duration tokens are `0ms`.

**Breakpoints:** mobile < 600, tablet 600-767, laptop 768-1023, desktop
1024-1279, wide 1280 and up. Container max-width 1440 px. Page gutter 16 px
(mobile) and 32 px (laptop and up).

## Presets

A preset is a folder in `presets/<slug>/` with a `preset.json` and a
`preview.jpg` (620x465, the Customizer thumbnail). Ten ship with the theme:

| Slug | Mode | Fonts (display / body) |
|------|------|------------------------|
| `peppery` (default) | light | Bricolage Grotesque / Atkinson Hyperlegible Next |
| `ember` | dark | Archivo / Inter |
| `midnight` | dark | Fraunces / Rubik |
| `verde` | light | Fraunces / Inter |
| `koyo` | light | DM Serif Display / Inter |
| `terracotta` | light | Archivo / Rubik |
| `azzurro` | light | Lora / Inter |
| `brioche` | light | DM Serif Display / Rubik |
| `saffron` | light | Fraunces / Manrope |
| `fjord` | light | Manrope / Inter |

Peppery is "The counter": warm white surfaces, tomato accent `#B0271D`, leaf-green
brand `#2E6A3B`, pool fonts with `font_display: optional`, 17/18 px body, and the
counter layout on every surface (see Layouts). The other nine keep the classic
layouts.

### Files

```
presets/<slug>/preset.json, preview.jpg
incl/presets/
  class-lafka-preset.php                   one preset: typed accessors, validate()
  class-lafka-presets.php                  registry: discovery, cache, lafka_presets filter
  lafka-preset-tokens.php                  LAFKA_PRESET_TOKEN_WHITELIST, _CHROME_WHITELIST,
                                           _VARIANT_WHITELIST, _CRITICAL_KEYS (pure data)
  lafka-preset-emit.php                    PTL builder, @font-face emitter, enqueue wiring,
                                           data attributes, lafka_preset_default()
  lafka-preset-fonts.php                   LAFKA_FONT_POOL
  class-lafka-color-contrast.php           WCAG ratio helper
  lafka-preset-customizer.php              Customizer section, control, live-preview payloads
  class-lafka-customize-preset-control.php radio-image control
incl/system/lafka-preset-reset.php         "Reset appearance to preset"
incl/system/class-lafka-preset-cli-command.php   wp lafka preset ...
assets/customizer/lafka-preset-preview.js  preview-iframe swap script
```

### API

- `lafka_presets()` returns the `Lafka_Presets` registry.
- `lafka_active_preset()` returns the active `Lafka_Preset` (falls back to
  `peppery`).
- `lafka_get_active_preset_slug()` reads the `lafka_active_preset` theme_mod
  (default `peppery`) through the `lafka_active_preset_slug` filter.
- `lafka_preset_default( $key, $fallback )` returns the active preset's chrome
  default for `$key`, else `$fallback`. The return is untyped so composite
  typography arrays work.
- `lafka_preset_variant( $key, $fallback )` returns a preset layout variant.
- `lafka_preset_preview_payloads()` returns, per preset, the CSS strings the
  Customizer preview swaps in.
- `lafka_sanitize_preset_slug()` is the setting's sanitize callback (unknown
  becomes `peppery`).
- Filters: `lafka_presets` (add or change presets), `lafka_active_preset_slug`,
  `lafka_font_pool`, `lafka_category_emoji`, `lafka_critical_css_async_handles`.
  The whitelists are constants and not filterable.

### `preset.json`

```jsonc
{
  "slug": "midnight",          // must equal the folder name; sanitize_key identity
  "schema": 1,
  "label": "Midnight",
  "description": "Late-night diner: neon on black.",
  "dark": true,                // default false
  "extends": null,             // slug of a preset to deep-merge over, or null
  "tokens": {                  // PTL overrides; every key must be in LAFKA_PRESET_TOKEN_WHITELIST
    "--lafka-color-surface-page": "#0a0a0a",
    "--lafka-color-text-primary": "#fafafa"
  },
  "chrome": {                  // default values for Customizer theme_mods;
    "lafka_accent_color": "#22d3ee",   // every key must be in LAFKA_PRESET_CHROME_WHITELIST
    "lafka_brand_color": "#a3e635"
  },
  "fonts": {
    "body":    { "family": "Rubik",    "source": "base" },
    "display": { "family": "Fraunces", "source": "base", "font_display": "swap" }
  },
  "category_emoji": {},        // product_cat slug => glyph, feeds lafka_category_emoji
  "variants": {},              // layout defaults, keys in LAFKA_PRESET_VARIANT_WHITELIST
  "contrast_exceptions": []    // audited AA waivers
}
```

`validate()` rejects a file preset with a missing or malformed slug, an
unsupported schema, any key outside the whitelists, an invalid `font_display`, or
an invalid variant key or value. The registry skips it and logs under `WP_DEBUG`.
Presets added through the `lafka_presets` filter skip `validate()`, so the
emitter also drops any non-whitelisted token.

The token whitelist excludes `accent-500`, `brand-500`, `accent-text`, spacing and
gap tokens, z-index, container and gutter sizes, tap-target sizes, and the legacy
alias tokens.

`variants` keys are `header_layout`, `home_layout`, `menu_layout`, `footer_layout`
and `drawer_layout` (each `classic` or `counter`), and `motif` (`none` or
`check`).

### Emission

1. Base: `lafka-tokens.css` on the `lafka-tokens` handle.
2. PTL: an inline-only style handle, `lafka-preset`, registered with no source
   (no extra request) and a dependency on `lafka-tokens`, so it prints after the
   base. `lafka-style` depends on it so the operator layer still prints last.
   A light preset emits `:root{...}`. A dark preset emits
   `:root[data-theme="dark"]{...}`.
3. Fonts: a second inline-only handle, `lafka-preset-fonts`, carrying `@font-face`
   rules for the active preset's pool families.
4. Operator: `styles/dynamic-css.php` inline on `lafka-style`. Its chrome reads go
   through `lafka_preset_default()`:

   ```php
   get_theme_mod( 'lafka_x', function_exists( 'lafka_preset_default' )
       ? lafka_preset_default( 'lafka_x', <literal> ) : <literal> );
   ```

   Do not use site-wide `theme_mod_{key}` filters for this. They affect every
   reader of the key.

The PTL and font strings are rebuilt each request from the cached registry.

`<html>` gets `data-lafka-preset="<slug>"` for any preset other than `peppery`
(`lafka_preset_language_attributes()`, on the `language_attributes` filter), and
`data-theme="dark"` for a dark preset.

### Dark mode

Dark mode is a dark preset, not `prefers-color-scheme`, and there is no operator
toggle. A `dark: true` preset:

1. Gets `data-theme="dark"` on `<html>`, which activates the
   `:root[data-theme="dark"]` scaffold in `lafka-tokens.css` (text, surface,
   border, `accent-50` and shadow tokens, plus `color-scheme`).
2. Emits its PTL under `:root[data-theme="dark"]` (specificity 0,2,0), which
   fills the tokens the scaffold leaves out and wins token for token.
3. Gets a dark `accent-text`: the emitter lightens the accent toward white
   (`color-mix(... 80%, #fff)`, with a static fallback), because the base
   derivation darkens.

The scaffold declares no `accent-500` or `accent-600`. At 0,2,0 they would out-rank
the operator's 0,1,0 accent and silently ignore the override. The dark accent comes
from the preset's `chrome.lafka_accent_color`, which the operator can override.

### Storage, caches and reset

- The active preset is the `lafka_active_preset` theme_mod (per stylesheet, so it
  is correct when a child theme is active). Switching writes only this value and
  never touches an operator's other theme_mods.
- Presets are discovered in the parent's `presets/` and, when a child theme is
  active, the child's `presets/`. A child preset with the same slug replaces the
  parent's. The `lafka_presets` filter can add more.
- Registry cache: transient `lafka_presets_<md5>` (one day), keyed by every
  `preset.json` path and mtime, so adding, editing or removing a file busts it.
- Dynamic CSS cache: key
  `lafka_dyncss_v<lafka_dynamic_css_version>_t<theme version>_<locale>_p<preset slug>`,
  kept in the object cache (group `lafka`, one day) and a transient (one week).
  Saving the `lafka` option or `theme_mods_<stylesheet>` bumps
  `lafka_dynamic_css_version`. Inside the Customizer preview it is always rebuilt
  and never cached.
- **Reset to preset:** `lafka_preset_reset_appearance( $dry_run )` removes only the
  active preset's chrome keys, the chrome whitelist, and the accent and brand
  mods, after saving them to a non-autoloaded `lafka_appearance_backup_<stamp>`
  option (index in `lafka_appearance_backups`). Unset keys then fall back to the
  active preset's defaults. `lafka_preset_restore_appearance( $backup )` undoes it.
  It never touches business info, layout, module, checkout or KDS settings.
  Surfaces: Customizer, Design Preset, "Reset appearance" (needs
  `edit_theme_options` and a nonce), and
  `wp lafka preset reset [--dry-run]`, `wp lafka preset restore <backup>`,
  `wp lafka preset backups`.

### Customizer live preview

The switcher previews CSS built by the real emitters, with no client-side style
math. `lafka_preset_preview_payloads()` builds, per preset, the PTL, the font CSS
and `lafka_dynamic_css_build()`. On selection, `lafka-preset-preview.js` swaps the
three live `<style>` blocks (`lafka-preset-inline-css`,
`lafka-preset-fonts-inline-css`, `lafka-style-inline-css`) and flips
`html[data-theme]`. Accent and brand bind to their `--lafka-*` variables over
postMessage.

In a live Customizer session WordPress pins every flat theme_mod to the value
captured with the active preset, which would make all payloads copies of the
active one. The payload builder suspends those pins for the build and restores
them in a `finally` block, with posted changeset values still winning. After any
change here, switch presets in a live Customizer session and check that each
preview differs.

### Authoring a preset

- Set only whitelisted keys. Put the accent and brand in `chrome`, not `tokens`.
- Measure the effective palette (base, PTL, chrome and derived `accent-text`) with
  `Lafka_Color_Contrast`: body text on surface, accent text on surface, button
  text on `accent-500`, focus ring, badges. Every pair must reach AA. A waiver in
  `contrast_exceptions` must stay at or above the AA-large floor (3.0).
- Pool fonts need the family in `LAFKA_FONT_POOL`. Add a `preview.jpg`.
- Regenerate the editor palette after changing base tokens:
  `npm run build:theme-json` rewrites the colour, font-size, font-family and
  spacing presets in `theme.json` from `lafka-tokens.css`.

## Layouts

Five storefront surfaces each render in one of two layouts: `classic` or `counter`
(design direction C). A motif, `none` or `check`, rides alongside.

Resolution per surface: operator theme_mod, then the active preset's `variants`,
then `classic`.

- Surfaces: `header`, `home`, `menu`, `footer`, `drawer`
  (`lafka_layout_surfaces()`).
- Customizer: Lafka Settings, Page layouts (`lafka_<surface>_layout`,
  `lafka_motif`; the control's default is the preset variant, so an operator who
  never touches them follows the preset).
- `lafka_layout( $surface )` returns the layout. `lafka_layout_is( $surface,
  'counter' )` branches templates. `lafka_any_counter_layout()` is true when any
  surface is `counter`. The `lafka_layout` filter has the last word.
- Body classes are added for non-classic choices only: `lafka-layout-<surface>-counter`
  and `lafka-motif-<motif>`. Classic markup is unchanged.
- While the drawer is `counter`, the theme declares `lafka-drawer-stepper` support,
  which the plugin reads to render its quantity-stepper row.

All of this lives in `incl/template-helpers/layout.php` and
`incl/customizer-counter.php`.

### The counter layout

`styles/lafka-counter.css` loads only while a surface uses the counter layout. It
uses tokens only, so any preset and the operator accent re-skin it.

- Tokens it adds (no-op defaults): `--lafka-font-size-body-desk`,
  `--lafka-radius-button`, `--lafka-motif-check-a/-b/-size/-h` (the checkered band;
  `a` tracks the operator accent), `--lafka-dish-shadow`.
- Components: `.lafka-counter-btn` (`--primary`, `--lg`, `--link`),
  `.lafka-counter-head` (`--ruled`), `.lafka-row` (`--photo`, `--compact`) with
  `.lafka-prices` (size-price columns), `.lafka-chooser` (the two-tap size chooser,
  a native `<dialog>`), `.lafka-deal` (`--featured`, `--photo`, `--text`),
  `.lafka-counter-hero`, `.lafka-jump`, `.lafka-counter-find`,
  `.lafka-counter-bar` (sticky mobile Call and Order bar, above the consent
  banner), `.lafka-cart-drawer--counter`, `.lafka-footer--counter`.
- The motif (`.lafka-motif-check`) draws only under `body.lafka-motif-check` and is
  hidden in forced-colors mode.
- Header and hero geometry is in `styles/critical-counter.css`, inlined so first
  paint does not shift. Under a counter header or home layout,
  `lafka_inline_critical_css()` also inlines the active preset's
  `LAFKA_PRESET_CRITICAL_KEYS` as a `:root{}` block.

## Component primitives

Shared primitives live in `styles/lafka-components.css` and `styles/product-card.css`.
New button-like UI reuses them.

- `.lafka-btn`: base button. Modifiers `--primary` (accent fill), `--ghost`
  (outline, inverts on hover), `--lg` (52 px minimum height).
- `.lafka-status-pill`: open and closed badge (`--closed`).
- `.lafka-product-card`: product list row, image left and body right.

## Stylesheets

Tokens are the contract. These are the files that consume them.

| File | Role |
|------|------|
| `styles/lafka-tokens.css` | Token source: colour, type, space, radii, motion, dark scaffold, `accent-text` derivation. |
| `styles/dynamic-css.php` | Operator layer: the accent and brand overrides and the chrome theme_mods, resolved through the active preset. |
| `incl/presets/`, `presets/*/preset.json` | The preset engine (above). |
| `styles/lafka-base.css` | Structural rules the parent's markup needs on its own: `.section-subtitle`, ingredient lists, `.screen-reader-text`, carousel height reservation. |
| `styles/lafka-counter.css`, `styles/critical-counter.css` | Counter layout and its inlined first-paint slice. |
| `styles/lafka-search.css` | Header search overlay (native `<dialog>`). |
| `styles/pdp-redesign.css` | Product page. |
| `styles/editorial.css` | Long-form page layouts. |
| `styles/product-card.css`, `styles/lafka-menu-archive.css` | Menu archive cards. |
| `styles/critical.css` | Above-the-fold CSS, inlined. |

Stylesheets are render-blocking by default, because a surface's sheet paints the
first viewport. Only off-screen modules (drawers, dialogs, overlays, fixed bars,
the footer) and vendored libraries load asynchronously, listed in
`lafka_critical_css_async_handles()`. A new off-screen sheet opts in there, and its
closed state belongs in `critical.css` so it cannot flash. Keep CLS at or below
0.02 on the order path.

## Changing the system

- Edit this file and `styles/lafka-tokens.css` in the same change.
- Record the WCAG ratio for any new colour pair.
- Run `npm run build:theme-json` after a base token change and commit the result.
- Run the gates: `npm run check-version`, `composer lint:php`, `composer phpcs`,
  `npm run lint`, `npm run build`.
- Keep Peppery rendering the counter design and the other nine presets stable
  unless the change is deliberate.
