<?php

if (!class_exists('woocommerce')) :

    // include traits
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/add_to_cart_basic_aj_action.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/add_to_cart_basic_js.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/add_to_cart_linked_aj_action.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/add_to_cart_linked_js.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/apply_cart_discounts.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/apply_reg_price_cart.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/get_package_price.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/get_price_summary_table.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/load_resources.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/return_linked_prod_var_dd.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/return_onepage_co_var_dd.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/remove-other-coupons.php';
    include MWC_PLUGIN_DIR . 'lib/front/traits/mwc-main/pll_register_strings.php';

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
            MWC_PLL_Register_Strings;

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
        public static function init()
        {
            if (!self::$initiated) :
                self::init_hooks();
            endif;
        }

        /**
         * Initializes WordPress hooks
         */
        private static function init_hooks()
        {

            self::$initiated = true;

            // disable coupons
            add_filter('woocommerce_coupons_enabled', '__return_false');

            // script
            add_action('wp_footer', array(__CLASS__, 'load_resources'));

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
            add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'mwc_cart_apply_regular_prices'));

            // action to apply cart discount
            add_action('woocommerce_cart_calculate_fees', array(__CLASS__, 'mwc_apply_cart_discounts'));

            // add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'mwc_apply_cart_discounts'));

            // action add referer to order note
            add_action('woocommerce_order_status_processing', array(__CLASS__, 'add_referer_url_order_note'), 10, 1);

            // register PLL strings
            self::mwc_pll_register_strings();

            // remove all product price filters if mwc flag is set to true
            global $is_mwc_checkout;

            // add_action('plugins_loaded', function () use ($is_mwc_checkout) {

            //     if ($is_mwc_checkout) {
            //         remove_all_filters('woocommerce_product_get_price');
            //         remove_all_filters('woocommerce_product_get_regular_price');
            //         remove_all_filters('woocommerce_product_variation_get_regular_price');
            //         remove_all_filters('woocommerce_product_variation_get_price');
            //     }
            // }, PHP_INT_MAX);

        }

        /**
         * add_referer_url_order_note
         *
         * @param int $order_id
         * @return void
         */
        public static function add_referer_url_order_note($order_id)
        {
            $order = wc_get_order($order_id);
            $order->add_order_note('Checkout url: ' . $_SERVER['HTTP_REFERER']);
        }
    }

endif;
