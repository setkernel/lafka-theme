(function($) {
    "use strict";
    let lafka_ajaxXHR = null;

    /**
     * lafkaOnVisible — IntersectionObserver-based one-shot trigger.
     *
     * Replaces the old jquery.appear / isInViewport vendor libs (P3-05).
     * Calls `fn` ONCE the first time `el` enters the viewport, then unobserves.
     * Behaves as a no-op on the small set of legacy browsers without IO.
     *
     * @param {Element|jQuery} el  Element or jQuery wrapper.
     * @param {Function} fn        Callback fired with the underlying DOM element bound as `this`.
     * @param {Object} opts        Optional IntersectionObserver init options.
     */
    function lafkaOnVisible(el, fn, opts) {
        const node = (el && el.jquery) ? el.get(0) : el;
        if (!node || typeof IntersectionObserver === 'undefined') {
            // Legacy fallback: fire immediately so the animated elements at
            // least settle into their final state instead of staying invisible.
            if (node && typeof fn === 'function') {
                fn.call(node);
            }
            return;
        }
        const observer = new IntersectionObserver(function(entries, obs) {
            for (let i = 0; i < entries.length; i++) {
                if (entries[i].isIntersecting) {
                    fn.call(node);
                    obs.unobserve(node);
                    return;
                }
            }
        }, opts || { rootMargin: '0px 0px -10% 0px', threshold: 0.01 });
        observer.observe(node);
    }
    // Expose for child themes / future modules that want the same primitive.
    window.lafkaOnVisible = lafkaOnVisible;

    /**
     * P6-PERF-7 (W3-T7): Guard $(window).on('load', ...) calls so they fire
     * immediately when the page is already loaded (i.e. deferred script execution)
     * rather than silently dropping the callback. jQuery Migrate warned that
     * $(window).on('load') registered after the load event fires is a no-op.
     *
     * @param {Function} callback
     */
    function lafkaOnLoad(callback) {
        if (document.readyState === 'complete') {
            callback();
        } else {
            jQuery(window).on('load', callback);
        }
    }

    /*
     * GX T-01: the preloader (off by default) is removed as soon as this
     * deferred script runs — i.e. at DOMContentLoaded — never held until
     * window.load plus a fade. The CSS fail-safe in lafka-preloader.css hides
     * it anyway if this script never runs.
     */
    $('.mask').remove();
    lafkaOnLoad(function() {
        checkRevealFooter();
    });

    $(document).ready(function() {

        //
        // -------------------------------------------------------------------------------------------------------
        // Dropdown Menu
        // -------------------------------------------------------------------------------------------------------

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
            lafkaInitSmallCountdowns($(this).find('.lafka-store-closed-card'));
        });

        // Order hours counter to the next opening
        lafkaInitSmallCountdowns($(document.body).find('.lafka-store-closed-card'));

        $('.woocommerce-review-link').on('click', function() {
            $('#tab-reviews').trigger('click');
            $('html, body').animate({
                scrollTop: $(".woocommerce-tabs").offset().top - 105
            }, 1200, 'swing');
        });

        $('div.content_holder.lafka_blog_masonry div.box.box-common').has('.pagination').parent().addClass('lafka-blog-has-pagination');

        $("div.summary.entry-summary table.variations td").has('div.lafka-wcs-swatches').addClass("lafka-has-swatches-option");
        $("ul#topnav li, ul#topnav2 li, ul.menu li").has('ul').addClass("dropdown");

        /* REMOVE PARENTHESIS ON WOO CATEGORIES */

        $('.count').text(function(_, text) {
            return text.replace(/\(|\)/g, '');
        });

        checkSummaryHeight();
        checkSidebarHeight();
        checkCommentsHeight();

        const customTitleHeight = $('body.lafka_transparent_header #header').height();
        $('body.lafka_transparent_header .lafka_title_holder .inner').css({ "padding-top": customTitleHeight + 160, "padding-bottom": customTitleHeight - 60 });

        $('p.demo_store').prependTo('#header');

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.off-canvas-sidebar').hasClass('active_sidebar')) {
                $(".sidebar.off-canvas-sidebar").removeClass("active_sidebar");
            }
        });

        $(document.body).find('a[href="#"]').on('click', function(event) {
            event.preventDefault();
        });

        // P3-04: video popups migrated to native <dialog> via lafkaDialog.
        // The iframe(url) call handles YouTube watch URLs, Vimeo links, and
        // direct .mp4/.mov/.swf URLs (the helper maps them to embed URLs).
        // The 700px-disable behaviour from Magnific's `disableOn` is preserved
        // — narrow viewports navigate to the original href (mobile bandwidth).
        if ( typeof window.lafkaDialog !== 'undefined' ) {
            $(document).on('click', 'a[href$=".mov"], a[href$=".swf"], a[href$=".mp4"], a[href*="vimeo.com/"], a[href*="youtube.com/watch"]', function (e) {
                if (window.innerWidth < 700) { return; }
                e.preventDefault();
                window.lafkaDialog.iframe(this.href);
            });
        }

        // -------------------------------------------------------------------------------------------------------
        // SLIDING ELEMENTS
        // -------------------------------------------------------------------------------------------------------


        if (!document.getElementById("lafka_page_title")) {
            $(document.body).addClass('page-no-title');
        } else {
            $(document.body).addClass('page-has-title');
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
            lafkaOnVisible(this, function() {
                $(this).animate({ opacity: 1, bottom: "0px" }, 400);
            });
        });

        $('html.no-touch .lafka-from-left').each(function() {
            lafkaOnVisible(this, function() {
                $(this).animate({ opacity: 1, left: "0px" }, 400);
            });
        });

        $('html.no-touch .lafka-from-right').each(function() {
            lafkaOnVisible(this, function() {
                $(this).animate({ opacity: 1, right: "0px" }, 400);
            });
        });

        $('html.no-touch .lafka-fade').each(function() {
            lafkaOnVisible(this, function() {
                $(this).animate({ opacity: 1 }, 400);
            });
        });

        $('.lafka-counter:not(.already_seen)').each(function() {
            lafkaOnVisible(this, function() {

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
            const quantity = parseInt($input.val());

            if (isNaN(quantity)) {
                $input.val(1);
                $input.trigger('change');
            } else if ($input.val() > 1) {
                $input.val(quantity - 1);
                $input.trigger('change');
            }
        }

        function lafka_handle_quantity_on_listing() {
            const $add_to_cart_button = $(this).closest(".links").find(".add_to_cart_button");

            // For AJAX add-to-cart actions
            $add_to_cart_button.attr("data-quantity", jQuery(this).val());
            // For non-AJAX add-to-cart actions
            $add_to_cart_button.attr("href", "?add-to-cart=" + $add_to_cart_button.attr("data-product_id") + "&quantity=" + $(this).val());
        }

        $(document.body).on('click', '.lafka-qty-plus', addQty);
        $(document.body).on('click', '.lafka-qty-minus', subtractQty);
        $(document.body).on('change input', '.quantity .qty', lafka_handle_quantity_on_listing);

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
        let scrollDuration = 0;
        if (lafka_main_js_params.enable_smooth_scroll) {
            scrollDuration = 1500;
        }

        $("li.menu-item a[href*='#']:not([href='#'])").on('click', function() {
            if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') && location.hostname === this.hostname) {
                const hashVal = this.hash;
                if (!hashVal || !/^#[a-zA-Z0-9_-]+$/.test(hashVal)) return;
                let target = $(document.getElementById(hashVal.slice(1)));
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
        const aChildren = $("li.menu-item a[href*='#']:not([href='#'])"); // find the a children of the list items
        const aArray = []; // create the empty aArray
        for (let i = 0; i < aChildren.length; i++) {
            const aChild = aChildren[i];
            const ahref = $(aChild).prop('href');
            aArray.push(ahref);
        } // this for loop fills the aArray with attribute href values

        // Throttled scroll handler using requestAnimationFrame
        let scrollTicking = false;
        $(window).on('scroll', function() {
            if (!scrollTicking) {
                window.requestAnimationFrame(function() {
                    const windowPos = $(window).scrollTop();
                    const windowHeight = $(window).height();
                    const docHeight = $(document).height();

                    for (let i = 0; i < aArray.length; i++) {
                        const theID = aArray[i];
                        let theHash;
                        try { theHash = new URL(theID).hash; } catch { continue; }
                        if (!theHash || !/^#[a-zA-Z0-9_-]+$/.test(theHash)) continue;
                        const theEl = document.getElementById(theHash.slice(1));
                        if (theEl) {
                            const divPos = $(theEl).offset().top - 145;
                            const divHeight = $(theEl).height();
                            if (windowPos >= divPos && windowPos < (divPos + divHeight)) {
                                $("li.current-menu-item").removeClass("current-menu-item");
                                $("li.menu-item a").filter(function() { return this.href === theID; }).parent().addClass("current-menu-item");
                            }
                        }
                    }

                    if (windowPos + windowHeight === docHeight) {
                        if (!$("li.menu-item:last-child").hasClass("current-menu-item")) {
                            const navActiveCurrent = $("li.current-menu-item a").prop("href");
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
            // Delegate from BOTH WC's stock button class and the redesigned PDP
            // CTAs ([data-lafka-add-to-cart] on .lafka-pdp-summary__cta /
            // .lafka-pdp-mobile-cta__btn), which intentionally drop the
            // single_add_to_cart_button class so lafka-libs-config.js doesn't
            // fight pdp-pickers.js over disabled/unavailable state. Both routes
            // serialize the same form.cart, so the AJAX add path is identical.
            $(document).on('click', '.single_add_to_cart_button, [data-lafka-add-to-cart]', function(e) {

                const $add_to_cart_form = $(this).closest('form.cart');

                if (!$add_to_cart_form.length) {
                    return true;
                }
                const is_combo_update_from_cart = $add_to_cart_form.find('input[name="update-combo"]').length !== 0;
                if (is_combo_update_from_cart) {
                    return true;
                }
                const is_variable = $add_to_cart_form.hasClass('variations_form');
                const is_grouped = $add_to_cart_form.hasClass('grouped_form');
                const is_external = $add_to_cart_form.prop('method') === 'get';
                const is_subscription = $add_to_cart_form.closest("div.product").hasClass("has-subscription-plans");

                if (!is_grouped && !is_external && !is_subscription) {

                    // perform the html5 validation
                    if ($add_to_cart_form[0].checkValidity()) {
                        e.preventDefault();
                    } else {
                        return true;
                    }

                    // If we've chosen unavailable variation don't execute
                    if (!$(this).is('.wc-variation-is-unavailable,.wc-variation-selection-needed')) {
                        const quantity = $add_to_cart_form.find('input[name="quantity"]').val();

                        let product_id;
                        if (is_variable) {
                            product_id = $add_to_cart_form.find('input[name="add-to-cart"]').val();
                        } else {
                            product_id = $add_to_cart_form.find('button[name="add-to-cart"]').val();
                        }

                        const data = { product_id: product_id, quantity: quantity, product_sku: "" };

                        // AJAX add to cart request.
                        const $thisbutton = $(this);

                        // Trigger event.
                        $(document.body).trigger('adding_to_cart', [$thisbutton, data]);

                        //AJAX call
                        $thisbutton.addClass('loading');
                        $thisbutton.prop('disabled', true);

                        const add_to_cart_ajax_data = {};
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
                                        // WooCommerce's own notice markup goes into the page's
                                        // notices area; a page without one falls back to an alert.
                                        const $notices = $('.woocommerce-notices-wrapper').first();
                                        if (results.notices_html && $notices.length) {
                                            $notices.html(results.notices_html);
                                            $notices[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
                                        } else {
                                            alert(results.error_message);
                                        }
                                    } else {
                                        // Trigger event so themes can refresh other areas
                                        $(document.body).trigger('added_to_cart', [results.fragments, results.cart_hash, $thisbutton]);
                                    }
                                }
                            },
                            complete: function() {
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

        // Initialise the small countdowns on products list (the shop card is
        // an <li class="prod_hold">, so match the class on any element).
        lafkaInitSmallCountdowns($('.prod_hold'));

        // if is set infinite load on shop - run it de..
        if (lafka_main_js_params.enable_infinite_on_shop === 'yes') {
            // hide the pagination
            const $pagination = $('#products-wrapper').find('div.pagination');
            $pagination.hide();

            // If enabled load more button
            if (lafka_main_js_params.use_load_more_on_shop === 'yes') {
                $(document.body).on('click', 'div.lafka-shop-pager.lafka-infinite button.lafka-load-more', function() {
                    $(this).hide();
                    $(document.body).find('div.lafka-shop-pager.lafka-infinite a.next_page').trigger("click");
                });
            } else {
                // Track scrolling, hunting for infinite ajax load (throttled)
                let infiniteTicking = false;
                $(window).on("scroll", function() {
                    if (!infiniteTicking) {
                        window.requestAnimationFrame(function() {
                            // Native bbox check replaces the old isInViewport
                            // jQuery selector (:in-viewport). Inside a scroll
                            // throttler, IO would be overkill — bbox is fine.
                            const $pager = $(document.body).find('div.lafka-shop-pager.lafka-infinite');
                            if ($pager.length) {
                                const rect = $pager[0].getBoundingClientRect();
                                const inView = rect.top < window.innerHeight && rect.bottom > 0;
                                if (inView) {
                                    $(document.body).find('div.lafka-shop-pager.lafka-infinite a.next_page').trigger("click");
                                }
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

                const $products = $('#products-wrapper').find('div.box-products.woocommerce');
                const $pageStatus = $pagination.prevAll('.lafka-page-load-status');

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

        // AJAXIFY products listing filters, widgets, etc
        if (lafka_main_js_params.use_product_filter_ajax === 'yes') {
            // products ordering and per page
            const woocommerceOrderingForm = $(document.body).find('form.woocommerce-ordering');
            if (woocommerceOrderingForm.length) {
                woocommerceOrderingForm.on('submit', function(e) {
                    e.preventDefault();
                });

                $(document.body).on('change', 'form.woocommerce-ordering select.orderby, form.woocommerce-ordering select.per_page', function(e) {
                    e.preventDefault();

                    const currentUrlParams = window.location.search;
                    const url = window.location.href.replace(window.location.search, '') + lafkaUpdateUrlParameters(currentUrlParams, woocommerceOrderingForm.serialize());

                    $(document.body).trigger('lafka_products_filter_ajax', [url, woocommerceOrderingForm]);
                });
            }

            // price slider
            $(document.body).find('#lafka-price-filter-form').on('submit', function(e) {
                e.preventDefault();
            });

            $(document.body).on('price_slider_change', function() {
                const form = $('.price_slider').closest('form').get(0);
                const $form = $(form);

                const currentUrlParams = window.location.search;
                const url = $form.prop('action') + lafkaUpdateUrlParameters(currentUrlParams, $form.serialize());

                $(document.body).trigger('lafka_products_filter_ajax', [url, $(this)]);
            });

            // lafka_product_filter
            $(document.body).on('click', 'div.lafka_product_filter a', function(e) {
                e.preventDefault();
                const url = $(this).prop('href');
                $(document.body).trigger('lafka_products_filter_ajax', [url, $(this)]);
            });

            // reset all filters
            $(document.body).on('click', 'a.lafka-reset-filters', function(e) {
                e.preventDefault();
                const url = $(this).prop('href');
                $(document.body).trigger('lafka_products_filter_ajax', [url, $(this)]);
            });
        }

        // Share links
        $(document.body).on('click', 'div.lafka-share-links a', function() {
            window.open(this.href, 'targetWindow', 'toolbar=no,location=0,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=300');
            return false;
        });

        // Show reset button if there are active filters
        $.lafka_handle_active_filters_reset_button();

        // Handle unavailable variations swatches on single product
        $(document.body).find(".variations_form").on("woocommerce_update_variation_values", function() {
            const $swatches = $('.lafka-wcs-swatches');
            $swatches.find('.swatch').removeClass('lafka-not-available');
            $swatches.each(function() {
                const $select = $(this).prev().find('select');
                $(this).find('.swatch').each(function() {
                    if (!$select.find('option[value="' + $(this).data('value') + '"]').length) {
                        $(this).addClass('lafka-not-available');
                    }
                })
            })
        });

        lafkaOrderHoursCountdown();

        // Full-width elements
        lafka_fullwidth_elements();

        // End of document.ready()
    });

    // Handle on Ajax complete global events
    $(document).ajaxComplete(function() {
        lafkaOrderHoursCountdown();
    });

    // Handle the products filtering
    $(document.body).on('lafka_products_filter_ajax', function(e, url) {

        const $products_wrapper = $('#products-wrapper');
        const $products = $products_wrapper.find('div.box-products.woocommerce');
        const $pagination = $products_wrapper.find('div.pagination');
        const $pageStatus = $pagination.prevAll('.lafka-page-load-status');

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
    let resizeTicking = false;
    window.addEventListener('resize', function() {
        if (!resizeTicking) {
            window.requestAnimationFrame(function() {
                checkRevealFooter();
                checkSummaryHeight();
                checkSidebarHeight();
                checkCommentsHeight();
                lafka_fullwidth_elements();
                resizeTicking = false;
            });
            resizeTicking = true;
        }
    });

    /**
     * (Re)start the countdowns inside the given elements — sale timers and the
     * store-closed card (js/lafka-countdown.js, loaded only where that markup
     * renders; a no-op elsewhere).
     * @param {jQuery|Element|string} elements
     */
    window.lafkaInitSmallCountdowns = function(elements) {
        if (typeof window.lafkaCountdown !== 'function') {
            return;
        }
        $(elements).each(function() {
            window.lafkaCountdown(this);
        });
    };

    /**
     * Restart the store opening countdowns anywhere on the page.
     */
    window.lafkaOrderHoursCountdown = function() {
        if (typeof window.lafkaCountdown === 'function') {
            window.lafkaCountdown(document);
        }
    };

    function checkRevealFooter() {
        const isReveal = $('#footer').height() - 1;
        if (isReveal < 550 && $(document.body).hasClass("lafka_fullwidth")) {
            $('html.no-touch body.lafka_fullwidth.lafka-reveal-footer #content').css("margin-bottom", isReveal + "px");
            $('body.lafka_fullwidth.lafka-reveal-footer #footer').addClass('lafka_do_reveal');
        } else {
            $('html.no-touch body.lafka_fullwidth.lafka-reveal-footer #content').css("margin-bottom", 0 + "px");
            $('body.lafka_fullwidth.lafka-reveal-footer #footer').removeClass('lafka_do_reveal');

        }
    }

    function checkSummaryHeight() {
        const $lafkaSummaryHeight = $('.lafka-product-summary-wrapper div.summary').height();
        const $lafkaVisibleHeight = $(window).height();
        const current_window_width = $(window).width();
        const $body_summary = $("body, .lafka-product-summary-wrapper div.summary");
        if ($lafkaSummaryHeight < $lafkaVisibleHeight - 250 && current_window_width > 768) {
            $body_summary.addClass("lafka-sticky-summary");
        } else {
            $body_summary.removeClass("lafka-sticky-summary");
        }
    }

    function checkSidebarHeight() {
        const $lafkaSidebarHeight = $('.sidebar').height();
        const $lafkaVisibleHeight = $(window).height();
        const current_window_width = $(window).width();
        const $body_sidebar = $("body, .sidebar");
        if ($lafkaSidebarHeight < $lafkaVisibleHeight - 250 && current_window_width > 768) {
            $body_sidebar.addClass("lafka-sticky-sidebar");
        } else {
            $body_sidebar.removeClass("lafka-sticky-sidebar");
        }
    }

    function checkCommentsHeight() {
        const $lafkaCommentsHeight = $('body.single-post #comments > #respond.comment-respond').height();
        const $lafkaVisibleHeight = $(window).height();
        const $body_summary = $("body.single-post #comments > #respond.comment-respond");
        if ($lafkaCommentsHeight < $lafkaVisibleHeight - 200) {
            $body_summary.addClass("lafka-sticky-comments");
        } else {
            $body_summary.removeClass("lafka-sticky-comments");
        }
    }

    function lafka_fullwidth_elements() {
        const $elements = $('#content:not(.has-sidebar) #products-wrapper .woocommerce-tabs.wc-tabs-wrapper, #content:not(.has-sidebar) p.woocommerce-thankyou-order-received, body.single-post #content:not(.has-sidebar) #comments, body.page #content:not(.has-sidebar) #comments, #content:not(.has-sidebar) ul.woocommerce-order-overview.woocommerce-thankyou-order-details.order_details');
        const $rtl = $('body.rtl');
        const $contentDiv = $('#content');

        if ($contentDiv.length) {
            // P6-PERF-8: Hoist ALL geometry reads outside the .each() loop.
            // Old pattern called $contentDiv.width() twice and .css("width") once
            // per iteration, each forcing a layout recalc after the previous
            // iteration's .css() write had invalidated the layout.
            const row_padding = 40;
            const contentWidth = $contentDiv.width();
            const contentOffset = -(contentWidth - parseFloat($('#content > .inner').css("width"))) / 2 - row_padding + 15;

            $elements.each(function() {
                $(this).css({
                    'position': 'relative',
                    'box-sizing': 'border-box',
                    'width': contentWidth,
                    'padding-left': Math.abs(contentOffset),
                    'padding-right': Math.abs(contentOffset)
                });

                if ($rtl.length && !($(this).prop('id') === 'comments')) {
                    $(this).css({ 'right': contentOffset });
                } else {
                    $(this).css({ 'left': contentOffset });
                }
            });
        }
    }


    // Showing loader
    jQuery.lafka_show_loader = function() {

        let overlay;
        const $shopbypricefilter_overlay = $('.shopbypricefilter-overlay');
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

        // P6-PERF-8: Batch reads before writes inside the loader .each() to
        // prevent read-after-write thrash. The old code set max-height/max-width
        // on the img then immediately read img.width() — forcing a layout flush
        // so the browser could return the post-constraint width.
        // Fix: read window geometry once before the loop; read overlay geometry
        // in a dedicated read pass before applying any CSS writes.
        const winHeight = $(window).outerHeight();
        const winWidth  = $(window).outerWidth();
        $shopbypricefilter_overlay.each(function() {
            const overlay = this;
            let img;

            if ($('img', overlay).length) {
                img = $('img', overlay);
            } else {
                img = $('<img id="price_fltr_loading_gif" src="' + lafka_main_js_params.img_path + 'loading3.gif" />').prependTo(overlay);
            }

            // Read phase: capture overlay dimensions before writing anything.
            const overlayHeight = $(overlay).height();
            const overlayWidth  = $(overlay).width();

            // Write phase: all CSS writes together; no reads in between.
            $(img).css({
                'max-height': overlayHeight * 0.8,
                'max-width':  overlayWidth  * 0.8,
                'position': 'fixed',
                'top':  winHeight / 2,
                'left': (winWidth - overlayWidth * 0.8) / 2
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
        const $lafka_product_filters = $(document.body).find('div.lafka_product_filter');
        const $new_lafka_product_filters = $(response).find('div.lafka_product_filter');

        if ($lafka_product_filters.length > $new_lafka_product_filters.length) {
            const existing_titles = [];
            const found_titles = [];

            $lafka_product_filters.each(function() {
                const $curr_elmnt = $(this);
                const title = $curr_elmnt.find('h3:first-of-type').html();
                existing_titles.push(title);

                $new_lafka_product_filters.each(function() {
                    if ($(this).find('h3:first-of-type').html() === title) {
                        $curr_elmnt.html($(this).html());
                        found_titles.push(title);
                    }
                });
            });

            for (let i = 0; i < existing_titles.length; i++) {
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

        const $price_slider_form = $(document).find('#lafka-price-filter-form');
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
        const $reset_button = $(document).find('div.lafka-filter-widgets-holder a.lafka-reset-filters');
        if (typeof $reset_button !== 'undefined') {
            let show_reset_button = false;

            const lafka_reset_query = $reset_button.data('lafka_reset_query');
            if (window.location.href.indexOf('?') !== -1) {
                const right_side_of_the_url = window.location.href.substr(window.location.href.indexOf('?'));
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
        $('#lafka-filter-widgets > div.one_half').filter(function(index) {
            return index % 2 === 1;
        }).addClass('last').after('<div class="clear"></div>');

        // Woocommerce part columns
        $('.woocommerce.columns-2').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 2 === 1;
            }).addClass('last').after('<div class="clear"></div>');
        });

        $('.woocommerce.columns-3').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 3 === 2;
            }).addClass('last').after('<div class="clear"></div>');
        });

        $('.woocommerce.columns-4').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 4 === 3;
            }).addClass('last').after('<div class="clear"></div>');
        });
        $('.woocommerce.columns-5').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 5 === 4;
            }).addClass('last').after('<div class="clear"></div>');
        });
        $('.woocommerce.columns-6').each(function() {
            $(this).find('div.prod_hold, .product-category').filter(function(index) {
                return index % 6 === 5;
            }).addClass('last').after('<div class="clear"></div>');
        });
    };

    // Refresh products list after ajax calls
    jQuery.lafka_refresh_products_after_ajax = function(response, $products, $pagination, $pageStatus) {

        const $newProducts = $(response).find('.content_holder').find('.prod_hold');
        const $pagination_html = $(response).find('.lafka-shop-pager .pagination').html();

        if (typeof $pagination_html === 'undefined') {
            $pagination.html('');
        } else {
            $pagination.html($pagination_html);
        }


        // Do the necessary for the appending products. GX T-25: imagesloaded
        // is not loaded on the counter surfaces — mark them loaded directly.
        const markLoaded = function() {
            $newProducts.each(function() {
                $(this).addClass('lafka-infinite-loaded');
            });
        };
        if (typeof $.fn.imagesLoaded === 'function') {
            $newProducts.imagesLoaded(markLoaded);
        } else {
            markLoaded();
        }

        // Now add the new products to the list
        $products.append($newProducts);

        lafkaInitSmallCountdowns($newProducts);

        // Woocommerce part columns
        $('.woocommerce.columns-2 div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 2 === 1;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-3 div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 3 === 2;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-4 div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 4 === 3;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-5 div.prod_hold').filter(function(index) {
            if ($(this).next().hasClass('clear')) {
                return false;
            } else {
                return index % 5 === 4;
            }
        }).addClass('last').after('<div class="clear"></div>');
        $('.woocommerce.columns-6 div.prod_hold').filter(function(index) {
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

    const newParamsObj = {};
    newParams.split('&').forEach(function(x) {
        const arr = x.split('=');
        arr[1] && (newParamsObj[arr[0]] = arr[1]);
    });

    for (const prop in newParamsObj) {
        // remove the hash part before operating on the uri
        const i = currentParams.indexOf('#');
        const hash = i === -1 ? '' : currentParams.substr(i);
        currentParams = i === -1 ? currentParams : currentParams.substr(0, i);

        const re = new RegExp("([?&])" + prop + "=.*?(&|$)", "i");
        const separator = "&";
        if (currentParams.match(re)) {
            currentParams = currentParams.replace(re, '$1' + prop + "=" + newParamsObj[prop] + '$2');
        } else {
            currentParams = currentParams + separator + prop + "=" + newParamsObj[prop];
        }
        currentParams + hash; // finally append the hash as well
    }

    return currentParams;
}