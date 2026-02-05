<?php
/**
 * Insert the customized css from selected options on wp_head hook + the custom css
 */
add_action('wp_enqueue_scripts', 'lafka_add_custom_css', 99);

if (!function_exists('lafka_add_custom_css')) {

    function lafka_add_custom_css()
    {
        ob_start();
        ?>
        <style media="all" type="text/css">
            /* Site main accent color */
            .lafka-all-stores-closed-countdown .count_holder_small, .lafka-branch-auto-locate i, .lafka-delivery-time-toggle:before, a.lafka-branch-delivery:before, a.lafka-branch-pickup:before, .wpb_lafka_banner.lafka-banner-dark a h5, ul.product_list_widget li span.quantity, .count_holder .countdown_time_tiny, .lafka_content_slider.lafka_content_slider_light_nav .owl-nav .owl-next, .lafka_content_slider.lafka_content_slider_light_nav .owl-nav .owl-prev, div.widget_categories ul li.current-cat > a:before, #lafka_price_range, ul.woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li:before, p.product.woocommerce.add_to_cart_inline, .lafka-promo-wrapper .lafka-promo-text, .lafka-related-blog-posts div.post.blog-post.lafka-post-no-image .lafka_post_data_holder h2.heading-title:before, button.single_add_to_cart_button:before, .links a.button.add_to_cart_button:after, .links a.button.add_to_cart_button.ajax_add_to_cart:after, #lafka-account-holder.lafka-user-is-logged .lafka-header-account-link-holder > ul li a:hover:before, .commentlist ul.children:before, .infinite-scroll-request:before, .widget_layered_nav_filters li a:before, .links a.button.add_to_cart_button:after, .links a.button.add_to_cart_button.ajax_add_to_cart:after, div.prod_hold .name sup, #main-menu li ul.sub-menu li a sup, div.prod_hold .name sub, #content div.product div.summary h1.heading-title sup, #content div.product div.summary h1.heading-title sub, .lafka-spec-dot, .count_holder .count_info:before, .lafka-pricing-table-shortcode .title-icon-holder, .count_holder .count_info_left:before, .widget_layered_nav ul li:hover .count, .widget_layered_nav ul li.chosen a, .widget_product_categories ul li:hover > .count, .widget_product_categories ul li.current-cat > a, .widget_layered_nav ul li:hover a:before, .widget_product_categories ul li:hover a:before, .wpb_lafka_banner a span.lafka_banner-icon, .lafka-event-countdown .is-countdown, .video_controlls a#video-volume:after, div.widget_categories ul li > a:hover:before, #main-menu ul.menu > li > a:hover, #main-menu ul.menu > li.current-menu-item > a, .otw-input-wrap:before, a.bbp-forum-title:hover, .foodmenu_top .project-data .main-features .checklist li:before, body.lafka_transparent_header #main-menu ul.menu > li.current-menu-item > a:before, body.lafka_transparent_header #main-menu ul.menu > li.current-menu-item > a:before, body.lafka_transparent_header #main-menu ul.menu > li > a:hover:before {
                color: <?php echo esc_attr(lafka_get_option('accent_color')) ?>;
            }

            #header #logo, .double-bounce1, #products-wrapper div.product-category.product:hover h2  {
                background-color: <?php echo esc_attr(lafka_get_option('logo_background_color')) ?>;
            }

            #header #logo:after {
                border-color: <?php echo esc_attr(lafka_get_option('logo_background_color')) ?> <?php echo esc_attr(lafka_get_option('logo_background_color')) ?> transparent transparent;
            }

            #header #logo:before {
                border-color: <?php echo esc_attr(lafka_get_option('logo_background_color')) ?> transparent transparent <?php echo esc_attr(lafka_get_option('logo_background_color')) ?>;
            }

            #header.lafka-has-header-top #logo a:before, .woocommerce-tabs ul.tabs li.active:before, .woocommerce-tabs ul.tabs li:hover:before {
                border-color: transparent <?php echo esc_attr(lafka_get_option('logo_background_color')) ?> <?php echo esc_attr(lafka_get_option('logo_background_color')) ?> transparent;
            }

            #header.lafka-has-header-top #logo a:after, .woocommerce-tabs ul.tabs li.active:after, .woocommerce-tabs ul.tabs li:hover:after {
                border-color: transparent transparent <?php echo esc_attr(lafka_get_option('logo_background_color')) ?> <?php echo esc_attr(lafka_get_option('logo_background_color')) ?>;
            }

            a.lafka-change-branch-button:before, .lafka-change-branch-button-select:before, .woocommerce form.checkout h3.lafka-address-not-found, .woocommerce form.checkout h3.lafka-address-not-found:after, .lafka-author-info .title h2:after, .widget > h3:first-child:before, h2.widgettitle:before, .double-bounce2, .foodmenu-unit-info a.foodmenu-lightbox-link, blockquote, q, .wp-block-pullquote:not(.is-style-solid-color), .lafka-product-slider .owl-nav .owl-next, .lafka-product-slider .owl-nav .owl-prev, .lafka_image_list .owl-nav .owl-prev, .lafka_image_list .owl-nav .owl-next, figure.woocommerce-product-gallery__wrapper .owl-nav .owl-prev, figure.woocommerce-product-gallery__wrapper .owl-nav .owl-next, .lafka_content_slider .owl-nav .owl-next, .lafka_content_slider .owl-nav .owl-prev, .woocommerce.owl-carousel .owl-nav .owl-next, .woocommerce.owl-carousel .owl-nav .owl-prev, .related.products .owl-nav .owl-prev, .related.products .owl-nav .owl-next, .similar_projects .owl-nav .owl-prev, .similar_projects .owl-nav .owl-next, .lafka-foodmenu-shortcode .owl-nav .owl-prev, .lafka-foodmenu-shortcode .owl-nav .owl-next, .lafka_shortcode_latest_posts .owl-nav .owl-prev, .lafka_shortcode_latest_posts .owl-nav .owl-next, .lafka-quickview-images .owl-nav .owl-prev, .lafka-quickview-images .owl-nav .owl-next, .tribe-mini-calendar-event .list-date, .widget_shopping_cart_content p.buttons .button.checkout, .lafka-wcs-swatches .swatch.swatch-label.selected, .lafka-wcs-swatches .swatch.swatch-label:hover, .is-lafka-video .mfp-iframe-holder .mfp-content .mfp-close, a#cancel-comment-reply-link, blockquote:before, q:before, .commentlist li .comment-body:hover .comment-reply-link, a.lafka-post-nav .entry-info-wrap:after, .lafka-author-info .title a:after, #comments h3.heading-title span.lafka_comments_count, #comments h3.heading-title span.lafka_comments_count, div.lafka_whole_banner_wrapper:after, .blog-post:hover > .lafka_post_data_holder h2.heading-title a:after, .wpb_text_column h6 a:hover:after, .wpb_text_column h5 a:hover:after, .wpb_text_column p a:hover:after, .blog-post-meta.post-meta-top .count_comments a, div:not(.lafka_blog_masonry) > .blog-post.sticky .lafka_post_data_holder:before, .wcmp_vendor_list .wcmp_sorted_vendors:before, .tribe-events-list div.type-tribe_events .tribe-events-event-cost, .tribe-events-schedule .tribe-events-cost, .woocommerce form.track_order input.button, #bbpress-forums li.bbp-body ul.forum:hover, #bbpress-forums li.bbp-body ul.topic:hover, .woocommerce-shipping-fields input[type="checkbox"]:checked + span:before, .widget_product_categories ul li.current-cat > .count, .widget_layered_nav ul li.chosen .count, .bypostauthor > .comment-body img.avatar, .lafka_added_to_cart_notification, #yith-wcwl-popup-message, .lafka-iconbox h5:after, .lafka-pricing-heading h5:after, .lafka_title_holder.centered_title .inner h1.heading-title:before, a.sidebar-trigger, td.tribe-events-present > div:first-of-type, a.mob-close-toggle:hover, .pagination .links a:hover, .dokan-pagination-container .dokan-pagination li a:hover, a.mob-menu-toggle i, .bbp-pagination-links a:hover, .lafka_content_slider .owl-dot.active span, #main-menu ul.menu > li > a .lafka-custom-menu-label, .product-category.product h2 mark:after, #main-menu li ul.sub-menu li.lafka_colum_title > a:after, #main-menu li ul.sub-menu li.lafka_colum_title > a:before, .blog-post-meta span.sticky_post, #bbpress-forums > #subscription-toggle a.subscription-toggle, .widget > h3:first-child:before, h2.widgettitle:before, .widget > h3:first-child:after, .lafka-foodmenu-categories ul li a:hover:before, .lafka-foodmenu-categories ul li a.is-checked:before, .lafka-foodmenu-categories ul li a:hover:after, .lafka-foodmenu-categories ul li a.is-checked:after, .flex-direction-nav a, ul.status-closed li.bbp-topic-title .bbp-topic-permalink:before, ul.sticky li.bbp-topic-title .bbp-topic-permalink:before, ul.super-sticky li.bbp-topic-title .bbp-topic-permalink:before {
                background-color: <?php echo esc_attr(lafka_get_option('accent_color')) ?>;
            }

            .lafka_image_list a.lafka-magnific-gallery-item:after, .gallery-item dt a:after, .gallery-item dd a:after, .blocks-gallery-item a:after, .lafka-user-is-logged .lafka-header-account-link-holder > ul li, .wpb_single_image a.prettyphoto:before, div.woocommerce-product-gallery__image a:before {
                background-color: <?php echo esc_attr(lafka_get_option('accent_color')) ?>;
            }

            .vc_tta-color-white.vc_tta-style-modern .vc_tta-tab.vc_active > a, .vc_tta-color-white.vc_tta-style-modern .vc_tta-tab > a:hover, li.product-category.product h2 mark, div.product-category.product h2 mark, .bbp-topics-front ul.super-sticky:hover, .box-sort-filter .ui-slider-horizontal .ui-slider-handle, .widget_price_filter .ui-slider-handle.ui-state-default.ui-corner-all, .bbp-topics ul.super-sticky:hover, .bbp-topics ul.sticky:hover, .bbp-forum-content ul.sticky:hover {
                background-color: <?php echo esc_attr(lafka_get_option('accent_color')) ?> !important;
            }
 
            ul.commentlist > li.pingback {border-left-color: <?php echo esc_attr(lafka_get_option('accent_color')) ?> !Important;}

            span.lafka-branch-select-image.lafka-branch-selected-image, .similar_projects > h4 a:after, .related.products h2 a:after, .post-type-archive-lafka-foodmenu .content_holder, .lafka-foodmenu-shortcode, .flex-direction-nav a:after, .lafka_content_slider .owl-dot.active span:after, .bypostauthor > .comment-body:before, .lafka-product-slider .count_holder, .owl-next:before, .owl-prev:before, .lafka_title_holder .inner .lafka-title-text-container:before, #spinner:before, blockquote, q, .sidebar.off-canvas-sidebar, body > div.widget.woocommerce.widget_shopping_cart, .commentlist li .comment-body:hover:before, .commentlist li .comment-body:hover:after, .lafka-header-account-link-holder, .is-lafka-video .mfp-iframe-holder .mfp-content, body > #search, .lafka-quick-view-lightbox .mfp-content, .lafka-icon-teaser-lightbox .mfp-content, div:not(.lafka_blog_masonry) > .blog-post.sticky .lafka_post_data_holder, #bbpress-forums li.bbp-body ul.forum:hover, #bbpress-forums li.bbp-body ul.topic:hover, div.product div.images ol.flex-control-nav li img.flex-active, div.product div.images ol.flex-control-nav li:hover img, .bbp-topics-front ul.super-sticky, .widget_layered_nav ul li:hover .count, .widget_layered_nav ul li.chosen .count, .widget_product_categories ul li.current-cat > .count, .widget_product_categories ul li:hover .count, #main-menu li ul.sub-menu li.lafka-highlight-menu-item:after, .error404 div.blog-post-excerpt, .lafka-none-overlay.lafka-10px-gap .foodmenu-unit-holder:hover, .foodmenu-unit-info a.foodmenu-lightbox-link:hover, body table.booked-calendar td.today .date span, .bbp-topics ul.super-sticky, .bbp-topics ul.sticky, .bbp-forum-content ul.sticky, .lafka-pulsator-accent .wpb_wrapper:after {
                border-color: <?php echo esc_attr(lafka_get_option('accent_color')) ?> !Important;
            }

            ::-moz-selection {
                background: <?php echo esc_attr(lafka_get_option('accent_color')) ?>;
            }

            ::selection {
                background: <?php echo esc_attr(lafka_get_option('accent_color')) ?>;
            }

            /* Links color */
            a, div.widget_categories ul li a:hover, nav.woocommerce-MyAccount-navigation ul li a:hover, nav.woocommerce-MyAccount-navigation ul li.is-active a, div.widget_nav_menu ul li a:hover, div.widget_archive ul li a:hover, div.widget_recent_comments ul li a:hover, div.widget_pages ul li a:hover, div.widget_links ul li a:hover, div.widget_recent_entries ul a:hover, div.widget_meta ul li a:hover, div.widget_display_forums ul li a:hover, .widget_display_replies ul li a:hover, .widget_display_topics li > a.bbp-forum-title:hover, .widget_display_stats dt:hover, .widget_display_stats dd:hover, div.widget_display_views ul li a:hover, .widget_layered_nav ul li a:hover, .widget_product_categories ul li a:hover {
                color: <?php echo esc_attr(lafka_get_option('links_color')) ?>;
            }

            /* Links hover color */
            a:hover {
                color: <?php echo esc_attr(lafka_get_option('links_hover_color')) ?>;
            }

            /* Widgets Title Color */
            .sidebar .widget > h3:first-of-type, .sidebar .widget h2.widgettitle, .wpb_widgetised_column .box h3:first-of-type, h2.wpb_flickr_heading {
                color: <?php echo esc_attr(lafka_get_option('sidebar_titles_color')) ?>;
            }

            /* Buttons Default style */
            <?php if (lafka_get_option('all_buttons_style') === 'round'): ?>
            .lafka-wcs-swatches .swatch {
                border-radius: 50%;
                -webkit-border-radius: 50%;
                -moz-border-radius: 50%;
            }

            span.onsale {
                border-radius: 5em;
            }

            .count_holder .count_info {
                border-radius: 3px 5em 5em 3px;
            }

            .count_holder .count_info_left {
                border-radius: 5em 3px 3px 5em;
            }

            .product-type-external .count_holder .count_info_left {
                border-radius: 5em 5em 5em 5em;
            }

            .prod_hold .lafka-variations-in-catalog.cart > span, div.prod_hold .links a.lafka-quick-view-link, div:not(.sidebar) div.widget_search input[type="text"], div:not(.sidebar) div.widget_product_search input[type="text"], a.button, .r_more_blog, a.mob-menu-toggle i, a.mob-menu-toggle i:after, .wishlist_table .links a.button.add_to_cart_button, .wcv-navigation ul.menu.horizontal li a, form .vendor_sort select, .wcv-pro-dashboard input[type="submit"], .lafka-pricing-table-button a, .widget_display_search input#bbp_search, #bbpress-forums > #subscription-toggle a.subscription-toggle, .bbp-topic-title span.bbp-st-topic-support, div.quantity, .lafka_banner_buton, .woocommerce .wishlist_table td.product-add-to-cart a.button, .widget_shopping_cart_content p.buttons .button, input.button, button.button, a.button-inline, #submit_btn, #submit, .wpcf7-submit, #bbpress-forums #bbp-search-form #bbp_search, input[type="submit"], form.mc4wp-form input[type=submit], form.mc4wp-form input[type=email] {
                border-radius: 300px !important;
            }

            <?php endif; ?>
            /* Wordpress Default Buttons Color */
            .lafka-banner-dark .lafka_banner_buton, a.button, .r_more_blog, button.wcv-button, input.button, .wcv-navigation ul.menu.horizontal li a, input.button, .woocommerce .wishlist_table td.product-add-to-cart a.button, button.button, a.button-inline, #submit_btn, #submit, .wpcf7-submit, input.otw-submit, form.mc4wp-form input[type=submit], .tribe-events-button, input[type="submit"] {
                background-color: <?php echo esc_attr(lafka_get_option('all_buttons_color')) ?>;
            }

            /* Wordpress Default Buttons Hover Color */
            a.button:hover, .r_more_blog:hover, .widget_shopping_cart_content p.buttons .button:hover, .vc_btn3-style-custom:hover, input.button:hover, .wcv-navigation ul.menu.horizontal li a:hover, .wcv-navigation ul.menu.horizontal li.active a, button.button:hover, .woocommerce .wishlist_table td.product-add-to-cart a.button:hover, a.button-inline:hover, #submit_btn:hover, #submit:hover, .wpcf7-submit:hover, .r_more:hover, .r_more_right:hover, button.single_add_to_cart_button:hover, .lafka-product-slide-cart .button.add_to_cart_button:hover, input.otw-submit:hover, form.mc4wp-form input[type=submit]:hover, .wc-proceed-to-checkout a.checkout-button.button:hover {
                background-color: <?php echo esc_attr(lafka_get_option('all_buttons_hover_color')) ?> !important;
            }

            /* NEW label color */
            div.prod_hold .new_prod {
                background-color: <?php echo esc_attr(lafka_get_option('new_label_color')) ?>;
            }

            /* SALE label color */
            div.prod_hold .sale, span.onsale {
                background-color: <?php echo esc_attr(lafka_get_option('sale_label_color')) ?>;
            }

            /* Standard page title color (no background image) */
            #lafka_page_title h1.heading-title, #lafka_page_title h1.heading-title a, .breadcrumb, .breadcrumb a, .lafka-dark-skin #lafka_page_title h1.heading-title a, body.single-post .lafka_title_holder .blog-post-meta a {
                color: <?php echo esc_attr(lafka_get_option('page_title_color')) ?>;
            }

            .breadcrumb {
                color: #999999;
            }

            /* Standard page subtitle color (no background image) */
            .lafka_title_holder h6 {
                color: <?php echo esc_attr(lafka_get_option('page_subtitle_color')) ?>;
            }

            /* Customized page title color (with background image) */
            #lafka_page_title.lafka_title_holder.title_has_image h1.heading-title, #lafka_page_title.lafka_title_holder.title_has_image .blog-post-meta *, #lafka_page_title.lafka_title_holder.title_has_image .blog-post-meta .post-meta-date:before, #lafka_page_title.lafka_title_holder.title_has_image h1.heading-title a, body.single-post #lafka_page_title.lafka_title_holder.title_has_image .blog-post-meta a, #lafka_page_title.lafka_title_holder.title_has_image h6, #lafka_page_title.lafka_title_holder.title_has_image .breadcrumb, #lafka_page_title.lafka_title_holder.title_has_image .breadcrumb a {
                color: <?php echo esc_attr(lafka_get_option('custom_page_title_color')) ?>;
            }

            body.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image h1.heading-title, body.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image .blog-post-meta *, body.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image .blog-post-meta .post-meta-date:before, body.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image h1.heading-title a, body.single-post.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image .blog-post-meta a, body.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image h6, body.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image .breadcrumb, body.lafka_transparent_header.lafka-transparent-dark #lafka_page_title.lafka_title_holder.title_has_image .breadcrumb a {
                color: <?php echo esc_attr(lafka_get_option('transparent_header_dark_menu_color')) ?>;
            }

            /* Standard page title background color (no background image) */
            .lafka_title_holder, .lafka_title_holder .inner:before, body.lafka_header_left .lafka_title_holder:not(.title_has_image) .inner {
                background-color: <?php echo esc_attr(lafka_get_option('page_title_bckgr_color')) ?>;
            }

            /* Standard page title border color (no background image) */
            .lafka_title_holder, body.lafka_header_left .lafka_title_holder:not(.title_has_image) .inner {
                border-color: <?php echo esc_attr(lafka_get_option('page_title_border_color')) ?>;
            }

            .lafka_title_holder .inner:before {
                border-color: transparent <?php echo esc_attr(lafka_get_option('page_title_border_color')) ?> <?php echo esc_attr(lafka_get_option('page_title_border_color')) ?> transparent;
            }

            <?php if (lafka_get_option('fancy_title_font')): ?>
                #lafka_page_title h1.heading-title, #content div.product div.summary h1.heading-title, .lafka-quick-view-lightbox h1.product_title {
                    font-family: "tiza" !important;
                    font-weight: normal !important;
                    font-size: 32px;
                }
                #content div.product div.summary h1.heading-title, .lafka-quick-view-lightbox h1.product_title {
                    font-size: 28px;
                }
                #lafka_page_title.title_has_image h1.heading-title {
                    font-size: 64px;
                }
            <?php endif; ?>

            <?php if (lafka_get_option('uppercase_page_titles')): ?>
            .lafka_title_holder .inner h1.heading-title {
                text-transform: uppercase;
            }
            <?php endif; ?>

            <?php if (lafka_get_option('categories_fancy')): ?>
                .widget > h3:first-of-type, h2.widgettitle {
                    font-family: "tiza" !important;
                    font-weight: normal !important;
                }
                div.product-category.product a h2.lafka-has-fancy {
                color: <?php echo esc_attr(lafka_get_option('fancy_category_title_color')) ?>;
                }
            <?php endif; ?>

            /* Top Menu Bar Visible on Mobile */
            <?php if (!lafka_get_option('header_top_mobile_visibility')): ?>
            <?php echo '@media only screen and (max-width: 1279px) {#header_top {background: none !Important} #header.lafka-has-header-top #logo a:before, #header.lafka-has-header-top #logo a:after {display:none} #header.lafka-has-header-top #logo { top: -16px;}}'; ?>
            <?php endif; ?>
            /* Header top bar background color */
            #header_top {
                background-color: <?php echo esc_attr(lafka_get_option('header_top_bar_color')) ?>;
                <?php if (lafka_get_option('header_top_bar_border_color')): ?>
                border-color: <?php echo esc_attr(lafka_get_option('header_top_bar_border_color')) ?> !Important;
                <?php endif; ?>
            }

            /* Main menu links color and typography */
            <?php
            $main_menu_typography = lafka_get_option('main_menu_typography');
            $main_menu_typography_style = json_decode($main_menu_typography['style'], true);
            $main_menu_typography_css_style = '';
            if ($main_menu_typography_style) {
                $main_menu_typography_css_style = 'font-weight:' . esc_attr($main_menu_typography_style['font-weight'] . ';font-style:' . $main_menu_typography_style['font-style'] . ';');
            }
            ?>

            #main-menu {
                background-color: <?php echo esc_attr(lafka_get_option('main_menu_background_color')) ?>;
            }

            .lafka-search-cart-holder a.sidebar-trigger:hover, .lafka-search-cart-holder .lafka-search-trigger > a:hover, .lafka-search-cart-holder #cart-module a.cart-contents:hover, .lafka-search-cart-holder .lafka-wishlist-counter a:hover, #lafka-account-holder > a:hover, #lafka-account-holder.active > a {
                background-color: <?php echo esc_attr(lafka_get_option('main_menu_background_color')) ?>
            }

            #main-menu ul.menu > li > a, #main-menu li div.lafka-mega-menu > ul.sub-menu > li > a, .lafka-wishlist-counter a, #header .lafka-search-cart-holder .video_controlls a, .lafka_mega_text_block .widget > h3:first-of-type {
                color: <?php echo esc_attr(lafka_get_option('main_menu_links_color')) ?>;
                font-size: <?php echo esc_attr($main_menu_typography['size']) ?>;
                <?php echo esc_attr($main_menu_typography_css_style) ?>
            }

            /* Main menu links hover color */
            ul#mobile-menu.menu li a {
                font-size: <?php echo esc_attr($main_menu_typography['size']) ?>;
            <?php echo esc_attr($main_menu_typography_css_style) ?>
            }

            /* Main menu links hover color */
            #main-menu ul.menu li:hover > a i, #main-menu ul.menu > li.current-menu-item > a i, #main-menu ul.menu > li:hover > a, #main-menu ul.menu > li.current-menu-item > a, #main-menu ul.menu > li.lafka-highlight-menu-item > a, body.lafka_transparent_header #header #main-menu ul.menu > li:hover > a, body.lafka_transparent_header #header #main-menu ul.menu > li.current-menu-item > a, #cart-module a.cart-contents, #main-menu li div.lafka-mega-menu > ul.sub-menu > li > a:hover {
                color: <?php echo esc_attr(lafka_get_option('main_menu_links_hover_color')) ?>;
            }

            /* Main menu background hover color */
            <?php if (lafka_get_option('main_menu_links_bckgr_hover_color')): ?>
            body:not(.lafka_transparent_header) #main-menu ul.menu > li:hover > a, body:not(.lafka_transparent_header) #main-menu ul.menu > li.current-menu-item > a, body:not(.lafka_transparent_header) #main-menu ul.menu > li:hover > a, #header2 #main-menu ul.menu > li.current-menu-item > a, #header2 #main-menu ul.menu > li:hover > a {
                background-color: <?php echo esc_attr(lafka_get_option('main_menu_links_bckgr_hover_color')) ?>;
            }

            #main-menu ul.menu > li.lafka-highlight-menu-item > a, #main-menu ul.menu > li.lafka-highlight-menu-item:after {
                background-color: <?php echo esc_attr(lafka_get_option('main_menu_links_bckgr_hover_color')) ?>;
            }

            #main-menu ul.menu > li.lafka-highlight-menu-item:after {
                border-color: <?php echo esc_attr(lafka_get_option('main_menu_links_bckgr_hover_color')) ?>;
            }

            <?php endif; ?>
            <?php if (!lafka_get_option('main_menu_links_bckgr_hover_color')): ?>
            #main-menu ul.menu > li.lafka-highlight-menu-item > a, #main-menu ul.menu > li.lafka-highlight-menu-item:after {
                background-color: <?php echo esc_attr(lafka_get_option('accent_color')) ?>;
            }

            #main-menu ul.menu > li.lafka-highlight-menu-item:after {
                border-color: <?php echo esc_attr(lafka_get_option('accent_color')) ?>;
            }

            <?php endif; ?>
            /* Top menu links color and typography */
            <?php
            $top_menu_typography = lafka_get_option('top_menu_typography');
            $top_menu_typography_style = json_decode($top_menu_typography['style'], true);
            $top_menu_typography_css_style = '';
            if ($top_menu_typography_style) {
                $top_menu_typography_css_style = 'font-weight:' . esc_attr($top_menu_typography_style['font-weight'] . ';font-style:' . $top_menu_typography_style['font-style'] . ';');
            }
            ?>
            <?php if (lafka_get_option('main_menu_transf_to_uppercase')): ?>
            #main-menu ul.menu > li > a, #lafka_footer_menu > li a, #header #logo .lafka-logo-title, ul.lafka-top-menu > li a {
                text-transform: uppercase;
            }
            <?php endif; ?>
            /* Main menu icons color */
            <?php if (lafka_get_option('main_menu_icons_color')): ?>
            #main-menu ul.menu li a i {
                color: <?php echo esc_attr(lafka_get_option('main_menu_icons_color')) ?>;
            }

            <?php endif; ?>

            #header .lafka-top-bar-message, #header .lafka-top-bar-message span, #header .lafka-top-bar-message span a, #header .lafka-top-bar-message span.lafka-top-bar-message-text {
                color: <?php echo esc_attr(lafka_get_option('top_bar_message_color')) ?>;
            }

            .lafka-search-cart-holder a.sidebar-trigger:before, .lafka-search-cart-holder .lafka-search-trigger > a, .lafka-search-cart-holder #cart-module a.cart-contents, .lafka-search-cart-holder #cart-module a.cart-contents::before, .lafka-search-cart-holder .lafka-wishlist-counter a, .lafka-search-cart-holder .lafka-wishlist-counter a i, #lafka-account-holder i {
                color: <?php echo esc_attr(lafka_get_option('header_services_color')) ?>;
            }

            /* Header top bar menu links color */
            ul.lafka-top-menu > li a {
                color: <?php echo esc_attr(lafka_get_option('top_bar_menu_links_color')) ?>;
                font-size: <?php echo esc_attr($top_menu_typography['size']) ?>;
                <?php echo esc_attr($top_menu_typography_css_style) ?>
            }

            /* Header top bar menu links hover color */
            ul.lafka-top-menu li a:hover, body.lafka_transparent_header ul.lafka-top-menu > li > a:hover, ul.lafka-top-menu > li.current-menu-item > a {
                color: <?php echo esc_attr(lafka_get_option('top_bar_menu_links_hover_color')) ?> !important;
            }

            ul.lafka-top-menu ul.sub-menu li a:hover, ul.lafka-top-menu li:hover ul.sub-menu a:hover {
                background-color: <?php echo esc_attr(lafka_get_option('header_top_bar_color')) ?>;
            }

            /* Collapsible Pre-Header background color */
            #pre_header, #pre_header:before {
                background-color: <?php echo esc_attr(lafka_get_option('collapsible_bckgr_color')) ?>;
            }

            /* Collapsible Pre-Header titles color */
            #pre_header .widget > h3:first-child {
                color: <?php echo esc_attr(lafka_get_option('collapsible_titles_color')) ?>;
            }

            /* Collapsible Pre-Header titles border color */
            #pre_header .widget > h3:first-child, #pre_header > .inner ul.product_list_widget li, #pre_header > .inner div.widget_nav_menu ul li a, #pre_header > .inner ul.products-list li {
                border-color: <?php echo esc_attr(lafka_get_option('collapsible_titles_border_color')) ?>;
            }

            #pre_header > .inner div.widget_categories ul li, #pre_header > .inner div.widget_archive ul li, #pre_header > .inner div.widget_recent_comments ul li, #pre_header > .inner div.widget_pages ul li,
            #pre_header > .inner div.widget_links ul li, #pre_header > .inner div.widget_recent_entries ul li, #pre_header > .inner div.widget_meta ul li, #pre_header > .inner div.widget_display_forums ul li,
            #pre_header > .inner .widget_display_replies ul li, #pre_header > .inner .widget_display_views ul li {
                border-color: <?php echo esc_attr(lafka_get_option('collapsible_titles_border_color')) ?>;
            }

            /* Collapsible Pre-Header links color */
            #pre_header a {
                color: <?php echo esc_attr(lafka_get_option('collapsible_links_color')) ?>;
            }

            /* Page Title background */
            <?php $title_backgr = lafka_get_option('page_title_default_bckgr_image'); ?>
           <?php if ($title_backgr): ?>
               #lafka_page_title:not(.title_has_image) {
                   background: url("<?php echo esc_url(wp_get_attachment_image_url($title_backgr, 'full')) ?>");
               }
               .lafka_title_holder .inner h1.heading-title {
                    font-size: 64px;
                }
           <?php endif; ?>

            /* Header background */
            <?php $header_backgr = lafka_get_option('header_background'); ?>
            <?php if ($header_backgr['image']): ?>
            #header {
                background: url("<?php echo esc_url(wp_get_attachment_image_url($header_backgr['image'], 'full')) ?>")<?php echo esc_attr($header_backgr['position']) ?> <?php echo esc_attr($header_backgr['repeat']) ?> <?php echo esc_attr($header_backgr['attachment']) ?>;
            }
            body:not(.lafka_transparent_header) #header.lafka-sticksy:before {
                opacity: 0;
            }
            <?php endif; ?>

            #header, #header.lafka-sticksy:before, .lafka-top-bar-message, .lafka-search-cart-holder {
                background-color: <?php echo esc_attr($header_backgr['color']) ?>;
            }

            /* footer_background */
            <?php $footer_backgr = lafka_get_option('footer_background'); ?>
            <?php if ($footer_backgr['image']): ?>
            #footer {
                background: url("<?php echo esc_url(wp_get_attachment_image_url($footer_backgr['image'], 'full')) ?>")<?php echo esc_attr($footer_backgr['position']) ?> <?php echo esc_attr($footer_backgr['repeat']) ?> <?php echo esc_attr($footer_backgr['attachment']) ?>;
            }

            <?php if ($footer_backgr['repeat'] === 'no-repeat' ): ?>
            #footer {
                background-size: cover;
            }
            #footer > .inner:nth-of-type(2) {
            padding-bottom: 50px;
            }
            <?php endif; ?>
            <?php endif; ?>
            #footer {
                background-color: <?php echo esc_attr($footer_backgr['color']) ?>;
            }

            /* footer_titles_color + footer_title_border_color */
            #footer .widget > h3:first-child {
                color: <?php echo esc_attr(lafka_get_option('footer_titles_color')) ?>;
                border-color: <?php echo esc_attr(lafka_get_option('footer_title_border_color')) ?>;
            }

            #footer > .inner ul.product_list_widget li, #footer > .inner div.widget_nav_menu ul li a, #footer > .inner ul.products-list li, #lafka_footer_menu > li {
                border-color: <?php echo esc_attr(lafka_get_option('footer_title_border_color')) ?>;
            }

           #powered .lafka-social ul li a {
                color: <?php echo esc_attr(lafka_get_option('footer_copyright_bar_text_color')) ?>;
            }

             /* footer_menu_links_color */
             #footer > .inner #lafka_footer_menu > li a {
                color: <?php echo esc_attr(lafka_get_option('footer_menu_links_color')) ?>;
            }

            <?php if (lafka_get_option('footer_copyright_bar_text_color') === "#ffffff"): ?>
            #powered .author_credits a {color: #ffffff;}
            <?php endif; ?>

            /* footer_links_color */
            #footer > .inner a {
                color: <?php echo esc_attr(lafka_get_option('footer_links_color')) ?>;
            }

            /* footer_text_color */
            #footer {
                color: <?php echo esc_attr(lafka_get_option('footer_text_color')) ?>;
            }

            #footer > .inner div.widget_categories ul li, #footer > .inner div.widget_archive ul li, #footer > .inner div.widget_recent_comments ul li, #footer > .inner div.widget_pages ul li,
            #footer > .inner div.widget_links ul li, #footer > .inner div.widget_recent_entries ul li, #footer > .inner div.widget_meta ul li, #footer > .inner div.widget_display_forums ul li,
            #footer > .inner .widget_display_replies ul li, #footer > .inner .widget_display_views ul li, #footer > .inner div.widget_nav_menu ul li {
                border-color: <?php echo esc_attr(lafka_get_option('footer_title_border_color')) ?>;
            }

            /* footer_copyright_bar_bckgr_color */
            #powered {
                <?php if (lafka_get_option('footer_copyright_bar_bckgr_color')): ?>
                background-color: <?php echo esc_attr(lafka_get_option('footer_copyright_bar_bckgr_color')) ?>;
                <?php endif; ?>
                color: <?php echo esc_attr(lafka_get_option('footer_copyright_bar_text_color')) ?>;
            }

            /* Body font */
            <?php $body_font = lafka_get_option('body_font'); ?>
            body, #bbpress-forums .bbp-body div.bbp-reply-content {
                <?php if(!empty($body_font['face'])): ?>
                    font-family: "<?php echo esc_attr($body_font['face']) ?>";
                <?php endif; ?>
                font-size: <?php echo esc_attr($body_font['size']) ?>;
                color: <?php echo esc_attr($body_font['color']) ?>;
                font-display:fallback;
            }

            #header #logo .lafka-logo-subtitle, #header2 #logo .lafka-logo-subtitle {
                color: <?php echo esc_attr($body_font['color']) ?>;
            }

            /* Text logo color and typography */
            <?php
            $text_logo_typography = lafka_get_option('text_logo_typography');
            $text_logo_typography_style = json_decode($text_logo_typography['style'], true);
            $text_logo_typography_color = $text_logo_typography['color'];
            $text_logo_typography_css_style = '';
            if ($text_logo_typography_style) {
                $text_logo_typography_css_style = 'font-weight:' . esc_attr($text_logo_typography_style['font-weight'] . ';font-style:' . $text_logo_typography_style['font-style'] . ';');
            }
            ?>
            #header #logo .lafka-logo-title, #header2 #logo .lafka-logo-title {
                color: <?php echo esc_attr($text_logo_typography_color) ?>;
                font-size: <?php echo esc_attr($text_logo_typography['size']) ?>;
            <?php echo esc_attr($text_logo_typography_css_style) ?>
            }

            <?php if (lafka_get_option('disable_logo_point_down')): ?>
            #header #logo:before, #header #logo:after, #header #logo a:before, #header #logo a:after {
                display: none !important;
            }
            #header #logo {
                padding: 20px 15px;
                top: auto !important;
                border-radius: 0 0 4px 4px;
                -webkit-box-shadow: 0 0px 30px 0 rgba(0,0,0,.15);
                box-shadow: 0 0px 30px 0 rgba(0,0,0,.15);
                margin-bottom: 15px;
            }
            #header.lafka-has-header-top #logo {
                top: -15px !important;
            }
            <?php if (lafka_get_option('logo_background_color') === $header_backgr['color']): ?>
            #header #logo { box-shadow: none !important; }
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!lafka_get_option('logo_background_color')): ?>
            #header #logo { box-shadow: none !important; }
            #header #logo:before, #header #logo:after, #header #logo a:before, #header #logo a:after {
                display: none !important;
            }
            <?php endif; ?>

            <?php if (!lafka_get_option('show_searchform') && !lafka_get_option('show_shopping_cart') && !lafka_get_option('show_my_account') && !lafka_get_option('show_wish_in_header')): ?>
            #header.lafka-has-header-top.lafka-sticksy {
                min-height: 130px;
            }
            .lafka-sticksy .main_menu_holder {
                padding-top: 0px;
            }

            <?php endif; ?>

            <?php if (!lafka_get_option('use_quickview')): ?>
            div.prod_hold .links a.button.add_to_cart_button {
                display: inline-block;
                width: auto !important;
                text-indent: 0 !important;
                color: #333 !important;
                font-size: 12px !important;
                font-weight: 500;
            }
            div.prod_hold .links a.button.add_to_cart_button::before, div.prod_hold .links a.button.product_type_grouped::before, div.prod_hold .links a.button.product_type_external::before {
                position: relative;
                top: auto;
                left: auto;
                display: inline-block;
                vertical-align: top;
            }
            div.prod_hold .woocommerce-product-details__short-description, div.prod_hold .woocommerce-product-details__short-description p { 
                margin-bottom: 0;
            }
            <?php endif; ?>

            /* Heading fonts */
            <?php $headings_font = lafka_get_option('headings_font'); ?>
            <?php if(!empty($headings_font['face'])): ?>
                h1, h2, h3, h4, h5, h6, .foodmenu_top .project-data .project-details .lafka-foodmenu-main-price, p.wp-block-cover-text, .lafka-product-summary-wrapper div.lafka-share-links span, #comments .nav-next a, #comments .nav-previous a, #tab-reviews #reply-title, .woocommerce-form-coupon-toggle .woocommerce-info, .woocommerce-form-login-toggle .woocommerce-info, .r_more_blog, p.woocommerce-thankyou-order-received, nav.woocommerce-MyAccount-navigation ul li a, #lafka-account-holder.lafka-user-is-logged .lafka-header-account-link-holder > ul li a, .lafka-header-user-data small, a.lafka-post-nav .entry-info span.entry-title, .wp-block-cover-image .wp-block-cover-image-text, .wp-block-cover-image h2, .lafka-product-popup-link > a, .vendor_description .vendor_img_add .vendor_address p.wcmp_vendor_name, .tribe-events-event-cost, .tribe-events-schedule .tribe-events-cost, .lafka-page-load-status, .widget_layered_nav_filters li a, section.woocommerce-order-details, ul.woocommerce-error, table.woocommerce-checkout-review-order-table, body.woocommerce-cart .cart-collaterals, .cart-info table.shop_table.cart, ul.woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li, .countdown_time_tiny, blockquote, q, #lafka_footer_menu > li a, .lafka-pagination-numbers .owl-dot:before, .lafka-wcs-swatches .swatch.swatch-label, .foodmenu-unit-info small, .widget .post-date, div.widget_nav_menu ul li a, .comment-body span, .comment-reply-link, span.edit-link a, #reviews .commentlist li .meta, div.widget_categories ul li a, div.widget_archive ul li a, div.widget_recent_entries ul li a, div.widget_recent_comments ul li a, .woocommerce p.cart-empty, div.woocommerce-MyAccount-content .myaccount_user, label, .lafka-pricing-table-content, p.product.woocommerce.add_to_cart_inline, .product-filter .limit b, .product-filter .sort b, .product-filter .price_label, .contact-form .content span, .tribe-countdown-text, .lafka-event-countdown .is-countdown, .lafka-foodmenu-categories ul li a, div.prod_hold .name, #header #logo .lafka-logo-title, #header2 #logo .lafka-logo-title, .lafka-counter-h1, .lafka-typed-h1, .lafka-typed-h2, .lafka-typed-h3, .lafka-typed-h4, .lafka-typed-h5, .lafka-typed-h6, .lafka-counter-h2, body.woocommerce-account #customer_login.col2-set .owl-nav button, .woocommerce #customer_login.u-columns.col2-set .owl-nav button, .lafka-counter-h3, .error404 div.blog-post-excerpt:before, #yith-wcwl-popup-message #yith-wcwl-message, div.added-product-text strong, .vc_pie_chart .vc_pie_chart_value, .countdown-amount, .lafka-product-slide-price, .lafka-counter-h4, .lafka-counter-h5, .lafka-search-cart-holder #search input[type="text"], .lafka-counter-h6, .vc_tta-tabs:not(.vc_tta-style-modern) .vc_tta-tab, div.product .price span, a.bbp-forum-title, p.logged-in-as, .lafka-pricing-table-price, li.bbp-forum-info, li.bbp-topic-title .bbp-topic-permalink, .breadcrumb, .offer_title, ul.tabs a, .wpb_tabs .wpb_tabs_nav li a, .wpb_tour .wpb_tabs_nav a, .wpb_accordion .wpb_accordion_wrapper .wpb_accordion_header a, .post-date .num, .lafka-products-list-view div.prod_hold .name, .lafka_shortcode_count_holder .countdown-amount, .blog-post-meta a, .widget_shopping_cart_content p.total, .foodmenu_top .project-data .project-details .simple-list-underlined li, .foodmenu_top .project-data .main-features .checklist li, .summary.entry-summary .yith-wcwl-add-to-wishlist a {
                    font-family: "<?php echo esc_attr($headings_font['face']) ?>";
                    font-display:fallback;
                }

                .u-column1 h2, .u-column2 h3, .lafka_title_holder h1.heading-title {
                    font-family: "<?php echo esc_attr($headings_font['face']) ?>" !important;
                    font-display:fallback;
                }

                <?php $use_google_face_for = lafka_get_option('use_google_face_for'); ?>

                <?php if ($use_google_face_for['main_menu']): ?>
                #main-menu ul.menu li a, ul#mobile-menu.menu li a, #main-menu li div.lafka-mega-menu > ul.sub-menu > li.lafka_colum_title > a, ul.lafka-top-menu > li a {
                    font-family: "<?php echo esc_attr($headings_font['face']) ?>";
                    font-display:fallback;
                }

                <?php endif; ?>

                <?php if ($use_google_face_for['buttons']): ?>
                a.button, input.button, .lafka-filter-widgets-triger, .lafka-reset-filters, .wcv-navigation ul.menu.horizontal li a, .wcv-pro-dashboard input[type="submit"], button.button, input[type="submit"], a.button-inline, .lafka_banner_buton, #submit_btn, #submit, .wpcf7-submit, .col2-set.addresses header a.edit, div.product input.qty, .lafka-pricing-table-button a, .vc_btn3 {
                    font-family: "<?php echo esc_attr($headings_font['face']) ?>";
                    font-display:fallback;
                }

                <?php endif; ?>
            <?php endif; ?>
            /* H1 */
            <?php
            $h1_font = lafka_get_option('h1_font');
            $h1_style = json_decode($h1_font['style'], true);
            $h1_css_style = '';
            if ($h1_style) {
                $h1_css_style = 'font-weight:' . esc_attr($h1_style['font-weight'] . ';font-style:' . $h1_style['font-style'] . ';');
            }
            ?>
            h1, .lafka-counter-h1, .lafka-typed-h1, .lafka-dropcap p:first-letter, .lafka-dropcap h1:first-letter, .lafka-dropcap h2:first-letter, .lafka-dropcap h3:first-letter, .lafka-dropcap h4:first-letter, .lafka-dropcap h5:first-letter, .lafka-dropcap h6:first-letter {
                color: <?php echo esc_attr($h1_font['color']) ?>;
                font-size: <?php echo esc_attr($h1_font['size']) ?>;
            <?php echo esc_attr($h1_css_style) ?>
            }

            /* H2 */
            <?php
            $h2_font = lafka_get_option('h2_font');
            $h2_style = json_decode($h2_font['style'], true);
            $h2_css_style = '';
            if ($h2_style) {
                $h2_css_style = 'font-weight:' . esc_attr($h2_style['font-weight'] . ';font-style:' . $h2_style['font-style'] . ';');
            }
            ?>
            h2, .lafka-counter-h2, p.wp-block-cover-text, .lafka-typed-h2, .wp-block-cover-image .wp-block-cover-image-text, .wp-block-cover-image h2, .icon_teaser h3:first-child, body.woocommerce-account #customer_login.col2-set .owl-nav button, .woocommerce #customer_login.u-columns.col2-set .owl-nav button, .related.products h2, .similar_projects > h4 a, .related.products h2 a, .upsells.products h2, .similar_projects > h4, .lafka-related-blog-posts > h4, .tribe-events-related-events-title {
                color: <?php echo esc_attr($h2_font['color']) ?>;
                font-size: <?php echo esc_attr($h2_font['size']) ?>;
            <?php echo esc_attr($h2_css_style) ?>
            }
            .lafka-foodmenu-categories ul li a {
                color: <?php echo esc_attr($h2_font['color']) ?>; 
            }

            /* H3 */
            <?php
            $h3_font = lafka_get_option('h3_font');
            $h3_style = json_decode($h3_font['style'], true);
            $h3_css_style = '';
            if ($h3_style) {
                $h3_css_style = 'font-weight:' . esc_attr($h3_style['font-weight'] . ';font-style:' . $h3_style['font-style'] . ';');
            }
            ?>
            h3, .lafka-counter-h3, .lafka-typed-h3, .woocommerce p.cart-empty, #tab-reviews #reply-title {
                color: <?php echo esc_attr($h3_font['color']) ?>;
                font-size: <?php echo esc_attr($h3_font['size']) ?>;
            <?php echo esc_attr($h3_css_style) ?>
            }

            /* H4 */
            <?php
            $h4_font = lafka_get_option('h4_font');
            $h4_style = json_decode($h4_font['style'], true);
            $h4_css_style = '';
            if ($h4_style) {
                $h4_css_style = 'font-weight:' . esc_attr($h4_style['font-weight'] . ';font-style:' . $h4_style['font-style'] . ';');
            }
            ?>
            h4, .lafka-counter-h4, .lafka-typed-h4 {
                color: <?php echo esc_attr($h4_font['color']) ?>;
                font-size: <?php echo esc_attr($h4_font['size']) ?>;
            <?php echo esc_attr($h4_css_style) ?>
            }

            /* H5 */
            <?php
            $h5_font = lafka_get_option('h5_font');
            $h5_style = json_decode($h5_font['style'], true);
            $h5_css_style = '';
            if ($h5_style) {
                $h5_css_style = 'font-weight:' . esc_attr($h5_style['font-weight'] . ';font-style:' . $h5_style['font-style'] . ';');
            }
            ?>
            h5, .lafka-counter-h5, .lafka-typed-h5 {
                color: <?php echo esc_attr($h5_font['color']) ?>;
                font-size: <?php echo esc_attr($h5_font['size']) ?>;
            <?php echo esc_attr($h5_css_style) ?>
            }

            /* H6 */
            <?php
            $h6_font = lafka_get_option('h6_font');
            $h6_style = json_decode($h6_font['style'], true);
            $h6_css_style = '';
            if ($h6_style) {
                $h6_css_style = 'font-weight:' . esc_attr($h6_style['font-weight'] . ';font-style:' . $h6_style['font-style'] . ';');
            }
            ?>
            h6, .lafka-counter-h6, .lafka-typed-h6 {
                color: <?php echo esc_attr($h6_font['color']) ?>;
                font-size: <?php echo esc_attr($h6_font['size']) ?>;
            <?php echo esc_attr($h6_css_style) ?>
            }

            <?php if (lafka_get_option('mobile_theme_logo')): ?>
            @media only screen and (max-width: 1279px) {

                #header #logo img {
                    display: none !important;
                }

                #header #logo img.lafka_mobile_logo {
                    display: table-cell !important;
                    width: auto !important;
                    opacity: 1;
                }
            }

            <?php endif; ?>
            <?php if (lafka_get_option('show_quantity_on_listing')): ?>
                @media only screen and (min-width: 380px) {
                .lafka-products-list-view div.prod_hold:not(.lafka-variations-list-in-catalog) .links {
                    width: 30px;
                    height: auto;
                    top: 0px;
                }
                .lafka-products-list-view div.prod_hold.product-type-combo:not(.lafka-variations-list-in-catalog) .links {
                    top: 30px;
                }
                .lafka-products-list-view div.prod_hold div.quantity {
                    width: 30px;
                    height: 81px;
                    padding: 0px;
                    margin-bottom: 3px;
                }
                .lafka-products-list-view div.quantity input.lafka-qty-plus, .lafka-products-list-view div.quantity input.lafka-qty-minus, .lafka-products-list-view div.prod_hold.product input.qty {
                    display: inline-block;
                    float: none;
                    margin-top: 0px;
                }
                .lafka-products-list-view div.quantity input.lafka-qty-plus {
                    top: 1px;
                    left: 0px;
                }
                .lafka-products-list-view div.quantity input.lafka-qty-minus {
                    top:auto;
                    bottom: 1px;
                    left: 0px;
                }
                .lafka-products-list-view div.prod_hold.product input.qty {
                    padding: 0px;
                    width: 30px !important;
                    top: 26px;
                    position: relative;
                    height: 30px;
                    font-size: 14px;
                }
            }
            @media only screen and (min-width: 300px) and (max-width: 379px) {
            .lafka-products-list-view div.prod_hold:not(.lafka-variations-list-in-catalog) .links {
                    width: 100%;
                }
                .lafka-products-list-view div.prod_hold div.quantity {
                    display: inline-block;
                    height: 28px;
                }
                .lafka-products-list-view div.quantity input.lafka-qty-plus, .lafka-products-list-view div.quantity input.lafka-qty-minus {
                    height: 28px;
                    width: 28px;
                }
                .lafka-products-list-view div.prod_hold.product input.qty {
                    padding: 0px;
                    width: 30px !important;
                    height: 28px;
                }
            }
            <?php endif; ?>

            <?php if (lafka_get_option('product_columns_mobile') === '2'): ?>
            @media only screen and (min-width: 320px) and (max-width: 767px) {
                body div.prod_hold, body li.product-category, body div.product-category {
                    width: 49.5% !important;
                }
                body div.prod_hold {
                    padding: 7px;
                }
                div.prod_hold .name  {
                    font-size:12px;
                    text-transform: none;
                }
                .prod_hold .price_hold {
                    font-size: 13px;
                    font-weight: 500;
                }
                .prod_hold .lafka-list-prod-summary, div.prod_hold .links {
                    padding: 10px 0px;
                    left: 0px;
                    right: 0px;
                }
                div.prod_hold .links {
                    padding: 0px 0px 15px 0px;
                    top: auto !important;
                    position: relative;
                    opacity: 1;
                }
                div.prod_hold a.button, .links a.button.add_to_cart_button, .links a.button.add_to_cart_button.ajax_add_to_cart, .links .yith-wcwl-add-to-wishlist, .links a.lafka-quick-view-link {
                    margin-right: 0 !important;
                }
                div.prod_hold .sale {
                    display: none;
                }
            }

            <?php endif; ?>

            /* Add to Cart Color */
            button.single_add_to_cart_button, .foodmenu_top .project-data .project-details a.button {
                background-color: <?php echo esc_attr(lafka_get_option('add_to_cart_color')); ?> !important;
            }
            div.prod_hold .links a.lafka-quick-view-link:hover, .lafka-product-slide-cart .button.add_to_cart_button:hover {
                color: <?php echo esc_attr(lafka_get_option('add_to_cart_color')); ?>;
            }
            .prod_hold .price_hold {
            color: <?php echo esc_attr(lafka_get_option('price_color_in_listings')); ?>;
            background-color: <?php echo esc_attr(lafka_get_option('price_background_color_in_listings')); ?>;
            }

            table.compare-list .add-to-cart td a.lafka-quick-view-link, table.compare-list .add-to-cart td a.compare.button {
                display: none !important;
            }</style>
        <?php
        $custom_css = ob_get_clean();
        $custom_css = trim(preg_replace('#<style[^>]*>(.*)</style>#is', '$1', $custom_css));

        wp_add_inline_style('lafka-style', $custom_css); // All dynamic data escaped
    }

}