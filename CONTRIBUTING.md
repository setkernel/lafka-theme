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
composer phpcs      # WordPress coding standards
composer phpcbf     # auto-fix what PHPCS can fix
```

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
| Theme options framework | `incl/lafka-options-framework/` |
| Per-template partials | `partials/` |
| WooCommerce overrides | `woocommerce/` |
| Tribe Events overrides | `tribe-events/` |
| WPBakery / VC element overrides | `vc_templates/` |
| Custom page templates | `page_templates/` |
| Frontend JS | `js/` |
| Frontend CSS | `style.css` (root) + `styles/` (variants) |
| Demo content | `store/demo/` |
| Translations | `languages/` |

## Coding standards

- WordPress-Extra rule set (PHPCS).
- Short array syntax allowed.
- Min PHP 8.1, min WP 6.6.
- Text domain: `lafka`.

## Compatibility matrix

See [LAFKA_AUDIT.md](../LAFKA_AUDIT.md) and [LAFKA_SYSTEM_MAP.md](../LAFKA_SYSTEM_MAP.md) at the workspace root.

## Releases

Tagging `vX.Y.Z` triggers `.github/workflows/release.yml` (where it exists), which builds an installable zip excluding dev files.

## Security

Never report security issues via public GitHub issues. Email security@setkernel.com (or the equivalent maintained channel).
