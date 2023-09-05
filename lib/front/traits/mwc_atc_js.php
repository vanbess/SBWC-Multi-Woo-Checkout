<?php
if (!trait_exists('AddToCartBasic')) :

    trait AddToCartBasicJS
    {

        /**
         * Add to cart JS is moved here now for better/easier bugfixing (shortcode template A)
         *
         * @return void
         */
        public static function mwc_atc_js()
        { ?>

            <script id="mwc-atc-updated">
                jQuery(document).ready(function($) {

                    // debug
                    console.log('mwc atc js loaded');

                    // only execute if linked products present in document
                    if (!$(document).find('.linked_product').length) {

                        // debug
                        console.log('no linked products');

                        /*********************************************
                         * Setup json data array for atc ajax request
                         *********************************************/
                        var data = {
                            'addon_variable_prods': {},
                            'addon_simple_prods': {},
                            'action': 'mwc_add_to_cart_multiple',
                            '_ajax_nonce': '<?php echo wp_create_nonce('add multiple products to cart') ?>'
                        };

                        /*****************************************************
                         * Click bundle checkbox on bundle container on click
                         *****************************************************/
                        $('.mwc_item_div').click(function(e) {

                            // debug
                            console.log('mwc_item_div clicked');

                            var target = $(e.target);

                            if (!target.is("select") && !target.is("input")) {
                                $(this).find('.mwc_package_checkbox').click();
                            }

                        });

                        /****************************************************
                         * Click add-on checkbox when clicking its container
                         ****************************************************/
                        $('.mwc_item_addon').click(function(e) {

                            var target = $(e.target);

                            if (!$(this).attr('disabled')) {

                                if (!target.is("select") && !target.is("input")) {
                                    $(this).find('.mwc_checkbox_addon').click();
                                }
                            }
                        });

                        /***************************************************
                         * If bundle not selected, disable add-on selection
                         ***************************************************/
                        // if ($('.mwc_active_product').length === 0) {
                        //     $('.mwc_item_addon').css('cursor', 'no-drop').attr('title', '<?php _e('Please select a product bundle first!', 'woocommerce') ?>').parent().attr('disabled', true);
                        //     $('.mwc_checkbox_addon, .addon_var_select, .addon_prod_qty, label, .mwc_fancybox_open').attr('disabled', true).css('cursor', 'no-drop');
                        // }

                        /******************************************
                         * Add-on variation id and img src on load
                         ******************************************/
                        $('.addon_var_select').each(function(index, element) {

                            var parent = $(this).parents('.mwc_item_addon');
                            var selected = {};
                            var variables = JSON.parse(atob($(this).data('variations')));

                            $(this).parents('.cao_var_options').find('.addon_var_select').each(function(i, e) {
                                selected[$(this).data('attribute_name')] = $(this).val();
                            });

                            // loop through variation data
                            $.each(variables, function(index, var_details) {

                                // extract variation attributes array
                                var v_attribs = var_details.attributes;

                                // set variation id and product image src if attribs match
                                if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                    parent.attr('data-addon_id', parseInt(var_details.variation_id));
                                    parent.find('.cao_img_cont > img').attr('src', var_details.image.src);
                                }

                            });

                        });

                        /**************************************
                         * Bundle variation id and img on load
                         **************************************/
                        $('.var_prod_attr').each(function(index, element) {

                            var parent = $(this).parents('.c_prod_item');
                            var selected = {};
                            var variables = JSON.parse(atob($(this).data('variations')));

                            $(this).parents('.variation_selectors').find('.var_prod_attr').each(function(i, e) {
                                selected[$(this).data('attribute_name')] = $(this).val();
                            });

                            // loop through variation data
                            $.each(variables, function(index, var_details) {

                                // extract variation attributes array
                                var v_attribs = var_details.attributes;

                                // set variation id and product image src if attribs match
                                if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                    parent.attr('data-variation_id', parseInt(var_details.variation_id));

                                    if (!$(document).find('.linked_product').length) {
                                        parent.find('.variation_img > img').attr('src', var_details.image.src);
                                    }
                                }

                            });

                        });

                        /**************************************
                         * ADD TO AJAX DATA OBJECT - MWC ITEMS
                         **************************************/

                        // only execute if linked products not present in document
                        //if (!$(document).find('.linked_product').length) {

                        $('.mwc_item_div').change(function(e) {

                            // debug
                            console.log('mwc_item_div change');


                            // enable addons if present
                            if ($('.mwc_item_addon').length > 0) {
                                $('.mwc_item_addon').css('cursor', 'pointer').attr('title', '<?php _e('Click to select add-on product', 'woocommerce') ?>').parent().attr('disabled', false);
                                $('.mwc_checkbox_addon, .addon_var_select, .addon_prod_qty, label, .mwc_fancybox_open').attr('disabled', false).css('cursor', 'pointer');
                            }

                            // used for searching main container for data
                            var mwc_main = $(this);

                            // schortcode template h check
                            var is_template_h = mwc_main.hasClass('template_h') ? true : false;

                            // retrieve bundle data
                            var bun_data = JSON.parse(atob(mwc_main.data('bundle-data')));

                            // add required data to ajax data object
                            data.bundle_type = bun_data.type;
                            data.bundle_id = parseInt(bun_data.bun_id);

                            // bail if linked products present (handled by different script)
                            if ($('.mwc_product_variations_' + bun_data.bun_id).find('.attribute-swatch').length > 0) {
                                return
                            }

                            // ****************************
                            // 1. If bundle type is bundle
                            // ****************************
                            if (data.bundle_type === 'bun') {

                                // reset ajax object
                                delete data.bun_discount;
                                delete data.bun_simple_prods;
                                delete data.bun_variable_prods;
                                delete data.free_simple_prod;
                                delete data.free_variable_prods;
                                delete data.off_discount;
                                delete data.off_simple_prod;
                                delete data.off_variable_prods;
                                delete data.paid_simple_prod;
                                delete data.paid_variable_prods;

                                // set discount
                                data.bun_discount = $.isNumeric(parseInt(bun_data.discount_percentage)) ? parseInt(bun_data.discount_percentage) : 0;

                                // ======================
                                // 1.1 If variable prods
                                // ======================

                                // -----------------
                                // If is Template H
                                // -----------------
                                if (is_template_h) {

                                    var bundle_id = bun_data.bun_id;

                                    // ========================================================
                                    // 1.1.1 loop to grab initially selected bundle variations
                                    // ========================================================
                                    if ($('.mwc_product_variations_' + bundle_id).find('.select-variation-bun').length !== 0) {

                                        // setup array which will hold variable product ids
                                        data.bun_variable_prods = [];

                                        // ========================================================
                                        // 1.1.1 loop to grab initially selected bundle variations
                                        // ========================================================
                                        $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {

                                            if ($(this).attr('data-variation_id')) {
                                                data.bun_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })
                                            }

                                        });

                                    }

                                    // ===========================
                                    // 1.1.2 variations on change
                                    // ===========================
                                    $('.select-variation-bun').on('change', function(e) {

                                        // reset bun_variable_prods
                                        data.bun_variable_prods = [];

                                        // holds selected variation attribs for later comparison
                                        var selected = {};

                                        $(this).parents('.variation_selectors').find('.select-variation-bun').each(function(i, e) {
                                            // push selected variation attributes to object
                                            selected[$(this).attr('data-attribute_name')] = $(this).val()
                                        });

                                        // find parent
                                        var parent = $(this).parents('.c_prod_item');

                                        // retrieve variation data
                                        var var_data = JSON.parse(atob($(this).data('variations')));

                                        // loop through variation data
                                        $.each(var_data, function(index, var_details) {

                                            // extract variation attributes array
                                            var v_attribs = var_details.attributes;

                                            // set variation id and product image src if attribs match
                                            if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                if (!$(document).find('.linked_product').length) {
                                                    parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                }
                                            }
                                        });

                                        // add items to data.bun_variable_prods
                                        $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {

                                            // only grab date from visible elements
                                            if ($(this).attr('data-variation_id')) {

                                                data.bun_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id'),
                                                })

                                            }

                                        });

                                        // trigger atc
                                        mwc_atc();

                                    });

                                    // ====================
                                    // 1.2 If simple prods
                                    // ====================

                                    // setup simple bundle products data array
                                    data.bun_simple_prods = [];

                                    $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {
                                        if (!$(this).attr('data-variation_id')) {
                                            data.bun_simple_prods.push($(this).attr('data-id'));
                                        }
                                    });
                                }

                                // -----------------
                                // If is Template A
                                // -----------------
                                if (!is_template_h) {

                                    if (mwc_main.find('.select-variation-bun').length !== 0) {

                                        // setup array which will hold variable product ids
                                        data.bun_variable_prods = [];

                                        // ========================================================
                                        // 1.1.1 loop to grab initially selected bundle variations
                                        // ========================================================
                                        $('.c_prod_item', $(this)).each(function(i, e) {

                                            if ($(this).is(':visible')) {
                                                data.bun_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })
                                            }

                                        });

                                        // =====================================
                                        // 1.1.1.1 set variation images on load
                                        // =====================================
                                        $(mwc_main.find('.variation_selectors')).each(function(i, e) {

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // object which contains all selected variation attributes- used to determine correct variation id/image
                                            var selected = {};

                                            $(this).find('.select-variation-bun').each(function(index, element) {

                                                // retrieve variation data
                                                var var_data = JSON.parse(atob($(this).data('variations')));

                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()

                                                // loop through variation data
                                                $.each(var_data, function(index, var_details) {

                                                    // extract variation attributes array
                                                    var v_attribs = var_details.attributes;

                                                    // set variation id and product image src if attribs match
                                                    if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                        parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                        if (!$(document).find('.linked_product').length) {
                                                            parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                        }
                                                    }

                                                });

                                            });

                                        });

                                        // ===========================
                                        // 1.1.2 variations on change
                                        // ===========================
                                        $('.select-variation-bun').on('change', function(e) {

                                            // reset bun_variable_prods
                                            data.bun_variable_prods = [];

                                            // holds selected variation attribs for later comparison
                                            var selected = {};

                                            $(this).parents('.variation_selectors').find('.select-variation-bun').each(function(i, e) {
                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()
                                            });

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // retrieve variation data
                                            var var_data = JSON.parse(atob($(this).data('variations')));

                                            // loop through variation data
                                            $.each(var_data, function(index, var_details) {

                                                // extract variation attributes array
                                                var v_attribs = var_details.attributes;

                                                // set variation id and product image src if attribs match
                                                if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                    parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                    if (!$(document).find('.linked_product').length) {
                                                        parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                    }
                                                }
                                            });

                                            // add items to data.bun_variable_prods
                                            $('.c_prod_item', $(this)).each(function(i, e) {

                                                // only grab date from visible elements
                                                if ($(this).is(':visible')) {

                                                    data.bun_variable_prods.push({
                                                        'variation_id': $(this).attr('data-variation_id'),
                                                        'parent_id': $(this).attr('data-id'),
                                                    })

                                                }

                                            });

                                        });

                                        // ====================
                                        // 1.2 If simple prods
                                        // ====================

                                        // setup simple bundle products data array
                                        data.bun_simple_prods = [];

                                        $('.c_prod_item', $(this)).each(function(i, e) {
                                            if ($(this).is(':hidden')) {
                                                data.bun_simple_prods.push($(this).attr('data-id'));
                                            }
                                        });

                                    }

                                }

                            }

                            // *************************
                            // 2. If bundle type is off
                            // *************************
                            if (data.bundle_type === 'off') {

                                // reset ajax object
                                delete data.bun_discount;
                                delete data.bun_simple_prods;
                                delete data.bun_variable_prods;
                                delete data.free_simple_prod;
                                delete data.free_variable_prods;
                                delete data.off_discount;
                                delete data.off_simple_prod;
                                delete data.off_variable_prods;
                                delete data.paid_simple_prod;
                                delete data.paid_variable_prods;

                                // push discount/coupon
                                data.off_discount = $.isNumeric(parseInt(bun_data.coupon)) ? parseInt(bun_data.coupon) : 0;

                                // -----------------
                                // If is Template H
                                // -----------------
                                if (is_template_h) {

                                    var bundle_id = bun_data.bun_id;

                                    // ======================
                                    // 2.1 If variable prods
                                    // ======================
                                    if ($('.mwc_product_variations_' + bundle_id).find('.select-variation-off').length !== 0) {

                                        // setup array which will hold variable product ids
                                        data.off_variable_prods = [];

                                        // ========================================================
                                        // 2.1.1 loop to grab initially selected bundle variations
                                        // ========================================================
                                        $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {

                                            // only grab date from visible elements
                                            if ($(this).attr('data-variation_id')) {

                                                data.off_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })

                                            }

                                        });

                                        // ===========================
                                        // 2.1.2 variations on change
                                        // ===========================
                                        $('.select-variation-off').on('change', function(e) {

                                            // reset bun_variable_prods
                                            data.off_variable_prods = [];

                                            // holds selected variation attribs for later comparison
                                            var selected = {};

                                            $(this).parents('.variation_selectors').find('.select-variation-off').each(function(i, e) {
                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()
                                            });

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // retrieve variation data
                                            var var_data = JSON.parse(atob($(this).data('variations')));

                                            // loop through variation data
                                            $.each(var_data, function(index, var_details) {

                                                // extract variation attributes array
                                                var v_attribs = var_details.attributes;

                                                // set variation id and product image src if attribs match
                                                if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                    parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                    if (!$(document).find('.linked_product').length) {
                                                        parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                    }
                                                }
                                            });

                                            // add items to data.bun_variable_prods
                                            $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {

                                                // only grab date from visible elements
                                                if ($(this).attr('data-variation_id')) {

                                                    data.off_variable_prods.push({
                                                        'variation_id': $(this).attr('data-variation_id'),
                                                        'parent_id': $(this).attr('data-id')
                                                    })

                                                }

                                            });

                                            // trigger atc
                                            mwc_atc();

                                        });

                                        // ====================
                                        // 2.2 If simple prods
                                        // ====================
                                    } else {

                                        // push discount/coupon
                                        data.off_discount = parseInt(bun_data.coupon);

                                        // setup simple bundle products data array
                                        data.off_simple_prod = {
                                            'prod_id': bun_data.id,
                                            'prod_qty': bun_data.qty,
                                        };

                                    }
                                }

                                // -----------------
                                // If is Template A
                                // -----------------
                                if (!is_template_h) {

                                    // ======================
                                    // 2.1 If variable prods
                                    // ======================
                                    if (mwc_main.find('.select-variation-off').length !== 0) {

                                        // setup array which will hold variable product ids
                                        data.off_variable_prods = [];

                                        // ========================================================
                                        // 2.1.1 loop to grab initially selected bundle variations
                                        // ========================================================
                                        $('.c_prod_item', $(this)).each(function(i, e) {

                                            // only grab date from visible elements
                                            if ($(this).is(':visible')) {

                                                data.off_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })

                                            }

                                        });

                                        // =====================================
                                        // 2.1.1.1 set variation images on load
                                        // =====================================
                                        $(mwc_main.find('.variation_selectors')).each(function(i, e) {

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // object which contains all selected variation attributes- used to determine correct variation id/image
                                            var selected = {};

                                            $(this).find('.select-variation-off').each(function(index, element) {

                                                // retrieve variation data
                                                var var_data = JSON.parse(atob($(this).data('variations')));

                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()

                                                // loop through variation data
                                                $.each(var_data, function(index, var_details) {

                                                    // extract variation attributes array
                                                    var v_attribs = var_details.attributes;

                                                    // set variation id and product image src if attribs match
                                                    if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                        parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                        if (!$(document).find('.linked_product').length) {
                                                            parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                        }
                                                    }

                                                });

                                            });

                                        });

                                        // ===========================
                                        // 2.1.2 variations on change
                                        // ===========================
                                        $('.select-variation-off').on('change', function(e) {

                                            // reset bun_variable_prods
                                            data.off_variable_prods = [];

                                            // holds selected variation attribs for later comparison
                                            var selected = {};

                                            $(this).parents('.variation_selectors').find('.select-variation-off').each(function(i, e) {
                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()
                                            });

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // retrieve variation data
                                            var var_data = JSON.parse(atob($(this).data('variations')));

                                            // loop through variation data
                                            $.each(var_data, function(index, var_details) {

                                                // extract variation attributes array
                                                var v_attribs = var_details.attributes;

                                                // set variation id and product image src if attribs match
                                                if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                    parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                    if (!$(document).find('.linked_product').length) {
                                                        parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                    }
                                                }
                                            });

                                            // add items to data.bun_variable_prods
                                            $('.c_prod_item', $(this)).each(function(i, e) {

                                                // only grab date from visible elements
                                                if ($(this).is(':visible')) {

                                                    data.off_variable_prods.push({
                                                        'variation_id': $(this).attr('data-variation_id'),
                                                        'parent_id': $(this).attr('data-id'),
                                                    })

                                                }

                                            });

                                        });

                                        // ====================
                                        // 2.2 If simple prods
                                        // ====================
                                    } else {

                                        // push discount/coupon
                                        data.off_discount = parseInt(bun_data.coupon);

                                        // setup simple bundle products data array
                                        data.off_simple_prod = {
                                            'prod_id': bun_data.id,
                                            'prod_qty': bun_data.qty,
                                        };

                                    }

                                }

                            };

                            // **************************
                            // 3. IF BUNDLE TYPE IS FREE
                            // **************************
                            if (data.bundle_type === 'free') {

                                // reset ajax object
                                delete data.bun_discount;
                                delete data.bun_simple_prods;
                                delete data.bun_variable_prods;
                                delete data.free_simple_prod;
                                delete data.free_variable_prods;
                                delete data.off_discount;
                                delete data.off_simple_prod;
                                delete data.off_variable_prods;
                                delete data.paid_simple_prod;
                                delete data.paid_variable_prods;

                                // -----------------
                                // If is Template H
                                // -----------------
                                if (is_template_h) {

                                    var bundle_id = bun_data.bun_id;

                                    // if variations present
                                    if ($('.mwc_product_variations_' + bundle_id).find('.select-variation-free').length !== 0) {

                                        // setup array which will hold variable product ids
                                        data.free_variable_prods = [];
                                        data.paid_variable_prods = [];

                                        // ========================================================
                                        // 3.1.1 loop to grab initially selected bundle variations
                                        // ========================================================
                                        $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {

                                            // push free items
                                            if ($(this).hasClass('free-item')) {

                                                data.free_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })

                                                // push paid items
                                            } else {

                                                data.paid_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })

                                            }

                                        });

                                        // ================================
                                        // 3.1.2 free variations on change
                                        // ================================
                                        $('.select-variation-free').on('change', function(e) {

                                            // setup array which will hold variable product ids
                                            data.free_variable_prods = [];
                                            data.paid_variable_prods = [];

                                            // holds selected variation attribs for later comparison
                                            var selected = {};

                                            $(this).parents('.variation_selectors').find('.select-variation-free').each(function(i, e) {
                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()
                                            });

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // retrieve variation data
                                            var var_data = JSON.parse(atob($(this).data('variations')));

                                            // loop through variation data
                                            $.each(var_data, function(index, var_details) {

                                                // extract variation attributes array
                                                var v_attribs = var_details.attributes;

                                                // set variation id and product image src if attribs match
                                                if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                    parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                    if (!$(document).find('.linked_product').length) {
                                                        parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                    }
                                                }
                                            });

                                            // add items to data.bun_variable_prods
                                            $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {

                                                // push free items
                                                if ($(this).hasClass('free-item')) {

                                                    data.free_variable_prods.push({
                                                        'variation_id': $(this).attr('data-variation_id'),
                                                        'parent_id': $(this).attr('data-id')
                                                    })

                                                    // push paid items
                                                } else {

                                                    data.paid_variable_prods.push({
                                                        'variation_id': $(this).attr('data-variation_id'),
                                                        'parent_id': $(this).attr('data-id')
                                                    })

                                                }

                                            });

                                            // trigger atc
                                            mwc_atc();

                                        });

                                        // ====================
                                        // 3.2 If simple prods
                                        // ====================
                                    } else {
                                        data.paid_simple_prod = [{
                                            'id': parseInt(bun_data.id),
                                            'qty': parseInt(bun_data.qty)
                                        }];

                                        data.free_simple_prod = [{
                                            'id': parseInt(bun_data.id_free),
                                            'qty': parseInt(bun_data.qty_free)
                                        }];
                                    }
                                }

                                // -----------------
                                // If is Template A
                                // -----------------
                                if (!is_template_h) {
                                    if (mwc_main.find('.select-variation-free').length !== 0) {

                                        // setup array which will hold variable product ids
                                        data.free_variable_prods = [];
                                        data.paid_variable_prods = [];

                                        // ========================================================
                                        // 3.1.1 loop to grab initially selected bundle variations
                                        // ========================================================
                                        $('.c_prod_item', $(this)).each(function(i, e) {

                                            // push free items
                                            if ($(this).hasClass('free-item')) {

                                                data.free_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })

                                                // push paid items
                                            } else {

                                                data.paid_variable_prods.push({
                                                    'variation_id': $(this).attr('data-variation_id'),
                                                    'parent_id': $(this).attr('data-id')
                                                })

                                            }

                                        });

                                        // =====================================
                                        // 3.1.1.1 set variation images on load
                                        // ===================================== 
                                        $(mwc_main.find('.variation_selectors')).each(function(i, e) {

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // object which contains all selected variation attributes- used to determine correct variation id/image
                                            var selected = {};

                                            $(this).find('.select-variation-free').each(function(index, element) {

                                                // retrieve variation data
                                                var var_data = JSON.parse(atob($(this).data('variations')));

                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()

                                                // loop through variation data
                                                $.each(var_data, function(index, var_details) {

                                                    // extract variation attributes array
                                                    var v_attribs = var_details.attributes;

                                                    // set variation id and product image src if attribs match
                                                    if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                        parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                        if (!$(document).find('.linked_product').length) {
                                                            parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                        }
                                                    }

                                                });

                                            });

                                        });

                                        // ===========================
                                        // 3.1.2 variations on change
                                        // ===========================
                                        $('.select-variation-free').on('change', function(e) {

                                            // setup array which will hold variable product ids
                                            data.free_variable_prods = [];
                                            data.paid_variable_prods = [];

                                            // holds selected variation attribs for later comparison
                                            var selected = {};

                                            $(this).parents('.variation_selectors').find('.select-variation-free').each(function(i, e) {
                                                // push selected variation attributes to object
                                                selected[$(this).attr('data-attribute_name')] = $(this).val()
                                            });

                                            // find parent
                                            var parent = $(this).parents('.c_prod_item');

                                            // retrieve variation data
                                            var var_data = JSON.parse(atob($(this).data('variations')));

                                            // loop through variation data
                                            $.each(var_data, function(index, var_details) {

                                                // extract variation attributes array
                                                var v_attribs = var_details.attributes;

                                                // set variation id and product image src if attribs match
                                                if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                                    parent.attr('data-variation_id', parseInt(var_details.variation_id));
                                                    if (!$(document).find('.linked_product').length) {
                                                        parent.find('.variation_img > img').attr('src', var_details.image.src);
                                                    }
                                                }
                                            });

                                            // add items to data.bun_variable_prods
                                            $('.c_prod_item', $(this)).each(function(i, e) {

                                                // push free items
                                                if ($(this).hasClass('free-item')) {

                                                    data.free_variable_prods.push({
                                                        'variation_id': $(this).attr('data-variation_id'),
                                                        'parent_id': $(this).attr('data-id')
                                                    })

                                                    // push paid items
                                                } else {

                                                    data.paid_variable_prods.push({
                                                        'variation_id': $(this).attr('data-variation_id'),
                                                        'parent_id': $(this).attr('data-id')
                                                    })

                                                }

                                            });

                                        });

                                        // ====================
                                        // 3.2 If simple prods
                                        // ====================
                                    } else {
                                        data.paid_simple_prod = [{
                                            'id': parseInt(bun_data.id),
                                            'qty': parseInt(bun_data.qty)
                                        }];

                                        data.free_simple_prod = [{
                                            'id': parseInt(bun_data.id_free),
                                            'qty': parseInt(bun_data.qty_free)
                                        }];
                                    }
                                }

                            }

                            // initial add to cart
                            mwc_atc();

                            // console.log(data);

                        });


                        /*****************************************
                         * ADD TO AJAX DATA OBJECT - ADD-ON ITEMS
                         *****************************************/
                        $('.mwc_checkbox_addon').each(function(i, e) {

                            var index = i;

                            // target is this
                            var checkbox = $(this);

                            // setup parent
                            var parent = $(this).parents('.mwc_item_addon');

                            // addon meta
                            var addon_meta = JSON.parse(atob($(this).attr('addon-meta')));

                            // ===============================================================
                            // preload/preselect relevant variation id and image on page load
                            // ===============================================================
                            if (checkbox.data('variations')) {

                                // retrieve variation data
                                var var_data = JSON.parse(atob($(this).data('variations')));

                                // object which contains all selected variation attributes- used to determine correct variation id/image
                                var selected = {};

                                parent.find('.addon_var_select').each(function(index, element) {

                                    // push selected variation attributes to object
                                    selected[$(this).attr('data-attribute_name')] = $(this).val()

                                    // loop through variation data
                                    $.each(var_data, function(index, var_details) {

                                        // extract variation attributes array
                                        var v_attribs = var_details.attributes;

                                        // set variation id and product image src if attribs match
                                        if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                            checkbox.attr('data-addon_id', var_details.variation_id);
                                            parent.find('.cao_img_cont.img_option > img').attr('src', var_details.image.src);
                                        }

                                    });

                                });
                            }

                            // checkbox on click
                            checkbox.on('click', function() {

                                // setup parent
                                var parent = $(this).parents('.mwc_item_addon');

                                if (checkbox.is(':checked')) {

                                    parent.addClass('i_selected');

                                    // addon discount
                                    // data.addon_discount = addon_meta.discount_perc;

                                    // =====================
                                    // 1. Variable products
                                    // =====================
                                    if (parent.find('.variation_item').length !== 0) {

                                        data.addon_variable_prods[index] = {
                                            'parent_id': parseInt(parent.attr('data-id')),
                                            'variation_id': parseInt(parent.attr('data-addon_id')),
                                            'qty': parseInt(parent.find('.addon_prod_qty').val()),
                                            'discount': addon_meta.discount_perc
                                        };

                                        // ===================
                                        // 2. Simple products
                                        // ===================
                                    } else {

                                        data.addon_simple_prods[index] = {
                                            'simple_id': parseInt(checkbox.data('product_id')),
                                            'qty': parseInt(checkbox.parents('.mwc_item_addon').find('.addon_prod_qty').val()),
                                            'discount': addon_meta.discount_perc
                                        };
                                    }


                                } else {
                                    parent.removeClass('i_selected');
                                    delete data.addon_variable_prods[index];
                                    delete data.addon_simple_prods[index];
                                }

                                // add to cart
                                mwc_atc();

                                // console.log(data);

                            });
                            // ====================================
                            // 3. Addon variable product on change
                            // ====================================

                            // 3.1 Variation on change
                            var addon_select = checkbox.parents('.mwc_addon_div').find('.addon_var_select');
                            var qty_select = checkbox.parents('.mwc_addon_div').find('.addon_prod_qty');

                            addon_select.on('change', function() {

                                // Update variation image and id on change
                                var parent = $(this).parents('.mwc_item_addon');

                                // selected variation(s)
                                var selected = {};

                                // retrieve variable
                                var variables = JSON.parse(atob($(this).data('variations')));

                                // push selected variable option(s) to selected
                                $(this).parents('.cao_var_options').find('.addon_var_select').each(function(i, e) {
                                    selected[$(this).data('attribute_name')] = $(this).val();
                                });

                                // loop through variation data
                                $.each(variables, function(index, var_details) {

                                    // extract variation attributes array
                                    var v_attribs = var_details.attributes;

                                    // set variation id and product image src if attribs match
                                    if (JSON.stringify(selected) === JSON.stringify(v_attribs)) {
                                        parent.attr('data-addon_id', parseInt(var_details.variation_id));
                                        parent.find('.cao_img_cont > img').attr('src', var_details.image.src);
                                    }

                                });

                                // push updated variation(s) to ajax data object
                                if (checkbox.is(':checked')) {

                                    delete data.addon_variable_prods[index];

                                    data.addon_variable_prods[index] = {
                                        'parent_id': parseInt(parent.attr('data-id')),
                                        'variation_id': parseInt(parent.attr('data-addon_id')),
                                        'qty': parseInt(parent.find('.addon_prod_qty').val()),
                                        'discount': addon_meta.discount_perc
                                    };

                                    // add to cart
                                    mwc_atc();
                                }

                                // console.log(data);

                            });

                            // 3.2 Qty on change
                            qty_select.on('change', function() {

                                if (checkbox.is(':checked')) {

                                    // update variable product qty if needed
                                    if (data.addon_variable_prods[index]) {
                                        delete data.addon_variable_prods[index].qty;
                                        data.addon_variable_prods[index].qty = parseInt(checkbox.parents('.mwc_item_addon').find('.addon_prod_qty').val())
                                    }

                                    // update simple product qty if needed
                                    if (data.addon_simple_prods[index]) {
                                        delete data.addon_simple_prods[index].qty;
                                        data.addon_simple_prods[index].qty = parseInt(checkbox.parents('.mwc_item_addon').find('.addon_prod_qty').val());
                                    }

                                    // add to cart
                                    mwc_atc();
                                }

                                // console.log(data);

                            });
                        });

                        /**
                         * ADD TO CART
                         *
                         * @return void
                         */
                        function mwc_atc() {

                            var ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>';

                            setTimeout(() => {

                                // debug
                                console.log(data);

                                $.post(ajaxurl, data, function(response) {

                                    // debug
                                    // console.log(response);

                                    // update cart
                                    // $(document.body).trigger('update_checkout');
                                    // $( document.body ).trigger( 'wc_fragment_refresh' );

                                });
                            }, 250);

                            return false;

                        }

                        /***********************
                         * CHECKOUT/PLACE ORDER
                         ***********************/
                        $('form.mwc_checkout.checkout.woocommerce-checkout').on('click', 'button#place_order', function(e) {

                            // prevent submission if no bundle data present
                            if (!data.bundle_type) {
                                e.preventDefault();
                                alert('<?php _e('Please select at least one product bundle first!', 'woocommerce'); ?>');
                                return;
                            }

                        });
                        //}

                    }

                    // if class mwc_active_product is found in document after load, trigger mwc_atc_linked after 2 seconds
                    if ($('.mwc_active_product').length) {
                        setTimeout(() => {

                            console.log('mwc_active_product found');

                            mwc_atc();
                        }, 2000);
                    }

                });
            </script>

<?php }
    }

endif;
