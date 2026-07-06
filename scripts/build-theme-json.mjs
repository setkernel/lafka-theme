#!/usr/bin/env node
/**
 * Regenerate theme.json's editor presets from the token SSOT.
 *
 * `styles/lafka-tokens.css` (the :root block — ~212 --lafka-* custom properties)
 * is the single source of truth for colour, type and spacing. The block editor,
 * however, reads its palette / font-size / font-family / spacing presets from
 * `theme.json`. When the two drift (they did: theme.json accent #DD430E vs token
 * accent-500 #dc2626) the editor and the front end render different brand colours.
 *
 * This script parses the tokens and rewrites ONLY these theme.json sections from
 * an explicit slug -> token map:
 *   - settings.color.palette
 *   - settings.typography.fontSizes
 *   - settings.typography.fontFamilies  (added — body/display/mono)
 *   - settings.spacing.spacingSizes
 * Every other key (appearanceTools, layout, styles, version, $schema, the inline
 * spacing.units array, ...) is preserved byte-for-byte. Output is deterministic:
 * stable key order + tab indentation, matching the committed file's style so the
 * git diff is limited to the reconciled values.
 *
 * theme.json stays committed — there is NO build step at install time. Run this
 * whenever tokens change; `ThemeJsonTokenParityTest` gates the two back into sync.
 *
 * Usage: `npm run build:theme-json`  (or `node scripts/build-theme-json.mjs`)
 *
 * NX2-06 (style variations) builds on this same generator.
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const THEME_JSON = join(ROOT, 'theme.json');
const TOKENS_CSS = join(ROOT, 'styles', 'lafka-tokens.css');

/* Palette slug -> --lafka-* colour token. Slugs keep their names; values come
 * from the SSOT. background-input / border-medium follow the token file's own
 * legacy-alias bridge (--lafka-bg-input -> surface-sunken,
 * --lafka-border-medium -> border-default). */
const PALETTE_MAP = {
	accent: '--lafka-color-accent-500',
	'text-primary': '--lafka-color-text-primary',
	'text-secondary': '--lafka-color-text-secondary',
	'text-muted': '--lafka-color-text-muted',
	background: '--lafka-color-surface-page',
	'background-subtle': '--lafka-color-surface-sunken',
	'background-input': '--lafka-color-surface-sunken',
	'border-light': '--lafka-color-border-subtle',
	'border-default': '--lafka-color-border-default',
	'border-medium': '--lafka-color-border-default',
	'border-dark': '--lafka-color-border-strong',
	'status-error': '--lafka-color-error-500',
	'status-warning': '--lafka-color-warning-500',
	'status-success': '--lafka-color-success-500',
	'status-info': '--lafka-color-info-500',
};

/* Font-size slug -> --lafka-* type token. `x-large` (28px) has no clean token
 * on the type scale, so it is left at its current value (logged below). */
const FONT_SIZE_MAP = {
	small: '--lafka-font-size-caption',
	medium: '--lafka-font-size-body',
	large: '--lafka-font-size-h3',
	'xx-large': '--lafka-font-size-display',
};

/* Font-family presets (new) generated from the family tokens. */
const FONT_FAMILY_MAP = {
	body: '--lafka-font-family-body',
	display: '--lafka-font-family-display',
	mono: '--lafka-font-family-mono',
};
const FONT_FAMILY_NAMES = { body: 'Body', display: 'Display', mono: 'Mono' };

/* Spacing slug -> --lafka-* space token. `3xl` (30px) / `5xl` (50px) sit between
 * scale steps and have no token, so they keep their current values (logged). */
const SPACING_MAP = {
	xs: '--lafka-space-1',
	sm: '--lafka-space-2',
	md: '--lafka-space-3',
	lg: '--lafka-space-4',
	xl: '--lafka-space-5',
	'2xl': '--lafka-space-6',
	'4xl': '--lafka-space-10',
};

/**
 * Parse the base (light-mode) :root { ... } block of lafka-tokens.css into a
 * name -> value map. Comments are stripped first so prose can't corrupt the
 * declaration split; the base block has no nested braces, so the first `}`
 * closes it.
 */
function parseRootTokens(css) {
	const stripped = css.replace(/\/\*[\s\S]*?\*\//g, '');
	const match = stripped.match(/:root\s*\{([\s\S]*?)\}/);
	if (!match) {
		throw new Error('Could not locate the base :root { … } block in lafka-tokens.css');
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
		const name = trimmed.slice(0, colon).trim();
		const value = trimmed.slice(colon + 1).trim().replace(/\s+/g, ' ');
		tokens[name] = value;
	}
	return tokens;
}

/**
 * Rewrite an array-of-preset-objects section in place: for each existing entry,
 * if its slug maps to a token, replace `valueKey`; otherwise keep + log it.
 */
function remap(section, map, valueKey, tokens, label, logKept) {
	for (const entry of section) {
		const token = map[entry.slug];
		if (!token) {
			if (logKept) {
				console.log(`  · ${label} "${entry.slug}" kept at ${entry[valueKey]} (no token maps).`);
			}
			continue;
		}
		if (!(token in tokens)) {
			throw new Error(`${label} "${entry.slug}" maps to ${token}, which is missing from lafka-tokens.css`);
		}
		entry[valueKey] = tokens[token];
	}
}

/**
 * Deterministic serializer matching the committed theme.json style: tab indent,
 * one member per line, arrays of primitives inline (reproduces the `units`
 * array byte-for-byte), arrays of objects expanded.
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

const tokens = parseRootTokens(readFileSync(TOKENS_CSS, 'utf8'));
const theme = JSON.parse(readFileSync(THEME_JSON, 'utf8'));

const typography = theme.settings.typography;

console.log('Regenerating theme.json presets from lafka-tokens.css …');
remap(theme.settings.color.palette, PALETTE_MAP, 'color', tokens, 'palette', false);
remap(typography.fontSizes, FONT_SIZE_MAP, 'size', tokens, 'fontSize', true);
remap(theme.settings.spacing.spacingSizes, SPACING_MAP, 'size', tokens, 'spacingSize', true);

/* fontFamilies: generated fresh from the family tokens, inserted right after
 * fontSizes so the typography object reads sizes-then-families. */
const fontFamilies = Object.entries(FONT_FAMILY_MAP).map(([slug, token]) => {
	if (!(token in tokens)) {
		throw new Error(`fontFamily "${slug}" maps to ${token}, which is missing from lafka-tokens.css`);
	}
	return { slug, name: FONT_FAMILY_NAMES[slug], fontFamily: tokens[token] };
});
const rebuiltTypography = {};
for (const [key, val] of Object.entries(typography)) {
	rebuiltTypography[key] = val;
	if (key === 'fontSizes') {
		rebuiltTypography.fontFamilies = fontFamilies;
	}
}
if (!rebuiltTypography.fontFamilies) {
	rebuiltTypography.fontFamilies = fontFamilies;
}
theme.settings.typography = rebuiltTypography;

writeFileSync(THEME_JSON, `${serialize(theme, 0)}\n`);
console.log('✓ theme.json regenerated from tokens.');
