<?php
if (!trait_exists('AddToCartBasicAjAction')) :

    trait AddToCartBasicAjAction
    {

        /**
         * mwc_add_to_cart_multiple AJAX action
         *
         * @return json
         */
        public static function mwc_add_to_cart_multiple()
        {

            check_ajax_referer('add multiple products to cart');

            // uncomment to test payload
            // print_r($_POST);
            // wp_die();

            // start session if not started
            if (!session_id()) :
                session_start();
            endif;

            // holds total mwc product count
            global $mwc_prod_count;

            // count bun_variable_prods and bun_simple_prods and addon_simple_prods and addon_variable_prods
            $bun_variable_prods_count   = isset($_POST['bun_variable_prods']) ? count($_POST['bun_variable_prods']) : 0;
            $bun_simple_prods_count     = isset($_POST['bun_simple_prods']) ? count($_POST['bun_simple_prods']) : 0;
            $addon_simple_prods_count   = isset($_POST['addon_simple_prods']) ? count($_POST['addon_simple_prods']) : 0;
            $addon_variable_prods_count = isset($_POST['addon_variable_prods']) ? count($_POST['addon_variable_prods']) : 0;

            // set mwc_prod_count
            $mwc_prod_count = $bun_variable_prods_count + $bun_simple_prods_count + $addon_simple_prods_count + $addon_variable_prods_count;

            // empty cart if cart total is 0
            if (wc()->cart->get_cart_contents_total() == 0) :
                wc()->cart->empty_cart();
            endif;

            // empty cart completely on each submission
            wc()->cart->empty_cart();

            // retrieve bundle type
            $bundle_type = isset($_POST['bundle_type']) ? $_POST['bundle_type'] : false;

            // retrieve bundle id
            $bundle_id = isset($_POST['bundle_id']) ? $_POST['bundle_id'] : false;

            // check shipping settings
            $bundle_meta = get_post_meta($bundle_id, 'product_discount', true);
            $free_shipping = isset($bundle_meta['free_shipping']) ? $bundle_meta['free_shipping'] : false;

            // holds all bundle and addon cart keys for later reference/troubleshooting
            $bundle_cart_keys = [];

            // *******************
            // 1. ADD BUN TO CART
            // *******************
            if ($bundle_type === 'bun') :

                // retrieve bundle variable product data
                $bun_variable_prods = isset($_POST['bun_variable_prods']) ? $_POST['bun_variable_prods'] : null;

                // retrieve bundle simple product data
                $bun_simple_prods = isset($_POST['bun_simple_prods']) ? $_POST['bun_simple_prods'] : null;

                // retrieve bundle discount
                $bun_discount = isset($_POST['bun_discount']) ? (int)$_POST['bun_discount'] : null;

                // 1.1 Add variable products to cart if present
                if (!is_null($bun_variable_prods) && !empty($bun_variable_prods)) :
                    foreach ($bun_variable_prods as $var_data) :
                        $bundle_cart_keys['variable_prod_keys'][] = wc()->cart->add_to_cart($var_data['parent_id'], 1, $var_data['variation_id'], [], ['mwc_bun_discount' => $bun_discount]);
                    endforeach;
                endif;

                // 1.2 Add simple products to cart if present
                if (!is_null($bun_simple_prods) && !empty($bun_simple_prods)) :
                    foreach ($bun_simple_prods as $simp_id) :
                        $bundle_cart_keys['simple_prod_keys'][] = wc()->cart->add_to_cart($simp_id, 1, 0, [], ['mwc_bun_discount' => $bun_discount]);
                    endforeach;
                endif;

                // set shipping
                if ($free_shipping === true) :
                    wc()->cart->set_shipping_total(0);
                endif;

            endif;

            // *******************
            // 2. ADD OFF TO CART
            // *******************
            if ($bundle_type === 'off') :

                // discount
                $discount = (int)$_POST['off_discount'];

                // variable prods
                $variable_prods = isset($_POST['off_variable_prods']) ? $_POST['off_variable_prods'] : null;

                // simple prod
                $simple_prod = isset($_POST['off_simple_prod']) ? $_POST['off_simple_prod'] : null;

                // add variable products to cart
                if (is_array($variable_prods) && !empty($variable_prods)) :
                    foreach ($variable_prods as $v_data) :
                        $bundle_cart_keys['var_item_keys'][] = wc()->cart->add_to_cart($v_data['parent_id'], 1, $v_data['variation_id'], [], ['mwc_off_discount' => $discount]);
                    endforeach;
                endif;

                // add simple products to cart
                if (is_array($simple_prod) && !empty($simple_prod)) :
                    $bundle_cart_keys['simp_item_key'][] = wc()->cart->add_to_cart($simple_prod['prod_id'], $simple_prod['prod_qty'], 0, [], ['mwc_off_discount' => $discount]);
                endif;

                // set shipping
                if ($free_shipping === true) :
                    wc()->cart->set_shipping_total(0);
                endif;

            endif;

            // ********************
            // 3. ADD FREE TO CART
            // ********************
            if ($bundle_type === 'free') :

                // print_r($_POST);

                // wp_die();

                // retrieve paid variable prods and add to cart
                $paid_variable_prods = isset($_POST['paid_variable_prods']) ? $_POST['paid_variable_prods'] : null;

                if (is_array($paid_variable_prods) && !empty($paid_variable_prods)) :
                    foreach ($paid_variable_prods as $v_data) :
                        $bundle_cart_keys['paid_var_prods'][] = wc()->cart->add_to_cart($v_data['parent_id'], 1, $v_data['variation_id'], [], ['mwc_bun_paid_prod' => true]);
                    endforeach;
                endif;

                // retrieve free variable prods and add to cart
                $free_variable_prods = isset($_POST['free_variable_prods']) ? $_POST['free_variable_prods'] : null;

                if (is_array($free_variable_prods) && !empty($free_variable_prods)) :
                    foreach ($free_variable_prods as $v_data) :
                        $bundle_cart_keys['free_var_prods'][] = wc()->cart->add_to_cart($v_data['parent_id'], 1, $v_data['variation_id'], [], ['mwc_bun_free_prod' => true]);
                    endforeach;
                endif;

                // retrieve paid simple prods and add to cart
                $paid_simple_prods = isset($_POST['paid_simple_prod']) ? $_POST['paid_simple_prod'] : null;

                if (is_array($paid_simple_prods) && !empty($paid_simple_prods)) :
                    foreach ($paid_simple_prods as $s_data) :
                        $bundle_cart_keys['paid_simp_prods'][] = wc()->cart->add_to_cart($s_data['id'], $s_data['qty'], 0, [], ['mwc_bun_paid_prod' => true]);
                    endforeach;
                endif;

                // retrieve free simple prods and add to cart
                $free_simple_prods = isset($_POST['free_simple_prod']) ? $_POST['free_simple_prod'] : null;

                if (is_array($free_simple_prods) && !empty($free_simple_prods)) :
                    foreach ($free_simple_prods as $s_data) :
                        $bundle_cart_keys['free_simp_prods'][] = wc()->cart->add_to_cart($s_data['id'], $s_data['qty'], 0, [], ['mwc_bun_free_prod' => true]);
                    endforeach;
                endif;

                // set shipping
                if ($free_shipping === true) :
                    wc()->cart->set_shipping_total(0);
                endif;

            endif;

            // ****************************
            // 4. ADD ADD-ON PRODS TO CART 
            // ****************************

            // simple addon prods to cart
            $simple_addons = isset($_POST['addon_simple_prods']) ? $_POST['addon_simple_prods'] : false;

            if ($simple_addons !== false && !empty($simple_addons)) :
                foreach ($simple_addons as $s_addon) :
                    $bundle_cart_keys['simple_addons'][] = wc()->cart->add_to_cart($s_addon['simple_id'], $s_addon['qty'], 0, [], ['mwc_addon_disc' => (int)$s_addon['discount']]);
                endforeach;
            endif;

            // variable addon prods to cart
            $variable_addons = isset($_POST['addon_variable_prods']) ? $_POST['addon_variable_prods'] : false;

            if ($variable_addons !== false && !empty($variable_addons)) :
                foreach ($variable_addons as $v_addon) :
                    $bundle_cart_keys['variable_addons'][] = wc()->cart->add_to_cart($v_addon['parent_id'], $v_addon['qty'], $v_addon['variation_id'], [], ['mwc_addon_disc' => (int)$v_addon['discount']]);
                endforeach;
            endif;

            // add bundle id to session for AFTER items added to cart later ref
            $_SESSION['mwc_bundle_id'] = $bundle_id;

            // set mwc bundle flag to session - used to remove all other coupons and discounts
            wc()->session->set('is_mwc_bundle', 'yes');

            // return error or success
            if (!empty($bundle_cart_keys)) :
                wp_send_json_success($bundle_cart_keys);
            else :
                wp_send_json_error();
            endif;

            wp_die();
        }
    }

endif;
