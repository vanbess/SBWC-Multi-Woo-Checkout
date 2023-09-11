<?php

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
            MWC_Update_MiniCart;

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

            // disable coupons
            add_filter('woocommerce_coupons_enabled', '__return_false');

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

            // action to remove all other discounts/coupons if mwc bundle in cart
            add_action('woocommerce_coupon_is_valid', [__CLASS__, 'mwc_remove_other_coupons'], 10, 3);

            // footer action to disable other coupons if mwc bundle is present on any page
            add_action('wp_footer', [__CLASS__, 'mwc_disable_other_coupons']);

            // action get price mwc package
            add_action('wp_ajax_mwc_get_price_package', array(__CLASS__, 'mwc_get_price_package'));
            add_action('wp_ajax_nopriv_mwc_get_price_package', array(__CLASS__, 'mwc_get_price_package'));

            // action to set item prices to regular
            add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'mwc_cart_apply_regular_prices'), 10, 1);

            // apply regular pricing to mini cart
            // add_filter('woocommerce_cart_item_price', [__CLASS__, 'mwc_apply_regular_price_mini_cart'], 30, 3);

            // update mini cart prices
            // add_action('wp_footer', array(__CLASS__, 'mwc_update_minicart_prices'));

            // action to apply cart discount
            // add_action('woocommerce_cart_calculate_fees', array(__CLASS__, 'mwc_apply_cart_discounts'));

            add_filter('woocommerce_cart_item_name', function ($product_name, $cart_item, $cart_item_key) {
                if (
                    isset($cart_item['mwc_bun_discount'])
                    || isset($cart_item['mwc_off_discount'])
                    || isset($cart_item['mwc_bun_free_prod'])
                    || isset($cart_item['mwc_bun_paid_prod'])
                ) {

                    // get bundle label
                    $bundle_label  = $_SESSION['mwc_bundle_label'] ? __($_SESSION['mwc_bundle_label'], 'woocommerce') : __('Bundle Discount', 'woocommerce');

                    // append to product name in cart
                    $product_name .= '<br><p class="woocommerce-cart-item-bundle-discount">' . $bundle_label . '</p>';
                }
                return $product_name;
            }, 10, 3);

            add_filter('woocommerce_cart_item_price', function ($price, $cart_item, $cart_item_key) {

                if (
                    isset($cart_item['mwc_bun_discount'])
                    || isset($cart_item['mwc_off_discount'])
                    || isset($cart_item['mwc_bun_free_prod'])
                    || isset($cart_item['mwc_bun_paid_prod'])
                ) {

                    // get original price
                    $original_price = $cart_item['data']->get_regular_price();

                    // retrieve bundle data from session
                    $disc_p_price  = $_SESSION['mwc_product_price'];
                    $disc_p_total  = $_SESSION['mwc_bundle_discounted_total'];
                    $disc_perc     = $_SESSION['mwc_bundle_discount_perc'];
                    $curr_currency = $_SESSION['mwc_current_curr'];

                    $price = sprintf(
                        __('<del>%s</del><br><b>%s</b>', 'woocommerce'),
                        wc_price($original_price),
                        wc_price($disc_p_price)
                    );
                }
                return $price;
            }, 10, 3);

            add_action('woocommerce_before_calculate_totals', function($cart_obj) {

                if (is_admin() && !defined('DOING_AJAX')) {
                    return;
                }
            
                $disc_p_price  = $_SESSION['mwc_product_price'];
            
                foreach ($cart_obj->get_cart() as $key => $value) {
                    if (
                        isset($value['mwc_bun_discount'])
                        || isset($value['mwc_off_discount'])
                        || isset($value['mwc_bun_free_prod'])
                        || isset($value['mwc_bun_paid_prod'])
                    ) {
                        
                        $value['data']->set_price($disc_p_price);
                    }
                }
            }, 10, 1);

            


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
