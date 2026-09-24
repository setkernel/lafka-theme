# Contributing to lafka-theme

Thanks for working on Lafka. This is the parent theme; logic that should outlive a theme switch belongs in the [lafka-plugin](https://github.com/setkernel/lafka-plugin) repo, and site-specific overrides belong in [lafka-child](https://github.com/setkernel/lafka-child).

## Local development

```bash
# One-time setup
npm ci
composer install

# Boot a full WP + WC + plugin stack (expects ../lafka-plugin checked out beside this repo)
npx @wordpress/env start
npx @wordpress/env run cli wp theme activate lafka
# WP runs at http://localhost:8890
# Tests-WP runs at http://localhost:8891
```

`@wordpress/env` requires Docker. `.wp-env.json` pulls WordPress 7.0 + PHP 8.4 + WooCommerce 10.9.1, mounts the sibling `../lafka-plugin`, and maps this checkout to `wp-content/themes/lafka` (the production slug, so a child theme with `Template: lafka` can activate against it).

## Before opening a PR

```bash
npm run lint        # ESLint + Stylelint (content-hash caches: .eslintcache, .stylelintcache)
composer phpcs      # WordPress coding standards (security sniffs enforced; parallel, cached in .phpcs.cache)
composer phpcbf     # auto-fix what PHPCS can fix
composer test       # PHPUnit — pure unit tests in one process, ~1 s
```

Unit tests run without WordPress. Common WordPress functions come from
`tests/support/wp-shims.php`: each is backed by a `$GLOBALS` store
(`lafka_test_theme_mods`, `lafka_test_options`, the `lafka_test_filters` hook
registry, …) that `ResetWpShimsExtension` resets before every test, and shared
class stubs (`WC_Product`, `Lafka_Order_Hours`) live in `tests/support/stubs.php`.
Configure the stores in `setUp()`; shim anything test-specific in the test file
behind `function_exists()`. Prefer rendering a partial or calling the helper and
asserting on the output over grepping source files.

A pre-push hook that runs all four gates in parallel ships in `.githooks/` — install once per clone:

```bash
git config core.hooksPath .githooks
```

Bypass for a single push with `git push --no-verify`.

### End-to-end (Playwright)

```bash
npm run test:e2e:install   # browsers, once
npm run test:e2e           # funnel (classic + block checkout), store-closed, cart-drawer a11y, Customizer preset switcher
npm run test:e2e:smoke     # just the @smoke money-path
```

The e2e suite (`tests/e2e/`) drives a **seeded** store and therefore needs the
companion **plugin** mounted alongside the theme (for `wp lafka seed-demo`, the
addon engine, and order-hours). The `.wp-env.json` above provides exactly that
stack on `http://localhost:8890`, which is the suite's default target; override
with `LAFKA_E2E_BASE_URL=<url>`.

`global-setup.js` fails fast if the target is unreachable, then re-seeds and
prepares the store (WooCommerce coming-soon off, COD on, `product_addons` on,
classic cart/checkout shortcodes). It discovers the wp-env CLI container
dynamically (`docker ps`); set `LAFKA_E2E_CLI_CONTAINER` to pin it. Because all
specs share one WordPress backend, the suite runs single-worker; the
store-closed spec is serial and self-restoring.

CI runs the `@smoke` subset via `.github/workflows/e2e.yml` (theme + plugin mounted).
It is **non-blocking** for now — `continue-on-error`, and not part of the
`ci-passed` gate — until it proves stable over ~a week.

### Visual & contrast gates / build scripts

Every npm script, one line each. The visual and contrast suites run against the
same seeded stack as e2e (`LAFKA_E2E_BASE_URL`, default `http://localhost:8890`).
**Visual goldens are local and untracked** (`tests/visual/__screenshots__/`,
gitignored, machine-specific) and **none of the visual/contrast suites run in
CI** — only the e2e `@smoke` job does.

| Script | What it does |
|--------|--------------|
| `lint` / `lint:fix` | ESLint + Stylelint (check / auto-fix). |
| `lint:js`, `lint:js:fix`, `lint:css`, `lint:css:fix` | The two linters individually. |
| `build` | Minify top-level `styles/*.css` + `js/*.js` to gitignored `.min` siblings (`scripts/build-assets.mjs`); served when `SCRIPT_DEBUG` is off; release.yml runs it before packaging. `js/lafka-dialog.min.js` is the one committed output (lafka-plugin registers it by path); CI fails if it drifts from the build. |
| `build:theme-json` | Regenerate `theme.json` editor presets from the `--lafka-*` token SSOT. |
| `i18n:pot` | Regenerate `languages/lafka.pot` with WP-CLI inside the theme's wp-env. |
| `sync:fonts` | Re-copy the six pool families' woff2 + licences from the dev-only `@fontsource/*` packages into `assets/fonts/` (Rubik/Fraunces woff2 untouched). |
| `previews:presets` | Screenshot each preset's home page into `presets/<slug>/preview.jpg` (Customizer switcher thumbnails); restores the previously active preset. `-- --only=ember,koyo` to limit. |
| `test:e2e` / `test:e2e:smoke` / `test:e2e:install` | Playwright e2e suite / `@smoke` subset / browser install (above). |
| `test:visual` | Peppery full-page goldens at 375/768/1280 (NX1-02 parity + NX1-10a surfaces). `-- --update-snapshots` to (re)capture. |
| `test:visual:dark` | Midnight (dark preset) goldens on home, menu, PDP and cart — same local contract. |
| `test:contrast` | Rendered text/CTA contrast for every registered preset on home, menu, PDP and cart (no goldens). |

The three visual/contrast scripts are projects of one config,
`playwright.visual.config.js` (`peppery`, `dark`, `contrast`).
| `sync-version` | Write the version from `package.json` into the `versionSync` targets. |
| `check-version` | Fail if any `versionSync` target drifted from `package.json` (CI runs it). |
| `version` | npm lifecycle hook used by `npm version`; not run directly. |

`scripts/nx1-10a-cascade-parity.mjs` (run with `node`) re-proves the legacy-sheet
cascade against the pre-split monolith; `CascadeParityTest` is its CI-visible lock.

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
| Design presets (10 built-in) | `presets/` + `incl/presets/` ([docs/PRESET_ENGINE.md](docs/PRESET_ENGINE.md)) |
| Per-template partials | `partials/` |
| WooCommerce overrides | `woocommerce/` |
| Tribe Events overrides | `tribe-events/` |
| Custom page templates | `page_templates/` |
| Frontend JS | `js/` |
| Frontend CSS | `style.css` (root) + `styles/` (design tokens + variants) |
| Design tokens / visual SSOT | `styles/lafka-tokens.css` + [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) |
| Self-hosted fonts (preset pool) | `assets/fonts/` |
| Demo content | none in the theme — the deterministic demo store comes from the plugin's `wp lafka seed-demo` (demo packs v2 planned, NX3-02) |
| Translations | `languages/` |
| Tests | `tests/Unit/` (PHPUnit), `tests/e2e/` (Playwright), `tests/visual/` (local goldens + contrast) |
| Dev scripts | `scripts/` |

## Coding standards

- WordPress-Extra rule set (PHPCS).
- Short array syntax allowed.
- Min PHP 8.1, min WP 6.6.
- Text domain: `lafka`.

## Compatibility matrix

See [COMPATIBILITY.md](https://github.com/setkernel/lafka-plugin/blob/main/COMPATIBILITY.md) in the plugin repo (support floors + CI matrix).

## Releases

1. `npm version <patch|minor|major>` — `package.json` is the canonical version. npm bumps
   `package.json` + `package-lock.json`, then the `version` hook runs
   `scripts/sync-version.mjs` to rewrite and stage the `versionSync` targets
   (`style.css` `Version:`, `readme.txt` `Version:`, and the "current theme vX.Y.Z" line in
   `DESIGN_SYSTEM.md`), and npm makes the release commit + `vX.Y.Z` tag.
   `npm run check-version` verifies nothing drifted.
2. Push the branch and the tag (`git push --follow-tags`).
3. The `v*` tag triggers `.github/workflows/release.yml`, which runs `npm run build`,
   packages an installable `lafka.zip` (dev files excluded) + SHA256, and creates or
   updates the GitHub Release for that tag.

## Security

Never report security issues via public GitHub issues. Email security@setkernel.com (or the equivalent maintained channel).
