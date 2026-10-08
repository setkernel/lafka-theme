# Contributing to lafka-theme

Thanks for working on Lafka. This is the parent theme; logic that should outlive a theme switch belongs in the [lafka-plugin](https://github.com/setkernel/lafka-plugin) repo, and site-specific overrides belong in [lafka-child](https://github.com/setkernel/lafka-child).

## Local development

```bash
# One-time setup
npm ci
composer install
git config core.hooksPath .githooks   # pre-push quality gates (below)
```

The theme is developed against the one local Docker stack in the sibling `../local-env` repository
(bring it up with `../local-env/up.sh`). It bind-mounts this checkout at
`/var/www/html/wp-content/themes/lafka` (the production slug, so a child theme with `Template: lafka`
can activate against it), serves the site at <http://localhost:8080> and runs WP-CLI in the
`lafka-local-cli` container. Edits are live; there is no build step for PHP/CSS/JS source.

## Before opening a PR

```bash
npm run lint        # ESLint + Stylelint (content-hash caches: .eslintcache, .stylelintcache)
composer phpcs      # WordPress coding standards (security sniffs enforced; parallel, cached in .phpcs.cache)
composer phpcbf     # auto-fix what PHPCS can fix
npm run check-version
npm run build       # minified assets must build cleanly
```

A pre-push hook that runs PHPCS, ESLint, Stylelint and the version check in parallel ships in
`.githooks/`. It never skips a gate: if `node_modules` or `vendor` is missing it fails and tells you
to run `npm ci` / `composer install`.

### npm scripts

Every npm script, one line each.

| Script | What it does |
|--------|--------------|
| `lint` / `lint:fix` | ESLint + Stylelint (check / auto-fix). |
| `lint:js`, `lint:js:fix`, `lint:css`, `lint:css:fix` | The two linters individually. |
| `build` | Minify top-level `styles/*.css` + `js/*.js` to gitignored `.min` siblings (`scripts/build-assets.mjs`); served when `SCRIPT_DEBUG` is off; release.yml runs it before packaging. `js/lafka-dialog.min.js` is the one committed output (lafka-plugin registers it by path); CI fails if it drifts from the build. |
| `build:theme-json` | Regenerate `theme.json` editor presets from the `--lafka-*` token SSOT. |
| `i18n:pot` | Regenerate `languages/lafka.pot` with WP-CLI inside the local stack's `lafka-local-cli` container. |
| `sync:fonts` | Re-copy the eight pool families' woff2 + licences (incl. the variable Bricolage Grotesque from the dev-only Fontsource variable package) from the dev-only `@fontsource/*` packages into `assets/fonts/` (Rubik/Fraunces woff2 untouched). |
| `previews:presets` | Screenshot each preset's home page into `presets/<slug>/preview.jpg` (Customizer switcher thumbnails) against the local stack (`LAFKA_BASE_URL`, default `http://localhost:8080`; `LAFKA_WPCLI_CONTAINER`, default `lafka-local-cli`); restores the previously active preset. Needs Docker and `npx playwright install chromium` once. `-- --only=ember,koyo` to limit. |
| `sync-version` | Write the version from `package.json` into the `versionSync` targets. |
| `check-version` | Fail if any `versionSync` target drifted from `package.json` (CI runs it). |
| `version` | npm lifecycle hook used by `npm version`; not run directly. |

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
| Legacy options shim (deprecated `lafka_get_option()`) | `incl/system/core-functions.php` |
| Design presets (10 built-in) | `presets/` + `incl/presets/` ([docs/PRESET_ENGINE.md](docs/PRESET_ENGINE.md)) |
| Per-template partials | `partials/` |
| WooCommerce overrides | `woocommerce/` |
| Custom page templates | `page_templates/` |
| Frontend JS | `js/` |
| Frontend CSS | `style.css` (root) + `styles/` (design tokens + variants) |
| Design tokens / visual SSOT | `styles/lafka-tokens.css` + [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) |
| Self-hosted fonts (preset pool) | `assets/fonts/` |
| Demo content | none in the theme — the deterministic demo store comes from the plugin's `wp lafka seed-demo` (demo packs v2 planned, NX3-02) |
| Translations | `languages/` |
| Dev scripts | `scripts/` |

## Coding standards

- The full WordPress-Extra rule set (PHPCS), no exclusions, warnings fail; `array()` syntax;
  every global (function, class, hook, variable in a template file) starts with `lafka` / `Lafka`.
- No lint suppressions anywhere: no inline PHPCS, ESLint or Stylelint suppression comments.
  Fix the cause. Only `vendor/`, `node_modules/`, the unmodified
  `incl/tgm-plugin-activation/` and vendored front-end libraries are excluded.
- Escape late: print markup with `wp_kses( $html, lafka_allowed_html() )` (or the `esc_` family);
  read public query-string arguments with `lafka_query_arg()`.
- ESLint and Stylelint run the shared configs with their rules on (`no-var`, `prefer-const`,
  `no-unused-vars` and `eqeqeq` are errors; warnings fail the build). The single rule left off is
  Stylelint's `no-descending-specificity`: satisfying it means reordering the cascade across
  about 1,050 selectors, which is deferred to the 1.0 CSS rewrite.
- CSS naming (`selector-class-pattern`, `selector-id-pattern`; the regexes live in
  `.stylelintrc.json`). Class names and IDs are public contracts with the PHP templates, the JS,
  the plugin's markup and WordPress/WooCommerce core, so they are never renamed to satisfy a lint
  rule. The rules are configured to the project's real convention instead:
  - New code: lowercase kebab-case words, BEM when a component has parts, with the `lafka-`
    prefix: `lafka-card`, `lafka-card__title`, `lafka-card--featured`.
  - Legacy and core-facing names: lowercase words joined by single underscores
    (`lafka_title_holder`, `prod_hold`, `add_to_cart_button`, `billing_city_field`); hyphens and
    underscores may be mixed (`lafka_banner-icon`). IDs follow the same rule.
  - Third-party names we must target keep their own spelling: WooCommerce's own classes that
    start with `woocommerce-` (`woocommerce-Price-amount`,
    `woocommerce-MyAccount-navigation-link--orders`, the only place capitals are accepted) and
    BlockUI's `blockUI` / `blockOverlay`, plus the two WooCommerce-generated names with a
    trailing hyphen, `variation-` and `addon-wrap-`. WordPress (`wp-block-`, `screen-reader-text`) and
    `wc-block-` names are already lowercase kebab/BEM and pass as they are.
  - Rejected: camelCase or PascalCase in our own namespace (`lafkaCard`, `lafka_cardTitle`),
    leading digits, hyphens or underscores, and runs of three separators. A new name that does
    not fit means the name is wrong, not the pattern.
- Stylelint `declaration-property-unit-allowed-list` allows `px`, `em` and `%` for `line-height`
  (the WordPress default is `px` only). Thirteen legacy `em` and `%` values sit on heading,
  paragraph, excerpt and account rules whose font size comes from the cascade or the Customizer,
  and an `em` or `%` line-height is inherited as a computed length while a unitless one is
  recomputed per descendant, so converting them changes rendering. New code uses a unitless
  `line-height`.
- `no-duplicate-selectors`: keep one block per selector. If a later block must stay later to
  outrank an in-between rule, fold the declarations into the right block; only when that is
  impossible, write the second block as an equivalent selector and say why in a comment.
- Min PHP 8.3, min WP 7.0, min WooCommerce 11.0.
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
