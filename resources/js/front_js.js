jQuery(document).ready(function ($) {

    // check if class .mwc_items_div exists and bail if not
    if (!$('.mwc_items_div').length) {
        return;
    }

    /**
     * Progress bar animation
     */
    let progress = '100%';

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

        let parent = $(this).parents('.mwc_item_div');
        let id_product = $(this).val();
        let i_index = $(this).data('index');

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

        // debug
        // console.log('var_prod_attr change');

        // vars
        let reg_price_total = 0, disc_perc = 0, disc_mp = 0, bundle_id, currency_sym, old_price_html;

        // get bundle id
        bundle_id = $(this).parents('.mwc_product_variations').attr('data-bundle_id');

        // loop through each associated variation dropdown
        $(this).parents('.mwc_product_variations').find('.var_prod_attr').each(function (i, e) {

            // get bundle id
            bundle_id = $(this).parents('.mwc_product_variations').attr('data-bundle_id');

            // get discount percentage
            disc_perc = parseFloat($('.mwc_item_div_' + bundle_id).attr('data-coupon'));

            // get variation data
            let variation_data = JSON.parse(atob($(e).attr('data-variations')));

            // get currently selected value
            let this_val = $(e).val();

            // loop through variation_data, fund matching attribute, extract regular price from variation_data and add together to get bundle total
            variation_data.forEach(element => {

                let attrib_string = '';
                let attribs = element.attributes;

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
        $('.mwc_item_div_' + bundle_id).find('.mwc_bundle_price_hidden').val(reg_price_total - (reg_price_total * (disc_perc / 100)));

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
        let img_src = $(this).attr('img-src');

        // set image src
        $(this).parents('.c_prod_item').find('.mwc_variation_img').attr('src', img_src);

        // retrieve product id
        let prod_id = $(this).data('linked_id');

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
     * Set summary prices default bun
     */
    function mwc_set_summary_prices_default_bun() {

        // return;

        let $ = jQuery;

        let has_template_h = $('.template_h').length,
            product_ids = [],
            var_attribs = [],
            bundle_id = $('.mwc_item_div.mwc_active_product').data('bundle_id');

        // discount/coupon value
        let disc_perc = parseFloat($('.mwc_item_div.mwc_active_product').attr('data-coupon'));

        $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function (index, element) {
            product_ids.push($(this).attr('data-id'));
            var_attribs.push($(this).find('.var_prod_attr').val());

            // change all types to int
            product_ids = product_ids.map(Number);
            var_attribs = var_attribs.map(Number);

        });

        // product qty
        let product_qty = $('.mwc_item_div.mwc_active_product .mwc_bundle_product_qty_hidden').val();

        // sale price
        let sale_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_hidden');

        // Old Price
        let old_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_regular_hidden');

        // bundle label
        let bundle_label = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_hidden').attr('data-label');

        $('.mwc_items_div #order_summary').addClass('mwc_loading');

        // addons
        if ($('.mwc_upsell_product_wrap').length) {

            addon_label = $('.mwc_upsell_product_wrap').data('label');

            $('.mwc_item_addon.i_selected').each(function (i, e) {
                let e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
                let e_qty = parseFloat($(e).find('.addon_prod_qty').val());
                addon_price += e_price * e_qty;
            });
        }

        // price_list['addon_price'] = {
        //     sum: 1,
        //     label: addon_label,
        //     price: addon_price
        // }

        // setup and send AJAX request
        let ajaxurl = mwc_ajax_obj.ajax_url;

        let data = {
            'action': 'mwc_get_price_summary_table',
            '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
            // 'price_list': price_list,
            'bundle_id': bundle_id,
            'product_qty': product_qty,
            'product_ids': product_ids,
            'var_attribs': var_attribs,
            'disc_perc': disc_perc,
            'sale_price': sale_price.val(),
            'old_price': old_price.val(),
            'bundle_label': bundle_label
        };

        $.post(ajaxurl, data, function (response) {

            let result = response;

            // console.log(response);
            // return;

            if (result.status !== false) {

                let img = $('.mwc_item_div.mwc_active_product').find('.op_c_package_image img').attr('src');

                $('#s_image').find('img').attr('src', img);
                $('.mwc_summary_table').empty();
                $('.mwc_summary_table').append(result.html);
                $('#order_summary').show();

                // if is product bundle
                if (result.is_bundle) {

                    $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append((result.old_total));
                    $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append(result.mc_total);

                    // if is 'off' or 'free'
                } else {

                    console.log(result);

                    $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append(result.p_price);
                    $('.mwc_item_div_' + bundle_id).find('.mwc-total-price').empty().append(result.old_total + ' ' + result.mc_total);
                }

                setTimeout(function () {
                    $('.mwc_items_div #order_summary').removeClass('mwc_loading');
                }, 500);

            } else {
                console.log('Price summary table HTML could not be fetched.');
            }

        });

    }

    /**
     * Set summary prices on click
     */
    function mwc_set_summary_prices() {

        console.log('wtf');

        let $ = jQuery;

        let has_template_h = $('.template_h').length,
            product_ids = [],
            var_attribs = [],
            bundle_id = $('.mwc_item_div.mwc_active_product').data('bundle_id');

        // discount/coupon value
        let disc_perc = parseFloat($('.mwc_item_div.mwc_active_product').attr('data-coupon'));

        // Template H
        $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function (index, element) {
            product_ids.push($(this).attr('data-id'));
            var_attribs.push($(this).find('.var_prod_attr').val());

            // change all types to int
            product_ids = product_ids.map(Number);
            var_attribs = var_attribs.map(Number);

        });

        // product qty
        let product_qty = $('.mwc_item_div.mwc_active_product .mwc_bundle_product_qty_hidden').val();

        // sale price
        let sale_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_hidden');

        // Old Price
        let old_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_regular_hidden');

        // bundle label
        let bundle_label = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_hidden').attr('data-label');

        $('.mwc_items_div #order_summary').addClass('mwc_loading');

        // addons
        if ($('.mwc_upsell_product_wrap').length) {

            addon_label = $('.mwc_upsell_product_wrap').data('label');

            $('.mwc_item_addon.i_selected').each(function (i, e) {
                let e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
                let e_qty = parseFloat($(e).find('.addon_prod_qty').val());
                addon_price += e_price * e_qty;
            });
        }

        // setup and send AJAX request
        let ajaxurl = mwc_ajax_obj.ajax_url;

        let data = {
            'action': 'mwc_get_price_summary_table',
            '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
            'bundle_id': bundle_id,
            'product_qty': product_qty,
            'product_ids': product_ids,
            'var_attribs': var_attribs,
            'disc_perc': disc_perc,
            'sale_price': sale_price.val(),
            'old_price': old_price.val(),
            'bundle_label': bundle_label
        };

        $.post(ajaxurl, data, function (response) {

            let result = response;

            console.log(response);

            // return;

            if (result.status !== false) {

                let img = $('.mwc_item_div.mwc_active_product').find('.op_c_package_image img').attr('src');

                $('#s_image').find('img').attr('src', img);
                $('.mwc_summary_table').empty();
                $('.mwc_summary_table').append(result.html);
                $('#order_summary').show();

                // if is product bundle
                if (result.is_bundle) {

                    $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append((result.old_total));
                    $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append(result.mc_total);

                    // if is 'off' or 'free'
                } else {

                    $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append(result.p_price);
                    $('.mwc_item_div_' + bundle_id).find('.mwc-total-price').empty().append(result.old_total + ' ' + result.mc_total);
                }

                setTimeout(function () {
                    $('.mwc_items_div #order_summary').removeClass('mwc_loading');
                }, 500);

            } else {
                console.log('Price summary table HTML could not be fetched.');
            }

        });

    }

    /**
     * Round to 2 decimals
     */
    function roundToTwo(num) {
        return +(Math.round(num + "e+2") + "e-2");
    }

    /**
     * Trigger mini cart refresh each time ajax is complete
     */
    jQuery(document).ajaxComplete(function (event, xhr, settings) {

        // console.log(settings.data.split('&'));

        if (settings.data.split('&')[0] === 'action=mwc_get_price_summary_table' || settings.data.split('&')[0] === 'action=mwc_atc_linked_products') {

            jQuery.post({
                url: wc_cart_fragments_params.ajax_url,
                data: { action: 'woocommerce_get_refreshed_fragments' },
                success: function (data) {
                    if (data && data.fragments) {
                        jQuery.each(data.fragments, function (key, value) {
                            jQuery(key).replaceWith(value);
                        });
                    }
                }
            });

        }

    });

    /**
     * Prepend size guide svg to size guide link
     */
    $('.sbhtml_link_text').prepend('<svg class="sbhtml-chart-svg" data-v-6b417351="" width="24" viewBox="0 -4 34 30" xmlns="http://www.w3.org/2000/svg" class="w-3 w-6"><path d="M32.5 11.1c-.6 0-1 .4-1 1v11.8h-1.9v-5.4c0-.6-.4-1-1-1s-1 .4-1 1v5.4h-3.7v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6h-3.7v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6h-4.1v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6H6.4v-5.4c0-.6-.4-1-1-1s-1 .4-1 1v5.4H2.5V12.1c0-.6-.4-1-1-1s-1 .4-1 1v12.8c0 .6.4 1 1 1h31c.6 0 1-.4 1-1V12.1c0-.6-.4-1-1-1z" fill="#666666"></path><path d="M27.1 12.4v-.6c0-.1-.1-.1-.1-.2l-2.6-3c-.4-.6-1-.6-1.5-.3-.4.4-.5 1-.1 1.4L24 11H10l1.2-1.3c.4-.4.3-1-.1-1.4-.5-.3-1.1-.3-1.5.1l-2.6 3s0 .1-.1.1l-.1.1c0 .1-.1.1-.1.2v.2c0 .1 0 .1.1.2 0 .1.1.1.1.1s0 .1.1.1l2.6 3c.2.2.5.3.8.3.2 0 .5-.1.7-.2.4-.4.5-1 .1-1.4l-1.2-1h14l-1.2 1.3c-.4.4-.3 1 .1 1.4.2.2.4.2.7.2.3 0 .6-.1.8-.3l2.6-3c0-.1.1-.1.1-.2v-.1z" fill="#666666"></path></svg>');


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

    /**
     * Trigger click on default bundle when all ajax requests have stopped on page load
     */

    // $('.mwc_selected_default_opt').trigger('click');

    // $(document.body).off('updated_cart_totals');
    // $(document.body).off('wc_fragments_refresh');
    // $(document.body).off('added_to_cart');

});