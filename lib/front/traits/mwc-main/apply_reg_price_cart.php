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

                // get product
                $product = $cart_item['data'];

                // check if is product and then set cart price to regular price (works for both simple and variable products)
                if (is_a($product, 'WC_Product')) :
                    $product->set_price($cart_item['data']->get_regular_price());
                endif;

            endforeach;
        }
    }

endif;
