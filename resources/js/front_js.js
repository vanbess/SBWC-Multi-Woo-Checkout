jQuery(document).ready(function ($) {

    // check if class .mwc_items_div exists and bail if not
    if (!$('.mwc_items_div').length) {
        return;
    }

    /**
     * Progress bar animation
     */
    var progress = '100%';

    $('.loadingMessageContainerWrapper .bar').animate({
        width: progress,
    }, {
        duration: 6000,
        step: function (now, fx) {
            if (now >= 25) {
                $('.counter .steps1').hide();
                $('.counter .steps2').show();
            }
            if (now >= 50) {
                $('.counter .steps2').hide();
                $('.counter .steps3').show();
            }
            if (now >= 75) {
                $('.counter .steps3').hide();
                $('.counter .steps4').show();
            }
        },
        complete: function () {
            $('.loadingMessageContainerWrapper').hide();
        }
    });

    /**
     * Load correct linked product swatch on page load
     */
    $('.linked_product').each(function () {
        if ($(this).hasClass('selected')) {
            setTimeout(() => {
                $(this).parents('.c_prod_item').find('.mwc_variation_img').attr('src', $(this).attr('img-src'));
            }, 200);
        }
    });



    /**
     * Package on change
     */
    $('.mwc_package_checkbox').change(function () {

        var parent = $(this).parents('.mwc_item_div');
        var id_product = $(this).val();
        var i_index = $(this).data('index');

        $('.mwc_item_config_div_variation').hide();

        $('.product_mwc_id_' + id_product + '_' + i_index).show();

        $('.mwc_item_div').removeClass('mwc_active_product');

        if ($(this).is(':checked')) {

            $(this).parents('.mwc_item_div').addClass('mwc_active_product');

            if ($('#shortcode_type').val() == 'op_c') {

                $('.select_button').removeClass('op_c_btn_selected');
                $('.op_c_desc').hide();

                mwc_set_summary_prices();

            } else {
                $('.btn-select').removeClass('btn_selected');
            }

        } else {
            $(this).parents('.mwc_item_div').removeClass('mwc_active_product');
        }

        if ($('.mwc_active_product .mwc_product_attribute').length) {
            $('.mwc_active_product .mwc_product_attribute').first().change();
        } else {
            $('.i_mwc_pack_variations_intro_div').hide();
            $('.step').css('padding-bottom', '0px');
        }

        $('.option_item').removeClass('option_active');
        $('#opt_item_' + id_product + '_' + i_index).addClass('option_active');

        // show form select product variations
        // $('.mwc_product_variations').hide();
        // if ($(this).parents('.mwc_item_div').find('.mwc_product_variations .product_variations_table tr td .variation_item').length > 0) {
        //     // $(this).parents('.mwc_item_div').find('.mwc_product_variations').slideDown();
        // }


    });

    /**
     * Update cart summary if default bundle is specified.
     */
    $('.mwc_item_div').each(function (index, element) {

        // if element has default bundle/option class...
        if ($(element).hasClass('mwc_selected_default_opt')) {

            // ...add active product class to it and...
            $(element).addClass('mwc_active_product');

            // show hover image if found
            if ($(this).find('.hover-image').length) {
                $(this).find('.hover-image').show();
                $(this).find('.attachment-woocommerce_thumbnail').hide();
            }


            // ...set summary prices
            mwc_set_summary_prices_default_bun();
        }

        // on click
        $(element).on('click', function () {

            $('.mwc_item_div').find('.hover-image').hide();
            $('.mwc_item_div').find('.attachment-woocommerce_thumbnail').show();

            if ($(this).find('.hover-image').length) {
                $(this).find('.hover-image').show();
                $(this).find('.attachment-woocommerce_thumbnail').hide();
            }

            $('.mwc_item_div').removeClass('mwc_active_product');

            $(element).addClass('mwc_active_product');

            $(element).find('.select_button').addClass('op_c_btn_selected');

            mwc_set_summary_prices();

        })
    });

    /**
     * Update cart summary on variation dropdown change for variations with different prices
     */
    $('.var_prod_attr').change(function () {

        // vars
        var reg_price_total = 0, disc_perc = 0, disc_mp = 0, bundle_id, currency_sym, old_price_html;

        // get bundle id
        bundle_id = $(this).parents('.mwc_product_variations').attr('data-bundle_id');

        // loop through each associated variation dropdown
        $(this).parents('.mwc_product_variations').find('.var_prod_attr').each(function (i, e) {

            // get bundle id
            bundle_id = $(this).parents('.mwc_product_variations').attr('data-bundle_id');

            // get discount percentage
            disc_perc = parseFloat($('.mwc_item_div_' + bundle_id).attr('data-coupon'));

            // get variation data
            var variation_data = JSON.parse(atob($(e).attr('data-variations')));

            // get currently selected value
            var this_val = $(e).val();

            // loop through variation_data, fund matching attribute, extract regular price from variation_data and add together to get bundle total
            variation_data.forEach(element => {

                var attrib_string = '';
                var attribs = element.attributes;

                $.each(attribs, function (key, val) {
                    attrib_string = val;
                });

                if (attrib_string === this_val) {
                    reg_price_total = reg_price_total + parseFloat(element.display_regular_price);
                }

            });

        });

        // get current currency symbol
        currency_sym = $('#summ-old-price .woocommerce-Price-currencySymbol').text();

        // prepare new old price html
        old_price_html = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">' + currency_sym + '</span>' + reg_price_total.toFixed(2) + '</bdi></span>'

        // set hidden bundle price to new discount price
        $('.mwc_item_div_' + bundle_id).find('.mwc_bundle_price_hidden').val(reg_price_total * ($disc_perc / 100));

        // trigger bundle summary prices update
        mwc_set_summary_prices();

        // once above summary prices update is complete, append new old price html to relevant spot in summary table
        $(document).ajaxComplete(function (event, xhr, settings) {
            if (settings.data.split('&')[0] === 'action=mwc_get_price_summary_table') {
                $('#summ-old-price').empty().append(old_price_html);
            }
        });

    });

    /**
     * Update cart summary on linked product click
     */
    $('.linked_product').on('click', function () {

        // remove selected class
        $(this).parent().find('.linked_product').removeClass('selected');

        // add selected class
        $(this).addClass('selected');

        // get image src
        var img_src = $(this).attr('img-src');

        // set image src
        $(this).parents('.c_prod_item').find('.mwc_variation_img').attr('src', img_src);

        // retrieve product id
        var prod_id = $(this).data('linked_id');

        // update c_prod_item with product id
        $(this).parents('.c_prod_item').attr('data-id', prod_id);

        mwc_set_summary_prices();

    });

    /**
     * Update cart summary on add-on select/deselect
     */
    $('.mwc_checkbox_addon').click(function () {
        mwc_set_summary_prices();
    });

    /**
     * Update cart on add-on qty change
     */
    $('.addon_prod_qty').change(function () {
        mwc_set_summary_prices();
    });

    /**
     * Update bundle pricing on page load/bundle click
     */
    jQuery.fn.getPriceTotalAndDiscountBundleOption = function () {

        var parent = $(this).parents('.mwc_item_div');

        if (parent.data('type') == "bun") {

            // get product ids
            var discountProductIDs = $(this).getDiscountProductIDs();

            // send ajax request
            var ajaxurl = mwc_ajax_obj.ajax_url;

            var data = {
                'action': 'mwc_get_price_package',
                '_ajax_nonce': $(this).parents('.mwc_item_div').data('nonce'),
                'product_ids': discountProductIDs.products,
                'discount': discountProductIDs.discount
            };

            // Send AJAX request and update
            $.post(ajaxurl, data, function (response) {

                // console.log(response);

                parent.find('.js-input-price_package').val(JSON.stringify(response));

                // change label price
                parent.find('.js-label-price_each').empty().append(response.each_price_html);
                parent.find('.js-label-price_total').empty().append(response.total_price_html);
                parent.find('.js-label-price_old').empty().append(response.old_price_html);

                // set price summary
                parent.find('.mwc_bundle_price_hidden').val(response.total_price);
                parent.find('.mwc_bundle_price_regular_hidden').val(response.old_price);

                if (parent.hasClass('mwc_active_product')) {
                    mwc_set_summary_prices();
                }
            });

        }
    }

    /**
     * Gets/sets summary prices for default bundles
     */
    function mwc_set_summary_prices_default_bun() {

        var $ = jQuery;

        // price list
        var price_list = {};

        // product qty
        var product_qty = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_product_qty_hidden').val();

        // sale price
        var sale_price = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_price_hidden');

        price_list['sale_price'] = {
            sum: 1,
            label: sale_price.data('label'),
            price: sale_price.val()
        }

        // Old Price
        var old_price = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_price_regular_hidden');

        price_list['old_price'] = {
            sum: 0,
            label: old_price.data('label'),
            price: old_price.val()
        }

        // Add-ons price
        var addon_label = '';
        var addon_price = 0;

        if ($('.mwc_upsell_product_wrap').length) {

            addon_label = $('.mwc_upsell_product_wrap').data('label');

            $('.mwc_item_addon.i_selected').each(function (i, e) {
                var e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
                var e_qty = parseFloat($(e).find('.addon_prod_qty').val());
                addon_price += e_price * e_qty;
            });
        }

        price_list['addon_price'] = {
            sum: 1,
            label: addon_label,
            price: addon_price
        }

        // setup and send AJAX request
        var ajaxurl = mwc_ajax_obj.ajax_url;

        var data = {
            'action': 'mwc_get_price_summary_table',
            '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
            'price_list': price_list,
            'bundle_id': $('.mwc_item_div.mwc_selected_default_opt').data('bundle_id')
        };

        $.post(ajaxurl, data, function (response) {

            // console.log(response);
            //  return;

            var result = response;

            if (result.status !== false) {
                var img = $('.mwc_item_div.mwc_selected_default_opt').find('.op_c_package_image img').attr('src');
                $('#s_image').find('img').attr('src', img);
                $('.mwc_summary_table').empty();
                $('.mwc_summary_table').append(result.html);
                $('#order_summary').show();

                setTimeout(function () {
                    $('.mwc_items_div #order_summary').removeClass('mwc_loading');
                }, 500);

            } else {
                console.log('Price summary table HTML could not be fetched.');
            }

        });

    }

    /**
     * Set summary prices
     */
    function mwc_set_summary_prices() {

        var $ = jQuery;

        // price list
        var price_list = {};

        // product qty
        var product_qty = $('.mwc_item_div.mwc_active_product .mwc_bundle_product_qty_hidden').val();

        $('.mwc_items_div #order_summary').addClass('mwc_loading');

        // sale price
        var sale_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_hidden');

        price_list['sale_price'] = {
            sum: 1,
            label: sale_price.data('label'),
            price: sale_price.val()
        }

        // Old Price
        var old_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_regular_hidden');

        price_list['old_price'] = {
            sum: 0,
            label: old_price.data('label'),
            price: old_price.val()
        }

        // addon price
        var addon_label = '';
        var addon_price = 0;

        if ($('.mwc_upsell_product_wrap').length) {

            addon_label = $('.mwc_upsell_product_wrap').data('label');

            $('.mwc_item_addon.i_selected').each(function (i, e) {
                var e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
                var e_qty = parseFloat($(e).find('.addon_prod_qty').val());
                addon_price += e_price * e_qty;
            });
        }

        price_list['addon_price'] = {
            sum: 1,
            label: addon_label,
            price: addon_price
        }

        // setup and send AJAX request
        var ajaxurl = mwc_ajax_obj.ajax_url;

        var data = {
            'action': 'mwc_get_price_summary_table',
            '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
            'price_list': price_list,
            'bundle_id': $('.mwc_item_div.mwc_active_product').data('bundle_id')
        };

        $.post(ajaxurl, data, function (response) {

            // var result = JSON.parse(response);
            var result = response;

            // console.log(response);

            if (result.status !== false) {
                var img = $('.mwc_item_div.mwc_active_product').find('.op_c_package_image img').attr('src');
                $('#s_image').find('img').attr('src', img);
                $('.mwc_summary_table').empty();
                $('.mwc_summary_table').append(result.html);
                $('#order_summary').show();

                setTimeout(function () {
                    $('.mwc_items_div #order_summary').removeClass('mwc_loading');
                }, 500);

            } else {
                console.log('Price summary table HTML could not be fetched.');
            }

        });

    }

    /**
     * Hide/show hover images
     */
    $(document).find('.mwc_item_div').on('mouseenter', function () {
        if (!$(this).hasClass('mwc_active_product')) {
            if ($(this).find('.hover-image').length) {
                $(this).find('.hover-image').show();
                $(this).find('.attachment-woocommerce_thumbnail').hide();
            }
        }
    });

    $(document).find('.mwc_item_div').on('mouseleave', function () {
        if (!$(this).hasClass('mwc_active_product')) {
            if ($(this).find('.hover-image').length) {
                $(this).find('.hover-image').hide();
                $(this).find('.attachment-woocommerce_thumbnail').show();
            }
        }
    });

});