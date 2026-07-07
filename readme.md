# Lafka - WordPress / WooCommerce Theme

A modern, feature-rich WordPress theme for restaurants, cafes, food businesses, and WooCommerce stores.

Originally developed by [theAlThemist](https://www.althemist.com) and sold on ThemeForest. The theme has since been delisted and is no longer supported by the original author. This repository continues development as an open-source project under the GPL v2+ license.

## Requirements

- WordPress 6.6+
- WooCommerce 9.5+
- PHP 8.1+
- [Lafka Plugin](https://github.com/setkernel/lafka-plugin) (companion plugin, required)

These match the floor declared in `style.css` (`Requires at least:` / `Requires PHP:` / `WC requires at least:`). The theme will fatal-error or behave unexpectedly on older versions.

## Optional Commercial Plugins

The theme has built-in support for these commercial plugins. They are **not required** but enable additional functionality:

- **WPBakery Page Builder** — Visual page building, custom element templates
- **Revolution Slider** — Advanced hero sliders in header areas

These must be purchased separately from their respective vendors.

## Installation

1. Download or clone this repository into `wp-content/themes/lafka`
2. Activate the theme in WordPress Admin → Appearance → Themes
3. Install the [Lafka Plugin](https://github.com/setkernel/lafka-plugin) when prompted (or manually)
4. Optionally install WPBakery Page Builder and/or Revolution Slider

## Features

- **Custom Food Menu System** — Restaurant menu items with categories, prices, ingredients, allergens, and nutrition facts
- **Deep WooCommerce Integration** — Custom shop layouts, ajax cart, quick view, wishlist, product comparison
- **Redesigned Product Page (PDP)** — Token-driven single-product layout with topping/size pickers, sticky add-to-cart, ingredients + reviews; partials in `partials/pdp-*.php`, behaviour in `js/pdp-pickers.js`, styles in `styles/pdp-redesign.css`
- **List-Card Menu Archive** — Image-left / body-right product rows with inline quick-add (`styles/product-card.css`, `styles/lafka-menu-archive.css`)
- **Editorial Page System** — Long-form / editorial layouts on the design-token system (`styles/editorial.css`)
- **Design-Token CSS** — Single visual source of truth in `styles/lafka-tokens.css` (color/type/space/radii/motion), with opt-in dark mode and an operator accent override; see `DESIGN_SYSTEM.md`
- **Mega Menu** — Multi-column menus with icons, images, and custom labels
- **Multiple Header Styles** — Sticky, transparent, with search, cart, and account dropdowns
- **Blog Layouts** — Standard, masonry, and mosaic styles
- **bbPress Forum Support**
- **The Events Calendar Support**
- **WPML Multilingual Support** with RTL
- **7 Demo Content Packages** — One-click import
- **Responsive Design** — Mobile-optimized with custom mobile menu
- **YouTube Video Backgrounds** — Per-page or global
- **Custom Options Framework** — Extensive theme customization panel

## Structure

```
lafka/
├── incl/                   # Core includes
│   ├── system/             # Core functions and config
│   ├── lafka-options-framework/  # Theme options panel
│   ├── tgm-plugin-activation/   # Plugin installer
│   └── ...
├── js/                     # JavaScript (custom + libraries)
├── styles/                 # CSS — design tokens (lafka-tokens.css),
│                           #   parent baseline (lafka-base.css), search
│                           #   overlay (lafka-search.css), PDP/editorial,
│                           #   dynamic, responsive, admin, RTL
├── woocommerce/            # WooCommerce template overrides
├── partials/               # Reusable template parts
├── vc_templates/           # WPBakery element templates
├── page_templates/         # Custom page templates
├── store/demo/             # Demo content XML files
├── tribe-events/           # Events Calendar templates
├── plugins/                # Place commercial plugin zips here
├── functions.php           # Main theme functions
├── header.php / footer.php # Layout templates
└── style.css               # Main stylesheet (v7.0.0)
```

The visual single source of truth is the `styles/` token system
(`styles/lafka-tokens.css`) and [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md). Do not
introduce hex literals or magic spacing — consume the tokens.

## Development

Standard local checks:

```bash
composer install        # PHPCS + WPCS + PHPUnit + Brain Monkey
npm ci                  # ESLint + Stylelint

composer phpcs          # full WordPress-Extra ruleset (security sniffs enforced)
composer test           # PHPUnit (Brain Monkey)
npm run lint            # ESLint + Stylelint
```

### End-to-end tests (Playwright)

The conversion funnel is covered by a Playwright suite in `tests/e2e/`, driven
against a **seeded** wp-env — it needs the companion plugin mounted (for the
`wp lafka seed-demo` fixture, the addon engine, and order-hours), so run it
against the **umbrella** wp-env (theme + plugin) rather than the theme-only
`.wp-env.json` stack.

```bash
npx playwright install chromium   # once
# Start the umbrella wp-env (theme + plugin) at the workspace root, then:
npm run test:e2e         # full funnel + store-closed + cart-drawer a11y
npm run test:e2e:smoke   # just the @smoke money-path
```

Target defaults to `http://localhost:8890`; override with
`LAFKA_E2E_BASE_URL=<url>`. `global-setup.js` fails fast if the target is
unreachable, then re-seeds and prepares the store (coming-soon off, COD on,
addons on, classic cart/checkout). The suite runs single-worker because every
spec shares one WordPress backend.

**CI:** `.github/workflows/e2e.yml` runs the `@smoke` subset on PRs against a
CI-built wp-env that mounts both repos. It is currently **non-blocking** — it is
`continue-on-error` and deliberately excluded from the `ci-passed` aggregate
gate; promotion to a required check is deferred until it has proven stable over
about a week.

A pre-push git hook is shipped under `.githooks/` that runs all four gates before any push — install once per clone:

```bash
git config core.hooksPath .githooks
```

To bypass for a single push: `git push --no-verify`.

## License

GPL v2 or later. See [LICENSE](LICENSE).

## Contributing

Contributions are welcome. Please open an issue to discuss changes before submitting a pull request.
