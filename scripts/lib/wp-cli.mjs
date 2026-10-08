/* lafka-theme/scripts/lib/wp-cli.mjs
 *
 * Thin WP-CLI bridge for the maintainer scripts. The site these scripts drive
 * is the ONE local stack (`../local-env`, Docker compose): the `wpcli` service
 * runs `wp` in the container `lafka-local-cli`, with this theme bind-mounted at
 * /var/www/html/wp-content/themes/lafka, and WordPress served on
 * http://localhost:8080.
 *
 * Overrides:
 *   LAFKA_WPCLI_CONTAINER  CLI container name (default `lafka-local-cli`).
 *   LAFKA_BASE_URL         Site URL (default `http://localhost:8080`).
 *
 * Args go to `docker exec` via execFileSync WITHOUT a shell, so PHP passed to
 * `wp eval` needs no shell-escaping — pass it as a single string argument.
 */
import { execFileSync } from 'node:child_process';

export const WPCLI_CONTAINER = process.env.LAFKA_WPCLI_CONTAINER || 'lafka-local-cli';
export const BASE_URL = process.env.LAFKA_BASE_URL || 'http://localhost:8080';

/**
 * Run a WP-CLI command inside the local stack's CLI container.
 *
 * @param {string[]} args   WP-CLI argv, e.g. [ 'option', 'update', 'k', 'v' ].
 * @param {object}   [opts] Extra execFileSync options.
 * @return {string} Trimmed stdout.
 */
export function wpCli( args, opts = {} ) {
	return execFileSync( 'docker', [ 'exec', WPCLI_CONTAINER, 'wp', ...args ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'pipe' ],
		...opts,
	} ).trim();
}

/**
 * Bust the dynamic-css cache so a just-activated preset's chrome is rebuilt
 * instead of served from a stale transient. The cache key folds in the active
 * preset slug + an options-version but NOT the preset file mtime; bumping the
 * version option (a new cache key) and flushing the object cache forces a fresh
 * build on the next page load.
 */
export function bustDynamicCss() {
	wpCli( [ 'option', 'update', 'lafka_dynamic_css_version', String( Date.now() ) ] );
	wpCli( [ 'cache', 'flush' ] );
}
