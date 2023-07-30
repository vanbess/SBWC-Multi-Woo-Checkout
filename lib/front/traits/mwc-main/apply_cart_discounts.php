<?php
if (!trait_exists('ApplyCartDiscounts')) :

    trait ApplyCartDiscounts
    {

        /**
         * Applies various bundle and add-on discounts if items found in cart
         *
         * @param object $cart
         * @return void
         */
        public static function mwc_apply_cart_discounts($cart)
        {

            // start session if not started
            if (!session_id()) :
                session_start();
            endif;

            // remove all cart fees
            WC()->cart->fees = array();

            // UNCOMMENT TO DEBUG - tested working 18 Jan 2023
            // $cart->add_fee('Test Fee', -333.33, true);
            // return;

            // holds calculated bundle subtotal for later use
            $bundle_stotal = 0;

            // holds total discount for free products
            $free_discount = false;

            // holds paid prod count
            $paid_prod_count = 0;

            // holds free prod count
            $free_prod_count = 0;

            // holds discount percentage (product bundle)
            $disc_perc = 0;

            // holds off pecentage (buy x get x off)
            $off_perc = 0;

            $addon_disc_total = 0;

            $item_qty_fee = 0;

            // retrieve bundle id
            $bundle_id = (int)$_SESSION['mwc_bundle_id'];

            // if $bundle_id === false, bail
            if ($bundle_id === false) :
                return;
            endif;

            // retrieve bundle data for later ref as/when needed
            $bundle_data = get_post_meta($bundle_id, 'product_discount', true);
            $bundle_data = is_array($bundle_data) ? $bundle_data : json_decode($bundle_data, true);

            // retrieve bundle type
            $bundle_type = $bundle_data['selValue'];

            // ********************************************
            // loop through cart items and updated as need
            // ********************************************
            foreach ($cart->get_cart() as $cart_item) :

                // debug
                error_log('cart item: ' . print_r($cart_item, true));

                // ****
                // bun
                // ****
                if (isset($cart_item['mwc_bun_discount'])) :

                    $disc_perc = (int)$cart_item['mwc_bun_discount'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get item regular price
                    $item_reg_price = (float)$cart_item['data']->get_regular_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // // set item qty fee
                    // $item_qty_fee += (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_reg_price * $item_qty;

                    // add to bundle subtotal variable
                    $bundle_stotal += $item_total;

                    // discount fee name
                    $fee_name = sprintf(__('Bundle discount - %d&#37;', 'woocommerce'), $disc_perc);

                endif;

                // ****
                // off
                // ****
                if (isset($cart_item['mwc_off_discount'])) :

                    // retrieve discount
                    $off_perc = (int)$cart_item['mwc_off_discount'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get regular price
                    $item_reg_price = (float)$cart_item['data']->get_regular_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // set item qty fee
                    $item_qty_fee += (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_reg_price * $item_qty;

                    // add to bundle subtotal variable
                    $bundle_stotal += $item_total;

                endif;

                // **********
                // free prod
                // **********
                if (isset($cart_item['mwc_bun_free_prod'])) :

                    $free_discount = true;

                    // get free prod count
                    $free_prod_count += (int)$cart_item['quantity'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get regular price
                    $item_reg_price = (float)$cart_item['data']->get_regular_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_reg_price * $item_qty;

                    // add to bundle subtotal variable
                    $bundle_stotal += $item_total;

                endif;

                // **********
                // paid prod
                // **********
                if (isset($cart_item['mwc_bun_paid_prod'])) :

                    // get paid prod count
                    $paid_prod_count += (int)$cart_item['quantity'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get item regular price
                    $item_reg_price = (float)$cart_item['data']->get_regular_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = 133 * $item_qty;

                    // add to bundle subtotal variable
                    $bundle_stotal += $item_total;

                endif;

                // *******
                // add-on
                // *******
                if (isset($cart_item['mwc_addon_disc'])) :

                    // retrieve discount
                    $addon_off_perc = (int)$cart_item['mwc_addon_disc'];

                    // get item price
                    $item_price = (float)$cart_item['data']->get_price();

                    // get item regular price
                    $item_reg_price = (float)$cart_item['data']->get_regular_price();

                    // get item qty
                    $item_qty = (int)$cart_item['quantity'];

                    // calc total for items
                    $item_total = $item_reg_price * $item_qty;

                    // discount multiplier
                    $disc_mp = (100 - $addon_off_perc) / 100;

                    // addon discount total
                    $addon_disc_total += $item_total - ($item_total * $disc_mp);

                    // add discount fee name
                    $add_fee_name = __('Add-on Product Discount', 'woocommerce');

                endif;


            endforeach;

            // ***************************************
            // Do cart fee calc for bundle discount
            // ***************************************
            if ($disc_perc !== 0) :
                $disc_mp = (100 - (int)$disc_perc) / 100;
                $disc_amt = (float)$bundle_stotal - ((float)$bundle_stotal * (float)$disc_mp);
                $cart->add_fee($fee_name, -$disc_amt, true);
            endif;

            // **********************************************
            // Do cart fee calc for buy x get x off products
            // **********************************************
            if ($off_perc !== 0) :
                $disc_mp = (100 - (int)$off_perc) / 100;
                $disc_amt = (float)$bundle_stotal - ((float)$bundle_stotal * (float)$disc_mp);
                $off_fee_name = sprintf(__('Buy %s + Get %d&#37; Off', 'woocommerce'), $item_qty_fee, $off_perc);
                $cart->add_fee($off_fee_name, -$disc_amt, true);
            endif;

            // ***********************************
            // Do cart fee calc for free products
            // ***********************************
            if ($free_discount) :
                $disc_mp = $free_prod_count / ($paid_prod_count + $free_prod_count);
                $disc_amt = $bundle_stotal * $disc_mp;
                $fee_name = sprintf(__('Buy %d + Get %d Free', 'woocommerce'), $paid_prod_count, $free_prod_count);
                $cart->add_fee($fee_name, -$disc_amt, true);
            endif;

            // *************************************
            // Do cart fee calc for add-on products
            // *************************************
            if ($addon_disc_total !== 0) :
                $cart->add_fee($add_fee_name, -$addon_disc_total, true);
            endif;
        }
    }

endif;
