<?php

if (!trait_exists('AddToCartLinkedAjAction')) :

    trait AddToCartLinkedAjAction
    {
        /**
         * AJAX function to add linked products to cart
         *
         * @return void
         */
        public function mwc_atc_linked_products()
        {

            check_ajax_referer('add linked products to cart');

            // debug cart object
            // wp_send_json(WC()->cart);

            // retrieve subbed vars
            $bundle_type = isset($_POST['type']) ? $_POST['type'] : false;
            $bundle_id   = isset($_POST['bundle_id']) ? (int)$_POST['bundle_id'] : false;
            $discount    = isset($_POST['discount']) ? $_POST['discount'] : false;

            // empty cart
            wc()->cart->empty_cart();

            // holds cart keys
            $cart_keys = [];

            if ($bundle_id) :

                // debug
                // wp_send_json('bundle ID: ' . $bundle_id);

                // bundle discount data
                $disc_data = get_post_meta($bundle_id, 'product_discount', true);

                // debug
                // wp_send_json($disc_data);

                // check if shipping is free or not
                $is_free_shipping = isset($disc_data['free_shipping']) ? true : false;

                // ----------------
                // bundle type BUN
                // ----------------
                if ($bundle_type === 'bun') :

                    // linked prods
                    $linked_off_prods = $_POST['linked_bun_prods'];

                    // loop through linked prods and add to cart
                    foreach ($linked_off_prods as $prod_data) :

                        // retrieve product
                        $prod = wc_get_product($prod_data['prod_id']);

                        // if variable product
                        if ($prod->get_type() === 'variable') :

                            // retrieve variation data
                            $vars = $prod->get_available_variations();

                            // loop to retrieve correct variation id
                            foreach ($vars as $var_data) :

                                // retrieve attribute string
                                $attrib_string = implode('', $var_data['attributes']);

                                // if string matches submitted string, retrieve variation id
                                if ($attrib_string === $prod_data['attribute']) :
                                    $var_id = $var_data['variation_id'];
                                endif;

                            endforeach;

                            // add to cart
                            $cart_keys[] = wc()->cart->add_to_cart($prod->get_id(), 1, $var_id, [], ['mwc_bun_discount' => $discount]);

                        // if simple prod
                        else :

                            // add to cart
                            $cart_keys[] = wc()->cart->add_to_cart($prod->get_id(), 1, 0, [], ['mwc_bun_discount' => $discount]);

                        endif;

                        // set shipping
                        if ($is_free_shipping === true) :
                            wc()->cart->set_shipping_total(0);
                        endif;

                    endforeach;

                endif;

                // ----------------
                // bundle type OFF
                // ----------------
                if ($bundle_type === 'off') :

                    // linked prods
                    $linked_off_prods = $_POST['linked_off_prods'];

                    // loop through linked prods and add to cart
                    foreach ($linked_off_prods as $prod_data) :


                        // retrieve product
                        $prod = wc_get_product($prod_data['prod_id']);


                        // if variable product
                        if ($prod->get_type() === 'variable') :

                            // retrieve variation data
                            $vars = $prod->get_available_variations();

                            // loop to retrieve correct variation id
                            foreach ($vars as $var_data) :

                                // retrieve attribute string
                                $attrib_string = implode('', $var_data['attributes']);


                                // if string matches submitted string, retrieve variation id
                                if ($attrib_string == $prod_data['attribute']) :
                                    $var_id = $var_data['variation_id'];
                                endif;

                            endforeach;

                            // add to cart
                            $cart_keys[] = WC()->cart->add_to_cart($prod->get_id(), 1, $var_id, [], ['mwc_off_discount' => $discount]);

                        // if simple prod
                        else :

                            // add to cart
                            $cart_keys[] = wc()->cart->add_to_cart($prod->get_id(), 1, 0, [], ['mwc_off_discount' => $discount]);

                        endif;

                        // set shipping
                        if ($is_free_shipping === true) :
                            wc()->cart->set_shipping_total(0);
                        endif;

                    endforeach;

                endif;

                // -----------------
                // bundle type FREE
                // -----------------
                if ($bundle_type === 'free') :

                    // linked prods
                    $linked_free_prods = $_POST['linked_free_prods'];

                    // loop through linked prods and add to cart
                    foreach ($linked_free_prods as $prod_data) :


                        // free prods
                        if (isset($prod_data['free_id'])) :

                            $prod = wc_get_product($prod_data['free_id']);

                            // if variable product
                            if ($prod->get_type() === 'variable') :

                                // retrieve variation data
                                $vars = $prod->get_available_variations();

                                // loop to retrieve correct variation id
                                foreach ($vars as $var_data) :

                                    // retrieve attribute string
                                    $attrib_string = implode('', $var_data['attributes']);

                                    // if string matches submitted string, retrieve variation id
                                    if ($attrib_string === $prod_data['attribute']) :
                                        $var_id = $var_data['variation_id'];
                                    endif;

                                endforeach;

                                // add to cart
                                $cart_keys[] = wc()->cart->add_to_cart($prod->get_id(), 1, $var_id, [], ['mwc_bun_free_prod' => $discount]);

                            // if simple prod
                            else :

                                // add to cart
                                $cart_keys[] = wc()->cart->add_to_cart($prod->get_id(), 1, 0, [], ['mwc_bun_free_prod' => $discount]);

                            endif;
                        endif;

                        // paid prods
                        if (isset($prod_data['paid_id'])) :

                            $prod = wc_get_product($prod_data['paid_id']);

                            // if variable product
                            if ($prod->get_type() === 'variable') :

                                // retrieve variation data
                                $vars = $prod->get_available_variations();

                                // loop to retrieve correct variation id
                                foreach ($vars as $var_data) :

                                    // retrieve attribute string
                                    $attrib_string = implode('', $var_data['attributes']);

                                    // if string matches submitted string, retrieve variation id
                                    if ($attrib_string === $prod_data['attribute']) :
                                        $var_id = $var_data['variation_id'];
                                    endif;

                                endforeach;

                                // add to cart
                                $cart_keys[] = wc()->cart->add_to_cart($prod->get_id(), 1, $var_id, [], ['mwc_bun_paid_prod' => $discount]);

                            // if simple prod
                            else :

                                // add to cart
                                $cart_keys[] = wc()->cart->add_to_cart($prod->get_id(), 1, 0, [], ['mwc_bun_paid_prod' => $discount]);

                            endif;

                        endif;

                        // set shipping
                        if ($is_free_shipping === true) :
                            wc()->cart->set_shipping_total(0);
                        endif;

                    endforeach;

                endif;

            endif;

            // ****************************
            // 4. ADD ADD-ON PRODS TO CART 
            // ****************************

            // simple addon prods to cart
            $simple_addons = isset($_POST['addon_simple_prods']) ? $_POST['addon_simple_prods'] : false;

            if ($simple_addons !== false && !empty($simple_addons)) :
                foreach ($simple_addons as $s_addon) :
                    $cart_keys[] = wc()->cart->add_to_cart($s_addon['simple_id'], $s_addon['qty'], 0, [], ['mwc_addon_disc' => (int)$s_addon['discount']]);
                endforeach;
            endif;

            // variable addon prods to cart
            $variable_addons = isset($_POST['addon_variable_prods']) ? $_POST['addon_variable_prods'] : false;

            if ($variable_addons !== false && !empty($variable_addons)) :
                foreach ($variable_addons as $v_addon) :
                    $cart_keys[] = wc()->cart->add_to_cart($v_addon['parent_id'], $v_addon['qty'], $v_addon['variation_id'], [], ['mwc_addon_disc' => (int)$v_addon['discount']]);
                endforeach;
            endif;

            // set bundle id to session
            wc()->session->set('mwc_bundle_id', $bundle_id);

            // UNCOMMENT TO DEBUG SESSION
            // print_r($_SESSION);
            // wp_die();

            // set mwc bundle flag to session - used to remove all other coupons and discounts
            wc()->session->set('is_mwc_bundle', 'yes');

            wp_send_json($cart_keys);

            wp_die();
        }
    }

endif;
