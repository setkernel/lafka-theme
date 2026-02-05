(function ($) {
	"use strict";
	$(document).ready(function () {
		$('#icon_teaser_1_icon,#icon_teaser_2_icon').fontIconPicker({
			emptyIcon: false,
			source:    params.icons
		});
	});
})(window.jQuery);