<?php

defined('ABSPATH') ?: exit();


if (!trait_exists('MWC_PLL_Reg_Strings_Add_Translations')) :

    trait MWC_PLL_Reg_Strings_Add_Translations {

        /**
         * Register PLL strings
         *
         * @return void
         */
        public static function mwc_pll_reg_strings_add_translations() {

            if(function_exists('pll_register_string')):
                pll_register_string('mwc_best_seller', 'Best Seller', 'Multi Woo Checkout');
                pll_register_string('mwc_popular', 'Popular', 'Multi Woo Checkout');
                pll_register_string('mwc_moderate', 'Moderate', 'Multi Woo Checkout');
                pll_register_string('mwc_select_package', 'Select Package:', 'Multi Woo Checkout');
                pll_register_string('mwc_please_choose', 'Please choose:', 'Multi Woo Checkout');
                pll_register_string('mwc_each', 'each', 'Multi Woo Checkout');
                pll_register_string('mwc_save', 'Save', 'Multi Woo Checkout');
                pll_register_string('mwc_old_price', 'Old Price', 'Multi Woo Checkout');
                pll_register_string('mwc_order_summary', 'Order Summary', 'Multi Woo Checkout');
                pll_register_string('mwc_order_item', 'Item', 'Multi Woo Checkout');
                pll_register_string('mwc_price', 'Price', 'Multi Woo Checkout');
                pll_register_string('mwc_select_free_product', 'Select Free Product:', 'Multi Woo Checkout');
                pll_register_string('mwc_payment_option', 'Payment Option', 'Multi Woo Checkout');
                pll_register_string('mwc_prompt_1', 'Please select at least one product bundle first!', 'Multi Woo Checkout');
                pll_register_string('mwc_prompt_2', 'Please select a product bundle first!', 'Multi Woo Checkout');
                pll_register_string('mwc_prompt_3', 'Click to select add-on product', 'Multi Woo Checkout');
            endif;

        }
    }

endif;
