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
				wp: "readonly",
				ajaxurl: "readonly",
				wc_add_to_cart_params: "readonly",
				wc_cart_fragments_params: "readonly",
				wc_single_product_params: "readonly",
				lafka_ajax_object: "readonly",
				lafka_options: "readonly",
				lafkaUpdateUrlParameters: "readonly",
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
			// Vendor JS libraries
			"js/cloud-zoom/**",
			"js/count/**",
			"js/flex/**",
			"js/fonticonpicker/**",
			"js/isotope/**",
			"js/jquery.nice-select.min.js",
			"js/jquery.appear.min.js",
			"js/jquery.nicescroll/**",
			"js/jquery.mb.YTPlayer/**",
			"js/magnific/**",
			"js/owl-carousel2-dist/**",
			"js/modernizr.custom.js",
			"js/typed.min.js",
			"js/isInViewport.min.js",
			"js/lafka-libs-config.min.js",
			// Vendor admin JS
			"incl/lafka-options-framework/**",
			"incl/tgm-plugin-activation/**",
		],
	},
];
