/**
 * Backend Lafka scripts
 */
(function ($) {
	"use strict";
	$(document).ready(function () {
        // Proper position Product Gallery Type Options metabox
        const product_gallery_options_meta = $('#lafka_product_gallery_type');
		const product_gallery_meta = $('#woocommerce-product-images');
        if (product_gallery_options_meta.length && product_gallery_meta.length) {
            product_gallery_options_meta.detach().insertBefore(product_gallery_meta);
        }

	});
})(window.jQuery);