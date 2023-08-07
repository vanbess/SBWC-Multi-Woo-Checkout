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

            // loop through cart items and set regular price (alg price if defined, else woocommerce regular price)
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) :

                // get product object
                $product = $cart_item['data'];

                // get product id
                $product_id = $product->get_id();

                // get regular price
                $reg_price = get_post_meta($product_id, "alg_currency_switcher_per_product_regular_price_{$current_curr}", true) ?
                    get_post_meta($product_id, "alg_currency_switcher_per_product_regular_price_{$current_curr}", true) :
                    get_post_meta($product_id, '_regular_price', true);

                // debug
                $cart_item['data']->set_price($reg_price);

            endforeach;

        }
    }

endif;
