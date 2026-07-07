#!/usr/bin/env node
/**
 * Minify-only dist step for first-party theme assets (NX1-10b).
 *
 * Emits a `.min.css` / `.min.js` sibling next to every first-party file in
 * `styles/` and `js/` using esbuild in minify-only mode — NO bundling, NO
 * source rewriting, NO syntax lowering. The output is deterministic (same input
 * + same esbuild version => byte-identical output), so it can run unattended in
 * release.yml before the theme is zipped.
 *
 * These `.min` siblings are BUILD ARTEFACTS: git-ignored (see .gitignore), never
 * committed. At runtime incl/system/asset-min.php swaps enqueued theme asset
 * URLs to the `.min` sibling when it exists on disk and SCRIPT_DEBUG is off.
 *
 * Scope rules:
 *   - top-level `styles/*.css` and `js/*.js` only — vendored libraries live in
 *     subdirectories (font-awesome/, owl-carousel2-dist/, ...) and are skipped
 *     automatically by not recursing;
 *   - already-minified `*.min.*` inputs are skipped (no `.min.min.*`);
 *   - `styles/dynamic-css.php` is a `.php` file, so it never matches;
 *   - the SKIP set below covers first-party scripts that ship a committed,
 *     hand-tuned `.min` sibling consumed by an existing `$suffix` enqueue, plus
 *     the one vendored top-level lib — leaving those out keeps the tree clean.
 *
 * Usage: `npm run build`  (or `node scripts/build-assets.mjs`)
 */

import { transform } from 'esbuild';
import { readdirSync, readFileSync, writeFileSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');

/* First-party assets that already ship a committed, hand-tuned `.min` sibling
 * (consumed by a SCRIPT_DEBUG `$suffix` enqueue), plus the vendored top-level
 * Modernizr build. The build must not regenerate these — doing so would clobber
 * the tuned output and dirty the tree. */
const SKIP = new Set([
	'lafka-front.js',
	'lafka-dialog.js',
	'lafka-libs-config.js',
	'lafka-price-slider.js',
	'modernizr.custom.js',
]);

const TARGETS = [
	{ dir: 'styles', ext: '.css', loader: 'css' },
	{ dir: 'js', ext: '.js', loader: 'js' },
];

/**
 * Top-level, non-minified, non-skipped first-party files for one target dir.
 *
 * @param {string} dir Directory relative to the theme root.
 * @param {string} ext File extension including the dot, e.g. '.css'.
 * @returns {string[]} File names, sorted for deterministic ordering.
 */
function firstPartyFiles(dir, ext) {
	const minExt = `.min${ext}`;
	return readdirSync(join(ROOT, dir))
		.filter(
			(name) =>
				name.endsWith(ext) &&
				!name.endsWith(minExt) &&
				!SKIP.has(name) &&
				statSync(join(ROOT, dir, name)).isFile()
		)
		.sort();
}

async function main() {
	let written = 0;
	let sourceBytes = 0;
	let minBytes = 0;

	for (const { dir, ext, loader } of TARGETS) {
		for (const name of firstPartyFiles(dir, ext)) {
			const srcPath = join(ROOT, dir, name);
			const outName = `${name.slice(0, -ext.length)}.min${ext}`;
			const outPath = join(ROOT, dir, outName);

			const source = readFileSync(srcPath, 'utf8');
			const result = await transform(source, {
				loader,
				minify: true,
				legalComments: 'none',
			});

			writeFileSync(outPath, result.code);
			written += 1;
			sourceBytes += Buffer.byteLength(source);
			minBytes += Buffer.byteLength(result.code);
			console.log(
				`  ${dir}/${name} -> ${outName}  (${Buffer.byteLength(source)} -> ${Buffer.byteLength(result.code)} B)`
			);
		}
	}

	const saved = sourceBytes - minBytes;
	const pct = sourceBytes > 0 ? Math.round((saved / sourceBytes) * 100) : 0;
	console.log(
		`build-assets: minified ${written} file(s); ${sourceBytes} -> ${minBytes} B (-${saved} B, -${pct}%).`
	);
}

main().catch((err) => {
	console.error('build-assets failed:', err);
	process.exit(1);
});
