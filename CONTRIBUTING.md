# Contributing to lafka-theme

Thanks for working on Lafka. This is the parent theme; logic that should outlive a theme switch belongs in the [lafka-plugin](../lafka-plugin) repo, and site-specific overrides belong in [lafka-child](../lafka-child).

## Local development

```bash
# One-time setup
npm ci
composer install

# Boot a full WP + WC stack
npx @wordpress/env start
# WP runs at http://localhost:8881
# Tests-WP runs at http://localhost:8882
```

`@wordpress/env` requires Docker. It will pull WP 6.9.4 + PHP 8.2 + WooCommerce 10.7.0 from `.wp-env.json`.

## Before opening a PR

```bash
npm run lint        # ESLint + Stylelint
composer phpcs      # WordPress coding standards (security sniffs enforced)
composer phpcbf     # auto-fix what PHPCS can fix
composer test       # PHPUnit (Brain Monkey)
```

A pre-push hook that runs all four gates ships in `.githooks/` — install once per clone:

```bash
git config core.hooksPath .githooks
```

Bypass for a single push with `git push --no-verify`.

### End-to-end (Playwright)

```bash
npm run test:e2e:install   # browsers, once
npm run test:e2e           # full funnel + store-closed + cart-drawer a11y
npm run test:e2e:smoke     # just the @smoke money-path
```

The e2e suite (`tests/e2e/`) drives a **seeded** store and therefore needs the
companion **plugin** mounted alongside the theme (for `wp lafka seed-demo`, the
addon engine, and order-hours). Run it against the **umbrella** wp-env (both
repos), not the theme-only `.wp-env.json` at `localhost:8881` — the seeder does
not exist there. The suite targets `http://localhost:8890` by default; override
with `LAFKA_E2E_BASE_URL=<url>`.

`global-setup.js` fails fast if the target is unreachable, then re-seeds and
prepares the store (WooCommerce coming-soon off, COD on, `product_addons` on,
classic cart/checkout shortcodes). It discovers the wp-env CLI container
dynamically (`docker ps`); set `LAFKA_E2E_CLI_CONTAINER` to pin it. Because all
specs share one WordPress backend, the suite runs single-worker; the
store-closed spec is serial and self-restoring.

CI runs the `@smoke` subset via `.github/workflows/e2e.yml` (both repos mounted).
It is **non-blocking** for now — `continue-on-error`, and not part of the
`ci-passed` gate — until it proves stable over ~a week.

## Branching

- `main` is the release branch — always green, always tagged.
- Feature branches: `feat/<short-description>`.
- Fix branches: `fix/<short-description>`.
- Use Conventional Commits for messages (`feat:`, `fix:`, `chore:`, `refactor:`, `perf:`, `docs:`).

## Where things live

| Concern | Path |
|---------|------|
| Top-level templates (single, archive, page, etc.) | repo root |
| Theme functions / hooks | `functions.php` |
| Reusable theme classes | `incl/` |
| Legacy options shim (deprecated `lafka_get_option()`) | `incl/lafka-options-framework/` |
| Design presets (10 built-in) | `presets/` + `incl/presets/` |
| Per-template partials | `partials/` |
| WooCommerce overrides | `woocommerce/` |
| Tribe Events overrides | `tribe-events/` |
| Custom page templates | `page_templates/` |
| Frontend JS | `js/` |
| Frontend CSS | `style.css` (root) + `styles/` (design tokens + variants) |
| Design tokens / visual SSOT | `styles/lafka-tokens.css` + [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) |
| Demo content | `store/demo/` |
| Translations | `languages/` |

## Coding standards

- WordPress-Extra rule set (PHPCS).
- Short array syntax allowed.
- Min PHP 8.1, min WP 6.6.
- Text domain: `lafka`.

## Compatibility matrix

See [COMPATIBILITY.md](../lafka-plugin/COMPATIBILITY.md) (support floors + CI matrix) and the workspace [ROADMAP](../ROADMAP_2026-07-05.md).

## Releases

Tagging `vX.Y.Z` triggers `.github/workflows/release.yml` (where it exists), which builds an installable zip excluding dev files.

## Security

Never report security issues via public GitHub issues. Email security@setkernel.com (or the equivalent maintained channel).
