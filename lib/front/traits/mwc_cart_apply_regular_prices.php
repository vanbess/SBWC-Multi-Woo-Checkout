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

            // bail if is admin or not ajax
            if (is_admin() && !defined('DOING_AJAX')) :
                return;
            endif;

            // get current currency
            $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_option('woocommerce_currency');

            // get default currency
            $default_curr = get_option('woocommerce_currency');

            // if current currency is not default currency, get exchange rate
            $ex_rate = $current_curr != $default_curr ? get_option("alg_currency_switcher_exchange_rate_{$default_curr}_{$current_curr}") : 1;

            // loop through cart items and set regular price (alg price if defined, else woocommerce regular price)
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) :

                // get product object
                $product = $cart_item['data'];

                // debug
                // $cart_item['data']->set_price($reg_price);
                $cart_item['data']->set_price($product->get_regular_price() / $ex_rate);

            endforeach;
        }
    }

endif;
