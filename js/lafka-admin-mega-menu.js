/**
 * Used for mega menu set up in admin
 */
(function ($) {
	"use strict";

	var lafka_mega_menu = {
		recalcTimeout: false,
		megaMenuInitAdminUI: function (checkbox) {
			var $container = checkbox.parents('.menu-item:eq(0)');
			var $container_label = $container.find('span.item-type');
			var lafka_mega_menu_label = '<span class="lafka-admin-mega-menu-label"> [' + lafka_mega_menu_js_params.mega_menu_label + ']</span>';

			if (checkbox.is(':checked')) {
				$container.addClass('lafka_is_mega');
				$container_label.html($container_label.html() + lafka_mega_menu_label);
			} else {
				$container.removeClass('lafka_is_mega');
				$container_label.find('span.lafka-admin-mega-menu-label').remove();
			}
		},
		bind_click: function ()
		{
			var megmenuActivator = '.lafka-menu-item-is_megamenu';

			$(document).on('click', megmenuActivator, function () {
				lafka_mega_menu.megaMenuInitAdminUI($(this));

				//check if anything in the dom needs to be changed to reflect the (de)activation of the mega menu
				lafka_mega_menu.recalc();

			});
		},
		recalcInit: function ()
		{
			$(document).on('mouseup', '.menu-item-bar', function (event, ui)
			{
				if (!$(event.target).is('a'))
				{
					clearTimeout(lafka_mega_menu.recalcTimeout);
					lafka_mega_menu.recalcTimeout = setTimeout(lafka_mega_menu.recalc, 500);
				}
			});
		},
		recalc: function ()
		{
			var menuItems = $('.menu-item');

			menuItems.each(function (i)
			{
				var item = $(this);
				var megaMenuCheckbox = $('.lafka-menu-item-is_megamenu', this);
				var $item_label = item.find('span.item-type');
				var lafka_column_label = '<span class="lafka-admin-mega-menu-column-label"> [' + lafka_mega_menu_js_params.column_label + ']</span>';

				if (!item.is('.menu-item-depth-0'))
				{
					var checkItem = menuItems.eq(i - 1);
					if (checkItem.is('.lafka_is_mega'))	{
						item.addClass('lafka_is_mega');
						megaMenuCheckbox.attr('checked', 'checked');
						if(item.hasClass('menu-item-depth-1')) {
							$item_label.find('span.lafka-admin-mega-menu-column-label').remove();
							$item_label.html($item_label.html() + lafka_column_label);
						}
					} else {
						item.removeClass('lafka_is_mega');
						megaMenuCheckbox.attr('checked', '');
						$item_label.find('span.lafka-admin-mega-menu-column-label').remove();
					}
				}
			});

		}
	};

	$(function ()
	{
		lafka_mega_menu.bind_click();
		lafka_mega_menu.recalcInit();
		lafka_mega_menu.recalc();
	});

	$(document).ready(function () {
		$(document.body).find('.menu-item-depth-0').find('input.lafka-menu-item-is_megamenu').each(function () {
			lafka_mega_menu.megaMenuInitAdminUI($(this));
		});
		lafka_mega_menu.recalc();
	});

})(jQuery);