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
            if (!function_exists('alg_convert_price')) :
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

                // convert price using ALG
                $conved_price = alg_convert_price([
                    'price'         => $reg_price,
                    'currency_from' => $default_currency,
                    'currency'      => $current_currency,
                    'format_price'  => false
                ]);

                file_put_contents(MWC_PLUGIN_DIR.'conved_prices.log', print_r($conved_price, true), FILE_APPEND);

                return $conved_price;

            endif;
        }
    }

endif;
