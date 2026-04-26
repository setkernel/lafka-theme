// `params` is provided by the WPBakery icon-picker host page when this script
// is loaded inside the editor. Declared as global so ESLint stops complaining
// without losing the no-undef sniff for genuine typos elsewhere.
/* global params */
(function ($) {
	"use strict";
	$(document).ready(function () {
		$('#icon_teaser_1_icon,#icon_teaser_2_icon').fontIconPicker({
			emptyIcon: false,
			source:    params.icons
		});
	});
})(window.jQuery);