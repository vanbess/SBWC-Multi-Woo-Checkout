<?php

defined('ABSPATH') ?: exit();

if (!trait_exists('MWC_Get_Product_ALG_Price')) :

    trait MWC_Get_Product_ALG_Price {

        /**
         * Retrieves and returns custom ALG price for product if defined, else returns converted price (default currency -> current currency) based on ALG defined exchange rate
         *
         * @param int $product_id
         * @param string $current_currency
         * @param string $default_currency
         * @return void
         */
        public static function mwc_get_product_ALG_Price($product_id, $current_currency, $default_currency) {

            // check if required ALG function exists before doing anything else to avoid errors; bail if false
            if (!function_exists('alg_wc_cs_get_currency_exchange_rate')) :
                return false;
            endif;

            // setup correct ALG price key to retrieve
            $retrieve_meta_key = '_alg_currency_switcher_per_product_regular_price_' . $current_currency;

            // if key exists and has value, return custom price
            if (get_post_meta($product_id, $retrieve_meta_key, true) && !empty(get_post_meta($product_id, $retrieve_meta_key, true))) :

                return get_post_meta($product_id, $retrieve_meta_key, true);

            // if key exists but has no value, get regular price and convert to current currency price using default ALG exchange rate
            elseif (get_post_meta($product_id, $retrieve_meta_key, true) && get_post_meta($product_id, $retrieve_meta_key, true) === '') :

                // get regular price
                $reg_price = get_post_meta($product_id, '_regular_price', true);

                // get exchange rate
                $curr_rate =
                                get_option("alg_currency_switcher_exchange_rate_{$default_currency}_{$current_currency}") ?
                                get_option("alg_currency_switcher_exchange_rate_{$default_currency}_{$current_currency}") :
                                1;

                // convert price and return
                $conved_price = $reg_price * $curr_rate;

                // log to debug with product id, current currency, default currency, regular price, exchange rate, converted price
                error_log("Product ID: {$product_id} | Current Currency: {$current_currency} | Default Currency: {$default_currency} | Regular Price: {$reg_price} | Exchange Rate: {$curr_rate} | Converted Price: {$conved_price}");

                return $conved_price;

            endif;
        }
    }

endif;
