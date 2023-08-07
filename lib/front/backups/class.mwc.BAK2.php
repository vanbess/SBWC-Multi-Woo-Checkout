<?php

if (!class_exists('woocommerce')) :

    class MWC {

        // vars
        private static $initiated = false;
        public static $mwc_products_variations = [];
        public static $mwc_products_variations_prices = [];
        public static $addon_products = '';
        public static $mwc_product_variations = [];

        /**
         * Init
         *
         * @return void
         */
        public static function init() {
            if (!self::$initiated) :
                self::init_hooks();
            endif;
        }

        /**
         * Initializes WordPress hooks
         */
        private static function init_hooks() {

            self::$initiated = true;

            // script
            add_action('wp_footer', array('MWC', 'load_resources'));

            // action ajax add products to cart
            add_action('wp_ajax_mwc_add_to_cart_multiple', array('MWC', 'mwc_add_to_cart_multiple'));
            add_action('wp_ajax_nopriv_mwc_add_to_cart_multiple', array('MWC', 'mwc_add_to_cart_multiple'));

            // action get price summary table
            add_action('wp_ajax_mwc_get_price_summary_table', array('MWC', 'mwc_get_price_summary_table'));
            add_action('wp_ajax_nopriv_mwc_get_price_summary_table', array('MWC', 'mwc_get_price_summary_table'));

            // action get price mwc package
            add_action('wp_ajax_mwc_get_price_package', array('MWC', 'mwc_get_price_package'));
            add_action('wp_ajax_nopriv_mwc_get_price_package', array('MWC', 'mwc_get_price_package'));

            // acction to apply cart discount
            add_action('woocommerce_before_calculate_totals', array('MWC', 'mwc_apply_cart_discounts'), PHP_INT_MAX);

            // action add referer to order note
            add_action('woocommerce_order_status_processing', array(__CLASS__, 'add_referer_url_order_note'), 10, 1);

            // register required PLL strings if Polylang installed
            if (function_exists('pll_register_string')) :
                pll_register_string('mwc_1', 'Please select at least one product bundle first!', 'mwc-strings');
                pll_register_string('mwc_2', 'Please select a product bundle first!', 'mwc-strings');
                pll_register_string('mwc_3', 'Click to select add-on product', 'mwc-strings');
            endif;
        }


        /**
         * Applies various bundle and add-on discounts if items found in cart
         *
         * @param object $cart
         * @return void
         */
        public static function mwc_apply_cart_discounts($cart) {

            // start session if not started
            if (!session_id()) :
                session_start();
            endif;

            // holds calculated bundle subtotal for later use
            $bundle_stotal = 0;

            // holds total discount for free products
            $free_discount = 0;

            // holds paid prod count
            $paid_prod_count = 0;

            // holds free prod count
            $free_prod_count = 0;

            // holds discount percentage (product bundle)
            $disc_perc = 0;

            // holds off pecentage (buy x get x off)
            $off_perc = 0;

            $addon_disc_total = 0;

            // retrieve bundle id
            $bundle_id = isset($_SESSION['mwc_bundle_id']) ? $_SESSION['mwc_bundle_id'] : false;

            // if $bundle_id === false, bail
            if ($bundle_id === false) :
                return;
            endif;

            // retrieve bundle data for later ref as/when needed
            $bundle_data = get_post_meta($bundle_id, 'product_discount', true);
            $bundle_data = is_array($bundle_data) ? $bundle_data : json_decode($bundle_data, true);

            // retrieve bundle type
            $bundle_type = $bundle_data['selValue'];

            // ********************************************
            // loop through cart items and updated as need
            // ********************************************
            foreach ($cart->get_cart() as $cart_item) :

                // set product regular price in cart, else sale price if present is used instead, causing calculation issues
                $cart_item['data']->set_price(get_post_meta($cart_item['data']->get_id(), '_regular_price', true));

                // ****
                // bun
                // ****
                if (isset($cart_item['mwc_bun_discount'])) :

                    $disc_perc = (int)$cart_item['mwc_bun_discount'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_price * $item_qty;

                    // add to bundle subtotal variable
                    $bundle_stotal += $item_total;

                    // discount fee name
                    $fee_name = sprintf(__('Bundle discount - %d&#37;', 'woocommerce'), $disc_perc);

                endif;

                // ****
                // off
                // ****
                if (isset($cart_item['mwc_off_discount'])) :

                    // retrieve discount
                    $off_perc = (int)$cart_item['mwc_off_discount'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_price * $item_qty;

                    // add to bundle subtotal variable
                    $bundle_stotal += $item_total;

                    // discount fee name
                    $off_fee_name = sprintf(__('Buy %s + Get %d&#37; Off', 'woocommerce'), $item_qty, $off_perc);

                endif;

                // **********
                // free prod
                // **********
                if (isset($cart_item['mwc_bun_free_prod'])) :

                    // get free prod count
                    $free_prod_count += (int)$cart_item['quantity'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_price * $item_qty;

                    // add to bundle subtotal variable
                    $free_discount += $item_total;

                endif;

                // **********
                // paid prod
                // **********
                if (isset($cart_item['mwc_bun_paid_prod'])) :

                    // get paid prod count
                    $paid_prod_count += (int)$cart_item['quantity'];

                endif;

                // *******
                // add-on
                // *******
                if (isset($cart_item['mwc_addon_disc'])) :

                    // retrieve discount
                    $addon_off_perc = (int)$cart_item['mwc_addon_disc'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_price * $item_qty;

                    // discount multiplier
                    $disc_mp = (100 - $addon_off_perc) / 100;

                    // addon discount total
                    $addon_disc_total += $item_total - ($item_total * $disc_mp);

                    // add discount fee name
                    $add_fee_name = __('Add-on Product Discount', 'woocommerce');

                endif;


            endforeach;

            // ***************************************
            // Do cart fee calc for bundle discount
            // ***************************************
            if ($disc_perc !== 0) :
                $disc_mp = (100 - (int)$disc_perc) / 100;
                $disc_amt = (float)$bundle_stotal - ((float)$bundle_stotal * (float)$disc_mp);
                $cart->add_fee($fee_name, -$disc_amt, true);
            endif;

            // **********************************************
            // Do cart fee calc for buy x get x off products
            // **********************************************
            if ($off_perc !== 0) :
                $disc_mp = (100 - (int)$off_perc) / 100;
                $disc_amt = (float)$bundle_stotal - ((float)$bundle_stotal * (float)$disc_mp);
                $cart->add_fee($off_fee_name, -$disc_amt, true);
            endif;

            // ***********************************
            // Do cart fee calc for free products
            // ***********************************
            if ($free_discount !== 0) :
                $fee_name = sprintf(__('Buy %d + Get %d Free', 'woocommerce'), $paid_prod_count, $free_prod_count);
                $cart->add_fee($fee_name, -$free_discount, true);
            endif;

            // *************************************
            // Do cart fee calc for add-on products
            // *************************************
            if ($addon_disc_total !== 0) :
                $cart->add_fee($add_fee_name, -$addon_disc_total, true);
            endif;
        }

        /**
         * load_resources
         *
         * @return void
         */
        public static function load_resources() {

            global $woocommerce;

            // setup cart and checkout urls
            $cart_url = '/cart/';
            $checkout_url = '/checkout/';

            if (!empty($woocommerce)) :
                $cart_url = wc_get_cart_url();
                $checkout_url = wc_get_checkout_url();
            endif;

            wp_enqueue_style('mwc_common_style', MWC_PLUGIN_URL . 'resources/style/common.css', array(), MWCVersion, 'all');
            wp_enqueue_style('mwc_style', MWC_PLUGIN_URL . 'resources/style/front_style.css', array(), MWCVersion . time(), 'all');
            wp_enqueue_script('mwc_front_script_js', MWC_PLUGIN_URL . 'resources/js/front_js.js', ['jquery'], time(), true);

            wp_localize_script(
                'mwc_front_script_js',
                'mwc_ajax_obj',
                array(
                    'ajax_url'              => admin_url('admin-ajax.php'),
                    'home_url'              => home_url(),
                    'cart_url'              => $cart_url,
                    'checkout_url'          => $checkout_url,
                    'summary_price_nonce'   => wp_create_nonce('get set summary prices'),
                    'variation_price_nonce' => wp_create_nonce('get set variation prices'),
                    'atc_nonce'             => wp_create_nonce('add multiple products to cart')
                )
            );

            /**
             * New ATC scripts
             */
            wp_enqueue_script('mwc_atc_reworked', self::mwc_atc_js(), ['jquery'], '', true);
        }

        /**
         * Add to cart JS is moved here now for better/easier bugfixing (shortcode template A)
         *
         * @return void
         */
        public static function mwc_atc_js() { ?>

            <script id="mwc-atc-updated">
                jQuery(document).ready(function($) {

                    

                    /*****************************************************
                     * Click bundle checkbox on bundle container on click
                     *****************************************************/
                    $('.mwc_item_div').click(function(e) {

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
                    if ($('.mwc_active_product').length === 0) {
                        $('.mwc_item_addon').css('cursor', 'no-drop').attr('title', '<?php _e('Please select a product bundle first!', 'woocommerce') ?>').parent().attr('disabled', true);
                        $('.mwc_checkbox_addon, .addon_var_select, .addon_prod_qty, label, .mwc_fancybox_open').attr('disabled', true).css('cursor', 'no-drop');
                    }

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
                     * Bundle variation is and img on load
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
                                parent.find('.variation_img > img').attr('src', var_details.image.src);
                            }

                        });

                    });

                    // setup json data array for atc ajax request
                    var data = {
                        'addon_variable_prods': {},
                        'addon_simple_prods': {},
                        'action': 'mwc_add_to_cart_multiple',
                        '_ajax_nonce': '<?php echo wp_create_nonce('add multiple products to cart') ?>'
                    };

                    /**************************************
                     * ADD TO AJAX DATA OBJECT - MWC ITEMS
                     **************************************/
                    $('.mwc_item_div').change(function(e) {

                        // if ($(this).hasClass('template_h')) {
                        //     console.log('has class template h');
                        //     return;
                        // }

                        // enable addons if present
                        if ($('.mwc_item_addon').length > 0) {
                            $('.mwc_item_addon').css('cursor', 'pointer').attr('title', '<?php _e('Click to select add-on product', 'woocommerce') ?>').parent().attr('disabled', false);
                            $('.mwc_checkbox_addon, .addon_var_select, .addon_prod_qty, label, .mwc_fancybox_open').attr('disabled', false).css('cursor', 'pointer');
                        }

                        // used for searching main container for data
                        var mwc_main = $(this);

                        // retrieve bundle data
                        var bun_data = JSON.parse(atob(mwc_main.data('bundle-data')));

                        // add required data to ajax data object
                        data.bundle_type = bun_data.type;
                        data.bundle_id = parseInt(bun_data.bun_id);

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
                            data.bun_discount = parseInt(bun_data.discount_percentage);

                            // ======================
                            // 1.1 If variable prods
                            // ======================

                            // -----------
                            // Template H
                            // -----------
                            if (mwc_main.hasClass('template_h')) {

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
                                            parent.find('.variation_img > img').attr('src', var_details.image.src);
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

                                // -----------
                                // Template A
                                // -----------
                            } else {

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
                                                    parent.find('.variation_img > img').attr('src', var_details.image.src);
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
                                                parent.find('.variation_img > img').attr('src', var_details.image.src);
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
                            data.off_discount = parseInt(bun_data.coupon);

                            // -----------
                            // Template H
                            // -----------
                            if (mwc_main.hasClass('template_h')) {

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
                                                parent.find('.variation_img > img').attr('src', var_details.image.src);
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

                                // -----------
                                // Template A
                                // -----------
                            } else {

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
                                                    parent.find('.variation_img > img').attr('src', var_details.image.src);
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
                                                parent.find('.variation_img > img').attr('src', var_details.image.src);
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

                            // -----------
                            // Template H
                            // -----------
                            if (mwc_main.hasClass('template_h')) {

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
                                                parent.find('.variation_img > img').attr('src', var_details.image.src);
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

                                // -----------
                                // Template A
                                // -----------
                            } else {

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
                                                    parent.find('.variation_img > img').attr('src', var_details.image.src);
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
                                                parent.find('.variation_img > img').attr('src', var_details.image.src);
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

                        // checbox on click
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
                            $.post(ajaxurl, data, function(response) {
                                // console.log(response);
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

                });
            </script>

            <?php }

        /**
         * mwc_add_to_cart_multiple AJAX action
         *
         * @return json
         */
        public static function mwc_add_to_cart_multiple() {

            check_ajax_referer('add multiple products to cart');

            // uncomment to test payload
            // print_r($_POST);
            // wp_die();

            // start session if not started
            if (!session_id()) :
                session_start();
            endif;

            // empty cart completely on each submission
            wc()->cart->empty_cart();

            // retrieve bundle type
            $bundle_type = isset($_POST['bundle_type']) ? $_POST['bundle_type'] : false;

            // retrieve bundle id
            $bundle_id = isset($_POST['bundle_id']) ? $_POST['bundle_id'] : false;

            // check shipping settings
            $bundle_meta = get_post_meta($bundle_id, 'product_discount', true);
            $free_shipping = isset($bundle_meta['free_shipping']) ? $bundle_meta['free_shipping'] : false;

            // add bundle id to session for later ref
            $_SESSION['mwc_bundle_id'] = $bundle_id;

            // holds all bundle and addon cart keys for later reference/troubleshooting
            $bundle_cart_keys = [];

            // *******************
            // 1. ADD BUN TO CART
            // *******************
            if ($bundle_type === 'bun') :

                // retrieve bundle variable product data
                $bun_variable_prods = isset($_POST['bun_variable_prods']) ? $_POST['bun_variable_prods'] : null;

                // retrieve bundle variable product data
                $bun_simple_prods = isset($_POST['bun_simple_prods']) ? $_POST['bun_simple_prods'] : null;

                // retrieve bundle discount
                $bun_discount = isset($_POST['bun_discount']) ? (int)$_POST['bun_discount'] : null;

                // 1.1 Add variable products to cart if present
                if (!is_null($bun_variable_prods) && !empty($bun_variable_prods)) :
                    foreach ($bun_variable_prods as $var_data) :
                        $bundle_cart_keys['variable_prod_keys'][] = wc()->cart->add_to_cart($var_data['parent_id'], 1, $var_data['variation_id'], [], ['mwc_bun_discount' => $bun_discount]);
                    endforeach;
                endif;

                // 1.2 Add simple products to cart if present
                if (!is_null($bun_simple_prods) && !empty($bun_simple_prods)) :
                    foreach ($bun_simple_prods as $simp_id) :
                        $bundle_cart_keys['simple_prod_keys'][] = wc()->cart->add_to_cart($simp_id, 1, 0, [], ['mwc_bun_discount' => $bun_discount]);
                    endforeach;
                endif;

                // set shipping
                if ($free_shipping === true) :
                    wc()->cart->set_shipping_total(0);
                endif;

            endif;

            // *******************
            // 2. ADD OFF TO CART
            // *******************
            if ($bundle_type === 'off') :

                // discount
                $discount = (int)$_POST['off_discount'];

                // variable prods
                $variable_prods = isset($_POST['off_variable_prods']) ? $_POST['off_variable_prods'] : null;

                // simple prod
                $simple_prod = isset($_POST['off_simple_prod']) ? $_POST['off_simple_prod'] : null;

                // add variable products to cart
                if (is_array($variable_prods) && !empty($variable_prods)) :
                    foreach ($variable_prods as $v_data) :
                        $bundle_cart_keys['var_item_keys'][] = wc()->cart->add_to_cart($v_data['parent_id'], 1, $v_data['variation_id'], [], ['mwc_off_discount' => $discount]);
                    endforeach;
                endif;

                // add simple products to cart
                if (is_array($simple_prod) && !empty($simple_prod)) :
                    $bundle_cart_keys['simp_item_key'][] = wc()->cart->add_to_cart($simple_prod['prod_id'], $simple_prod['prod_qty'], 0, [], ['mwc_off_discount' => $discount]);
                endif;

                // set shipping
                if ($free_shipping === true) :
                    wc()->cart->set_shipping_total(0);
                endif;

            endif;

            // ********************
            // 3. ADD FREE TO CART
            // ********************
            if ($bundle_type === 'free') :

                // print_r($_POST);

                // wp_die();

                // retrieve paid variable prods and add to cart
                $paid_variable_prods = isset($_POST['paid_variable_prods']) ? $_POST['paid_variable_prods'] : null;

                if (is_array($paid_variable_prods) && !empty($paid_variable_prods)) :
                    foreach ($paid_variable_prods as $v_data) :
                        $bundle_cart_keys['paid_var_prods'][] = wc()->cart->add_to_cart($v_data['parent_id'], 1, $v_data['variation_id'], [], ['mwc_bun_paid_prod' => true]);
                    endforeach;
                endif;

                // retrieve free variable prods and add to cart
                $free_variable_prods = isset($_POST['free_variable_prods']) ? $_POST['free_variable_prods'] : null;

                if (is_array($free_variable_prods) && !empty($free_variable_prods)) :
                    foreach ($free_variable_prods as $v_data) :
                        $bundle_cart_keys['free_var_prods'][] = wc()->cart->add_to_cart($v_data['parent_id'], 1, $v_data['variation_id'], [], ['mwc_bun_free_prod' => true]);
                    endforeach;
                endif;

                // retrieve paid simple prods and add to cart
                $paid_simple_prods = isset($_POST['paid_simple_prod']) ? $_POST['paid_simple_prod'] : null;

                if (is_array($paid_simple_prods) && !empty($paid_simple_prods)) :
                    foreach ($paid_simple_prods as $s_data) :
                        $bundle_cart_keys['paid_simp_prods'][] = wc()->cart->add_to_cart($s_data['id'], $s_data['qty'], 0, [], ['mwc_bun_paid_prod' => true]);
                    endforeach;
                endif;

                // retrieve free simple prods and add to cart
                $free_simple_prods = isset($_POST['free_simple_prod']) ? $_POST['free_simple_prod'] : null;

                if (is_array($free_simple_prods) && !empty($free_simple_prods)) :
                    foreach ($free_simple_prods as $s_data) :
                        $bundle_cart_keys['free_simp_prods'][] = wc()->cart->add_to_cart($s_data['id'], $s_data['qty'], 0, [], ['mwc_bun_free_prod' => true]);
                    endforeach;
                endif;

                // set shipping
                if ($free_shipping === true) :
                    wc()->cart->set_shipping_total(0);
                endif;

            endif;

            // ****************************
            // 4. ADD ADD-ON PRODS TO CART 
            // ****************************

            // simple addon prods to cart
            $simple_addons = isset($_POST['addon_simple_prods']) ? $_POST['addon_simple_prods'] : false;

            if ($simple_addons !== false && !empty($simple_addons)) :
                foreach ($simple_addons as $s_addon) :
                    $bundle_cart_keys['simple_addons'][] = wc()->cart->add_to_cart($s_addon['simple_id'], $s_addon['qty'], 0, [], ['mwc_addon_disc' => (int)$s_addon['discount']]);
                endforeach;
            endif;

            // variable addon prods to cart
            $variable_addons = isset($_POST['addon_variable_prods']) ? $_POST['addon_variable_prods'] : false;

            if ($variable_addons !== false && !empty($variable_addons)) :
                foreach ($variable_addons as $v_addon) :
                    $bundle_cart_keys['variable_addons'][] = wc()->cart->add_to_cart($v_addon['parent_id'], $v_addon['qty'], $v_addon['variation_id'], [], ['mwc_addon_disc' => (int)$v_addon['discount']]);
                endforeach;
            endif;

            // return error or success
            if (!empty($bundle_cart_keys)) :
                wp_send_json_success($bundle_cart_keys);
            else :
                wp_send_json_error();
            endif;

            wp_die();
        }

        /**
         * return_wc_dropdown_variation_attribute_options dropdown
         *
         * @param array $args
         * @return void
         */
        public static function return_wc_dropdown_variation_attribute_options($args = array()) {

            $args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), array(
                'options'          => false,
                'attribute'        => false,
                'product'          => false,
                'selected'         => false,
                'n_item'           => false,
                'img_variations'   => '',
                'name'             => '',
                'id'               => '',
                'class'            => '',
                'show_option_none' => __('Choose an option', 'woocommerce'),
            ));

            $options               = $args['options'];
            $product               = $args['product'];
            $attribute             = $args['attribute'];
            $name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title($attribute);
            $id                    = $args['id'] ? $args['id'] : sanitize_title($attribute);
            $class                 = $args['class'];

            if (empty($options) && !empty($product) && !empty($attribute)) :
                $attributes = $product->get_variation_attributes();
                $options    = $attributes[$attribute];
            endif;

            $html  = '<select class="' . esc_attr($class) . ' mwc_product_attribute sel_product_' . esc_attr($id) . '" name="i_variation_' . esc_attr($name) . '" data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute)) . '" data-item="' . $args['n_item'] . '">';

            if (!empty($options)) :
                if ($product && taxonomy_exists($attribute)) :
                    // Get terms if this is a taxonomy - ordered. We need the names too.
                    $terms = wc_get_product_terms($product->get_id(), $attribute, array(
                        'fields' => 'all',
                    ));

                    foreach ($terms as $i => $term) :
                        if (in_array($term->slug, $options, true)) :
                            $html .= '<option data-item="' . $args['n_item'] . '" data-img="' . $args['img_variations'][$i] . '" value="' . esc_attr($term->slug) . '" ' . selected(sanitize_title($args['selected']), $term->slug, false) . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '</option>';
                        endif;
                    endforeach;
                else :
                    foreach ($options as $option) :
                        // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                        $selected = sanitize_title($args['selected']) === $args['selected'] ? selected($args['selected'], sanitize_title($option), false) : selected($args['selected'], $option, false);
                        $html    .= '<option data-item="' . $args['n_item'] . '" value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $option)) . '</option>';
                    endforeach;
                endif;
            endif;

            $html .= '</select>';

            return apply_filters('woocommerce_dropdown_variation_attribute_options_html', $html, $args); // WPCS: XSS ok.
        }

        /**
         * mwc_return_onepage_checkout_variation_dropdown HTML
         *
         * @param array $args
         * @return html
         */
        public static function mwc_return_onepage_checkout_variation_dropdown($args = []) {

            $html = '';

            if ($args['options']) :

                $product_id            = $args['product_id'];
                $options               = $args['options'];
                $attribute_name        = $args['attribute_name'];
                $default_option        = $args['default_option'];
                $disable_woo_swatches  = !empty($args['disable_woo_swatches']) ? $args['disable_woo_swatches'] : 'no';
                $var_data              = isset($args['var_data']) ? $args['var_data'] : null;
                $name                  = isset($args['name']) ? $args['name'] : '';
                $id                    = isset($args['id']) ? $args['id'] : '';
                $class                 = isset($args['class']) ? $args['class'] : '';
                $type                  = isset($args['type']) ? $args['type'] : 'dropdown';

                $_hidden = false;

                // retrieve product object
                $product = wc_get_product($product_id);

                // load label woothumb(Wooswatch)
                $woothumb_products = get_post_meta($product_id, '_coloredvariables', true);

                // get woothumb attribute name
                $woothumb = !empty($woothumb_products[$attribute_name]) ? $woothumb_products[$attribute_name] : '';

                if ($var_data && !empty($woothumb_products[$attribute_name])) :

                    // get woothumb attribute name
                    $woothumb = $woothumb_products[$attribute_name];

                    $taxonomies = array($attribute_name);
                    $args = array(
                        'hide_empty' => 0
                    );

                    $newvalues = get_terms($taxonomies, $args);

                    // woothumb type color of image
                    if ($disable_woo_swatches != 'yes' && $woothumb['display_type'] == 'colororimage') :

                        // hidden dropdown
                        $_hidden = true;

                        $extra = array(
                            "display_type" => $woothumb['display_type']
                        );

                        if (class_exists('wcva_swatch_form_fields')) :
                            $swatch_fields = new wcva_swatch_form_fields();
                            $swatch_fields->wcva_load_colored_select($product, $attribute_name, $options, $woothumb_products, $newvalues, $default_option, $extra, 2);
                        else :
                            $html .= '<div class="attribute-swatch" attribute-index>
                        <div class="swatchinput">';
                            foreach ($options as $key => $option) :

                                // get slug attribute
                                $term_obj  = get_term_by('slug', $option, $attribute_name);
                                if ($term_obj) :
                                    $option = $term_obj->slug;
                                endif;

                                // show option image
                                if ($woothumb['values'][$option]['type'] == 'Image') :
                                    // get image option
                                    $label_image = wp_get_attachment_thumb_url($woothumb['values'][$option]['image']);

                                    $html .= '<label selectid="' . $attribute_name . '" class="attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . ' wcvaswatchlabel  wcvaround" data-option="' . $option . '" style="background-image:url(' . $label_image . '); width:32px; height:32px; "></label>';
                                // show option color
                                elseif ($woothumb['values'][$option]['type'] == 'Color') :
                                    // get color option
                                    $label_color = $woothumb['values'][$option]['color'];

                                    $html .= '<label selectid="' . $attribute_name . '" class="attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . ' wcvaswatchlabel  wcvaround" data-option="' . $option . '" style="background-color:' . $label_color . '; width:32px; height:32px; "></label>';
                                // show option text block
                                else :
                                    // get text block option
                                    $label_text = $woothumb['values'][$option]['textblock'];
                                    $html .= '<label selectid="' . $attribute_name . '" class="attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . ' wcvaswatchlabel  wcvaround" data-option="' . $option . '" style="width:32px; height:32px; ">' . $label_text . '</label>';
                                endif;
                            endforeach;
                            $html .= '</div></div>';
                        endif;
                    endif;
                // woothumb type variation image
                elseif ($disable_woo_swatches != 'yes' && !empty($woothumb) && $woothumb['display_type'] == 'variationimage') :
                    // hidden dropdown
                    $_hidden = true;

                    $html .= '<div class="select_woothumb">';
                    foreach ($options as $key => $option) :

                        // get slug attribute
                        $term_obj  = get_term_by('slug', $option, $attribute_name);
                        if ($term_obj) :
                            $option = $term_obj->slug;
                        endif;

                        $html .= '<label class="label_woothumb attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . '"
                        data-option="' . $option . '" style="background-image:url(' . $var_data[$key]['image'] . ');  width:40px; height:40px; "></label>';
                    endforeach;
                    $html .= '</div>';
                endif;


                // default dropdown

                // add post_id ACF
                add_filter('acf/pre_load_post_id', function () use ($product_id) {
                    return $product_id;
                }, 1, 2);

                // load select option
                if ('dropdown' === $type) :

                    $html .= '<select id="' . $id . '" class="' . $class . '" name="" data-variations="' . base64_encode(json_encode($product->get_available_variations())) . '" data-attribute_name="attribute_' . $attribute_name . '" ' . (($_hidden) ? 'style="display:none"' : '') . '>';

                    $options = wc_get_product_terms($product_id, $attribute_name);

                    foreach ($options as $key => $option) :
                        $html .= '<option value="' . $option->slug . '" ' . (($default_option == $option->slug) ? 'selected' : '') . '>' . apply_filters('woocommerce_variation_option_name', $option->name) . '</option>';
                    endforeach;

                    $html .= '</select>';

                else :

                    $options = $product->get_variation_attributes()[$attribute_name];
                    $html .= riode_wc_product_listed_attributes_html($attribute_name, $options, $product, 'label', true);

                endif;

            endif;
            return $html;
        }

        /**
         * mwc_return_linked_variations_dropdown HTML
         *
         * @param array $args
         * @param array $var_data
         * @return html
         */
        public static function mwc_return_linked_variations_dropdown($args = [], &$var_data = []) {

            $html = '';

            if (!empty($args)) :

                $product_id       = $args['product_id'];
                $general_settings = get_option('plgfyqdp_save_gnrl_settingsplv');

                if (!$general_settings) :

                    $general_settings = array(
                        'is_hyper' => 'true',
                        'brdractive'      => '#621bff',
                        'brdrinactv'      => '#dddddd',
                        'pdngclr'         => '#ffffff',
                        'bckgrdclr'       => '#f1f1f1',
                        'txtclr'          => '#000000'
                    );

                endif;

                $is_hyper = $general_settings['is_hyper'];

                if ('true' == $is_hyper) :
                    $is_hyper = ' target="_blank" ';
                else :
                    $is_hyper = '';
                endif;

                $current_id = $product_id;
                $all        = [];
                $sub        = [];
                $is_applied = '';
                $all_rules  = get_option('plgfymao_all_rulesplgfyplv');

                if ('' == $all_rules) :
                    $all_rules = [];
                endif;

                $all_attributes   = wc_get_attribute_taxonomies();

                // loop
                foreach ($all_rules as $key => $value) :

                    $to_be_sent     = $all_rules[$key];
                    $previous_attrs = [];

                    if (isset($to_be_sent['selected_checks_attr'])) :

                        // loop
                        foreach ($to_be_sent['selected_checks_attr'] as $keyi => $valuei) :
                            $attribute_id = $valuei[4];
                            $new_record_against_attr_id = $all_attributes['id:' . $attribute_id];
                            $all_rules[$key]['selected_checks_attr'][$keyi][0] = $new_record_against_attr_id->attribute_name;
                            $all_rules[$key]['selected_checks_attr'][$keyi][3] = $new_record_against_attr_id->attribute_label;
                            $previous_attrs[] = $valuei[4];
                        endforeach;

                    endif;

                    // loop
                    foreach ($all_attributes as $key11 => $value11) :

                        if (!in_array($value11->attribute_id, $previous_attrs)) :
                            $new_attribute = array($value11->attribute_name, 'false', 'false', $value11->attribute_label, $value11->attribute_id);
                            $all_rules[$key]['selected_checks_attr'][] = $new_attribute;
                        endif;

                    endforeach;

                endforeach;

                update_option('plgfymao_all_rulesplgfyplv', $all_rules);

                $all_rules = get_option('plgfymao_all_rulesplgfyplv');

                if ('' == $all_rules) :
                    $all_rules = [];
                endif;

                $all = [];

                // loop
                foreach ($all_rules as $key => $val) :

                    if ('true' == $val['plgfyplv_activate_rule']) :

                        if ('Products' == $val['applied_on']) :

                            if (in_array($current_id, $val['apllied_on_ids'])) :
                                $linked          = [];
                                $breakitbab      = true;
                                $linked          = $val['apllied_on_ids'];
                            endif;

                        else :
                            $prod_ids = [];

                            // loop
                            foreach ($val['apllied_on_ids'] as $key0po => $value0po) :

                                $all_prod_ids_q = get_posts(array(
                                    'post_type'   => array('product', 'product_variation'),
                                    'numberposts' => -1,
                                    'post_status' => 'publish',
                                    'fields'      => 'ids',
                                    'tax_query'   => array(
                                        array(
                                            'taxonomy' => 'product_cat',
                                            'terms'    => $value0po,
                                            'operator' => 'IN',
                                        )
                                    )
                                ));

                                // loop
                                foreach ($all_prod_ids_q as $idalp => $valalp) :
                                    $prod_ids[] = $valalp;
                                endforeach;

                                if (in_array($current_id, $all_prod_ids_q)) :

                                    $linked = [];

                                    // loop
                                    foreach ($prod_ids as $keypprr => $valuepprr) :
                                        $linked[] = $valuepprr;
                                    endforeach;

                                    $breakitbab      = true;

                                endif;
                            endforeach;
                        endif;

                        $sub = [];
                        $atr = [];

                        // loop
                        foreach ($val['selected_checks_attr'] as $key => $val) :
                            if ('true' == $val[1]) :
                                $atr[] = $val[0];
                            endif;
                        endforeach;

                        if ($breakitbab) :

                            $sub[] = $linked;
                            $sub[] = $atr;
                            $all[] = $sub;
                            $is_applied = $key;
                            break;
                        endif;
                    endif;
                endforeach;

                if ('-1' > $is_applied) :
                    return;
                endif;

                $attr_slug_linked_prods = [];

                // loop
                foreach ($all[0][1] as $key => $attrib_slug) :

                    $uppersub = [];

                    if (count($all[0][0]) > 0) :
                        $al_grouped_p_idsyyuiop = $all[0][0];
                        $temp_val_of_0 = $al_grouped_p_idsyyuiop[0];
                        $al_grouped_p_idsyyuiop[0] = $current_id;
                        $al_grouped_p_idsyyuiop[] = $temp_val_of_0;
                        $al_grouped_p_idsyyuiop = array_unique($al_grouped_p_idsyyuiop);
                    endif;

                    // loop
                    foreach ($al_grouped_p_idsyyuiop as $key => $applied_on_id_pid) :

                        $product  = wc_get_product($applied_on_id_pid);
                        $innersub = [];
                        $attribs  = $product->get_attribute($attrib_slug);

                        if ('' != $attribs) :
                            $attribs = explode(',', $attribs);
                            $attribs = $attribs[0];
                            $innersub[] = $attribs;
                            $innersub[] = $applied_on_id_pid;
                            $uppersub[] = $innersub;
                        endif;

                    endforeach;

                    $attr_slug_linked_prods[$attrib_slug] = $uppersub;
                endforeach;

                // loop
                foreach ($attr_slug_linked_prods as $attr_slug => $all_linked_products) :

                    $istrue     = false;

                    // loop
                    foreach ($all_rules[$is_applied]['selected_checks_attr'] as $lostkey => $lost_val) :

                        if ($attr_slug == $lost_val[0]) :
                            $istrue = $lost_val[2];
                            break;
                        endif;

                    endforeach;
            ?>

                    <div class="variation_item">

                        <p class="variation_name"><?php echo __('Color', 'woocommerce') ?>: </p>

                        <div class="attribute-swatch" attribute-index="">
                            <div class="swatchinput">
                                <?php

                                $unique_attrs = [];

                                // loop
                                foreach ($all_linked_products as $keyplugify => $valueplugify) :

                                    $is_out_of_stock = 'false';
                                    $_backorders     = get_post_meta($valueplugify[1], '_backorders', true);
                                    $stock_status    = get_post_meta($valueplugify[1], '_stock_status', true);

                                    // Image swatch for linked products
                                    $product = wc_get_product($valueplugify[1]);

                                    if (!isset($var_data[$valueplugify[1]]) && $product->is_type('variable')) :

                                        $var_arr = [];

                                        // loop
                                        foreach ($product->get_available_variations() as $key => $value) :

                                            $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_option('woocommerce_currency');

                                            array_push($var_arr, [
                                                'id'         => $value['variation_id'],
                                                // 'price'      => is_array$product['custom_price'][$value['variation_id']][$current_curr],
                                                'price'      => '',
                                                'attributes' => $value['attributes'],
                                                'image'      => $value['image']['url']
                                            ]);

                                        endforeach;

                                        $var_data[$valueplugify[1]] = $var_arr;
                                    endif;

                                    // if WC exits and Riode active
                                    if (class_exists('WooCommerce') && defined('RIODE_VERSION')) :

                                        $term_ids = wc_get_product_terms($valueplugify[1], 'pa_color', array('fields' => 'ids'));

                                        // loop
                                        foreach ($term_ids as $term_id) :
                                            $attr_value = get_term_meta($term_id, 'attr_color', true);
                                            $attr_img   = get_term_meta($term_id, 'attr_image', true);
                                        endforeach;

                                    endif;

                                    // if stock status is in stock
                                    if ('instock' == $stock_status) :

                                        $stock_count   = get_post_meta($valueplugify[1], '_stock', true);
                                        $_manage_stock = get_post_meta($valueplugify[1], '_manage_stock', true);
                                        $_backorders   = get_post_meta($valueplugify[1], '_backorders', true);

                                        if ('no' != $_manage_stock && 0 >= $stock_count && 'no' == $_backorders) :
                                            $is_out_of_stock = 'true';
                                        endif;

                                    // if stock status is out of stock
                                    elseif ('outofstock' == $stock_status && 'no' == $_backorders) :
                                        $is_out_of_stock = 'true';
                                    endif;

                                    if ('' != $valueplugify[0]) :

                                        if (!in_array($valueplugify[0], $unique_attrs)) :

                                            $unique_attrs[] = $valueplugify[0];

                                            if ($valueplugify[1] == $current_id) :

                                                if ('true' == $istrue) :

                                                    $image    = wp_get_attachment_image_src(get_post_thumbnail_id($valueplugify[1]), 'single-post-thumbnail');
                                                    $img_srcy = '';

                                                    if ('' == $image) :
                                                        $image = [];
                                                    endif;

                                                    if (0 < count($image) && isset($image[0]) && '' != $image[0]) :
                                                        $img_srcy = $image[0];
                                                    else :
                                                        $img_srcy = plugins_url() . '/products-linked-by-variations-for-woocommerce/Front/Assets/woocommerce-placeholder-plugify.png';
                                                    endif;
                                ?>
                                                    <div class="imgclasssmallactive tooltipplugify" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid green;">
                                                        <img class="child_class_plugify" style="height: 40px;text-align: center;" src="<?php echo filter_var($img_srcy); ?>">
                                                        <div class="tooltiptextplugify">
                                                            <?php echo filter_var($valueplugify[0]); ?>
                                                        </div>
                                                    </div>
                                                    <?php

                                                else :
                                                    if ($attr_value) : ?>
                                                        <label selectid="" class="wcvaswatchlabel wcvaround linked_product selected" data-attribute_name="attribute_pa_color" data-option="<?php echo filter_var($valueplugify[0]); ?>" data-linked_id="<?php echo $valueplugify[1] ?>" style="background-color:<?php echo sanitize_hex_color($attr_value); ?>; width:32px; height:32px; "></label>
                                                        <?php
                                                    else :

                                                        if ($attr_img) :

                                                            $attr_image = '';
                                                            $attr_image = wp_get_attachment_image_src($attr_img, array(32, 32));

                                                            if ($attr_image) :
                                                                $attr_image = $attr_image[0];
                                                            endif;

                                                            if (!$attr_image) :
                                                                $attr_image = wc_placeholder_img_src(array(32, 32));
                                                            endif;
                                                        ?>
                                                            <div class="imgclasssmallactive tooltipplugify" style="margin: 0 5px;width:35px;height: 35px;border-radius: 50%;overflow: hidden;border: 1px solid green;">
                                                                <img class="child_class_plugify" style="height: 35px;text-align: center;" src="<?php echo filter_var($attr_image); ?>">
                                                                <div class="tooltiptextplugify">
                                                                    <?php echo filter_var($valueplugify[0]); ?>
                                                                </div>
                                                            </div>
                                                        <?php

                                                        else :
                                                        ?>
                                                            <div class="imgclasssmallactive" style="width:auto; border-radius: 2px;padding: 3px;border: 1px solid green;">
                                                                <div class="child_class_plugify" style="text-align: center;padding: 2px 15px;"><?php echo filter_var($valueplugify[0]); ?></div>
                                                            </div>
                                                    <?php
                                                        endif;
                                                    endif;
                                                endif;
                                            else :

                                                $style_cursor = '';
                                                $htmllpluigg  = '';
                                                $is_hyper     = $general_settings['is_hyper'];

                                                if ('true' == $is_hyper) :
                                                    $is_hyper = ' target="_blank" ';
                                                else :
                                                    $is_hyper = '';
                                                endif;

                                                if ('true' == $is_out_of_stock) :
                                                    $style_cursor = ' cursor:not-allowed; ';
                                                    $htmllpluigg  = ' href="javascript:void(0)" ';
                                                    $is_hyper     = '  ';
                                                endif;

                                                if ('true' == $istrue) :

                                                    $image    = wp_get_attachment_image_src(get_post_thumbnail_id($valueplugify[1]), 'single-post-thumbnail');
                                                    $img_srcy = '';

                                                    if ('' == $image) :
                                                        $image = [];
                                                    endif;

                                                    if (0 < count($image) && isset($image[0]) && '' != $image[0]) :
                                                        $img_srcy = $image[0];
                                                    else :
                                                        $img_srcy = plugins_url() . '/products-linked-by-variations-for-woocommerce/Front/Assets/woocommerce-placeholder-plugify.png';
                                                    endif;

                                                    ?>
                                                    <div class="imgclasssmall tooltipplugify <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid #ddd; <?php echo filter_var($style_cursor); ?>">
                                                        <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($is_hyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>"><img class="child_class_plugify" style="height: 40px;text-align: center;" src="<?php echo filter_var($img_srcy); ?>">
                                                        </a>
                                                        <div class="tooltiptextplugify">
                                                            <?php
                                                            if ('true' == $is_out_of_stock) :
                                                                echo esc_attr_e('Out Of Stock', 'woocommerce');
                                                            else :
                                                                echo filter_var($valueplugify[0]);
                                                            endif;
                                                            ?>
                                                        </div>
                                                    </div>

                                                    <?php
                                                else :
                                                    if ($attr_value) : ?>
                                                        <label selectid="" class=" wcvaswatchlabel wcvaround linked_product <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" data-attribute_name="attribute_pa_color" data-option="<?php echo filter_var($valueplugify[0]); ?>" data-linked_id="<?php echo $valueplugify[1] ?>" style="background-color:<?php echo sanitize_hex_color($attr_value); ?>; width:32px; height:32px; <?php echo $style_cursor; ?>">
                                                        </label>
                                                        <?php
                                                    else :
                                                        if ($attr_img) :

                                                            $attr_image = '';
                                                            $attr_image = wp_get_attachment_image_src($attr_img, array(32, 32));

                                                            if ($attr_image) :
                                                                $attr_image = $attr_image[0];
                                                            endif;

                                                            if (!$attr_image) :
                                                                $attr_image = wc_placeholder_img_src(array(32, 32));
                                                            endif;
                                                        ?>
                                                            <div class="imgclasssmall tooltipplugify <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="margin: 0 5px;width:35px;height:35px;border-radius: 50%;overflow: hidden;border: 1px solid #ddd; <?php echo filter_var($style_cursor); ?>">

                                                                <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($is_hyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>">
                                                                    <img class="child_class_plugify" style="height: 35px;text-align: center;" src="<?php echo filter_var($attr_image); ?>">
                                                                </a>

                                                                <div class="tooltiptextplugify">
                                                                    <?php
                                                                    if ('true' == $is_out_of_stock) :
                                                                        echo esc_attr_e('Out Of Stock', 'woocommerce');
                                                                    else :
                                                                        echo filter_var($valueplugify[0]);
                                                                    endif;
                                                                    ?>
                                                                </div>
                                                            </div>

                                                        <?php
                                                        else :
                                                        ?>
                                                            <div class="imgclasssmall <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid #ddd; background-color: <?php echo sanitize_hex_color($attr_value);
                                                                                                                                                                                                                                            echo filter_var($style_cursor); ?>">
                                                                <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($is_hyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>">
                                                                    <div class="child_class_plugify" style="text-align: center;padding: 2px 15px;"><?php echo filter_var($valueplugify[0]); ?>
                                                                </a>
                                                            </div>
                            </div>

<?php
                                                        endif;
                                                    endif;
                                                endif;
                                            endif;
                                        endif;
                                    endif;
                                endforeach; ?>
                        </div>
                    </div>
                    </div>
<?php
                endforeach;
            endif;

            return $html;
        }

        /**
         * mwc_get_price_summary_table AJAX action
         *
         * @return json
         */
        public static function mwc_get_price_summary_table() {

            check_ajax_referer('get set summary prices');

            // retrieve price list
            $price_list = $_POST['price_list'];

            // setup default/failed response
            $return = [
                'status' => false
            ];

            // build table HTML as needed
            if ($price_list) :

                $p_total = 0;

                $html = '<table>';

                foreach ($price_list as $bundle_price) :

                    if (isset($bundle_price['label']) && isset($bundle_price['price']) && (int)$bundle_price['price'] !== 0) :

                        if ($bundle_price['sum']  == 1) :

                            $html .= '<tr>';
                            $html .= '<td>' . $bundle_price['label'] . '</td>';
                            $html .= '<td style="text-align: right;">' . wc_price($bundle_price['price']) . '</td>';
                            $html .= '</tr>';

                            $p_total += $bundle_price['price'];

                        else :

                            $html .= '<tr>';
                            $html .= '<td>' . $bundle_price['label'] . '</td>';
                            $html .= '<td style="text-align: right; text-decoration: line-through;">' . wc_price($bundle_price['price']) . '</td>';
                            $html .= '</tr>';

                        endif;
                    endif;
                endforeach;

                // get shipping total
                $meta          = isset($_POST['bundle_id']) ? get_post_meta($_POST['bundle_id'], 'product_discount', true) : [];
                $free_shipping = isset($meta['free_shipping']) ? $meta['free_shipping'] : false;

                if ($free_shipping) :
                    WC()->cart->set_shipping_total(0);
                endif;

                $html .= '<tr>';
                $html .= '<td>' . __('Shipping', 'woocommerce') . '</td>';

                $shipping_total = WC()->cart->get_shipping_total();

                if ($shipping_total) :

                    $html .= '<td  style="text-align: right"><span class="amount">' . wc_price($shipping_total) . '</span></td>';
                    $p_total += $shipping_total;

                else :

                    $html .= '<td  style="text-align: right"><span class="amount">' . __('Free Shipping', 'woocommerce') . '</span></td>';

                endif;

                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td>' . __('Total', 'woocommerce') . '</td>';
                $html .= '<td style="text-align: right">' . wc_price($p_total) . '</td>';
                $html .= '</tr>';
                $html .= '</table>';

                $return = [
                    'status' => true,
                    'html'   => $html
                ];

            endif;

            // send json
            wp_send_json($return);

            wp_die();
        }

        /**
         * mwc_get_price_package AJAX action
         * 
         * update/improved on 9 November 2022
         *
         * @return json
         */
        public static function mwc_get_price_package() {

            check_ajax_referer('get update bundle pricing');

            // grab vars
            $arr_discount    = $_POST['discount'];
            $arr_product_ids = $_POST['product_ids'];

            if (!empty($arr_product_ids) && !empty($arr_discount['type']) && isset($arr_discount['qty']) && isset($arr_discount['value'])) :

                // get total price
                $loop_prod   = [];
                $total_price = 0;
                $old_price   = 0;

                foreach ($arr_product_ids as $key => $prod_id) :

                    $product = wc_get_product($prod_id);
                    $loop_prod[$prod_id] = $product;

                    $total_price += $product->get_regular_price();
                    $old_price += $product->get_regular_price();

                endforeach;

                // get discount
                if ($arr_discount['type'] == 'percentage') :
                    $total_price -= ($total_price * $arr_discount['value']) / 100;
                elseif ($arr_discount['type'] == 'free' && in_array($arr_discount['value'], $arr_product_ids)) :
                    $free_price = wc_get_product($arr_discount['value'])->get_regular_price();
                    $total_price -= $free_price;
                endif;

                $return = array(
                    'status'           => true,
                    'total_price'      => $total_price,
                    'total_price_html' => wc_price($total_price),
                    'old_price'        => $old_price,
                    'old_price_html'   => wc_price($old_price),
                    'each_price'       => $total_price / count($arr_product_ids),
                    'each_price_html'  => wc_price($total_price / count($arr_product_ids))
                );

            endif;

            wp_send_json($return);

            wp_die();
        }

        /**
         * add_referer_url_order_note
         *
         * @param int $order_id
         * @return void
         */
        public static function add_referer_url_order_note($order_id) {
            $order = wc_get_order($order_id);
            $order->add_order_note('Checkout url: ' . $_SERVER['HTTP_REFERER']);
        }
    }

endif;
