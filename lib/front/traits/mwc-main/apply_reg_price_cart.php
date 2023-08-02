<?php
if (!trait_exists('ApplyRegPriceCart')) :

    trait ApplyRegPriceCart
    {

        /**
         * Sets regular price for all items in cart
         *
         * @param object $cart
         * @return void
         */
        public static function mwc_cart_apply_regular_prices($cart)
        {

            // get cart currency


            // debug
            // return;

            if (is_admin() && !defined('DOING_AJAX')) :
                return;
            endif;

            // mwc product count
            global $mwc_prod_count;

            // if product count is less than $mwc_prod_count, remove all fees from cart
            if (wc()->cart->get_cart_contents_count() < $mwc_prod_count) :

                // get fees
                $fees = wc()->cart->get_fees();

                // remove fees
                foreach ($fees as $key => $fee) :
                    unset($fees[$key]);
                endforeach;

            endif;

            foreach ($cart->get_cart() as $cart_item_key => $cart_item) :

                // debug to log
                // error_log('CART ITEM DATA: ' . PHP_EOL . print_r($cart_item['data'], true));

                // error_log('CART ITEM: ' . print_r($cart_item, true));

                // get product object
                $product = $cart_item['data'];

                // get regular price
                $reg_price = $product->get_regular_price();

                // get defualt currency
                $default_curr = get_option('woocommerce_currency');

                // get current currency
                $current_curr = function_exists('alg_get_current_currency_code') ?
                    alg_get_current_currency_code() : get_option('woocommerce_currency');

                // if current currency is not equal to default currency, convert price back to default currency (divide by exchange rate) because ALG auto converts to current currency when calling get_regular_price(), so if we don't convert back to default currency, the price will be converted twice
                if ($default_curr !== $current_curr) :

                    // get alg exchange rate
                    $ex_rate = get_option("alg_currency_switcher_exchange_rate_{$default_curr}_{$current_curr}") ?
                        get_option("alg_currency_switcher_exchange_rate_{$default_curr}_{$current_curr}") : 1;

                    // convert price
                    $reg_price = $reg_price / $ex_rate;

                    // set cart item price
                    $cart_item['data']->set_price($reg_price);

                else :

                    // set cart item price
                    $cart_item['data']->set_price($reg_price);

                endif;

            endforeach;
        }
    }

endif;
