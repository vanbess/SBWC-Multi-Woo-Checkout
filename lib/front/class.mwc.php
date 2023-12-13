<?php

use Elementor\Core\Logger\Items\PHP;

if (!class_exists('MWC')) :

    // include traits
    require_once __DIR__ . '/traits/mwc_add_to_cart_multiple.php';
    require_once __DIR__ . '/traits/mwc_apply_cart_discounts.php';
    require_once __DIR__ . '/traits/mwc_atc_js_linked_prods.php';
    require_once __DIR__ . '/traits/mwc_atc_js.php';
    require_once __DIR__ . '/traits/mwc_atc_linked_products.php';
    require_once __DIR__ . '/traits/mwc_cart_apply_regular_prices.php';
    require_once __DIR__ . '/traits/mwc_get_price_package.php';
    require_once __DIR__ . '/traits/mwc_get_price_summary_table.php';
    require_once __DIR__ . '/traits/mwc_load_resources.php';
    require_once __DIR__ . '/traits/mwc_pll_reg_strings_add_translations.php';
    require_once __DIR__ . '/traits/mwc_pll_register_strings.php';
    require_once __DIR__ . '/traits/mwc_remove_other_coupons.php';
    require_once __DIR__ . '/traits/mwc_return_linked_variations_dropdown.php';
    require_once __DIR__ . '/traits/mwc_return_onepage_checkout_variation_dropdown.php';
    require_once __DIR__ . '/traits/mwc_update_minicart_prices.php';
    require_once __DIR__ . '/traits/mwc_atc_style_d.php';

    class MWC
    {

        // Traits
        use AddToCartBasicAjAction,
            AddToCartBasicJS,
            AddToCartLinkedJS,
            AddToCartLinkedAjAction,
            ApplyRegPriceCart,
            ApplyCartDiscounts,
            GetPackagePrice,
            GetPriceSummaryTable,
            LoadResources,
            ReturnLinkedProdVarDD,
            ReturnOnePageCoVarDD,
            MWC_Remove_Other_Coupons,
            MWC_PLL_Register_Strings,
            MWC_Update_MiniCart,
            MWC_ATC_Style_D;

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
        // public static function init()
        // {
        //     if (!self::$initiated) :
                //         self::init_hooks();
            //     endif;
        // }

        /**
         * Initializes WordPress hooks
         */
        public static function init()
        {

            self::$initiated = true;

            // script
            add_action('wp_footer', array(__CLASS__, 'mwc_load_resources'));

            // action ajax add products to cart
            add_action('wp_ajax_mwc_add_to_cart_multiple', array(__CLASS__, 'mwc_add_to_cart_multiple'));
            add_action('wp_ajax_nopriv_mwc_add_to_cart_multiple', array(__CLASS__, 'mwc_add_to_cart_multiple'));

            // action ajax add linked products to cart
            add_action('wp_ajax_mwc_atc_linked_products', array(__CLASS__, 'mwc_atc_linked_products'));
            add_action('wp_ajax_nopriv_mwc_atc_linked_products', array(__CLASS__, 'mwc_atc_linked_products'));

            // action get price summary table
            add_action('wp_ajax_mwc_get_price_summary_table', array(__CLASS__, 'mwc_get_price_summary_table'));
            add_action('wp_ajax_nopriv_mwc_get_price_summary_table', array(__CLASS__, 'mwc_get_price_summary_table'));

            // add to cart mwc template d products
            add_action('wp_ajax_mwc_atc_template_d_products', array(__CLASS__, 'mwc_atc_template_d_products'));
            add_action('wp_ajax_nopriv_mwc_atc_template_d_products', array(__CLASS__, 'mwc_atc_template_d_products'));

            // action to set item prices to regular
            add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'mwc_cart_apply_regular_prices'), PHP_INT_MAX, 1);

            /*****************************
             * Add bundle discount as fee
             *****************************/
            add_action('woocommerce_cart_calculate_fees', function () {

                // is mwc
                $is_mwc = false;

                if (is_admin() && !defined('DOING_AJAX')) {
                    return;
                }

                // don't run this more than once
                if (did_action('woocommerce_cart_calculate_fees') >= 2) {
                    return;
                }

                // get bundle id from session
                $bundle_id = WC()->session->get('mwc_bundle_id');

                // if bundle id is not set, return
                if (!$bundle_id) :
                    return;
                endif;

                // file put contents bundle id
                // file_put_contents(MWC_PLUGIN_DIR . 'bundle_id.txt', print_r($bundle_id, true));

                // get bundle data
                $bundle_data = get_post_meta($bundle_id, 'product_discount', true);

                // file put contents bundle data
                // file_put_contents(MWC_PLUGIN_DIR . 'bundle_data.txt', print_r($bundle_data, true), FILE_APPEND);

                // find bundle products and associated quantities

                // ------------
                // type bundle
                // ------------
                if ($bundle_data['selValue'] == 'bun') :

                    // get products
                    $product_data = array_column($bundle_data, 'post')[0];

                    // sum values of all 'quantity' keys in $product_data array
                    $bundle_product_quantity = array_sum(array_column($product_data, 'quantity'));

                // ---------
                // type off
                // ---------
                elseif ($bundle_data['selValue'] == 'off') :

                    // get product qty
                    $product_data = $bundle_data['selValue_off'];
                    $bundle_product_quantity = $product_data['quantity'];

                // ---------
                // type free
                // ---------
                elseif ($bundle_data['selValue'] == 'free') :
                    $bundle_product_quantity = array_sum(array_column($bundle_data, 'quantity'));
                endif;

                // file put contents product data
                // file_put_contents(MWC_PLUGIN_DIR . 'product_data.txt', print_r($product_data, true), FILE_APPEND);

                // file put contents bundle product quantity
                // file_put_contents(MWC_PLUGIN_DIR . 'bundle_product_quantity.txt', print_r($bundle_product_quantity, true));

                // get cart contents
                $cart_contents = WC()->cart->get_cart_contents();

                // bundle type
                $bundle_type = '';

                // mwc product count
                $mwc_prod_count = 0;

                // discount percent
                $discount_percent = 0;

                // free product count in the case of free and paid products
                $free_prod_count = 0;

                // paid product count in the case of free and paid products
                $paid_prod_count = 0;

                // loop through cart contents and check for cart item meta 'mwc_bun_discount' or 'mwc_off_discount' or 'mwc_bun_free_prod' or 'mwc_bun_paid_prod'
                foreach ($cart_contents as $cart_item) :

                    // if any mwc item is present, increment mwc product count
                    if (isset($cart_item['mwc_bun_discount']) || isset($cart_item['mwc_off_discount']) || isset($cart_item['mwc_bun_free_prod']) || isset($cart_item['mwc_bun_paid_prod'])) :
                        $mwc_prod_count += $cart_item['quantity'];
                        $is_mwc = true;
                    endif;

                    // set bundle type
                    if (isset($cart_item['mwc_bun_discount'])) :
                        $bundle_type = 'bundle';
                    elseif (isset($cart_item['mwc_off_discount'])) :
                        $bundle_type = 'off';
                    elseif (isset($cart_item['mwc_bun_free_prod'])) :
                        $bundle_type = 'free';
                    else :
                        $bundle_type = null;
                    endif;

                    // if is bundle discount or off discount, get/set discount %
                    if (isset($cart_item['mwc_bun_discount']) || isset($cart_item['mwc_off_discount'])) :

                        $discount_percent = isset($cart_item['mwc_bun_discount']) ? $cart_item['mwc_bun_discount'] : $cart_item['mwc_off_discount'] ?? 0;

                        // skip to next iteration
                        continue;

                    endif;

                    // count free products
                    if (isset($cart_item['mwc_bun_free_prod'])) :
                        $free_prod_count += $cart_item['quantity'];
                    endif;

                    // count paid products
                    if (isset($cart_item['mwc_bun_paid_prod'])) :
                        $paid_prod_count += $cart_item['quantity'];
                    endif;

                endforeach;

                // DEBUG
                // $is_mwc = false;

                // if not mwc, return
                if (!$is_mwc) :
                return;
                endif;

                // file put contents paid and free product count
                // file_put_contents(MWC_PLUGIN_DIR . 'paid_and_free_prod_count.txt', print_r($paid_prod_count . ' ' . $free_prod_count, true));

                // file put contents bundle type
                // file_put_contents(MWC_PLUGIN_DIR . 'bundle_type.txt', print_r($bundle_type, true));

                // if free product count and paid product count is not 0, calculate discount percent
                if ($free_prod_count != 0 && $paid_prod_count != 0) :
                    $discount_percent = ($free_prod_count / $bundle_product_quantity) * 100;
                endif;

                // file put contents discount percent
                // file_put_contents(MWC_PLUGIN_DIR . 'discount_percent.txt', print_r($discount_percent, true));

                // if discount percent is not 0 and product count === to mwc product count, calculate fee
                if ($discount_percent != 0 && $bundle_product_quantity == $mwc_prod_count) :

                    //  get cart total
                    $cart_total = WC()->cart->subtotal;

                    // calculate fee
                    $bundle_fee = ($cart_total * $discount_percent) / 100;

                    // // file put contents discounted total
                    // file_put_contents(MWC_PLUGIN_DIR . 'discounted_total.txt', print_r($mwc_bundle_total, true));

                    // set up discount fee name based on bundle type
                    switch ($bundle_type) {
                        case 'bundle':
                            $bundle_label = sprintf(__('Bundle Discount - %s%%', 'woocommerce'), round($discount_percent));
                            break;
                        case 'off':
                            $bundle_label = sprintf(__('Buy %s Get %s%% Off', 'woocommerce'), $mwc_prod_count, round($discount_percent));
                            break;
                        case 'free':
                            $bundle_label = sprintf(__('Buy %s Get %s Free', 'woocommerce'), $paid_prod_count, $free_prod_count);
                            break;
                        default:
                            $bundle_label = __('Bundle Discount', 'woocommerce');
                            break;
                    }

                    if ($bundle_fee != 0) :

                        // file put contents fee
                        // file_put_contents(MWC_PLUGIN_DIR . 'fee.txt', print_r($bundle_fee, true));

                        WC()->cart->add_fee($bundle_label, -$bundle_fee);
                    endif;

                endif;
            }, PHP_INT_MAX);

            // action add referer to order note
            add_action('woocommerce_order_status_processing', array(__CLASS__, 'mwc_add_referer_url_order_note'), 10, 1);

            // register PLL strings
            self::mwc_pll_register_strings();

            
        }


        /**
         * add_referer_url_order_note
         *
         * @param int $order_id
         * @return void
         */
        public static function mwc_add_referer_url_order_note($order_id)
        {
            $order = wc_get_order($order_id);
            $order->add_order_note('Checkout url: ' . $_SERVER['HTTP_REFERER']);
        }
    }

endif;

MWC::init();

// // init
// $mwc = new MWC();

// $mwc::init();
