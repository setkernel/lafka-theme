# Lafka Design System

Single source of truth for every visual decision in the theme. If you can't
find an answer here, the answer doesn't exist yet — propose it via PR.

**Locked**: 2026-05-15 design lock; current theme v6.19.0.

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
| `--lafka-color-brand-700`      | `#b45309` | text-on-yellow (AAA on `brand-50`)            |
| `--lafka-color-brand-900`      | `#451a03` | display text on yellow surfaces (AAA)         |

### Accent — pizza red (calls to action)

| Token                          | Hex      | Role                                            |
|--------------------------------|----------|-------------------------------------------------|
| `--lafka-color-accent-50`      | `#fef2f2` | error-state surface                           |
| `--lafka-color-accent-500`     | `#dc2626` | primary CTA fill, "Add to Cart"               |
| `--lafka-color-accent-600`     | `#b91c1c` | CTA hover/pressed                             |
| `--lafka-color-accent-700`     | `#991b1b` | CTA text on light surfaces                    |
| `--lafka-color-accent-contrast`| `#ffffff` | text on accent fills (AAA on `accent-500`)    |

### Neutrals (text + surfaces)

| Token                            | Hex      | Role                                          |
|----------------------------------|----------|-----------------------------------------------|
| `--lafka-color-text-primary`     | `#18181b` | body, headings (AAA on surface-page)         |
| `--lafka-color-text-secondary`   | `#3f3f46` | meta, captions (AA on surface-page)          |
| `--lafka-color-text-muted`       | `#71717a` | hints, disabled (AA on surface-page)         |
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
pick any brand red — e.g. Peppery's `#f2002d`, which yields only 4.36:1
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
*backgrounds* with white text (white-on-mid-red always clears contrast).

### Dark mode (opt-in)

Dark mode is **strictly opt-in** via `[data-theme="dark"]` on `<html>` (a
forward hook for operators who explicitly want it — e.g. a Customizer/admin
toggle). It is *not* driven by `prefers-color-scheme` because the handoff design
language is light-mode-only (warm food photography on light surfaces, red
accent) and many components hardcode which surface is light. The override block
lives in `styles/lafka-tokens.css` (`:root[data-theme="dark"]`) and re-points
the text, surface, border, accent (`accent-500` → `#ef4444`), and shadow tokens
to their dark equivalents.

### Forbidden

- ❌ Pure black `#000` — use `text-primary` (#18181b).
- ❌ Pure red `#ff0000` — too saturated for screens.
- ❌ Any hex outside this table.

## Typography

Two families. Both self-hosted, WOFF2, `font-display: swap`.

| Family    | Role        | Weights loaded   | License |
|-----------|-------------|------------------|---------|
| **Rubik**     | UI / body / small headings | 400, 600, 700 | OFL |
| **Fraunces**  | Display / h1 / h2 only     | 600, 800      | OFL |

### Type scale (1.25 modular, mobile-first, fluid where it matters)

| Token                          | Mobile (`< 600px`) | Desktop (`≥ 768px`) | Family   | Weight |
|--------------------------------|--------------------|---------------------|----------|--------|
| `--lafka-font-size-display`    | `2.5rem` (40px)    | `clamp(2.5rem, 4vw + 1rem, 4.5rem)` | Fraunces | 800 |
| `--lafka-font-size-h1`         | `2rem` (32px)      | `2.75rem` (44px)    | Fraunces | 800    |
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
`shadow-focus` is the 3-ring focus indicator (accent-500 @ 35%).

## Motion

| Token                           | Value          | Use                              |
|---------------------------------|----------------|----------------------------------|
| `--lafka-motion-duration-fast`  | 120ms          | hover state                      |
| `--lafka-motion-duration-base`  | 200ms          | menu open, card hover            |
| `--lafka-motion-duration-slow`  | 320ms          | modal/drawer                     |
| `--lafka-motion-ease-out`       | cubic-bezier(0.2, 0.8, 0.4, 1) | exit motion       |
| `--lafka-motion-ease-in-out`    | cubic-bezier(0.4, 0, 0.2, 1)   | reversible        |

Respect `prefers-reduced-motion` — animations collapse to 1ms.

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

These are the only "button"-like primitives. Anything else is a bug.

- **`.lafka-btn`** — base button. Variants via modifier classes:
  - `--primary` (accent fill)
  - `--secondary` (outline)
  - `--ghost` (text only)
  - `--brand` (yellow fill, dark text)
- **`.lafka-chip`** — small toggleable option (toppings, sizes).
- **`.lafka-input`** — form field. Always paired with `.lafka-label`.
- **`.lafka-card`** — surface container. `--raised` / `--sunken` variants.
- **`.lafka-product-card`** — product list row. Image-left + body-right.

## WPBakery

**Deprecated as required dependency** (see memory:
`feedback_wpbakery_deprecated.md`). New default page templates render
without it. Existing operator content keeps working until migrated.

## Stylesheet entry points

Tokens are the contract; these are the key files that consume them.

| File | Role |
|------|------|
| `styles/lafka-tokens.css` | The token SSOT — color/type/space/radii/motion, dark-mode block, accent-text derivation. |
| `styles/dynamic-css.php` | Emits the operator's Customizer accent override into the cascade. |
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
