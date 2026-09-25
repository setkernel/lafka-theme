# Lafka Design System

Single source of truth for every visual decision in the theme. If you can't
find an answer here, the answer doesn't exist yet — propose it via PR.

**Locked**: 2026-05-15 design lock; current theme v7.3.0.

## Principles

1. **Mobile-first**, breakpoints at 600px / 768px / 1024px / 1280px.
2. **Token-driven**: no hex literals, no magic spacing, no inline styles
   outside tokenized custom properties.
3. **WCAG-AA minimum** for body text (≥4.5:1 contrast); AAA targeted for
   prose and form fields.
4. **One way to do it**: if two CSS rules can produce the same visual
   result, the older one is wrong. Delete it.
5. **Conversion before decoration**: every decision laddered to order
   completion. Pretty without functional payoff is a regression.

## Color system

All values exposed via CSS custom properties in `styles/lafka-tokens.css`.

### Brand — pepper yellow (operator identity, locked)

| Token                          | Hex      | Role                                            |
|--------------------------------|----------|-------------------------------------------------|
| `--lafka-color-brand-50`       | `#fff7ed` | softest tint, hero backgrounds                |
| `--lafka-color-brand-100`      | `#ffedd5` | section-fill banners                          |
| `--lafka-color-brand-300`      | `#fdba74` | hover overlays                                |
| `--lafka-color-brand-500`      | `#f59e0b` | brand fill (primary yellow)                   |
| `--lafka-color-brand-600`      | `#d97706` | active/pressed states                         |
| `--lafka-color-brand-700`      | `#b45309` | text-on-yellow (AA 4.73:1 on `brand-50`)      |
| `--lafka-color-brand-900`      | `#451a03` | display text on yellow (AAA on `brand-50`/`-100`; AA 6.97:1 on `brand-500`) |

### Accent — pizza red (calls to action)

| Token                          | Hex      | Role                                            |
|--------------------------------|----------|-------------------------------------------------|
| `--lafka-color-accent-50`      | `#fef2f2` | error-state surface                           |
| `--lafka-color-accent-500`     | `#dc2626` | primary CTA fill, "Add to Cart"               |
| `--lafka-color-accent-600`     | `#b91c1c` | CTA hover/pressed                             |
| `--lafka-color-accent-700`     | `#991b1b` | CTA text on light surfaces                    |
| `--lafka-color-accent-contrast`| `#ffffff` | text on accent fills (AA 4.83:1 on `accent-500`) |

### Neutrals (text + surfaces)

| Token                            | Hex      | Role                                          |
|----------------------------------|----------|-----------------------------------------------|
| `--lafka-color-text-primary`     | `#18181b` | body, headings (AAA 17.7:1 on surface-page)  |
| `--lafka-color-text-secondary`   | `#3f3f46` | meta, captions (AAA 10.4:1 on surface-page)  |
| `--lafka-color-text-muted`       | `#71717a` | hints, disabled (AA 4.83:1 on surface-page; 4.40:1 on surface-muted — Peppery's one audited contrast waiver) |
| `--lafka-color-text-inverse`     | `#ffffff` | text on dark surfaces                        |
| `--lafka-color-surface-page`     | `#ffffff` | page background                              |
| `--lafka-color-surface-raised`   | `#ffffff` | card fill                                    |
| `--lafka-color-surface-sunken`   | `#fafafa` | inset / form field bg                        |
| `--lafka-color-surface-muted`    | `#f4f4f5` | section dividers, chip rest                  |
| `--lafka-color-border-subtle`    | `#e4e4e7` | card divider                                 |
| `--lafka-color-border-default`   | `#d4d4d8` | form field rest                              |
| `--lafka-color-border-strong`    | `#a1a1aa` | form field hover                             |
| `--lafka-color-border-focus`     | `var(--lafka-color-accent-500)` | form focus ring         |

### Semantic

Success `#047857`, error `#b91c1c`, warning `#b45309`, info `#1d4ed8`.
Each paired with a 50-tint background; all WCAG-AA on white.

### Operator accent override + `accent-text` derivation

The accent ramp is the one color an operator may override (via Customizer,
flowing through `styles/dynamic-css.php` as the SSOT). Because an operator can
pick any brand red — e.g. an operator red like `#f2002d`, which yields only 4.36:1
accent-on-white (sub-AA) — there is a dedicated **`--lafka-color-accent-text`**
token for accent rendered as *text* (eyebrows, prices, link colors). It is
derived 15% darker from the operator's accent:

```css
--lafka-color-accent-text: var(--lafka-color-accent-600); /* fallback */

@supports (color: color-mix(in srgb, red 50%, white)) {
  :root {
    --lafka-color-accent-text:
      color-mix(in srgb, var(--lafka-color-accent-500) 85%, #000);
  }
}
```

The `color-mix(... 85% ..., #000)` darken clears AA for any reasonable mid-tone
accent; older browsers (Safari <16.4 / Firefox <113 / Chrome <111) fall back to
`accent-600`. Use `accent-text` for accent-as-text; keep `accent-500` for accent
*backgrounds* with white text (4.83:1 on the default `#dc2626`; `PresetContrastTest` gates
button-text-on-accent for every preset, but an operator's own accent override is not
gated — `#f2002d` with white text is 4.36:1).

### Dark mode (dark presets)

Dark mode is driven by **`dark: true` presets** — Midnight and Ember ship built in.
The active dark preset stamps `data-theme="dark"` on `<html>` (a
`language_attributes` filter in the preset engine) and emits its own palette under
`:root[data-theme="dark"]`; see [`docs/PRESET_ENGINE.md`](docs/PRESET_ENGINE.md) §6.
It is *not* driven by `prefers-color-scheme` (many components assume which surface is
light, so an automatic flip broke them) and there is no separate operator toggle —
choosing a dark preset is the switch. The base scaffold in `styles/lafka-tokens.css`
(`:root[data-theme="dark"]`) re-points the text, surface, border, `accent-50` and shadow
tokens; it deliberately declares **no** `accent-500`/`-600`, so the dark accent comes from
the preset's chrome default and an operator accent override still wins.

### Forbidden

- ❌ Pure black `#000` — use `text-primary` (#18181b).
- ❌ Pure red `#ff0000` — too saturated for screens.
- ❌ Any hex outside this table.

## Typography

Every preset uses two families — body and display — all self-hosted WOFF2 under
`assets/fonts/`.

**Peppery (default, GX4 "counter")** uses two *pool* families with
`font-display: optional` and preloads their first-view files (zero font layout shift):
**Atkinson Hyperlegible Next** (body, 400/700 — the design's 500 maps to 400) and
**Bricolage Grotesque** (display, one variable opsz + wght 200–800 file per subset).
Body is 17 px on phones and 18 px from 768 px (`--lafka-font-size-body-desk`, counter
layouts only); display weight 800; buttons use `--lafka-radius-button` (8 px).

The base families below remain for the identity fixture and any preset that uses
`source: "base"`:

| Family    | Role        | Weights loaded   | License |
|-----------|-------------|------------------|---------|
| **Rubik**     | UI / body / small headings | 400, 600, 700 (`style.css`) | OFL |
| **Fraunces**  | Display / h1 / h2 only     | 600, 800 site-wide (`styles/lafka-tokens.css`); 400/600/800 + italics on the editorial templates (`styles/editorial.css`) | OFL |

**Other presets** pick their pair from the 8-family OFL pool in
`incl/presets/lafka-preset-fonts.php` (Rubik, Fraunces, Inter, Archivo, Lora, Manrope,
Space Grotesk, DM Serif Display). Pool families are emitted as inline `@font-face` with
`font-display: swap`, and only the active preset's two families are loaded.

### Type scale (1.25 modular, mobile-first, fluid where it matters)

| Token                          | Mobile (`< 600px`) | Desktop (`≥ 768px`) | Family   | Weight |
|--------------------------------|--------------------|---------------------|----------|--------|
| `--lafka-font-size-display`    | `2.5rem` (40px)    | `clamp(2.5rem, 4vw + 1rem, 4.5rem)` | Fraunces | 800 |
| `--lafka-font-size-h1`         | `clamp(2.5rem, 5vw, 3.5rem)` (40–56px) | same token | Fraunces | 800 |
| `--lafka-font-size-h2`         | `1.5rem` (24px)    | `2rem` (32px)       | Fraunces | 600    |
| `--lafka-font-size-h3`         | `1.25rem` (20px)   | `1.5rem` (24px)     | Rubik    | 700    |
| `--lafka-font-size-h4`         | `1.125rem` (18px)  | `1.25rem` (20px)    | Rubik    | 700    |
| `--lafka-font-size-body-lg`    | `1.0625rem` (17px) | `1.125rem` (18px)   | Rubik    | 400    |
| `--lafka-font-size-body`       | `1rem` (16px)      | `1rem` (16px)       | Rubik    | 400    |
| `--lafka-font-size-body-sm`    | `0.9375rem` (15px) | `0.9375rem` (15px)  | Rubik    | 400    |
| `--lafka-font-size-caption`    | `0.8125rem` (13px) | `0.8125rem` (13px)  | Rubik    | 500    |

Line-heights: display 1.1, headings 1.15, body 1.5, small 1.4.

### Forbidden

- ❌ Script/handwritten/decorative fonts. Restaurant-genre identity is
  done through **photography + color + Fraunces serif**, never via
  Pacifico/cursive lookalikes.
- ❌ Font sizes outside the token table.
- ❌ (counter layout) Italic accents in headings — the counter renders `<em>` upright; no
  visible text below 14 px (the e2e suite measures it).

## Spacing

8px-base scale (already in tokens). Use named tokens, not raw px.

| Token              | Value     | Typical use                |
|--------------------|-----------|----------------------------|
| `--lafka-space-1`  | 4px       | hairline gap, icon padding |
| `--lafka-space-2`  | 8px       | inline gap, small padding  |
| `--lafka-space-3`  | 12px      | tight padding              |
| `--lafka-space-4`  | 16px      | default card padding       |
| `--lafka-space-5`  | 20px      | mobile section padding     |
| `--lafka-space-6`  | 24px      | desktop card padding       |
| `--lafka-space-8`  | 32px      | section gap                |
| `--lafka-space-10` | 40px      | hero block padding         |
| `--lafka-space-12` | 48px      | large hero, page bottom    |
| `--lafka-space-16` | 64px      | section separator          |
| `--lafka-space-20` | 80px      | huge hero (desktop only)   |

## Radii

| Token              | Value | Use                                            |
|--------------------|-------|------------------------------------------------|
| `--lafka-radius-xs` | 2px  | inline tags                                    |
| `--lafka-radius-sm` | 6px  | toast / chip                                   |
| `--lafka-radius-md` | 10px | form fields, small buttons                     |
| `--lafka-radius-lg` | 16px | cards, modals                                  |
| `--lafka-radius-xl` | 24px | hero blocks, large cards                       |
| `--lafka-radius-pill` | 999px | CTAs, status badges                          |

## Elevation (shadows)

Five-level scale. `shadow-0` (none) → `shadow-4` (modal overlay).
`shadow-focus` is the 2-ring focus indicator: a 2px `surface-page` spacer, then a 4px
solid `accent-700` ring (8.31:1 on white; the spacer keeps it visible on accent and ink
fills — WCAG 1.4.11).

## Motion

| Token                           | Value          | Use                              |
|---------------------------------|----------------|----------------------------------|
| `--lafka-motion-duration-fast`  | 120ms          | hover state                      |
| `--lafka-motion-duration-base`  | 200ms          | menu open, card hover            |
| `--lafka-motion-duration-slow`  | 320ms          | modal/drawer                     |
| `--lafka-motion-ease-out`       | cubic-bezier(0.2, 0.8, 0.4, 1) | exit motion       |
| `--lafka-motion-ease-in-out`    | cubic-bezier(0.4, 0, 0.2, 1)   | reversible        |

Respect `prefers-reduced-motion` — under `reduce` the `--lafka-motion-duration-*` tokens
collapse to `0ms`.

## Breakpoints

| Name      | Range          | Use                                          |
|-----------|----------------|----------------------------------------------|
| mobile    | < 600px        | phones (default)                             |
| tablet    | 600–767px      | large phones / small tablets                 |
| laptop    | 768–1023px     | tablets / small laptops                      |
| desktop   | 1024–1279px    | standard desktop                             |
| wide      | ≥ 1280px       | wide desktop                                 |

Container max-width: 1440px. Page gutter: 16px (mobile) / 32px (laptop+).

## Component primitives

Shared primitives (`styles/lafka-components.css`, `styles/product-card.css`). New
button-like UI reuses these rather than inventing another.

- **`.lafka-btn`** — base button. Modifiers:
  - `--primary` (accent fill, `accent-contrast` text)
  - `--ghost` (transparent with ink outline; inverts on hover)
  - `--lg` (52px min-height)
- **`.lafka-status-pill`** — open/closed service badge (`--closed` modifier).
- **`.lafka-product-card`** — product list row. Image-left + body-right.

Planned, not yet built: `.lafka-btn--secondary` / `--brand`, `.lafka-chip`,
`.lafka-input` + `.lafka-label`, `.lafka-card` (`--raised` / `--sunken`).

### Counter layout (GX4, `styles/lafka-counter.css`)

Design direction C ("The counter") — Peppery's default; any preset opts in per surface
(Customizer → Lafka Settings → Page layouts, `lafka_<surface>_layout`, or a preset's
`variants`). Tokens only; loaded only while a surface uses it.

- **Tokens added (no-op base values):** `--lafka-font-size-body-desk` (= body),
  `--lafka-radius-button` (= pill; `.lafka-btn` and the primary CTAs read it),
  `--lafka-motif-check-a/-b/-size/-h` (checkered band; `a` tracks the operator accent),
  `--lafka-dish-shadow` (none).
- **`.lafka-counter-btn`** (`--primary`, `--lg`, `--link`) — worded 48 px+ buttons.
- **`.lafka-counter-head`** (`--ruled`) — section heading + tagline + "See all".
- **`.lafka-row`** (`--photo`, `--compact`) + **`.lafka-prices`** — product row with
  size-price columns (`lafka_price_columns()`), worded Add (button, never inside a link).
- **`.lafka-chooser`** — the 2-tap size chooser (native `<dialog>`).
- **`.lafka-deal`** (`--featured`, `--photo`, `--text`), `.lafka-counter-hero`,
  `.lafka-jump`, `.lafka-counter-find`, `.lafka-counter-bar` (sticky mobile Call/Order,
  above the consent banner), `.lafka-cart-drawer--counter`, `.lafka-footer--counter`.
- **Motif:** `.lafka-motif-check`, drawn only under `body.lafka-motif-check`, hidden in
  forced-colors mode.
- Header + hero geometry lives in `styles/critical-counter.css` (inlined) so first
  paint does not shift.

## WPBakery

WPBakery is optional: default templates render without it; existing content keeps
working.

## Stylesheet entry points

Tokens are the contract; these are the key files that consume them.

| File | Role |
|------|------|
| `styles/lafka-tokens.css` | The token SSOT — color/type/space/radii/motion, dark-mode block, accent-text derivation. |
| `styles/dynamic-css.php` | Emits the operator's Customizer accent override into the cascade. Its 34 chrome defaults (29 `lafka_preset_default()` call sites) resolve through the active preset (operator theme_mods still win). |
| `incl/presets/` + `presets/*/preset.json` | **Preset engine** — the "10 designs in one theme" system; file layout: see [`docs/PRESET_ENGINE.md`](docs/PRESET_ENGINE.md) §2. Peppery is preset #1 and the default (the counter design since 7.2.0); the engine's no-op is the `__fixtures__/identity` preset. |
| `styles/lafka-base.css` | **Parent baseline a11y / CLS** — structural rules the parent's own markup depends on (`.section-subtitle`, `.foodmenu-unit-info .ingredients`, `.screen-reader-text`, pre-mount `.lafka-owl-carousel` height reservation). Previously these lived only in lafka-child, leaving the OSS parent non-accessible on its own. |
| `styles/lafka-search.css` | Header search overlay — native `<dialog>`; consumes tokens with neutral fallbacks. |
| `styles/pdp-redesign.css` | Redesigned product page. |
| `styles/editorial.css` | Editorial / long-form page layouts. |
| `styles/product-card.css`, `styles/lafka-menu-archive.css` | List-card menu archive. |

## Updating this system

Open `DESIGN_SYSTEM.md` and `styles/lafka-tokens.css` in the same PR.
Add the WCAG ratio for any new color pair in the table above. If you
can't justify the change in one sentence on the PR, the change is wrong.

### Where theme settings live (config SSOT)

As of theme 7.0 the legacy **Options Framework** (`incl/lafka-options-framework/`,
the single `wp_options['lafka']` array read via `lafka_get_option()`) is retired.
Every appearance/behaviour setting the theme owns is now a **Customizer
`theme_mod`**, namespaced `lafka_<key>`, and `styles/dynamic-css.php` emits its
`--lafka-*` tokens from those `theme_mods` (with the shipped default inline at
each reader, so a fresh install renders the pixel-perfect defaults). To add or
change a setting:

- Register the control in `incl/customizer-bridge.php` (or a sibling
  `incl/customizer-*.php` panel) writing a `theme_mod` named `lafka_<key>`, and
  read it with `get_theme_mod( 'lafka_<key>', <default> )` — never re-introduce a
  `lafka_get_option()` read for a theme setting (it is a deprecated back-compat
  shim; `tests/Unit/LegacyOptionShimScanTest.php` fails the build if you do).
- If the setting must survive an upgrade from the old panel, add its legacy key →
  `lafka_<key>` pair to `lafka_legacy_migrate_map()` in
  `incl/system/lafka-legacy-migrate.php` and bump `LAFKA_LEGACY_MIGRATION_VERSION`.
- The `wp_options['lafka']` array still exists but is now **plugin-owned** storage
  (feature-module flags + functional-shared keys); the theme never writes it. Its
  plugin-owned defaults live in `incl/system/lafka-option-defaults.php`.
