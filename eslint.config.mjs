import js from "@eslint/js";
import globals from "globals";

export default [
	js.configs.recommended,
	{
		languageOptions: {
			ecmaVersion: 2020,
			sourceType: "script",
			globals: {
				...globals.browser,
				...globals.jquery,
				// Core WP / WC
				wp: "readonly",
				wc_add_to_cart_params: "readonly",
				wc_cart_fragments_params: "readonly",
				// Lafka wp_localize_script payloads
				lafka_main_js_params: "readonly",
				lafka_owl_carousel_cat: "readonly",
				lafka_rtl: "readonly",
				lafka_quickview: "readonly",
				// Lafka helpers (functions defined in other files / window-scoped)
				lafkaUpdateUrlParameters: "writable",
				lafkaInitSmallCountdowns: "writable",
				lafkaOrderHoursCountdown: "writable",
				// Third-party APIs / libs loaded via <script>
				google: "readonly",
			},
		},
		rules: {
			"no-unused-vars": "error",
			"no-undef": "error",
			"eqeqeq": ["error", "smart"],
			"no-var": "error",
			"prefer-const": "error",
			// Allow user code to declare locals that shadow our wp_localize_script globals.
			"no-redeclare": ["error", { "builtinGlobals": false }],
		},
	},
	// Service worker file has its own global scope
	{
		files: ["js/sw.js"],
		languageOptions: {
			globals: {
				self: "readonly",
				caches: "readonly",
				clients: "readonly",
				skipWaiting: "readonly",
			},
		},
	},
	// Node.js build scripts (ES modules) — e.g. scripts/sync-version.mjs.
	// ecmaVersion 2022 for top-level await (nx2-04-preset-previews.mjs).
	{
		files: ["scripts/**/*.mjs", "eslint.config.mjs"],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: "module",
			globals: {
				...globals.node,
			},
		},
	},
	{
		ignores: [
			"vendor/**",
			"node_modules/**",
			// Minified files are build artifacts — lint the source, not the output.
			"**/*.min.js",
		],
	},
];
