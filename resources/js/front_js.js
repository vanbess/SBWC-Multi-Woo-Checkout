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

        // debug
        console.log('var_prod_attr change');

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
     * Update bundle pricing on page load/bundle click
     */
    // jQuery.fn.getPriceTotalAndDiscountBundleOption = function () {

    //     let parent = $(this).parents('.mwc_item_div');

    //     if (parent.data('type') == "bun") {

    //         // get product ids
    //         let discountProductIDs = $(this).getDiscountProductIDs();

    //         // send ajax request
    //         let ajaxurl = mwc_ajax_obj.ajax_url;

    //         let data = {
    //             'action': 'mwc_get_price_package',
    //             '_ajax_nonce': $(this).parents('.mwc_item_div').data('nonce'),
    //             'product_ids': discountProductIDs.products,
    //             'discount': discountProductIDs.discount
    //         };

    //         // Send AJAX request and update
    //         $.post(ajaxurl, data, function (response) {

    //             // console.log(response);

    //             parent.find('.js-input-price_package').val(JSON.stringify(response));

    //             // change label price
    //             parent.find('.js-label-price_each').empty().append(response.each_price_html);
    //             parent.find('.js-label-price_total').empty().append(response.total_price_html);
    //             parent.find('.js-label-price_old').empty().append(response.old_price_html);

    //             // set price summary
    //             parent.find('.mwc_bundle_price_hidden').val(response.total_price);
    //             parent.find('.mwc_bundle_price_regular_hidden').val(response.old_price);

    //             if (parent.hasClass('mwc_active_product')) {
    //                 mwc_set_summary_prices();
    //             }
    //         });

    //     }
    // }

    /**
     * Gets/sets summary prices for default bundles
     */
    function mwc_set_summary_prices_default_bun() {

        let $ = jQuery;

        // price list
        let price_list = {};

        // product qty
        let product_qty = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_product_qty_hidden').val();

        // sale price
        let sale_price = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_price_hidden');

        // discount/coupon value
        let disc_perc = parseFloat($('.mwc_item_div.mwc_active_product').attr('data-coupon'));

        price_list['sale_price'] = {
            sum: 1,
            label: sale_price.data('label'),
            price: sale_price.val()
        }

        // Old Price
        let old_price = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_price_regular_hidden');

        price_list['old_price'] = {
            sum: 0,
            label: old_price.data('label'),
            price: old_price.val()
        }

        // Add-ons price
        let addon_label = '';
        let addon_price = 0;

        if ($('.mwc_upsell_product_wrap').length) {

            addon_label = $('.mwc_upsell_product_wrap').data('label');

            $('.mwc_item_addon.i_selected').each(function (i, e) {
                let e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
                let e_qty = parseFloat($(e).find('.addon_prod_qty').val());
                addon_price += e_price * e_qty;
            });
        }

        price_list['addon_price'] = {
            sum: 1,
            label: addon_label,
            price: addon_price
        }

        // setup and send AJAX request
        let ajaxurl = mwc_ajax_obj.ajax_url;

        let data = {
            'action': 'mwc_get_price_summary_table',
            '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
            'price_list': price_list,
            'bundle_id': $('.mwc_item_div.mwc_selected_default_opt').data('bundle_id'),
            'product_qty': product_qty,
            'disc_perc': disc_perc
        };

        $.post(ajaxurl, data, function (response) {

            // console.log(response);
            //  return;

            let result = response;

            if (result.status !== false) {
                let img = $('.mwc_item_div.mwc_selected_default_opt').find('.op_c_package_image img').attr('src');
                $('#s_image').find('img').attr('src', img);
                $('.mwc_summary_table').empty();
                $('.mwc_summary_table').append(result.html);
                $('#order_summary').show();

                // empty mini cart and append new html after add to cart ajax complete
                // $(document).ajaxComplete(function (event, xhr, settings) {
                //     $('.cart-price').empty().append(result.mc_total);
                //     $('p.woocommerce-mini-cart__total.total').find('span').remove();
                //     $('p.woocommerce-mini-cart__total.total').append(result.mc_total);

                //     $('.cart-item-meta.mini-item-meta').each(function (index, element) {
                //         $(this).find('.woocommerce-Price-amount').remove();
                //         $(this).find('.quantity').append(result.p_price);
                //     });
                // });

                setTimeout(function () {
                    $('.mwc_items_div #order_summary').removeClass('mwc_loading');
                }, 500);

            } else {
                console.log('Price summary table HTML could not be fetched.');
            }

        });

    }

    /**
     * Set summary prices default bun
     */
    function mwc_set_summary_prices_default_bun() {

        console.log('mwc_set_summary_prices default');

        let $ = jQuery;

        // price list
        let price_list = {};

        let has_template_h = $('.template_h').length,
            product_ids = [],
            var_attribs = [],
            bundle_id = $('.mwc_item_div.mwc_selected_default_opt').data('bundle_id');

        // discount/coupon value
        let disc_perc = parseFloat($('.mwc_item_div.mwc_selected_default_opt').attr('data-coupon'));

        $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function (index, element) {
            product_ids.push($(this).attr('data-id'));
            var_attribs.push($(this).find('.var_prod_attr').val());

            // change all types to int
            product_ids = product_ids.map(Number);
            var_attribs = var_attribs.map(Number);

        });

        // product qty
        let product_qty = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_product_qty_hidden').val();

        $('.mwc_items_div #order_summary').addClass('mwc_loading');

        // sale price
        let sale_price = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_price_hidden');

        price_list['sale_price'] = {
            sum: 1,
            label: sale_price.data('label'),
            price: sale_price.val()
        }

        // Old Price
        let old_price = $('.mwc_item_div.mwc_selected_default_opt .mwc_bundle_price_regular_hidden');

        price_list['old_price'] = {
            sum: 0,
            label: old_price.data('label'),
            price: old_price.val()
        }

        // addon price
        let addon_label = '';
        let addon_price = 0;

        if ($('.mwc_upsell_product_wrap').length) {

            addon_label = $('.mwc_upsell_product_wrap').data('label');

            $('.mwc_item_addon.i_selected').each(function (i, e) {
                let e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
                let e_qty = parseFloat($(e).find('.addon_prod_qty').val());
                addon_price += e_price * e_qty;
            });
        }

        price_list['addon_price'] = {
            sum: 1,
            label: addon_label,
            price: addon_price
        }

        // setup and send AJAX request
        let ajaxurl = mwc_ajax_obj.ajax_url;

        let data = {
            'action': 'mwc_get_price_summary_table',
            '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
            'price_list': price_list,
            'bundle_id': bundle_id,
            'product_qty': product_qty,
            'product_ids': product_ids,
            'var_attribs': var_attribs,
            'disc_perc': disc_perc
        };

        $.post(ajaxurl, data, function (response) {

            let result = response;

            console.log(response);

            // return;

            if (result.status !== false) {

                let img = $('.mwc_item_div.mwc_selected_default_opt').find('.op_c_package_image img').attr('src');

                $('#s_image').find('img').attr('src', img);
                $('.mwc_summary_table').empty();
                $('.mwc_summary_table').append(result.html);
                $('#order_summary').show();
                $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append(result.p_price);
                $('.mwc_item_div_' + bundle_id).find('.mwc-total-price').empty().append(result.old_total + ' ' + result.mc_total);

                setTimeout(function () {
                    $('.mwc_items_div #order_summary').removeClass('mwc_loading');
                }, 500);

            } else {
                console.log('Price summary table HTML could not be fetched.');
            }

        });

    }

    /**
     * Set bundle prices for all bundles on page load
     */
    // $('.mwc_item_div').each(function (index, element) {

    //     // price list
    //     let price_list = {};

    //     let has_template_h = $('.template_h').length,
    //         product_ids = [],
    //         var_attribs = [],
    //         bundle_id = $(element).data('bundle_id');

    //     // discount/coupon value
    //     let disc_perc = parseFloat($(element).attr('data-coupon'));

    //     $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function (index, element) {
    //         product_ids.push($(this).attr('data-id'));
    //         var_attribs.push($(this).find('.var_prod_attr').val());

    //         // change all types to int
    //         product_ids = product_ids.map(Number);
    //         var_attribs = var_attribs.map(Number);

    //     });

    //     // product qty
    //     let product_qty = $(element).find('.mwc_bundle_product_qty_hidden').val();

    //     $(element).find('#order_summary').addClass('mwc_loading');

    //     // sale price
    //     let sale_price = $(element).find('.mwc_bundle_price_hidden');

    //     price_list['sale_price'] = {
    //         sum: 1,
    //         label: sale_price.data('label'),
    //         price: sale_price.val()
    //     }

    //     // Old Price
    //     let old_price = $(element).find('.mwc_bundle_price_regular_hidden');

    //     price_list['old_price'] = {
    //         sum: 0,
    //         label: old_price.data('label'),
    //         price: old_price.val()
    //     }

    //     // addon price
    //     let addon_label = '';
    //     let addon_price = 0;

    //     if ($('.mwc_upsell_product_wrap').length) {

    //         addon_label = $('.mwc_upsell_product_wrap').data('label');

    //         $('.mwc_item_addon.i_selected').each(function (i, e) {
    //             let e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
    //             let e_qty = parseFloat($(e).find('.addon_prod_qty').val());
    //             addon_price += e_price * e_qty;
    //         });
    //     }

    //     price_list['addon_price'] = {
    //         sum: 1,
    //         label: addon_label,
    //         price: addon_price
    //     }

    //     // setup and send AJAX request
    //     let ajaxurl = mwc_ajax_obj.ajax_url;

    //     let data = {
    //         'action': 'mwc_get_price_summary_table',
    //         '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
    //         'price_list': price_list,
    //         'bundle_id': bundle_id,
    //         'product_qty': product_qty,
    //         'product_ids': product_ids,
    //         'var_attribs': var_attribs,
    //         'disc_perc': disc_perc
    //     };

    //     $.post(ajaxurl, data, function (response) {

    //         let result = response;

    //         // console.log(response);

    //         // return;

    //         if (result.status !== false) {

    //             // let img = $(element).find('.op_c_package_image img').attr('src');

    //             // $('#s_image').find('img').attr('src', img);
    //             // $('.mwc_summary_table').empty();
    //             // $('.mwc_summary_table').append(result.html);
    //             // $('#order_summary').show();
    //             $('.mwc_item_div_'+bundle_id).find('.mwc-sub-price').empty().append(result.p_price);
    //             $('.mwc_item_div_'+bundle_id).find('.mwc-total-price').empty().append(result.old_total+' '+result.mc_total);

    //             setTimeout(function () {
    //                 $('.mwc_items_div #order_summary').removeClass('mwc_loading');
    //             }, 500);

    //         } else {
    //             console.log('Price summary table HTML could not be fetched.');
    //         }

    //     });

    // });

    /**
     * Set summary prices on click
     */
    function mwc_set_summary_prices() {

        console.log('mwc_set_summary_prices');

        let $ = jQuery;

        // price list
        let price_list = {};

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

        $('.mwc_items_div #order_summary').addClass('mwc_loading');

        // sale price
        let sale_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_hidden');

        price_list['sale_price'] = {
            sum: 1,
            label: sale_price.data('label'),
            price: sale_price.val()
        }

        // Old Price
        let old_price = $('.mwc_item_div.mwc_active_product .mwc_bundle_price_regular_hidden');

        price_list['old_price'] = {
            sum: 0,
            label: old_price.data('label'),
            price: old_price.val()
        }

        // addon price
        let addon_label = '';
        let addon_price = 0;

        if ($('.mwc_upsell_product_wrap').length) {

            addon_label = $('.mwc_upsell_product_wrap').data('label');

            $('.mwc_item_addon.i_selected').each(function (i, e) {
                let e_price = parseFloat($(e).find('.mwc_addon_price_hidden').val());
                let e_qty = parseFloat($(e).find('.addon_prod_qty').val());
                addon_price += e_price * e_qty;
            });
        }

        price_list['addon_price'] = {
            sum: 1,
            label: addon_label,
            price: addon_price
        }

        // setup and send AJAX request
        let ajaxurl = mwc_ajax_obj.ajax_url;

        let data = {
            'action': 'mwc_get_price_summary_table',
            '_ajax_nonce': mwc_ajax_obj.summary_price_nonce,
            'price_list': price_list,
            'bundle_id': bundle_id,
            'product_qty': product_qty,
            'product_ids': product_ids,
            'var_attribs': var_attribs,
            'disc_perc': disc_perc
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
                $('.mwc_item_div_' + bundle_id).find('.mwc-sub-price').empty().append(result.p_price);
                $('.mwc_item_div_' + bundle_id).find('.mwc-total-price').empty().append(result.old_total + ' ' + result.mc_total);

                setTimeout(function () {
                    $('.mwc_items_div #order_summary').removeClass('mwc_loading');
                }, 500);

            } else {
                console.log('Price summary table HTML could not be fetched.');
            }

        });

    }

    /**
     * Set bundle pricing on page load
     */
    function mwc_set_bundle_pricing_on_load() {

        let $ = jQuery;

        $('.mwc_item_div').each(function (index, element) {

            // if has default bundle class, skip
            if ($(element).hasClass('mwc_selected_default_opt')) {
                return;
            }

            // get bundle id
            let bundle_id = $(element).data('bundle_id');

            // get currency symbol
            let currency_sym = $('span.woocommerce-Price-currencySymbol:first').text();

            // get per product price
            let per_prod_price = $(element).find('.mwc-sub-price span.woocommerce-Price-amount.amount > bdi').text().replace(currency_sym, '');

            // round product price to 2 decimal places using math.round
            per_prod_price = Math.round(per_prod_price).toFixed(2);

            // get bundle product count
            let bundle_prod_count = $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').length;

            // calc bundle total
            let bundle_total = per_prod_price * bundle_prod_count;

            // pricing html single product
            let pricing_html = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">' + currency_sym + '</span>' + per_prod_price + '</bdi></span>';

            // pricing html bundle
            let pricing_html_bundle = '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">' + currency_sym + '</span>' + bundle_total.toFixed(2) + '</bdi></span>';

            // set pricing html
            $(element).find('.mwc-sub-price').empty().append(pricing_html);

            // set pricing html bundle
            $(element).find('.mwc-total-price').find('span.woocommerce-Price-amount.amount:eq(1)').replaceWith(pricing_html_bundle);

        });

    }

    mwc_set_bundle_pricing_on_load();


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