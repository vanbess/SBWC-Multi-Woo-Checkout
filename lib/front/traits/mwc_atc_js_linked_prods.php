<?php
if (!trait_exists('AddToCartLinkedJS')) :

    trait AddToCartLinkedJS
    {

        /**
         * JS to correctly add linked products to cart
         *
         * @return void
         */
        public static function mwc_atc_js_linked_prods()
        { ?>

            <script id="linked_prods_swatch_js">
                jQuery(document).ready(function($) {

                    // only execute if linked products present in document
                    if ($(document).find('.linked_product').length) {

                        // debug
                        // console.log('linked products present');

                        /*********************************************
                         * Setup json data array for atc ajax request
                         *********************************************/
                        var linked_data = {
                            'addon_variable_prods': {},
                            'addon_simple_prods': {},
                            'action': 'mwc_atc_linked_products',
                            '_ajax_nonce': '<?php echo wp_create_nonce('add linked products to cart') ?>'
                        };

                        $('.mwc_item_div').each(function(index, element) {

                            // -----------
                            // Basic vars
                            // -----------
                            var type = $(this).attr('data-type');
                            var bundle_id = $(this).attr('data-bundle_id');
                            var v_dd;
                            var linked_prod;

                            // Add basic data to AJAX data object
                            // linked_data.type = type, linked_data.bundle_id = bundle_id;

                            // --------------------
                            // Variation selectors
                            // --------------------
                            if (type === 'bun') {
                                v_dd = $(this).hasClass('template_a') ? $(this).find($('.select-variation-bun')) : $('.mwc_product_variations_' + bundle_id).find('.select-variation-bun');
                                linked_prod = $(this).hasClass('template_a') ? $(this).find($('.linked_product')) : $('.mwc_product_variations_' + bundle_id).find('.linked_product');
                            }
                            if (type === 'off') {
                                v_dd = $(this).hasClass('template_a') ? $(this).find($('.select-variation-off')) : $('.mwc_product_variations_' + bundle_id).find('.select-variation-off');
                                linked_prod = $(this).hasClass('template_a') ? $(this).find($('.linked_product')) : $('.mwc_product_variations_' + bundle_id).find('.linked_product');
                            }
                            if (type === 'free') {
                                v_dd = $(this).hasClass('template_a') ? $(this).find($('.select-variation-free')) : $('.mwc_product_variations_' + bundle_id).find('.select-variation-free');
                                linked_prod = $(this).hasClass('template_a') ? $(this).find($('.linked_product')) : $('.mwc_product_variations_' + bundle_id).find('.linked_product');
                            }

                            // -----------
                            // Template A
                            // -----------
                            if ($(this).hasClass('template_a')) {

                                // set template and main target for use in functions
                                var template = 'A';
                                var target_main = $(this);

                                // ------------------------
                                // load default if present
                                // ------------------------
                                if ($(this).hasClass('mwc_selected_default_opt')) {

                                    $(this).find('.mwc_package_checkbox').click();

                                    // debug: check if mwc_package_checkbox is clicked on page load
                                    // console.log('checking if mwc_package_checkbox is clicked on page load...');
                                    // console.log($(this).find('.mwc_package_checkbox').click());

                                    // setup basic vars
                                    bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.type = $(this).attr('data-type');
                                    linked_data.discount = $(this).attr('data-coupon');

                                    // var bun_data = JSON.parse(atob($(this).attr('data-bundle-data')));
                                    var discount = $(this).attr('data-coupon');

                                    linked_data.discount = discount;

                                    $(document).find('.mwc_product_variations').hide();
                                    $(this).find('.mwc_product_variations').slideDown();

                                    // type bun
                                    if (type === 'bun') {
                                        linked_data.linked_bun_prods = build_bundle_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type off
                                    if (type === 'off') {
                                        linked_data.linked_off_prods = build_off_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type free
                                    if (type === 'free') {
                                        linked_data.linked_free_prods = build_free_prod_dataset(target_main, v_dd, template);
                                    }

                                    // console.log(linked_data);

                                    // mwc_atc_linked();

                                }

                                // -------------------
                                // container on click
                                // -------------------
                                $(this).on('mousedown', function(e) {

                                    if (e.which === 3) {
                                        return;
                                    }

                                    // don't execute if target/child is linked_product or var_prod_attr dropdown, else multiple atc events will be triggered
                                    if ($(e.target).hasClass('linked_product') || $(e.target).hasClass('var_prod_attr')) {
                                        return;
                                    }

                                    // setup basic vars
                                    bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.type = $(this).attr('data-type');
                                    linked_data.discount = $(this).attr('data-coupon');

                                    if (!$(this).find('.mwc_product_variations').is(':visible')) {
                                        $(document).find('.mwc_product_variations').hide();
                                        $(this).find('.mwc_product_variations').slideDown();
                                    }


                                    // type bun
                                    if (type === 'bun') {
                                        linked_data.linked_bun_prods = build_bundle_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type off
                                    if (type === 'off') {
                                        linked_data.linked_off_prods = build_off_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type free
                                    if (type === 'free') {
                                        linked_data.linked_free_prods = build_free_prod_dataset(target_main, v_dd, template);
                                    }


                                    mwc_atc_linked();

                                })

                                // -----------------------------
                                // variation dropdown on change
                                // -----------------------------
                                v_dd.on('change', function(e) {

                                    var target = $(this);

                                    setTimeout(() => {

                                        // type bun
                                        if ($(this).hasClass('select-variation-bun')) {
                                            linked_data.linked_bun_prods = build_bundle_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type off
                                        if ($(this).hasClass('select-variation-off')) {
                                            linked_data.linked_off_prods = build_off_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type free
                                        if ($(this).hasClass('select-variation-free')) {
                                            linked_data.linked_free_prods = build_free_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // console.log(linked_data);

                                        mwc_atc_linked();

                                    }, 500);

                                });

                                // ------------------------
                                // linked product on click
                                // ------------------------
                                linked_prod.on('mousedown', function(e) {

                                    if (e.which === 3) {
                                        return;
                                    }

                                    var target = $(this);

                                    setTimeout(() => {

                                        // type bun
                                        if (type === 'bun') {
                                            linked_data.linked_bun_prods = build_bundle_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type off
                                        if (type === 'off') {
                                            linked_data.linked_off_prods = build_off_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type free
                                        if (type === 'free') {
                                            linked_data.linked_free_prods = build_free_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // console.log(linked_data);

                                        mwc_atc_linked('linked product on mousedown')

                                    }, 500);
                                });

                            };

                            // -----------
                            // Template H
                            // -----------
                            if ($(this).hasClass('template_h')) {

                                // debug
                                // console.log('is template H');

                                // set template and main target for use in functions
                                var template = 'H';
                                var target_main = $('.mwc_product_variations_' + bundle_id);

                                // ------------------------
                                // load default if present
                                // ------------------------
                                if ($(this).hasClass('mwc_selected_default_opt')) {

                                    // debug
                                    // console.log('default option present');

                                    mwc_atc_linked('default bundle on load');

                                    $(this).find('.mwc_package_checkbox').click();

                                    // debug
                                    // debug: check if mwc_package_checkbox is clicked on page load
                                    // console.log('checking if mwc_package_checkbox is clicked on page load...');
                                    // console.log($(this).find('.mwc_package_checkbox').click());

                                    // setup basic vars
                                    bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.type = $(this).attr('data-type');
                                    linked_data.discount = $(this).attr('data-coupon');

                                    // show variation/linked products selector
                                    $(document).find('.mwc_product_variations').hide();
                                    $(document).find('.mwc_product_variations_' + bundle_id).slideDown();

                                    // type bun
                                    if (type === 'bun') {
                                        linked_data.linked_bun_prods = build_bundle_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type off
                                    if (type === 'off') {
                                        linked_data.linked_off_prods = build_off_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type free
                                    if (type === 'free') {
                                        linked_data.linked_free_prods = build_free_prod_dataset(target_main, v_dd, template);
                                    }

                                    // debug
                                    // console.log(linked_data);

                                }

                                // -----------------------
                                // container on mousedown
                                // -----------------------
                                $(this).on('mousedown', function(e) {

                                    // debug
                                    // console.log('container mousedown');
                                    // console.log(e.which);

                                    if (e.which === 3) {
                                        return;
                                    }

                                    // don't execute if target/child is linked_product or var_prod_attr dropdown, else multiple atc events will be triggered
                                    if ($(e.target).hasClass('linked_product') || $(e.target).hasClass('var_prod_attr') || $(e.target).hasClass('c_prod_item')) {

                                        // debug
                                        // console.log('target is linked_product or var_prod_attr or c_prod_item');
                                        return;
                                    }


                                    // setup basic vars
                                    bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.bundle_id = $(this).attr('data-bundle_id');
                                    linked_data.type = $(this).attr('data-type');
                                    linked_data.discount = $(this).attr('data-coupon');

                                    // show variation/linked products selector
                                    $(document).find('.mwc_product_variations').hide();
                                    $(document).find('.mwc_product_variations_' + bundle_id).slideDown();

                                    // type bun
                                    if (type === 'bun') {
                                        linked_data.linked_bun_prods = build_bundle_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type off
                                    if (type === 'off') {
                                        linked_data.linked_off_prods = build_off_prod_dataset(target_main, v_dd, template);
                                    }

                                    // type free
                                    if (type === 'free') {
                                        linked_data.linked_free_prods = build_free_prod_dataset(target_main, v_dd, template);
                                    }

                                    // debug
                                    // console.log(linked_data);

                                    mwc_atc_linked('template h bundle on mouse down');

                                });

                                // ---------------------------
                                // variation select on change
                                // ---------------------------
                                v_dd.on('change', function(e) {

                                    var target = $(this);

                                    setTimeout(() => {

                                        // type bun
                                        if ($(this).hasClass('select-variation-bun')) {
                                            linked_data.linked_bun_prods = build_bundle_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type off
                                        if ($(this).hasClass('select-variation-off')) {
                                            linked_data.linked_off_prods = build_off_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type free
                                        if ($(this).hasClass('select-variation-free')) {
                                            linked_data.linked_free_prods = build_free_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // console.log(linked_data);

                                        mwc_atc_linked('variation select on change');

                                    }, 500);
                                });

                                // ----------------------------
                                // linked product on mousedown
                                // ----------------------------
                                linked_prod.on('mousedown', function(e) {

                                    if (e.which === 3) {
                                        return;
                                    }

                                    var target = $(this);

                                    setTimeout(() => {

                                        // type bun
                                        if (type === 'bun') {
                                            linked_data.linked_bun_prods = build_bundle_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type off
                                        if (type === 'off') {
                                            linked_data.linked_off_prods = build_off_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // type free
                                        if (type === 'free') {
                                            linked_data.linked_free_prods = build_free_prod_dataset_dd_linked(target, v_dd, template);
                                        }

                                        // console.log(linked_data);

                                        mwc_atc_linked('linked product on mousedown');

                                    }, 500);

                                });

                            }

                        });

                        // debug
                        // console.log(linked_data);

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
                            if (checkbox.attr('data-variations')) {

                                // retrieve variation data
                                var var_data = JSON.parse(atob($(this).attr('data-variations')));

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
                            checkbox.on('click', function(e) {

                                e.stopPropagation();

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

                                        linked_data.addon_variable_prods[index] = {
                                            'parent_id': parseInt(parent.attr('data-id')),
                                            'variation_id': parseInt(parent.attr('data-addon_id')),
                                            'qty': parseInt(parent.find('.addon_prod_qty').val()),
                                            'discount': addon_meta.discount_perc
                                        };

                                        // ===================
                                        // 2. Simple products
                                        // ===================
                                    } else {

                                        linked_data.addon_simple_prods[index] = {
                                            'simple_id': parseInt(checkbox.attr('data-product_id')),
                                            'qty': parseInt(checkbox.parents('.mwc_item_addon').find('.addon_prod_qty').val()),
                                            'discount': addon_meta.discount_perc
                                        };
                                    }

                                    // console.log(linked_data);

                                } else {

                                    parent.removeClass('i_selected');
                                    delete linked_data.addon_variable_prods[index];
                                    delete linked_data.addon_simple_prods[index];
                                }

                                // add to cart
                                try {
                                    mwc_atc_linked('checkbox on click - 1st instance');
                                } catch (error) {
                                    console.log(error);
                                }

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
                                var variables = JSON.parse(atob($(this).attr('data-variations')));

                                // push selected variable option(s) to selected
                                $(this).parents('.cao_var_options').find('.addon_var_select').each(function(i, e) {
                                    selected[$(this).attr('data-attribute_name')] = $(this).val();
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

                                    delete linked_data.addon_variable_prods[index];

                                    linked_data.addon_variable_prods[index] = {
                                        'parent_id': parseInt(parent.attr('data-id')),
                                        'variation_id': parseInt(parent.attr('data-addon_id')),
                                        'qty': parseInt(parent.find('.addon_prod_qty').val()),
                                        'discount': addon_meta.discount_perc
                                    };

                                    // add to cart
                                    mwc_atc_linked('addon checkbox checked');
                                }

                                // console.log(data);

                            });

                            // 3.2 Qty on change
                            qty_select.on('change', function() {

                                if (checkbox.is(':checked')) {

                                    // update variable product qty if needed
                                    if (linked_data.addon_variable_prods[index]) {
                                        delete linked_data.addon_variable_prods[index].qty;
                                        linked_data.addon_variable_prods[index].qty = parseInt(checkbox.parents('.mwc_item_addon').find('.addon_prod_qty').val())
                                    }

                                    // update simple product qty if needed
                                    if (linked_data.addon_simple_prods[index]) {
                                        delete linked_data.addon_simple_prods[index].qty;
                                        linked_data.addon_simple_prods[index].qty = parseInt(checkbox.parents('.mwc_item_addon').find('.addon_prod_qty').val());
                                    }

                                    // add to cart
                                    mwc_atc_linked('addon qty changed');
                                }

                                // console.log(data);

                            });
                        });

                        // mwc_atc_linked('in the midst of nowhere');

                        // **********
                        // FUNCTIONS
                        // **********
                        // build product bundle product dataset
                        function build_bundle_prod_dataset(target, v_dd, template) {

                            linked_data.linked_bun_prods = [];

                            var prods = [];

                            // -----------
                            // template A
                            // -----------
                            if (template === 'A') {

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });

                                    // simple prods
                                } else {

                                    target.find('.c_prod_item').each(function() {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    })

                                }

                            }

                            // -----------
                            // template H
                            // -----------
                            if (template === 'H') {

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });

                                    // simple prods
                                } else {
                                    target.find('.c_prod_item').each(function() {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    })

                                }

                            }

                            return prods;

                        }

                        // build product bundle product data set on linked product click/dropdown select
                        function build_bundle_prod_dataset_dd_linked(target, v_dd, template) {

                            linked_data.linked_bun_prods = [];

                            var prods = [];

                            // -----------
                            // template A
                            // -----------
                            if (template === 'A') {

                                // variable prod
                                if (v_dd.length) {

                                    target.parents('.mwc_item_div').find('.select-variation-bun').each(function(i, e) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });

                                    // simple prod
                                } else {
                                    target.parents('.mwc_item_div').find('.c_prod_item').each(function(i, e) {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    });
                                }

                            }

                            // -----------
                            // template H
                            // -----------
                            if (template === 'H') {

                                var bundle_id = target.attr('data-bundle_id');

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });

                                    // simple prods
                                } else {
                                    $('mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function() {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    })

                                }

                            }

                            return prods;

                        }

                        // build free bundle product dataset
                        function build_free_prod_dataset(target, v_dd, template) {

                            linked_data.linked_free_prods = [];

                            var prods = [];

                            // -----------
                            // template A
                            // -----------
                            if (template === 'A') {

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {

                                        if ($(this).hasClass('free-prod')) {
                                            prods.push({
                                                'attribute': $(this).val(),
                                                'free_id': $(this).parents('.c_prod_item').attr('data-id')
                                            })
                                        } else {
                                            prods.push({
                                                'attribute': $(this).val(),
                                                'paid_id': $(this).parents('.c_prod_item').attr('data-id')
                                            })
                                        }

                                    });

                                    // simple prods
                                } else {
                                    target.find('.c_prod_item').each(function(i, e) {

                                        if ($(this).hasClass('free-prod')) {
                                            prods.push({
                                                'free_id': $(this).attr('data-id')
                                            })
                                        } else {
                                            prods.push({
                                                'paid_id': $(this).attr('data-id')
                                            })
                                        }

                                    })
                                }

                            }

                            // -----------
                            // template H
                            // -----------
                            if (template === 'H') {

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {

                                        if ($(this).hasClass('free-prod')) {
                                            prods.push({
                                                'attribute': $(this).val(),
                                                'free_id': $(this).parents('.c_prod_item').attr('data-id')
                                            })
                                        } else {
                                            prods.push({
                                                'attribute': $(this).val(),
                                                'paid_id': $(this).parents('.c_prod_item').attr('data-id')
                                            })
                                        }

                                    });

                                    // simple prods
                                } else {
                                    target.find('.c_prod_item').each(function(i, e) {

                                        if ($(this).hasClass('free-prod')) {
                                            prods.push({
                                                'free_id': $(this).attr('data-id')
                                            })
                                        } else {
                                            prods.push({
                                                'paid_id': $(this).attr('data-id')
                                            })
                                        }

                                    })
                                }

                            }

                            return prods;

                        }

                        // build free bundle product data set on linked product click/dropdown select
                        function build_free_prod_dataset_dd_linked(target, v_dd, template) {

                            linked_data.linked_free_prods = [];

                            var prods = [];

                            // template A
                            if (template === 'A') {

                                // variable prods
                                if (v_dd.length) {

                                    target.parents('.mwc_item_div').find('.linked_product.selected').each(function() {

                                        var parent = $(this).parents('.c_prod_item');
                                        var prod_id = $(this).attr('data-linked_id');

                                        if (parent.hasClass('free-item')) {
                                            prods.push({
                                                'attribute': parent.find('.select-variation-free.free-prod').val(),
                                                'free_id': prod_id
                                            })
                                        } else {
                                            prods.push({
                                                'attribute': parent.find('.select-variation-free').val(),
                                                'paid_id': prod_id
                                            })
                                        }
                                    });

                                    // simple prods
                                } else {
                                    target.parents('.mwc_item_div').find('.c_prod_item').each(function(i, e) {

                                        if ($(this).hasClass('free-prod')) {
                                            prods.push({
                                                'free_id': $(this).attr('data-id')
                                            })
                                        } else {
                                            prods.push({
                                                'paid_id': $(this).attr('data-id')
                                            })
                                        }

                                    })
                                }

                            }

                            // -----------
                            // template H
                            // -----------
                            if (template === 'H') {

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {

                                        if ($(this).hasClass('free-prod')) {
                                            prods.push({
                                                'attribute': $(this).val(),
                                                'free_id': $(this).parents('.c_prod_item').attr('data-id')
                                            })
                                        } else {
                                            prods.push({
                                                'attribute': $(this).val(),
                                                'paid_id': $(this).parents('.c_prod_item').attr('data-id')
                                            })
                                        }

                                    });

                                    // simple prods
                                } else {
                                    target.find('.c_prod_item').each(function(i, e) {

                                        if ($(this).hasClass('free-prod')) {
                                            prods.push({
                                                'free_id': $(this).attr('data-id')
                                            })
                                        } else {
                                            prods.push({
                                                'paid_id': $(this).attr('data-id')
                                            })
                                        }

                                    })
                                }

                            }

                            return prods;

                        }

                        // build off bundle product dataset
                        function build_off_prod_dataset(target, v_dd, template) {

                            linked_data.linked_off_prods = [];

                            var prods = [];

                            // -----------
                            // template A
                            // -----------
                            if (template === 'A') {

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });

                                    // simple prods
                                } else {

                                    target.find('.c_prod_item').each(function() {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    })

                                }

                            }

                            // -----------
                            // template H
                            // -----------
                            if (template === 'H') {

                                // variable prods
                                if (v_dd.length) {

                                    $(v_dd).each(function(index, element) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });

                                    // simple prods
                                } else {

                                    target.find('.c_prod_item').each(function() {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    })

                                }
                            }

                            return prods;
                        }

                        // build off bundle product data set on linked product click/dropdown select
                        function build_off_prod_dataset_dd_linked(target, v_dd, template) {

                            linked_data.linked_off_prods = [];

                            var prods = [];

                            // -----------
                            // template A
                            // -----------
                            if (template === 'A') {

                                // variable prod
                                if (v_dd.length) {
                                    target.parents('.mwc_item_div').find('.select-variation-off').each(function(i, e) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });
                                    // simple prod
                                } else {
                                    target.parents('.mwc_item_div').find('.c_prod_item').each(function(i, e) {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    });
                                }

                            }

                            // -----------
                            // template H
                            // -----------
                            if (template === 'H') {

                                // variable prod
                                if (v_dd.length) {
                                    target.parents('.mwc_product_variations').find('.select-variation-off').each(function(i, e) {
                                        prods.push({
                                            'attribute': $(this).val(),
                                            'prod_id': $(this).parents('.c_prod_item').attr('data-id')
                                        })
                                    });
                                    // simple prod
                                } else {
                                    target.parents('.mwc_product_variations').find('.c_prod_item').each(function(i, e) {
                                        prods.push({
                                            'prod_id': $(this).attr('data-id')
                                        })
                                    });
                                }

                            }

                            return prods;
                        }
                    }

                    // 5. Add linked products to cart
                    function mwc_atc_linked(source) {

                        var ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>';

                        // debug
                        // console.log(source);
                        // console.log(linked_data);

                        setTimeout(() => {

                            // debug
                            // console.log('triggering mwc_atc_linked after 1 second...');

                            // send ajax request without cache
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: linked_data,
                                cache: false,
                                success: function(response) {

                                    // debug
                                    // console.log(response);

                                }
                            });

                        }, 1000);
                    }

                });
            </script>

<?php }
    }

endif;
?>