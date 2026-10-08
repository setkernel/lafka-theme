#!/usr/bin/env node
/**
 * Generate the editor presets and the PHP token defaults from the token SSOT.
 *
 * `styles/lafka-tokens.css` (the base `:root` block and the dark scaffold) plus
 * the preset engine (`presets/<default>/preset.json`) are the single source for
 * colour, type and spacing. The block editor reads its palette, font sizes,
 * font families and spacing from `theme.json`; PHP needs the shipped token
 * values as fallbacks for Customizer defaults. Both are derived here from
 * `incl/presets/theme-json-map.json` (slug -> token), never edited by hand:
 *
 *   theme.json                     settings.color.palette, settings.typography.fontSizes,
 *                                  settings.typography.fontFamilies, settings.spacing.spacingSizes
 *                                  (values = base tokens overlaid with the default preset, so the
 *                                  shipped editor matches what the default design renders)
 *   incl/presets/token-defaults.json  { light: base tokens, dark: dark-scaffold tokens }
 *                                  (read by lafka_token_default() and the theme.json overlay)
 *
 * Every other theme.json key is preserved byte for byte. Output is
 * deterministic (tab indentation, stable key order).
 *
 * Usage:
 *   npm run build:theme-json    write both files (npm run build runs it)
 *   npm run check-theme-json    exit 1 when either file is stale (CI + pre-push)
 *
 * At runtime the active preset and the operator's accent/brand are laid over
 * these values by lafka_theme_json_overlay() (incl/presets/lafka-token-defaults.php),
 * so the editor follows the front end for every preset.
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const THEME_JSON = join(ROOT, 'theme.json');
const TOKENS_CSS = join(ROOT, 'styles', 'lafka-tokens.css');
const MAP_JSON = join(ROOT, 'incl', 'presets', 'theme-json-map.json');
const DEFAULTS_JSON = join(ROOT, 'incl', 'presets', 'token-defaults.json');
const DEFAULT_PRESET = 'peppery';

const CHECK = process.argv.includes('--check');

/**
 * Declarations of one rule block as a name -> value map. Comments are stripped
 * first so prose cannot corrupt the split. The blocks parsed here hold no
 * nested braces, so the first `}` closes them.
 *
 * @param {string} css Stylesheet text.
 * @param {RegExp} selector Matches the block's selector, with the body in group 1.
 * @returns {Record<string,string>} Token name -> value.
 */
function parseBlock(css, selector) {
	const stripped = css.replace(/\/\*[\s\S]*?\*\//g, '');
	const match = stripped.match(selector);
	if (!match) {
		throw new Error(`Could not locate ${selector} in lafka-tokens.css`);
	}
	const tokens = {};
	for (const decl of match[1].split(';')) {
		const trimmed = decl.trim();
		if (!trimmed.startsWith('--')) {
			continue;
		}
		const colon = trimmed.indexOf(':');
		if (colon === -1) {
			continue;
		}
		tokens[trimmed.slice(0, colon).trim()] = trimmed
			.slice(colon + 1)
			.trim()
			.replace(/\s+/g, ' ');
	}
	return tokens;
}

/**
 * Follow `var(--other)` aliases (single hop, repeated) to a literal value.
 *
 * @param {string} name Token name.
 * @param {Record<string,string>} tokens Token map.
 * @returns {string} Resolved value.
 */
function resolve(name, tokens) {
	let value = tokens[name];
	for (let i = 0; i < 5 && typeof value === 'string'; i++) {
		const alias = value.match(/^var\((--[a-z0-9-]+)\)$/);
		if (!alias) {
			break;
		}
		value = tokens[alias[1]];
	}
	return value;
}

/**
 * Deterministic serializer matching the committed theme.json style: tab indent,
 * one member per line, arrays of primitives inline, arrays of objects expanded.
 *
 * @param {*} value Value.
 * @param {number} depth Indent depth.
 * @returns {string} JSON text.
 */
function serialize(value, depth) {
	const pad = '\t'.repeat(depth);
	const padIn = '\t'.repeat(depth + 1);
	if (value === null || typeof value !== 'object') {
		return JSON.stringify(value);
	}
	if (Array.isArray(value)) {
		if (value.length === 0) {
			return '[]';
		}
		if (value.every((v) => v === null || typeof v !== 'object')) {
			return `[${value.map((v) => JSON.stringify(v)).join(', ')}]`;
		}
		const items = value.map((v) => padIn + serialize(v, depth + 1));
		return `[\n${items.join(',\n')}\n${pad}]`;
	}
	const keys = Object.keys(value);
	if (keys.length === 0) {
		return '{}';
	}
	const items = keys.map((k) => `${padIn}${JSON.stringify(k)}: ${serialize(value[k], depth + 1)}`);
	return `{\n${items.join(',\n')}\n${pad}}`;
}

const css = readFileSync(TOKENS_CSS, 'utf8');
const map = JSON.parse(readFileSync(MAP_JSON, 'utf8'));
const theme = JSON.parse(readFileSync(THEME_JSON, 'utf8'));

const base = parseBlock(css, /(?<![\w-]):root\s*\{([\s\S]*?)\n\}/);
const dark = parseBlock(css, /:root\[data-theme="dark"\]\s*\{([\s\S]*?)\n\}/);

/* The default preset overlays the base so the shipped editor matches the
 * default design: its PTL tokens, and its chrome accent/brand (which feed
 * --lafka-color-accent-500 / --lafka-color-brand-500 through the operator
 * layer). */
const preset = JSON.parse(readFileSync(join(ROOT, 'presets', DEFAULT_PRESET, 'preset.json'), 'utf8'));
const shipped = { ...base, ...(preset.tokens || {}) };
if (preset.chrome && preset.chrome.lafka_accent_color) {
	shipped['--lafka-color-accent-500'] = preset.chrome.lafka_accent_color;
}
if (preset.chrome && preset.chrome.lafka_brand_color) {
	shipped['--lafka-color-brand-500'] = preset.chrome.lafka_brand_color;
}

/**
 * Build a preset list (`slug`, `name`, then `valueKey`) from one map section.
 *
 * @param {Record<string,{name:string,token:string}>} section Map section.
 * @param {string} valueKey `color` | `size` | `fontFamily`.
 * @param {string} label For error messages.
 * @returns {Array<Object>} Preset entries.
 */
function build(section, valueKey, label) {
	return Object.entries(section).map(([slug, { name, token }]) => {
		const value = resolve(token, shipped);
		if (typeof value !== 'string') {
			throw new Error(`${label} "${slug}" maps to ${token}, which is missing from lafka-tokens.css`);
		}
		return { slug, name, [valueKey]: value };
	});
}

const settings = theme.settings;
settings.color.palette = build(map.palette, 'color', 'palette');
const typography = {};
for (const [key, val] of Object.entries(settings.typography)) {
	if (key === 'fontFamilies') {
		continue;
	}
	typography[key] = key === 'fontSizes' ? build(map.fontSizes, 'size', 'fontSize') : val;
	if (key === 'fontSizes') {
		typography.fontFamilies = build(map.fontFamilies, 'fontFamily', 'fontFamily');
	}
}
settings.typography = typography;
settings.spacing.spacingSizes = build(map.spacing, 'size', 'spacingSize');

/* Every token that is a literal (not an alias): the PHP defaults. Sorted so the
 * file diff is stable. */
const literals = (tokens) =>
	Object.fromEntries(
		Object.entries(tokens)
			.filter(([, v]) => !/^var\(/.test(v))
			.sort(([a], [b]) => a.localeCompare(b))
	);
const defaults = { light: literals(base), dark: literals(dark) };

const outputs = [
	[THEME_JSON, `${serialize(theme, 0)}\n`],
	[DEFAULTS_JSON, `${serialize(defaults, 0)}\n`],
];

let stale = 0;
for (const [file, text] of outputs) {
	const current = (() => {
		try {
			return readFileSync(file, 'utf8');
		} catch {
			return '';
		}
	})();
	if (CHECK) {
		if (current !== text) {
			stale += 1;
			console.error(`✖ ${file.replace(`${ROOT}/`, '')} is stale — run: npm run build:theme-json`);
		}
	} else if (current !== text) {
		writeFileSync(file, text);
		console.log(`✓ wrote ${file.replace(`${ROOT}/`, '')}`);
	}
}

if (CHECK) {
	if (stale) {
		process.exit(1);
	}
	console.log('✓ theme.json and token-defaults.json match lafka-tokens.css and the default preset.');
} else {
	console.log('✓ editor presets and token defaults generated from the token SSOT.');
}
