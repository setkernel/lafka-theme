/* lafka-theme/tests/visual/nx1-10a.spec.js
 *
 * ============================================================================
 * NX1-10a LEGACY-SURFACE VISUAL GATE — README FOR AGENTS (read before editing)
 * ============================================================================
 * The sibling of the NX1-02 parity gate (nx1-02.spec.js), covering the classic
 * LEGACY monolith surfaces that NX1-10a retires: the blog index, a category
 * archive, search results (all rendered by content.php via index.php /
 * archive.php / search.php against the ~15k-line style.css monolith) and a
 * single post WITH comments (single.php + comments.php). These four surfaces
 * exercise the blog / widget / sidebar / comment CSS that is being extracted
 * out of style.css into styles/legacy-blog.css.
 *
 * The whole point: capture these goldens on the pre-teardown HEAD (full
 * monolith), then prove they stay PIXEL-IDENTICAL after the monolith is split
 * into scoped, conditionally-enqueued legacy-*.css sheets. A green run == the
 * extraction moved no rendered pixels on the legacy surfaces. The NX1-02 spec
 * simultaneously proves the six handoff pages stay identical while going
 * monolith-FREE.
 *
 * GOLDENS ARE LOCAL + UNTRACKED (gitignored), machine-specific — same contract
 * as nx1-02.spec.js. Regenerate only intentionally:
 *   npm run test:visual:nx1-02 -- --update-snapshots
 * (the visual config runs BOTH specs). REQUIREMENTS: the umbrella wp-env up at
 * LAFKA_E2E_BASE_URL (default http://localhost:8890). global-setup reseeds the
 * demo store AND the deterministic blog fixture (support/blog.js).
 *
 * DETERMINISM: dynamic date/time text (.post-meta-date, .lafka-post__date) and
 * network-loaded avatars (img.avatar, .lafka-post__avatar) are MASKED; fonts +
 * lazy images settle before every capture; animations frozen by config. The
 * announce-bar clock badge is masked too (shared with nx1-02).
 *
 * @since lafka-theme 6.21.0 (NX1-10a monolith teardown)
 */
const { test } = require( '@playwright/test' );
const { shootAllBreakpoints } = require( './support/capture' );
const { BLOG, blogPostPath } = require( '../e2e/support/blog' );

// Non-deterministic regions to blank so the goldens reproduce run to run:
//   - [data-lafka-status]   announce-bar open/closed dot + "Open until HH:MM".
//   - .post-meta-date       blog index / archive / search per-post date link.
//   - .lafka-post__date     single-post <time> stamp.
//   - img.avatar            author + comment Gravatars (network-loaded; masked
//                           so a slow/blocked gravatar.com fetch can't flake).
//   - .lafka-post__avatar   single-post author avatar wrapper.
// Locators that match nothing on a page are a no-op mask.
const MASK_SELECTORS = [
	'[data-lafka-status]',
	'.post-meta-date',
	'.lafka-post__date',
	'img.avatar',
	'.lafka-post__avatar',
];

test.describe( 'NX1-10a legacy-surface visual goldens', () => {
	test( 'blog index', async ( { page } ) => {
		await page.goto( '/' + BLOG.blogPageSlug + '/' );
		await shootAllBreakpoints( page, 'blog-index', MASK_SELECTORS );
	} );

	test( 'category archive', async ( { page } ) => {
		await page.goto( '/category/' + BLOG.categorySlug + '/' );
		await shootAllBreakpoints( page, 'blog-category', MASK_SELECTORS );
	} );

	test( 'search results', async ( { page } ) => {
		await page.goto( '/?s=' + encodeURIComponent( BLOG.searchTerm ) );
		await shootAllBreakpoints( page, 'blog-search', MASK_SELECTORS );
	} );

	test( 'single post + comments', async ( { page } ) => {
		await page.goto( blogPostPath( BLOG.posts.longform ) );
		await shootAllBreakpoints( page, 'single-post-comments', MASK_SELECTORS );
	} );
} );
