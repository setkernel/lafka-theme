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
				ajaxurl: "readonly",
				wc_add_to_cart_params: "readonly",
				wc_cart_fragments_params: "readonly",
				wc_single_product_params: "readonly",
				// Lafka wp_localize_script payloads
				lafka_ajax_object: "readonly",
				lafka_options: "readonly",
				lafka_main_js_params: "readonly",
				lafka_back_js_params: "readonly",
				lafka_map_config: "readonly",
				lafka_mega_menu_js_params: "readonly",
				lafka_owl_carousel_cat: "readonly",
				lafka_rtl: "readonly",
				// Lafka helpers (functions defined in other files / window-scoped)
				lafkaUpdateUrlParameters: "writable",
				lafkaStickyHeaderInit: "writable",
				lafkaInitSmallCountdowns: "writable",
				lafkaOrderHoursCountdown: "writable",
				// Third-party APIs / libs loaded via <script>
				google: "readonly",
				Typed: "readonly",
				Modernizr: "readonly",
			},
		},
		rules: {
			"no-unused-vars": "warn",
			"no-undef": "error",
			"eqeqeq": ["warn", "smart"],
			"no-var": "off",
			"prefer-const": "off",
			"no-prototype-builtins": "off",
			// Allow user code to declare locals that shadow our wp_localize_script globals.
			"no-redeclare": ["error", { "builtinGlobals": false }],
			// Codebase pre-dates these modern rules — re-evaluate after a separate cleanup pass.
			"no-useless-assignment": "off",
			"no-useless-escape": "off",
			"no-shadow-restricted-names": "off",
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
	{
		ignores: [
			"vendor/**",
			"node_modules/**",
			"eslint.config.mjs",
			// Minified files are build artifacts — lint the source, not the output.
			"**/*.min.js",
			// Vendor JS libraries
			"js/cloud-zoom/**",
			"js/count/**",
			"js/flex/**",
			"js/fonticonpicker/**",
			"js/isotope/**",
			"js/jquery.nice-select.min.js",
			"js/jquery.nicescroll/**",
			"js/jquery.mb.YTPlayer/**",
			"js/magnific/**",
			"js/owl-carousel2-dist/**",
			"js/modernizr.custom.js",
			"js/typed.min.js",
			// Vendor admin JS
			"incl/lafka-options-framework/**",
			"incl/tgm-plugin-activation/**",
		],
	},
];
