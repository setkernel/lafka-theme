#!/usr/bin/env node
/**
 * Design-token gate (runs inside `npm run lint:css`, so hook and CI agree).
 *
 *  1. Every `var(--lafka-*)` the theme reads must be defined: declared in
 *     styles/lafka-tokens.css, declared by another theme sheet or PHP-emitted
 *     CSS, or set by script (`setProperty`). The dynamic families that PHP
 *     builds from a loop (icons, per-heading typography) are listed in DYNAMIC.
 *  2. Every token declared in styles/lafka-tokens.css must be read somewhere: by
 *     theme CSS/PHP/JS, by the preset whitelist or the editor map, or carry a
 *     `@public` marker, or sit between `@public-start` and `@public-end`
 *     comments (read by the companion plugin or a child theme, which this
 *     repo's CI cannot see).
 *
 * Exits 1 with the offending names. No suppressions: fix the token.
 */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, relative } from 'node:path';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const TOKENS = join(ROOT, 'styles', 'lafka-tokens.css');

/* Families PHP emits from a loop or a registry; their names never appear as a
 * literal custom-property declaration. */
const DYNAMIC = [
	/^--lafka-icon-[a-z0-9-]+$/, // incl/template-helpers/icons.php lafka_icon_css_properties()
	/^--lafka-h[1-6]-(color|size|weight|style)$/, // styles/dynamic-css.php heading loop
];

const SKIP_DIRS = new Set(['node_modules', 'vendor', '.git', 'languages', 'plugins', 'assets', 'docs', 'scripts']);

/**
 * All first-party source files of the theme.
 *
 * @param {string} dir Directory.
 * @param {string[]} out Accumulator.
 * @returns {string[]} Paths.
 */
function walk(dir, out = []) {
	for (const name of readdirSync(dir)) {
		const path = join(dir, name);
		if (statSync(path).isDirectory()) {
			if (!SKIP_DIRS.has(name)) {
				walk(path, out);
			}
		} else if (/\.(css|php|js|json)$/.test(name) && !/\.min\.(css|js)$/.test(name)) {
			out.push(path);
		}
	}
	return out;
}

const files = walk(ROOT).filter((f) => !/(package-lock|composer\.lock|\.stylelintrc)/.test(f) && !/(package-lock|composer)\.(json|lock)$/.test(f));
const tokensText = readFileSync(TOKENS, 'utf8');

const declared = new Set();
const used = new Map();
const usedAnywhere = new Set();

for (const file of files) {
	const text = readFileSync(file, 'utf8');
	const rel = relative(ROOT, file);
	for (const m of text.matchAll(/(--lafka-[a-z0-9]+(?:-[a-z0-9]+)*)\s*:/g)) {
		declared.add(m[1]);
	}
	for (const m of text.matchAll(/setProperty\(\s*['"](--lafka-[a-z0-9]+(?:-[a-z0-9]+)*)['"]/g)) {
		declared.add(m[1]);
	}
	for (const m of text.matchAll(/var\(\s*(--lafka-[a-z0-9]+(?:-[a-z0-9]+)*)(?=\s*[,)])/g)) {
		usedAnywhere.add(m[1]);
		if (!used.has(m[1])) {
			used.set(m[1], rel);
		}
	}
	/* A quoted name (preset whitelist, editor map, JS getPropertyValue) is a read. */
	for (const m of text.matchAll(/['"](--lafka-[a-z0-9]+(?:-[a-z0-9]+)*)['"]/g)) {
		usedAnywhere.add(m[1]);
	}
}

const failures = [];

for (const [name, where] of used) {
	if (!declared.has(name) && !DYNAMIC.some((re) => re.test(name))) {
		failures.push(`used but never defined: ${name} (first read in ${where})`);
	}
}

const tokenLines = tokensText.split('\n');
const publicTokens = new Set();
tokenLines.forEach((line, i) => {
	const m = line.match(/^\s*(--lafka-[a-z0-9]+(?:-[a-z0-9]+)*)\s*:/);
	if (m && (/@public/.test(line) || /@public/.test(tokenLines[i - 1] || ''))) {
		publicTokens.add(m[1]);
	}
});
const inTokens = [...new Set([...tokensText.matchAll(/^\s*(--lafka-[a-z0-9]+(?:-[a-z0-9]+)*)\s*:/gm)].map((m) => m[1]))];
let publicOn = false;
tokenLines.forEach((line) => {
	if (/@public-start/.test(line)) {
		publicOn = true;
	}
	const m = line.match(/^\s*(--lafka-[a-z0-9]+(?:-[a-z0-9]+)*)\s*:/);
	if (m && publicOn) {
		publicTokens.add(m[1]);
	}
	if (/@public-end/.test(line)) {
		publicOn = false;
	}
});

for (const name of inTokens) {
	if (!usedAnywhere.has(name) && !publicTokens.has(name)) {
		failures.push(`declared in lafka-tokens.css but never read: ${name} (delete it, or mark it @public if the plugin or a child theme reads it)`);
	}
}

if (failures.length) {
	console.error(`✖ check-tokens: ${failures.length} problem(s)\n  ${failures.join('\n  ')}`);
	process.exit(1);
}
console.log(`✓ check-tokens: ${inTokens.length} tokens declared, every var(--lafka-*) read is defined.`);
