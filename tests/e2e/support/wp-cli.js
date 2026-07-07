/* lafka-theme/tests/e2e/support/wp-cli.js
 *
 * Thin WP-CLI bridge for the e2e suite. The seeded WordPress under test runs
 * inside the wp-env Docker stack, so every store mutation the specs need
 * (re-seed, force-close the store, flip a flag) is a `wp` call executed via
 * `docker exec` against that stack's CLI container.
 *
 * Container discovery is deliberately dynamic so the SAME helper works in two
 * environments the project cares about (cross-repo test-isolation trap):
 *   - LOCAL: the umbrella `npx wp-env start` names its dev CLI container
 *     `<hash>-cli-1` (and a separate `<hash>-tests-cli-1` we must avoid).
 *   - CI: the workflow boots its own wp-env whose hash differs per run.
 * Override explicitly with LAFKA_E2E_CLI_CONTAINER when autodiscovery can't
 * pick the right one.
 *
 * Args are passed to `docker exec` via execFileSync WITHOUT a shell, so PHP
 * passed to `wp eval` needs no shell-escaping — pass it as a single string arg.
 *
 * @since lafka-theme 6.20.0 (NX1-09b)
 */
const { execFileSync } = require( 'node:child_process' );

let cachedContainer = null;

/**
 * Resolve the wp-env DEV cli container name (not the -tests- one).
 *
 * @return {string} Container name.
 */
function resolveContainer() {
	if ( cachedContainer ) {
		return cachedContainer;
	}
	if ( process.env.LAFKA_E2E_CLI_CONTAINER ) {
		cachedContainer = process.env.LAFKA_E2E_CLI_CONTAINER;
		return cachedContainer;
	}
	let names = '';
	try {
		names = execFileSync( 'docker', [ 'ps', '--format', '{{.Names}}' ], {
			encoding: 'utf8',
		} );
	} catch ( err ) {
		throw new Error(
			'e2e: could not run `docker ps` to find the wp-env CLI container. ' +
				'Is Docker running and wp-env started? (' + err.message + ')',
			{ cause: err }
		);
	}
	const candidates = names
		.split( '\n' )
		.map( ( n ) => n.trim() )
		.filter( Boolean )
		// wp-env CLI containers end in `-cli-1`; skip the parallel `-tests-cli-1`
		// stack, which points at a different (blank) database.
		.filter( ( n ) => /-cli-1$/.test( n ) && ! /tests/.test( n ) );

	if ( candidates.length === 0 ) {
		throw new Error(
			'e2e: no wp-env CLI container found (looked for a running ' +
				'`*-cli-1` container). Start the umbrella wp-env first ' +
				'(`npx wp-env start` at the workspace root), or set ' +
				'LAFKA_E2E_CLI_CONTAINER.'
		);
	}
	cachedContainer = candidates[ 0 ];
	return cachedContainer;
}

/**
 * Run a WP-CLI command inside the wp-env CLI container.
 *
 * @param {string[]} args    WP-CLI argv, e.g. [ 'option', 'update', 'k', 'v' ].
 * @param {object}   [opts]  Extra execFileSync options.
 * @return {string} Trimmed stdout.
 */
function wpCli( args, opts = {} ) {
	const container = resolveContainer();
	return execFileSync( 'docker', [ 'exec', container, 'wp', ...args ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'pipe' ],
		...opts,
	} ).trim();
}

/**
 * Bust the dynamic-css cache so a just-activated preset's CHROME (the ~57
 * theme_mod-default reads in styles/dynamic-css.php — header/menu background,
 * link colours, …) is rebuilt instead of served from a stale transient. The
 * dynamic-css cache key folds in the active-preset slug + an options-version
 * but NOT the preset FILE mtime, so editing a preset's chrome{} alone can leave
 * an entry keyed before the edit still cached; bumping the version option (a new
 * cache key) + flushing the object cache forces a fresh build on the next page
 * load. Call after switching lafka_active_preset in a spec's beforeAll.
 */
function bustDynamicCss() {
	wpCli( [ 'option', 'update', 'lafka_dynamic_css_version', String( Date.now() ) ] );
	wpCli( [ 'cache', 'flush' ] );
}

module.exports = { wpCli, resolveContainer, bustDynamicCss };
