/**
 * Backend Lafka scripts
 */
(function ($) {
	"use strict";
	$(document).ready(function () {
		// Init wpColorPicker color picker for menu label colors
		$('input.lafka-menu-colorpicker').wpColorPicker();

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

		// Init fonticonpicker on menu edit
		$('#menu-to-edit a.item-edit').on('click', function () {
			$(this).parents("li.menu-item").find("input.lafka-menu-icons").fontIconPicker({
				source: ['flaticon-001-popcorn', 'flaticon-002-tea', 'flaticon-003-chinese-food', 'flaticon-004-tomato-sauce', 'flaticon-005-cola-1', 'flaticon-006-burger-2', 'flaticon-007-burger-1', 'flaticon-008-fried-potatoes', 'flaticon-009-coffee', 'flaticon-010-burger', 'flaticon-011-ice-cream-1', 'flaticon-012-cola', 'flaticon-013-milkshake', 'flaticon-014-sauces', 'flaticon-015-hot-dog-1', 'flaticon-016-chicken-leg-1', 'flaticon-017-croissant', 'flaticon-018-cheese', 'flaticon-019-sausage', 'flaticon-020-fried-egg', 'flaticon-021-fried-chicken', 'flaticon-022-serving-dish', 'flaticon-023-pizza-slice', 'flaticon-024-chef-hat', 'flaticon-025-meat', 'flaticon-026-ice-cream', 'flaticon-027-donut', 'flaticon-028-rice', 'flaticon-029-package', 'flaticon-030-kebab', 'flaticon-031-delivery', 'flaticon-032-food-truck', 'flaticon-033-waiter-1', 'flaticon-034-waiter', 'flaticon-035-taco', 'flaticon-036-chips', 'flaticon-037-soda', 'flaticon-038-take-away', 'flaticon-039-fork', 'flaticon-040-coffee-cup', 'flaticon-041-waffle', 'flaticon-042-beer', 'flaticon-043-chicken-leg', 'flaticon-044-pitcher', 'flaticon-045-coffee-machine', 'flaticon-046-noodles', 'flaticon-047-menu', 'flaticon-048-hot-dog', 'flaticon-049-breakfast', 'flaticon-050-french-fries']
			});
		});
	});
})(window.jQuery);