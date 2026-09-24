/**
 * Backend Lafka scripts
 */
(function ($) {
	"use strict";
	$(document).ready(function () {
        // Init wpColorPicker color picker for theme options
        $('input.lafka-theme-options-colorpicker').wpColorPicker({
            change: function(event, ui){
                $(this).closest('div.controls').find('div.lafka_font_preview p').css({color: ui.color});
			}
		});

		// Proper position featured images metaboxes
		var featured_img_meta = $('#postimagediv');
		var featured_imgs_arr = new Array();
		if (featured_img_meta.length) {
			for (var i = 6; i >= 2; i--) {
				featured_imgs_arr[i] = $('#lafka_featured_' + i);
				if (featured_imgs_arr[i].length) {
					featured_imgs_arr[i].detach().insertAfter(featured_img_meta);
				}
			}
		}

		// Proper position Foodmenu Gallery Options metabox
		var prtfl_gallery_options_meta = $('#lafka_foodmenu_cz');
		if (prtfl_gallery_options_meta.length && featured_img_meta.length) {
			prtfl_gallery_options_meta.detach().insertBefore(featured_img_meta);
		}

        // Proper position Product Gallery Type Options metabox
        var product_gallery_options_meta = $('#lafka_product_gallery_type');
		var product_gallery_meta = $('#woocommerce-product-images');
        if (product_gallery_options_meta.length && product_gallery_meta.length) {
            product_gallery_options_meta.detach().insertBefore(product_gallery_meta);
        }

	});
})(window.jQuery);