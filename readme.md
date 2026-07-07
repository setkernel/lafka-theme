# Lafka — WordPress / WooCommerce Restaurant Theme

Design-token-driven WordPress theme for restaurants and online food ordering, built on WooCommerce.

Originally developed by [theAlThemist](https://www.althemist.com) and sold on ThemeForest; since delisted and continued here as an open-source project under GPL v2+.

## Requirements

- WordPress 6.6+ · WooCommerce 9.5+ · PHP 8.1+

These match the floors declared in `style.css`. The [Lafka Plugin](https://github.com/setkernel/lafka-plugin) (restaurant menus, product addons, delivery zones, order hours, KDS) is **recommended** — the theme runs standalone, but the full ordering experience needs it.

## Installation

1. Copy or clone this repository into `wp-content/themes/lafka` and activate it in Appearance → Themes.
2. Install the [Lafka Plugin](https://github.com/setkernel/lafka-plugin) when prompted (optional but recommended).

## Highlights

- **Design-token system** — single visual source of truth in `styles/lafka-tokens.css` (color/type/space/radii/motion) with opt-in dark mode; see [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md)
- **10 built-in design presets** — pure-data `presets/<slug>/preset.json`, WCAG-AA contrast-gated, including two dark presets; see [docs/PRESET_ENGINE.md](docs/PRESET_ENGINE.md)
- **Customizer-first configuration** — every knob has a sane default, a Customizer control, and a filter hook
- **Redesigned ordering surfaces** — token-driven single product page with topping/size pickers and sticky add-to-cart, list-card menu archive with inline quick-add, ajax cart drawer
- **Deep WooCommerce integration** — classic and block Cart/Checkout, quick view, wishlist, product comparison
- **Editorial page system, mega menu, multiple header styles, blog layouts**
- **7 demo content packages**, WPML/RTL, bbPress and The Events Calendar support
- Optional commercial integrations (not required): WPBakery Page Builder, Revolution Slider

## Structure (short)

- `styles/` — design tokens + component CSS (visual SSOT)
- `presets/` + `incl/presets/` — preset engine (10 presets)
- `incl/` — theme classes and Customizer sections (`incl/lafka-options-framework/` remains only as a deprecated `lafka_get_option()` shim)
- `partials/`, `woocommerce/`, `page_templates/`, `tribe-events/` — templates and partials
- `js/` — front-end behaviour
- `store/demo/` — demo content packages

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for the local wp-env stack, the four quality gates (`composer phpcs`, `composer test`, `npm run lint`, e2e), the Playwright visual/e2e suites, and the pre-push hook.

## License

GPL v2 or later. See [LICENSE](LICENSE).
