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

            // if did action >= 2, return
            if (did_action('woocommerce_before_calculate_totals') >= 2) :
                return;
            endif;

            // get bundle id from session
            $bundle_id = WC()->session->get('mwc_bundle_id');

            // if bundle id is not set, return
            if (!$bundle_id) :
                return;
            endif;

            // is mwc
            $is_mwc = false;

            // mwc prod count
            $mwc_prod_count = 0;

            // product bundle quantity
            $bundle_product_quantity = 0;

            // get cart contents
            $cart_contents = $cart->get_cart_contents();

            // loop and check for mwc products
            // loop through cart contents and check for cart item meta 'mwc_bun_discount' or 'mwc_off_discount' or 'mwc_bun_free_prod' or 'mwc_bun_paid_prod'
            foreach ($cart_contents as $cart_item) :

                // if any mwc item is present, increment mwc product count
                if (isset($cart_item['mwc_bun_discount']) || isset($cart_item['mwc_off_discount']) || isset($cart_item['mwc_bun_free_prod']) || isset($cart_item['mwc_bun_paid_prod'])) :
                    $mwc_prod_count += $cart_item['quantity'];
                    $is_mwc = true;
                endif;

            endforeach;

            // DEBUG
            // $is_mwc = false;

            // if not mwc, return
            if (!$is_mwc) :
                return;
            endif;

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

            // DEBUG
            // $bundle_product_quantity = 3;

            // if bundle product quantity is not equal to mwc product count, return
            if ($bundle_product_quantity != $mwc_prod_count) :
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
