(function($) {
    "use strict";
    var lafka_ajaxXHR = null;
    var is_mailto_or_tel_link = false;
    var is_rtl = false;
    if (lafka_main_js_params.is_rtl === 'true') {
        is_rtl = true;
    }

    /* If preloader is enabled */
    if (lafka_main_js_params.show_preloader) {
        $(window).on("load", function() {
            $("#loader").delay(100).fadeOut();
            $(".mask").delay(300).fadeOut();
        });

    }
    $(window).on("load", function() {
        checkRevealFooter();
        checkProductGalleryCarousel();
        defineMegaMenuSizing();
    });

    $(document).ready(function() {

        //
        // -------------------------------------------------------------------------------------------------------
        // Dropdown Menu
        // -------------------------------------------------------------------------------------------------------

        $('.box-sort-filter .woocommerce-ordering .limit select, .box-sort-filter .woocommerce-ordering .sort select, .widget_archive select, .widget_categories select').niceSelect();

        /*
         * Special Characters
         */

        // Removed: heading &nbsp; replacement — unnecessary DOM manipulation on every page load

        if (lafka_main_js_params.categories_fancy === 'yes') {
            $("div.product-category.product h2").html(function() {
                $(this).addClass('lafka-has-fancy');
            });
        }

        if (lafka_main_js_params.order_hours_cart_update === 'yes') {
            $(document.body).trigger('updated_wc_div');
        }

        $(document.body).on('added_to_cart updated_checkout', function() {
            lafkaInitSmallCountdowns($(this).find('div.lafka-closed-store-message'));
        });

        // Order hours counter to the next opening
        lafkaInitSmallCountdowns($(document.body).find('div.lafka-closed-store-message'));

        $('.woocommerce-review-link').on('click', function(event) {
            $('#tab-reviews').trigger('click');
            $('html, body').animate({
                scrollTop: $(".woocommerce-tabs").offset().top - 105
            }, 1200, 'swing');
        });

        $('div.content_holder.lafka_blog_masonry div.box.box-common').has('.pagination').parent().addClass('lafka-blog-has-pagination');

        // Keep srcset for responsive images; CloudZoom works with src directly
        $("div.summary.entry-summary table.variations td").has('div.lafka-wcs-swatches').addClass("lafka-has-swatches-option");
        $("ul#topnav li, ul#topnav2 li, ul.menu li").has('ul').addClass("dropdown");
        $("ul.menu li").has('div').addClass("has-mega");
        $('#main-menu li ul.sub-menu li').has('.lafka-custom-menu-label').addClass('has-menu-label');

        $("div.vc_row").has('.lafka-fullheight-content-slider').addClass("lafka-row-has-full-slider");


        /*
         * Manipulate the cart
         */

        $('#header #cart-module div.widget.woocommerce.widget_shopping_cart').prependTo('body');
        $('body > div.widget.woocommerce.widget_shopping_cart').prepend('<span class="close-cart-button"></span>');
        $('body > #search').prepend('<span class="close-search-button"></span>');

        /* REMOVE PARENTHESIS ON WOO CATEGORIES */

        $('.count').text(function(_, text) {
            return text.replace(/\(|\)/g, '');
        });

        /**
         * Sticky header (if on)
         */
        if ((lafka_main_js_params.sticky_header) && ($('#container').has('#header').length)) {
            lafkaStickyHeaderInit();
        }

        $("#header #lafka-account-holder > a").on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $("#lafka-account-holder, .lafka-header-account-link-holder").toggleClass("active");
        });

        if ($("#header .lafka-header-account-link-holder .woocommerce").has('ul.woocommerce-error').length) {
            $("#header .lafka-header-account-link-holder").addClass("active");
        }

        checkSummaryHeight();
        checkSidebarHeight();
        checkCommentsHeight();
        checkFoodmenuHeight();
        defineCartIconClickBehaviour();

        var customTitleHeight = $('body.lafka_transparent_header #header').height();
        $('body.lafka_transparent_header .lafka_title_holder .inner').css({ "padding-top": customTitleHeight + 160, "padding-bottom": customTitleHeight - 60 });

        $('#header .lafka-search-trigger a, .close-search-button').on('click', function(event) {
            event.stopPropagation();
            $("body > #search").toggleClass("active");
            $("body > #search #s").focus();
        });

        $('#main-menu .lafka-mega-menu').css("display", "");

        $('p.demo_store').prependTo('#header');

        var $accountMenuSliderElement = $('body.woocommerce-account .content_holder #customer_login.col2-set, .content_holder .woocommerce #customer_login.u-columns.col2-set, .lafka-header-account-link-holder .woocommerce #customer_login.u-columns.col2-set, #lafka_mobile_account_tab .woocommerce #customer_login.u-columns.col2-set');
        if ($accountMenuSliderElement.length) {
            $accountMenuSliderElement.addClass('owl-carousel');
            $accountMenuSliderElement.owlCarousel({
                rtl: is_rtl,
                items: 1,
                dots: false,
                mouseDrag: false,
                nav: true,
                navText: [
                    lafka_main_js_params.login_label,
                    lafka_main_js_params.register_label
                ]
            });
        }

        //
        // -------------------------------------------------------------------------------------------------------
        // Mobile Menu
        // -------------------------------------------------------------------------------------------------------
        $(".mob-menu-toggle, .mob-close-toggle, ul#mobile-menu.menu li:not(.menu-item-has-children) a").on('click', function(event) {
            event.stopPropagation();
            $("#menu_mobile").toggleClass("active");
        });
        $("ul#mobile-menu.menu .menu-item a").each(function() {
            if ($(this).html() == "–") {
                $(this).remove();
            }
        });

        $("ul#mobile-menu.menu > li.menu-item-has-children:not(.current-menu-item) > a").prepend('<span class="drop-mob">+</span>');
        $("ul#mobile-menu.menu > li.menu-item-has-children.current-menu-item > a").prepend('<span class="drop-mob">-</span>');
        $("ul#mobile-menu.menu > li.menu-item-has-children > a .drop-mob").on('click', function(event) {
            event.preventDefault();
            $(this).closest('li').find('ul.sub-menu').toggleClass("active");

            var $activeSubmenus = $(this).closest('li').find('ul.sub-menu.active');

            if ($activeSubmenus.length) {
                $(this).html("-");
            } else if (!$(this).closest('li').hasClass('current-menu-item')) {
                $(this).html("+");
            }
        });
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.widget_shopping_cart').hasClass('active_cart')) {
                $("body > div.widget.woocommerce.widget_shopping_cart").removeClass("active_cart");
            }
            if (!$(e.target).closest('#menu_mobile').hasClass('active')) {
                $("#menu_mobile").removeClass("active");
            }
            if (!$(e.target).closest('#search').hasClass('active')) {
                $("#search.active").removeClass("active");
            }
            if (!$(e.target).closest('.off-canvas-sidebar').hasClass('active_sidebar')) {
                $(".sidebar.off-canvas-sidebar").removeClass("active_sidebar");
            }
            if (!$(e.target).closest('.lafka-header-account-link-holder').hasClass('active')) {
                $("body, .lafka-header-account-link-holder").removeClass("active");
            }
        });

        $(".video_controlls a#video-volume").on('click', function() {
            $(".video_controlls a#video-volume").toggleClass("disabled");
        });

        $(document.body).find('a[href="#"], a.cloud-zoom').on('click', function(event) {
            event.preventDefault();
        });

        $('a[href$=".mov"] , a[href$=".swf"], a[href$=".mp4"], a[href*="vimeo.com/"], a[href*="youtube.com/watch"]').magnificPopup({
            disableOn: 700,
            type: 'iframe',
            mainClass: 'mfp-fade is-lafka-video',
            removalDelay: 160,
            preloader: false,
            fixedContentPos: false
        });

        $(".prod_hold a.add_to_wishlist").prop("title", function() {
            return $(this).data("title");
        });

        // -------------------------------------------------------------------------------------------------------
        // SLIDING ELEMENTS
        // -------------------------------------------------------------------------------------------------------

        $(document).find("a#toggle_switch").on("click", function() {
            var $togglerone = $(this).siblings("#togglerone");
            if ($(this).hasClass("swap")) {
                $(this).removeClass("swap")
                $togglerone.slideToggle("slow");
            } else {
                $(this).addClass("swap");
                $togglerone.slideToggle("slow");
            }
        });

        if (!document.getElementById("lafka_page_title")) {
            $(document.body).addClass('page-no-title');
        } else {
            $(document.body).addClass('page-has-title');
        }

        $('.sidebar-trigger').prependTo('#header .lafka-search-cart-holder');
        if ($('div#lafka_page_title .inner').has('div.breadcrumb').length) {
            $('.video_controlls').appendTo('div.breadcrumb');
        } else {
            $('.video_controlls').prependTo('#header .lafka-search-cart-holder');
        }


        $('.sidebar-trigger, .close-off-canvas').on('click', function(event) {
            event.stopPropagation();
            $(".off-canvas-sidebar").toggleClass("active_sidebar");
        });

        $('a.lafka-filter-widgets-triger').on("click", function() {
            $('#lafka-filter-widgets').slideToggle("slow");

            return false;
        }, function() {
            $('#lafka-filter-widgets').slideToggle("slow");
            return false;
        });

        $('html.no-touch .lafka-from-bottom').each(function() {
            $(this).appear(function() {
                $(this).delay(300).animate({ opacity: 1, bottom: "0px" }, 500);
            });
        });

        $('html.no-touch .lafka-from-left').each(function() {
            $(this).appear(function() {
                $(this).delay(300).animate({ opacity: 1, left: "0px" }, 500);
            });
        });

        $('html.no-touch .lafka-from-right').each(function() {
            $(this).appear(function() {
                $(this).delay(300).animate({ opacity: 1, right: "0px" }, 500);
            });
        });

        $('html.no-touch .lafka-fade').each(function() {
            $(this).appear(function() {
                $(this).delay(300).animate({ opacity: 1 }, 700);
            });
        });

        $('html.no-touch div.prod_hold, html.no-touch .wpb_lafka_banner:not(.lafka-from-bottom), html.no-touch .wpb_lafka_banner:not(.lafka-from-left), html.no-touch .wpb_lafka_banner:not(.lafka-from-right), html.no-touch .wpb_lafka_banner:not(.lafka-fade)').each(function() {
            $(this).appear(function() {
                $(this).addClass('prod_visible').delay(2000);
            });
        });

        $('.lafka-counter:not(.already_seen)').each(function() {
            $(this).appear(function() {

                $(this).prop('Counter', 0).animate({
                    Counter: $(this).text()
                }, {
                    duration: 3000,
                    decimals: 2,
                    easing: 'swing',
                    step: function(now) {
                        $(this).text(Math.ceil(now).toLocaleString('en'));
                    }
                });
                $(this).addClass('already_seen');

            });
        });

        // -------------------------------------------------------------------------------------------------------
        // FADING ELEMENTS
        // -------------------------------------------------------------------------------------------------------

        $.lafka_widget_columns();

        // Number of products to show in category
        // per_page and auto load
        $('select.per_page').on('change', function() {
            $('.woocommerce-ordering').trigger("submit");
        });

        function addQty() {
            const $input = $(this).parent().find('input[type=number]');
            let quantity = parseInt($input.val());

            if (isNaN(quantity)) {
                quantity = 0;
            }
            $input.val(quantity + 1);
            $input.trigger('change');
        }

        function subtractQty() {
            const $input = $(this).parent().find('input[type=number]');
            let quantity = parseInt($input.val());

            if (isNaN(quantity)) {
                $input.val(1);
                $input.trigger('change');
            } else if ($input.val() > 1) {
                $input.val(quantity - 1);
                $input.trigger('change');
            }
        }

        function lafka_handle_quantity_on_listing() {
            var $add_to_cart_button = $(this).closest(".links").find(".add_to_cart_button");

            // For AJAX add-to-cart actions
            $add_to_cart_button.attr("data-quantity", jQuery(this).val());
            // For non-AJAX add-to-cart actions
            $add_to_cart_button.attr("href", "?add-to-cart=" + $add_to_cart_button.attr("data-product_id") + "&quantity=" + $(this).val());
        }

        $(document.body).on('click', '.lafka-qty-plus', addQty);
        $(document.body).on('click', '.lafka-qty-minus', subtractQty);
        $(document.body).on('change input', '.quantity .qty', lafka_handle_quantity_on_listing);

        if ($('#cart-module').length !== 0) {
            track_ajax_add_to_cart();
            $(document.body).on('added_to_cart', update_cart_dropdown);
        }

        $(".lafka-latest-grid.lafka-latest-blog-col-3 div.post:nth-child(3n)").after("<div class='clear'></div>");
        $(".lafka-latest-grid.lafka-latest-blog-col-2 div.post:nth-child(2n)").after("<div class='clear'></div>");
        $(".lafka-latest-grid.lafka-latest-blog-col-4 div.post:nth-child(4n)").after("<div class='clear'></div>");
        $(".lafka-latest-grid.lafka-latest-blog-col-5 div.post:nth-child(5n)").after("<div class='clear'></div>");
        $(".lafka-latest-grid.lafka-latest-blog-col-6 div.post:nth-child(6n)").after("<div class='clear'></div>");

        // HIDE EMPTY COMMENTS DIV
        $('div#comments').each(function() {
            if ($(this).children().length === 0) {
                $(this).hide();
            }
        });

        // Smooth scroll
        var scrollDuration = 0;
        if (lafka_main_js_params.enable_smooth_scroll) {
            scrollDuration = 1500;
        }

        $("li.menu-item a[href*='#']:not([href='#']), .wpb_text_column a[href*='#']:not([href='#']), a.vc_btn3[href*='#']:not([href='#']), .vc_icon_element a[href*='#']:not([href='#'])").on('click', function() {
            if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') && location.hostname === this.hostname) {
                var hashVal = this.hash;
                if (!hashVal || !/^#[a-zA-Z0-9_-]+$/.test(hashVal)) return;
                var target = $(document.getElementById(hashVal.slice(1)));
                target = target.length ? target : $('[name=' + CSS.escape(hashVal.slice(1)) + ']');
                if (target.length) {
                    $('html,body').animate({
                        scrollTop: target.offset().top - 75
                    }, scrollDuration, 'swing');
                }
                return false;
            }
        });

        /**
         * This part handles the menu highlighting functionality.
         * When using anchors
         */
        var aChildren = $("li.menu-item a[href*='#']:not([href='#'])"); // find the a children of the list items
        var aArray = []; // create the empty aArray
        for (var i = 0; i < aChildren.length; i++) {
            var aChild = aChildren[i];
            var ahref = $(aChild).prop('href');
            aArray.push(ahref);
        } // this for loop fills the aArray with attribute href values

        // Throttled scroll handler using requestAnimationFrame
        var scrollTicking = false;
        $(window).on('scroll', function() {
            if (!scrollTicking) {
                window.requestAnimationFrame(function() {
                    var windowPos = $(window).scrollTop();
                    var windowHeight = $(window).height();
                    var docHeight = $(document).height();

                    for (var i = 0; i < aArray.length; i++) {
                        var theID = aArray[i];
                        var theHash = '';
                        try { theHash = new URL(theID).hash; } catch(e) { continue; }
                        if (!theHash || !/^#[a-zA-Z0-9_-]+$/.test(theHash)) continue;
                        var theEl = document.getElementById(theHash.slice(1));
                        if (theEl) {
                            var divPos = $(theEl).offset().top - 145;
                            var divHeight = $(theEl).height();
                            if (windowPos >= divPos && windowPos < (divPos + divHeight)) {
                                $("li.current-menu-item").removeClass("current-menu-item");
                                $("li.menu-item a").filter(function() { return this.href === theID; }).parent().addClass("current-menu-item");
                            }
                        }
                    }

                    if (windowPos + windowHeight == docHeight) {
                        if (!$("li.menu-item:last-child").hasClass("current-menu-item")) {
                            var navActiveCurrent = $("li.current-menu-item a").prop("href");
                            $("li.menu-item a").filter(function() { return this.href === navActiveCurrent; }).parent().removeClass("current-menu-item");
                            $("li.menu-item:last-child a").addClass("current-menu-item");
                        }
                    }
                    scrollTicking = false;
                });
                scrollTicking = true;
            }
        });

        // Add to cart Ajax if enable_ajax_add_to_cart is set in the WooCommerce settings and product is simple or variable
        if (lafka_main_js_params.enable_ajax_add_to_cart === 'yes') {
            $(document).on('click', '.single_add_to_cart_button', function(e) {

                var $add_to_cart_form = $(this).closest('form.cart');

                if ($add_to_cart_form.length) {
                    var is_combo_update_from_cart = $add_to_cart_form.find('input[name="update-combo"]').length !== 0;
                    if (is_combo_update_from_cart) {
                        return true;
                    }
                    var is_variable = $add_to_cart_form.hasClass('variations_form');
                    var is_grouped = $add_to_cart_form.hasClass('grouped_form');
                    var is_external = $add_to_cart_form.prop('method') === 'get';
                    var is_subscription = $add_to_cart_form.closest("div.product").hasClass("has-subscription-plans");
                } else {
                    return true;
                }

                if (!is_grouped && !is_external && !is_subscription) {

                    // perform the html5 validation
                    if ($add_to_cart_form[0].checkValidity()) {
                        e.preventDefault();
                    } else {
                        return true;
                    }

                    // If we've chosen unavailable variation don't execute
                    if (!$(this).is('.wc-variation-is-unavailable,.wc-variation-selection-needed')) {
                        var quantity = $add_to_cart_form.find('input[name="quantity"]').val();

                        var product_id;
                        if (is_variable) {
                            product_id = $add_to_cart_form.find('input[name="add-to-cart"]').val();
                        } else {
                            product_id = $add_to_cart_form.find('button[name="add-to-cart"]').val();
                        }

                        var data = { product_id: product_id, quantity: quantity, product_sku: "" };

                        // AJAX add to cart request.
                        var $thisbutton = $(this);

                        // Trigger event.
                        $(document.body).trigger('adding_to_cart', [$thisbutton, data]);

                        //AJAX call
                        $thisbutton.addClass('loading');
                        $thisbutton.prop('disabled', true);

                        var add_to_cart_ajax_data = {};
                        add_to_cart_ajax_data.action = 'lafka_wc_add_cart';
                        add_to_cart_ajax_data.security = lafka_main_js_params.nonce;

                        if (product_id) {
                            add_to_cart_ajax_data["add-to-cart"] = product_id;
                        }

                        $.ajax({
                            url: lafka_main_js_params.admin_url,
                            type: 'POST',
                            data: $add_to_cart_form.serialize() + "&" + $.param(add_to_cart_ajax_data),

                            success: function(results) {
                                // Redirect to cart option
                                if (lafka_main_js_params.cart_redirect_after_add === 'yes') {
                                    window.location = lafka_main_js_params.cart_url;
                                } else {
                                    if ("error_message" in results) {
                                        alert(results.error_message);
                                    } else {
                                        // Trigger event so themes can refresh other areas
                                        $(document.body).trigger('added_to_cart', [results.fragments, results.cart_hash, $thisbutton]);
                                    }
                                }
                            },
                            complete: function(jqXHR, status) {
                                $thisbutton.removeClass('loading');
                                $thisbutton.prop('disabled', false);
                            }
                        });
                    }
                } else {
                    return true;
                }
            });
        }

        // Initialise the small countdowns on products list
        lafkaInitSmallCountdowns($('div.prod_hold'));

        // if is set infinite load on shop - run it de..
        if (lafka_main_js_params.enable_infinite_on_shop === 'yes') {
            // hide the pagination
            var $pagination = $('#products-wrapper').find('div.pagination');
            $pagination.hide();

            // If enabled load more button
            if (lafka_main_js_params.use_load_more_on_shop === 'yes') {
                $(document.body).on('click', 'div.lafka-shop-pager.lafka-infinite button.lafka-load-more', function(e) {
                    $(this).hide();
                    $(document.body).find('div.lafka-shop-pager.lafka-infinite a.next_page').trigger("click");
                });
            } else {
                // Track scrolling, hunting for infinite ajax load (throttled)
                var infiniteTicking = false;
                $(window).on("scroll", function() {
                    if (!infiniteTicking) {
                        window.requestAnimationFrame(function() {
                            if ($(document.body).find('div.lafka-shop-pager.lafka-infinite').is(':in-viewport')) {
                                $(document.body).find('div.lafka-shop-pager.lafka-infinite a.next_page').trigger("click");
                            }
                            infiniteTicking = false;
                        });
                        infiniteTicking = true;
                    }
                });
            }

            // Shop Page
            $(document.body).on('click', 'div.lafka-shop-pager.lafka-infinite a.next_page', function(e) {
                e.preventDefault();

                if ($(this).data('requestRunning')) {
                    return;
                }

                $(this).data('requestRunning', true);

                var $products = $('#products-wrapper').find('div.box-products.woocommerce');
                var $pageStatus = $pagination.prevAll('.lafka-page-load-status');

                $pageStatus.children('.infinite-scroll-last').hide();
                $pageStatus.children('.infinite-scroll-request').show();
                $pageStatus.show();

                $.get(
                    $(this).prop('href'),
                    function(response) {

                        $.lafka_refresh_products_after_ajax(response, $products, $pagination, $pageStatus);

                        $(document.body).trigger('lafka_shop_ajax_loading_success');
                    }
                );
            });
        }

        if (typeof lafka_foodmenu_js_params !== 'undefined') {

            var $container = $('div.foodmenus', '#main');

            var $isotopedGrid = $container.isotope({
                itemSelector: 'div.foodmenu-unit',
                layoutMode: 'masonry',
                transitionDuration: '0.5s'
            });

            // layout Isotope after each image loads
            $isotopedGrid.imagesLoaded().progress(function() {
                $isotopedGrid.isotope('layout');
            });

            // bind filter button click
            $('.lafka-foodmenu-categories').on('click', 'a', function() {
                var filterValue = $(this).prop('data-filter');
                // use filterFn if matches value
                $isotopedGrid.isotope({ filter: filterValue });
            });

            // change is-checked class on buttons
            $('div.lafka-foodmenu-categories', '#main').each(function(i, buttonGroup) {
                var $buttonGroup = $(buttonGroup);
                $buttonGroup.on('click', 'a', function() {
                    $buttonGroup.find('.is-checked').removeClass('is-checked');
                    $(this).addClass('is-checked');
                });
            });
        }

        // AJAXIFY products listing filters, widgets, etc
        if (lafka_main_js_params.use_product_filter_ajax === 'yes') {
            // products ordering and per page
            var woocommerceOrderingForm = $(document.body).find('form.woocommerce-ordering');
            if (woocommerceOrderingForm.length) {
                woocommerceOrderingForm.on('submit', function(e) {
                    e.preventDefault();
                });

                $(document.body).on('change', 'form.woocommerce-ordering select.orderby, form.woocommerce-ordering select.per_page', function(e) {
                    e.preventDefault();

                    var currentUrlParams = window.location.search;
                    var url = window.location.href.replace(window.location.search, '') + lafkaUpdateUrlParameters(currentUrlParams, woocommerceOrderingForm.serialize());

                    $(document.body).trigger('lafka_products_filter_ajax', [url, woocommerceOrderingForm]);
                });
            }

            // price slider
            $(document.body).find('#lafka-price-filter-form').on('submit', function(e) {
                e.preventDefault();
            });

            $(document.body).on('price_slider_change', function(event, ui) {
                var form = $('.price_slider').closest('form').get(0);
                var $form = $(form);

                var currentUrlParams = window.location.search;
                var url = $form.prop('action') + lafkaUpdateUrlParameters(currentUrlParams, $form.serialize());

                $(document.body).trigger('lafka_products_filter_ajax', [url, $(this)]);
            });

            // lafka_product_filter
            $(document.body).on('click', 'div.lafka_product_filter a', function(e) {
                e.preventDefault();
                var url = $(this).prop('href');
                $(document.body).trigger('lafka_products_filter_ajax', [url, $(this)]);
            });

            // reset all filters
            $(document.body).on('click', 'a.lafka-reset-filters', function(e) {
                e.preventDefault();
                var url = $(this).prop('href');
                $(document.body).trigger('lafka_products_filter_ajax', [url, $(this)]);
            });
        }

        // Set flag when mailto: and tel: links are clicked
        $(document.body).on('click', 'div.widget_lafka_contacts_widget a, div.lafka-top-bar-message a', function(e) {
            is_mailto_or_tel_link = true;
        });

        // Share links
        $(document.body).on('click', 'div.lafka-share-links a', function(e) {
            window.open(this.href, 'targetWindow', 'toolbar=no,location=0,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=300');
            return false;
        });

        /*
         * Listen for added_to_wishlist to increase number in header
         */
        $(document.body).on("added_to_wishlist", function() {
            var wishNumberSpan = $("span.lafka-wish-number");
            if (wishNumberSpan.length) {
                var wishNum = parseInt(wishNumberSpan.html(), 10);
                if (!isNaN(wishNum)) {
                    wishNumberSpan.html(wishNum + 1);
                }
            }
        });

        /*
         * Listen for removed_from_wishlist to decrease number in header
         */
        $(document.body).on("removed_from_wishlist", function() {
            var wishNumberSpan = $("span.lafka-wish-number");
            if (wishNumberSpan.length) {
                var wishNum = parseInt(wishNumberSpan.html(), 10);
                if (!isNaN(wishNum) && wishNum > 0) {
                    wishNumberSpan.html(wishNum - 1);
                }
            }
        });

        // Show reset button if there are active filters
        $.lafka_handle_active_filters_reset_button();

        // Build mobile menu tabs
        $(document.body).find('div#menu_mobile').tabs({
            beforeActivate: function(event, ui) {
                if (!$.isEmptyObject(ui.newTab)) {
                    var $link = ui.newTab.find('a');
                    // If is wishlist link - do not open tab, instead redirect
                    if ($link.length && $link.hasClass('lafka-mobile-wishlist')) {
                        window.location.href = $link.prop('href');
                        return false;
                    }
                }
            }
        });

        // Handle unavailable variations swatches on single product
        $(document.body).find(".variations_form").on("woocommerce_update_variation_values", function() {
            var $swatches = $('.lafka-wcs-swatches');
            $swatches.find('.swatch').removeClass('lafka-not-available');
            $swatches.each(function() {
                var $select = $(this).prev().find('select');
                $(this).find('.swatch').each(function() {
                    if (!$select.find('option[value="' + $(this).data('value') + '"]').length) {
                        $(this).addClass('lafka-not-available');
                    }
                })
            })
        });

        lafkaOrderHoursCountdown();

        // Add column classes to mega menu
        defineMegaMenuColumns();

        // Full-width elements
        lafka_fullwidth_elements();

        defineMegaMenuSizing();

        // End of document.ready()
    });

    // Handle on Ajax complete global events
    $(document).ajaxComplete(function() {
        lafkaOrderHoursCountdown();
    });

    // Handle the products filtering
    $(document.body).on('lafka_products_filter_ajax', function(e, url, element) {

        var $products_wrapper = $('#products-wrapper');
        var $products = $products_wrapper.find('div.box-products.woocommerce');
        var $pagination = $products_wrapper.find('div.pagination');
        var $pageStatus = $pagination.prevAll('.lafka-page-load-status');

        $.lafka_show_loader();

        if ('?' === url.slice(-1)) {
            url = url.slice(0, -1);
        }

        url = url.replace(/%2C/g, ',');
        window.history.pushState({ page: url }, "", url);

        if (lafka_ajaxXHR) {
            lafka_ajaxXHR.abort();
        }

        lafka_ajaxXHR = $.get(url, function(res) {

            // Empty the products container
            $products.empty();

            $.lafka_refresh_product_filters_areas(res);
            $.lafka_refresh_products_after_ajax(res, $products, $pagination, $pageStatus);

            $.lafka_hide_loader();
            $(document.body).trigger('lafka_products_filter_ajax_success', [res, url]);
        }, 'html');

    });

    // Throttled resize handler using requestAnimationFrame
    var resizeTicking = false;
    window.addEventListener('resize', function() {
        if (!resizeTicking) {
            window.requestAnimationFrame(function() {
                checkRevealFooter();
                checkProductGalleryCarousel();
                checkSummaryHeight();
                checkSidebarHeight();
                checkCommentsHeight();
                checkFoodmenuHeight();
                lafka_fullwidth_elements();
                defineMegaMenuSizing();
                resizeTicking = false;
            });
            resizeTicking = true;
        }
    });

    /**
     * Initialise the small countdowns on products list
     * @param prodHoldElements
     */
    window.lafkaInitSmallCountdowns = function(prodHoldElements) {
        $(prodHoldElements).each(function() {
            var data = $(this).find('.count_holder_small').data();
            if (typeof data !== 'undefined') {
                var timeFormat = '{dn} {dl} {hn}:{mnn}:{snn}';
                if (typeof data.countdownShowDays !== 'undefined' && data.countdownShowDays === 'no') {
                    timeFormat = '{hn}:{mnn}:{snn}';
                }
                $(data.countdownId).countdown({
                    until: new Date(data.countdownTo),
                    compact: false,
                    layout: '<span class="countdown_time_tiny">' + timeFormat + '</span>'
                });
            }
        })
    };

    /**
     * Initialize the sticky header
     */
    window.lafkaStickyHeaderInit = function() {
        var headerHeight = $('body:not(.lafka_transparent_header) #header').height();
        $("body").addClass("lafka-sticky-header").css("padding-top", headerHeight + "px");
        var stickyTicking = false;
        $(window).on("scroll", function() {
            if (!stickyTicking) {
                window.requestAnimationFrame(function() {
                    var $header = $("#header");
                    $(window).scrollTop() > 0 ? $header.addClass("lafka-sticksy") : $header.removeClass("lafka-sticksy");
                    stickyTicking = false;
                });
                stickyTicking = true;
            }
        });
    };

    /**
     * Initialize the counter for opening the store
     */
    window.lafkaOrderHoursCountdown = function() {
        $(document.body).find('.lafka_order_hours_countdown').each(function() {
            var count_to = '+' + $(this).data('diff-days') + 'd +' + $(this).data('diff-hours') + 'h +' + $(this).data('diff-minutes') + 'm +' + $(this).data('diff-seconds') + 's';
            var counter_format = $(this).data('output-format');

            $(this).countdown({
                until: count_to,
                compact: false,
                layout: '<span class="countdown_time_small">' + counter_format + '</span>'
            });
        });
    }

    /* Mega Menu */

    function defineMegaMenuColumns() {
        $('#main-menu .lafka-mega-menu').each(function() {
            var menuColumns = $(this).find('li.lafka_colum_title').length;
            $(this).addClass('menu-columns' + menuColumns);
        });
    }

    function defineMegaMenuSizing() {
        var $menuElement = $('#main-menu');

        var $menuHolderElement = $('#header .menu-main-menu-container');
        var menuOffset = $menuHolderElement.offset();

        $menuElement.find('.lafka-mega-menu').each(function() {
            $(this).css('max-width', $menuHolderElement.outerWidth() + 'px');
            var dropdown = $(this).parent().offset();
            var i;
            if (is_rtl) {
                var dropdown_right_offset = $(window).width() - (dropdown.left + $(this).parent().outerWidth());
                i = (dropdown_right_offset + $(this).outerWidth()) - (menuOffset.left + $menuHolderElement.outerWidth());
                if (i > 0) {
                    $(this).css('margin-right', '-' + (i) + 'px');
                }
            } else {
                i = (dropdown.left + $(this).outerWidth()) - (menuOffset.left + $menuHolderElement.outerWidth());
                if (i > 0) {
                    $(this).css('margin-left', '-' + (i) + 'px');
                }
            }
        });

        $menuElement.find('li.lafka_colum_title > .sub-menu').each(function() {
            if ($(this).children("li").length == $(this).children("li.lafka_mega_text_block").length) {
                $(this).parent().addClass("lafka_mega_text_block_parent");
            }
        });
    }

    /**
     * Define behaviour for click on shopping cart icon
     */
    function defineCartIconClickBehaviour() {
        $(document).on("click", "body:not(.woocommerce-checkout) #lafka_quick_cart_link", function(event) {
            event.preventDefault();
            event.stopPropagation();

            var shoppingCart = $(document.body).find("div.widget.woocommerce.widget_shopping_cart");

            // Order hours counter to the next opening
            lafkaInitSmallCountdowns($(document.body).find('div.lafka-closed-store-message'));

            shoppingCart.addClass("active_cart");
            $(document.body).find('div.widget.woocommerce.widget_shopping_cart .widget_shopping_cart_content ul.cart_list.product_list_widget').niceScroll({ horizrailenabled: false });

        });

        $(document).on("click", ".close-cart-button", function(event) {
            var $parent = $(this).parent();
            $parent.removeClass('active_cart');
        });
    }

    function checkRevealFooter() {
        var isReveal = $('#footer').height() - 1;
        if (isReveal < 550 && $(document.body).hasClass("lafka_fullwidth")) {
            $('html.no-touch body.lafka_fullwidth.lafka-reveal-footer #content').css("margin-bottom", isReveal + "px");
            $('body.lafka_fullwidth.lafka-reveal-footer #footer').addClass('lafka_do_reveal');
        } else {
            $('html.no-touch body.lafka_fullwidth.lafka-reveal-footer #content').css("margin-bottom", 0 + "px");
            $('body.lafka_fullwidth.lafka-reveal-footer #footer').removeClass('lafka_do_reveal');

        }
    }

    function checkProductGalleryCarousel() {
        var current_window_width = $(window).width();
        var $singleProductImages = $(document.body).find('div.lafka-single-product .lafka-image-list-product-gallery .woocommerce-product-gallery__wrapper, .lafka_image_list_foodmenu .lafka_image_list');

        if (current_window_width < 769 && $singleProductImages.length) {
            $singleProductImages.addClass('owl-carousel');
            $singleProductImages.owlCarousel({
                rtl: is_rtl,
                items: 1,
                dots: false,
                loop: false,
                rewind: true,
                nav: true,
                navText: [
                    "<i class='fa fa-angle-left'></i>",
                    "<i class='fa fa-angle-right'></i>"
                ]
            });
        } else if ($singleProductImages.length) {
            $singleProductImages.trigger('destroy.owl.carousel').removeClass('owl-carousel owl-loaded');
            $singleProductImages.find('.owl-stage-outer').children().unwrap();
        }
    }

    function checkSummaryHeight() {
        var $lafkaSummaryHeight = $('.lafka-product-summary-wrapper div.summary').height();
        var $lafkaVisibleHeight = $(window).height();
        var current_window_width = $(window).width();
        var $body_summary = $("body, .lafka-product-summary-wrapper div.summary");
        if ($lafkaSummaryHeight < $lafkaVisibleHeight - 250 && current_window_width > 768) {
            $body_summary.addClass("lafka-sticky-summary");
        } else {
            $body_summary.removeClass("lafka-sticky-summary");
        }
    }

    function checkSidebarHeight() {
        var $lafkaSidebarHeight = $('.sidebar').height();
        var $lafkaVisibleHeight = $(window).height();
        var current_window_width = $(window).width();
        var $body_sidebar = $("body, .sidebar");
        if ($lafkaSidebarHeight < $lafkaVisibleHeight - 250 && current_window_width > 768) {
            $body_sidebar.addClass("lafka-sticky-sidebar");
        } else {
            $body_sidebar.removeClass("lafka-sticky-sidebar");
        }
    }

    function checkCommentsHeight() {
        var $lafkaCommentsHeight = $('body.single-post #comments > #respond.comment-respond').height();
        var $lafkaVisibleHeight = $(window).height();
        var $body_summary = $("body.single-post #comments > #respond.comment-respond");
        if ($lafkaCommentsHeight < $lafkaVisibleHeight - 200) {
            $body_summary.addClass("lafka-sticky-comments");
        } else {
            $body_summary.removeClass("lafka-sticky-comments");
        }
    }

    function checkFoodmenuHeight() {
        var $lafkaFoodmenuHeight = $('.foodmenu_top div.one_third.last.project-data').height();
        var $lafkaPortVisibleHeight = $(window).height();
        var current_window_width = $(window).width();
        var $body_PortSummary = $("body, .foodmenu_top div.one_third.last.project-data");
        if ($lafkaFoodmenuHeight < $lafkaPortVisibleHeight - 250 && current_window_width > 768) {
            $body_PortSummary.addClass("lafka-sticky-summary");
        } else {
            $body_PortSummary.removeClass("lafka-sticky-summary");
        }
    }

    function lafka_fullwidth_elements() {
        var $elements = $('#content:not(.has-sidebar) #products-wrapper .woocommerce-tabs.wc-tabs-wrapper, #content:not(.has-sidebar) p.woocommerce-thankyou-order-received, body.single-post #content:not(.has-sidebar) #comments, body.page #content:not(.has-sidebar) #comments, #content:not(.has-sidebar) ul.woocommerce-order-overview.woocommerce-thankyou-order-details.order_details');
        var $rtl = $('body.rtl');
        var $contentDiv = $('#content');

        if ($contentDiv.length) {
            $elements.each(function(index) {
                var width = $contentDiv.width();
                var row_padding = 40;
                var offset = -($contentDiv.width() - $('#content > .inner ').css("width").replace("px", "")) / 2 - row_padding + 15;

                $(this).css({
                    'position': 'relative',
                    'box-sizing': 'border-box',
                    'width': width,
                    'padding-left': Math.abs(offset),
                    'padding-right': Math.abs(offset)
                });

                if ($rtl.length && !($(this).prop('id') === 'comments')) {
                    $(this).css({ 'right': offset });
                } else {
                    $(this).css({ 'left': offset });
                }
            });
        }
    }

    // Full-width vc row elements
    $(document).on("vc-full-width-row-single", lafka_vc_row_fullwidth_elements);

    function lafka_vc_row_fullwidth_elements(event, {el, offset, marginLeft, marginRight, elFull, width}) {
        var $rtl = $('body.rtl');
        var is_boxed = $('body.lafka_boxed').length;
        var is_left_header = $('body.lafka_header_left').length;
        var is_vc_stretch_content = el.data('vc-stretch-content');
        var $content = $(document.body).find('#content');
        if ($content.length) {
            var width = $content.width();
            var row_padding = 40;
            var offset = -($('#content').width() - $('#content > .inner ').css("width").replace("px", "")) / 2 - row_padding + 15;
            el.css({"width": width + "px"});
            if ((is_boxed || is_left_header) && !is_vc_stretch_content) {
                el.css({
                    'padding-left': Math.abs(offset),
                    'padding-right': Math.abs(offset)
                });
            }
            if ($rtl.length) {
                el.css({
                    'left': 0,
                    'right': offset
                });
            } else {
                el.css({
                    'left': offset,
                    'right': 0
                });
            }
        }
    }

    //updates the shopping cart in the sidebar, hooks into the added_to_cart event which is triggered by woocommerce
    function update_cart_dropdown(event) {
        var product = jQuery.extend({ name: lafka_main_js_params.product_label, price: "", image: "" }, lafka_added_product);
        var notice = $("<div class='lafka_added_to_cart_notification'>" + product.image + "<div class='added-product-text'><strong>" + product.name + " " + lafka_main_js_params.added_to_cart_label + "</strong></div></div>");

        if (typeof event !== 'undefined') {
            var $cart_add_sound = $('#cart_add_sound');
            if ($cart_add_sound.length) {
                $cart_add_sound[0].play && $cart_add_sound[0].play();
            }
            if (lafka_main_js_params.shopping_cart_on_add === 'yes') {
                $(document.body).find("div.widget.woocommerce.widget_shopping_cart").addClass("active_cart");
            }
            defineCartIconClickBehaviour();
            notice.appendTo($("body")).hide().fadeIn('slow');

            setTimeout(function() {
                notice.fadeOut('slow');
            }, 2000);
            setTimeout(function() {
                $(document.body).find("div.widget.woocommerce.widget_shopping_cart").removeClass("active_cart");
            }, 8000);

            $(document.body).find('div.widget.woocommerce.widget_shopping_cart .widget_shopping_cart_content ul.cart_list.product_list_widget').niceScroll({ horizrailenabled: false });
        }
    }

    var lafka_added_product = {};

    function track_ajax_add_to_cart() {
        jQuery('body').on('click', '.add_to_cart_button', function() {
            var productContainer = jQuery(this).parents('.product').eq(0),
                product = {};
            product.name = productContainer.find('span.name').text();
            product.image = productContainer.find('div.image img');
            product.price = productContainer.find('.price_hold .amount').last().text();

            /*fallbacks*/
            if (productContainer.length === 0) {
                return;
            }

            if (product.image.length) {
                product.image = "<img class='added-product-image' src='" + product.image.get(0).src + "' title='' alt='' />";
            } else {
                product.image = "";
            }

            lafka_added_product = product;
        });
    }

    // Showing loader
    jQuery.lafka_show_loader = function() {

        var overlay;
        var $shopbypricefilter_overlay = $('.shopbypricefilter-overlay');
        if ($shopbypricefilter_overlay.length) {
            overlay = $shopbypricefilter_overlay;
        } else {
            overlay = $('<div class="ui-widget-overlay shopbypricefilter-overlay">&nbsp;</div>').prependTo('body');
        }

        $(overlay).css({
            'position': 'fixed',
            'top': 0,
            'left': 0,
            'width': '100%',
            'height': '100%',
            'z-index': 19999,
        });

        $shopbypricefilter_overlay.each(function() {
            var overlay = this;
            var img;

            if ($('img', overlay).length) {
                img = $('img', overlay);
            } else {
                img = $('<img id="price_fltr_loading_gif" src="' + lafka_main_js_params.img_path + 'loading3.gif" />').prependTo(overlay);
            }

            $(img).css({
                'max-height': $(overlay).height() * 0.8,
                'max-width': $(overlay).width() * 0.8
            });

            $(img).css({
                'position': 'fixed',
                'top': $(window).outerHeight() / 2,
                'left': ($(window).outerWidth() - $(img).width()) / 2
            });
        }).show();

    };

    // Hiding loader
    jQuery.lafka_hide_loader = function() {
        $('.shopbypricefilter-overlay').remove();
    };

    // Refresh product filters area
    jQuery.lafka_refresh_product_filters_areas = function(response) {
        // lafka_product_filter widget
        var $lafka_product_filters = $(document.body).find('div.lafka_product_filter');
        var $new_lafka_product_filters = $(response).find('div.lafka_product_filter');

        if ($lafka_product_filters.length > $new_lafka_product_filters.length) {
            var existing_titles = [];
            var found_titles = [];

            $lafka_product_filters.each(function() {
                var $curr_elmnt = $(this);
                var title = $curr_elmnt.find('h3:first-of-type').html();
                existing_titles.push(title);

                $new_lafka_product_filters.each(function() {
                    if ($(this).find('h3:first-of-type').html() === title) {
                        $curr_elmnt.html($(this).html());
                        found_titles.push(title);
                    }
                });
            });

            for (var i = 0; i < existing_titles.length; i++) {
                if ($.inArray(existing_titles[i], found_titles) === -1) {
                    $lafka_product_filters.each(function() {
                        $(this).find("h3:contains('" + existing_titles[i] + "')").parent().remove();
                    });
                }
            }
        } else {
            $new_lafka_product_filters.each(function(index) {
                if (typeof $lafka_product_filters.get(index) !== 'undefined') {
                    $($lafka_product_filters.get(index)).html($(this).html());
                } else if ($lafka_product_filters.length === 0) {
                    $(document.body).find('div#lafka-filter-widgets').append($(this));
                } else {
                    $lafka_product_filters.first().parent().find('div.widget').last().after($(this));
                }
            });
        }

        $.lafka_widget_columns();

        var $price_slider_form = $(document).find('#lafka-price-filter-form');
        if ($price_slider_form.length === 0) {
            $(document).find('div#main').find('div.product-filter').prepend($(response).find('#lafka-price-filter-form'));
        } else {
            $price_slider_form.replaceWith($(response).find('#lafka-price-filter-form'));
        }

        if (typeof $.lafka_build_price_slider === "function") {
            $.lafka_build_price_slider();
        }

        // Show reset button if there are active filters
        $.lafka_handle_active_filters_reset_button();

    };

    jQuery.lafka_handle_active_filters_reset_button = function() {
        // Show reset button if there are active filters
        var $reset_button = $(document).find('div.lafka-filter-widgets-holder a.lafka-reset-filters');
        if (typeof $reset_button !== 'undefined') {
            var show_reset_button = false;

            var lafka_reset_query = $reset_button.data('lafka_reset_query');
            var right_side_of_the_url = '';

            if (window.location.href.indexOf('?') !== -1) {
                right_side_of_the_url = window.location.href.substr(window.location.href.indexOf('?'));
                if (right_side_of_the_url !== lafka_reset_query) {
                    show_reset_button = true;
                }
            }

            if (show_reset_button) {
                $reset_button.show();
            } else {
                $reset_button.hide();
            }
        }
    };

    jQuery.lafka_widget_columns = function() {
        // Put class .last on each 4th widget in the footer
        $('#slide_footer div.one_fourth').filter(function(index) {
            return index % 4 === 3;
        }).addClass('last').after('<div class="clear"></div>');
        $('#footer > div.inner div.one_fourth').filter(function(index) {
            return index % 4 === 3;
        }).addClass('last').after('<div class="clear"></div>');
        // Put class .last on each 4th widget in pre header
        $('#pre_header > div.inner div.one_fourth').filter(function(index) {
            return index % 4 === 3;
        }).addClass('last').after('<div class="clear"></div>');
        $('#lafka-filter-widgets > div.one_fourth').filter(function(index) {
            return index % 4 === 3;
        }).addClass('last').after('<div class="clear"></div>');

        // Put class .last on each 3th widget in the footer
        $('#slide_footer div.one_third').filter(function(index) {
            return index % 3 === 2;
        }).addClass('last').after('<div class="clear"></div>');
        $('#footer > div.inner div.one_third').filter(function(index) {
            return index % 3 === 2;
        }).addClass('last').after('<div class="clear"></div>');
        // Put class .last on each 3th widget in pre header
        $('#pre_header > div.inner div.one_third').filter(function(index) {
            return index % 3 === 2;
        }).addClass('last').after('<div class="clear"></div>');
        $('#lafka-filter-widgets > div.one_third').filter(function(index) {
            return index % 3 === 2;
        }).addClass('last').after('<div class="clear"></div>');

        // Put class .last on each 2nd widget in the footer
        $('#slide_footer div.one_half').filter(function(index) {
            return index % 2 === 1;
        }).addClass('last').after('<div class="clear"></div>');
        $('#footer > div.inner div.one_half').filter(function(index) {
            return index % 2 === 1;
        }).addClass('last').after('<div class="clear"></div>');
        // Put class .last on each 2nd widget in pre header
        $('#pre_header > div.inner div.one_half').filter(function(index) {
            return index % 2 === 1;
        }).addClass('last').after('<div class="clear"></div>');
        $('#lafka-filter-widgets > div.one_half').filter(function(index) {
            return index % 2 === 1;
        }).addClass('last').after('<div class="clear"></div>');

        // Woocommerce part columns
        $('.woocommerce.columns-2:not(.owl-carousel)').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 2 === 1;
            }).addClass('last').after('<div class="clear"></div>');
        });

        $('.woocommerce.columns-3:not(.owl-carousel)').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 3 === 2;
            }).addClass('last').after('<div class="clear"></div>');
        });

        $('.woocommerce.columns-4:not(.owl-carousel)').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 4 === 3;
            }).addClass('last').after('<div class="clear"></div>');
        });
        $('.woocommerce.columns-5:not(.owl-carousel)').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 5 === 4;
            }).addClass('last').after('<div class="clear"></div>');
        });
        $('.woocommerce.columns-6:not(.owl-carousel)').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 6 === 5;
            }).addClass('last').after('<div class="clear"></div>');
        });
    };

    // Refresh products list after ajax calls
    jQuery.lafka_refresh_products_after_ajax = function(response, $products, $pagination, $pageStatus) {

        var $newProducts = $(response).find('.content_holder').find('.prod_hold');
        var $pagination_html = $(response).find('.lafka-shop-pager .pagination').html();

        if (typeof $pagination_html === 'undefined') {
            $pagination.html('');
        } else {
            $pagination.html($pagination_html);
        }


        // Do the necessary for the appending products
        $newProducts.imagesLoaded(function() {
            $newProducts.each(function() {
                $(this).addClass('lafka-infinite-loaded');

                if ($(document.documentElement).hasClass('no-touch')) {
                    $(this).appear(function() {
                        $(this).addClass('prod_visible').delay(2000);
                    });
                }
            });
        });

        // Now add the new products to the list
        $products.append($newProducts);

        lafkaInitSmallCountdowns($newProducts);

        // Woocommerce part columns
        $('.woocommerce.columns-2:not(.owl-carousel) div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 2 === 1;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-3:not(.owl-carousel) div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 3 === 2;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-4:not(.owl-carousel) div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 4 === 3;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-5:not(.owl-carousel) div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 5 === 4;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-6:not(.owl-carousel) div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 6 === 5;
            }
        }).addClass('last').after('<div class="clear"></div>');

        $pagination.find('a.next_page').data('requestRunning', false);
        // hide loading
        $pageStatus.children('.infinite-scroll-request').hide();

        if (!$pagination.find('a.next_page').length) {
            $pageStatus.children('.infinite-scroll-last').show();
            $('button.lafka-load-more').hide();
        } else {
            $('button.lafka-load-more').show();
        }
    };
})(window.jQuery);

// non jQuery scripts below
"use strict";
// Add or Update a key-value pairs in the URL query parameters (with leading '?')
function lafkaUpdateUrlParameters(currentParams, newParams) {

    if (currentParams.trim() === '') {
        return "?" + newParams;
    }

    var newParamsObj = {};
    newParams.split('&').forEach(function(x) {
        var arr = x.split('=');
        arr[1] && (newParamsObj[arr[0]] = arr[1]);
    });

    for (var prop in newParamsObj) {
        // remove the hash part before operating on the uri
        var i = currentParams.indexOf('#');
        var hash = i === -1 ? '' : uri.substr(i);
        currentParams = i === -1 ? currentParams : currentParams.substr(0, i);

        var re = new RegExp("([?&])" + prop + "=.*?(&|$)", "i");
        var separator = "&";
        if (currentParams.match(re)) {
            currentParams = currentParams.replace(re, '$1' + prop + "=" + newParamsObj[prop] + '$2');
        } else {
            currentParams = currentParams + separator + prop + "=" + newParamsObj[prop];
        }
        currentParams + hash; // finally append the hash as well
    }

    return currentParams;
}