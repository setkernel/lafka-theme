jQuery( function( $ ) {
    "use strict";

    /**************************
     * "lafka-price-slider"
     **************************/
    jQuery.lafka_build_price_slider = function() {

        var $body = $('body');
        var $price_slider_form = $body.find('#lafka-price-filter-form');

        // Price slider uses jquery ui
        var min_price = $price_slider_form.find('#min_price').data('min');
        var max_price = $price_slider_form.find('#max_price').data('max');
        var step      = $( '.price_slider_amount' ).data( 'step' ) || 1

        var current_min_price = parseInt($price_slider_form.find('#min_price').val(), 10);
        var current_max_price = parseInt($price_slider_form.find('#max_price').val(), 10);

        if(current_min_price < min_price) {
            current_min_price = min_price;
        }
        if(current_max_price > max_price) {
            current_max_price = max_price;
        }

        var currency_pos = $price_slider_form.data('currency_pos');
        var currency_symbol = $price_slider_form.data('currency_symbol');

        $body.on('price_slider_create price_slider_slide', function (event, min, max) {
            if (currency_pos === "left") {

                $price_slider_form.find("span.from", "#lafka_price_range").html(currency_symbol + min);
                $price_slider_form.find("span.to", "#lafka_price_range").html(currency_symbol + max);

            } else if (currency_pos === "left_space") {

                $price_slider_form.find("span.from", "#lafka_price_range").html(currency_symbol + " " + min);
                $price_slider_form.find("span.to", "#lafka_price_range").html(currency_symbol + " " + max);

            } else if (currency_pos === "right") {

                $price_slider_form.find("span.from", "#lafka_price_range").html(min + currency_symbol);
                $price_slider_form.find("span.to", "#lafka_price_range").html(max + currency_symbol);

            } else if (currency_pos === "right_space") {

                $price_slider_form.find("span.from", "#lafka_price_range").html(min + " " + currency_symbol);
                $price_slider_form.find("span.to", "#lafka_price_range").html(max + " " + currency_symbol);

            }

            $body.trigger('price_slider_updated', min, max);
        });

        $price_slider_form.find('div.price_slider').slider({
            range: true,
            animate: true,
            min: min_price,
            max: max_price,
            step: step,
            values: [current_min_price, current_max_price],
            create: function (event, ui) {

                $price_slider_form.find("#min_price").val(current_min_price);
                $price_slider_form.find("#max_price").val(current_max_price);

                $body.trigger('price_slider_create', [current_min_price, current_max_price]);
            },
            slide: function (event, ui) {

                $price_slider_form.find("#min_price").val(ui.values[0]);
                $price_slider_form.find("#max_price").val(ui.values[1]);

                $body.trigger('price_slider_slide', [ui.values[0], ui.values[1]]);
            },
            change: function (event, ui) {

                $body.trigger('price_slider_change', [ui.values[0], ui.values[1]]);

            },
            stop: function (event, ui) {
                $.lafka_show_loader();

                setTimeout(function () {
                    $price_slider_form.trigger("submit");
                }, 300);

            }
        });

        // price slider
        $price_slider_form.on('submit', function (e) {
            e.preventDefault();
        });

        $(document.body).on('price_slider_change', function (event, ui) {
            var form = $('.price_slider').closest('form').get(0);
            var $form = $(form);

            var currentUrlParams = window.location.search;
            var url = $form.attr('action') + lafkaUpdateUrlParameters(currentUrlParams, $form.serialize());

            $(document.body).trigger('lafka_products_filter_ajax', [url, $(this)]);
        });
    };

    $.lafka_build_price_slider();

    /**************************
     * END "lafka-price-slider"
     **************************/
})