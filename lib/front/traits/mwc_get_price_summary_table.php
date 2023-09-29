<?php
if (!trait_exists('GetPriceSummaryTable')) :

    trait GetPriceSummaryTable
    {


        /**
         * mwc_get_price_summary_table AJAX action
         *
         * @return json
         */
        public static function mwc_get_price_summary_table()
        {

            check_ajax_referer('get set summary prices');

            // retrieve price list
            // $price_list = $_POST['price_list'];

            // wp_send_json($_POST);

            // start session
            if (!session_id()) :
                session_start();
            endif;

            // wp_send_json($price_list);

            // uncomment to debug
            // file_put_contents(MWC_PLUGIN_DIR.'summ-pricelists.log', print_r($price_list, true));

            // current currency
            $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_option('woocommerce_currency');

            // get default currency
            $default_currency = get_option('woocommerce_currency');

            // get alg exchange rate
            $ex_rate = get_option("alg_currency_switcher_exchange_rate_{$default_currency}_{$current_curr}") ? get_option("alg_currency_switcher_exchange_rate_{$default_currency}_{$current_curr}") : 1;

            // get product ids and variation attributes
            // $prod_ids    = $_POST['product_ids'];
            // $var_attribs = $_POST['var_attribs'];

            // discount percentage
            $discount_perc = $_POST['disc_perc'];

            // wp_send_json($discount_perc);

            // get bundle data
            $bundle_data = get_post_meta(trim($_POST['bundle_id']), 'product_discount', true);

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
                $product_qty = array_sum(array_column($product_data, 'quantity'));

            // ---------
            // type off
            // ---------
            elseif ($bundle_data['selValue'] == 'off') :

                // get product qty
                $product_data = $bundle_data['selValue_off'];
                $product_qty = $product_data['quantity'];

            // ---------
            // type free
            // ---------
            elseif ($bundle_data['selValue'] == 'free') :
                $product_qty = array_sum(array_column($bundle_data, 'quantity'));
            endif;

            // product qty
            // $product_qty = intval($_POST['product_qty']);

            // get bundle type
            $bun_type = $bundle_data['selValue'];

            // holds individual prices
            $b_total_arr = [];

            // -----------------------
            // if bundle type is "bun"
            // -----------------------
            if ($bun_type == 'bun') :

                // get products
                $prods = array_column($bundle_data, 'post')[0];

                // get custom pricing array
                $custom_pricing = array_column($bundle_data, 'price_currency')[0];

                // get custom price for current currency
                $custom_price = $custom_pricing[$current_curr];

                // loop and get product prices and qtys
                foreach ($prods as $prod) :

                    // wp_send_json($prod);

                    //  get product object
                    $prod_obj = wc_get_product($prod['id']);

                    // get product type
                    $prod_type = $prod_obj->get_type();

                    // if product is variable
                    if ($prod_type == 'variable') :

                        // get first child
                        $var_id = $prod_obj->get_children()[0];

                        // get child object
                        $var_obj = wc_get_product($var_id);

                        // get regular price
                        $prod_price = $var_obj->get_regular_price() * $prod['quantity'];

                        // add to product qty
                        $product_qty += $prod['quantity'];

                        $b_total_arr[] = $prod_price;

                    // if product is simple
                    else :

                        // retrieve regular price
                        $prod_price = $prod_obj->get_regular_price() * $prod['id'];

                        $b_total_arr[] = $prod_price;

                    endif;

                endforeach;

                // calculate $b_total_arr total
                $b_total_full = array_sum($b_total_arr);

                // calculate discounted total
                $b_discounted_total = $custom_price !== '' ? $custom_price : $b_total_full - ($b_total_full * ($discount_perc / 100));

                // calculate discounted product price
                $p_price = $b_discounted_total / $product_qty;

                // setup default/failed response
                $return = [
                    'status' => false
                ];

                // build table HTML as needed
                $html = '<table>';

                // old price
                $html .= '<tr id="mwc-summ-old-price">';
                $html .= '<td>' . __('Old Price', 'woocommerce') . '</td>';
                $html .= '<td style="text-align: right;"><del>' . wc_price($b_total_full) . '</del></td>';
                $html .= '</tr>';

                // bundle price
                $html .= '<tr id="mwc-summ-bundle-price">';
                $html .= '<td>' . $_POST['bundle_label'] . '</td>';
                $html .= '<td style="text-align: right;">' . wc_price($b_discounted_total) . '</td>';
                $html .= '</tr>';

                // get shipping total
                $meta          = isset($_POST['bundle_id']) ? get_post_meta($_POST['bundle_id'], 'product_discount', true) : [];
                $free_shipping = isset($meta['free_shipping']) ? $meta['free_shipping'] : false;

                if ($free_shipping) :
                    WC()->cart->set_shipping_total(0);
                endif;

                $html .= '<tr id="mwc-summ-ship-cost">';
                $html .= '<td>' . __('Shipping', 'woocommerce') . '</td>';

                $shipping_total = WC()->cart->get_shipping_total();

                if ($shipping_total) :

                    $html .= '<td  style="text-align: right"><span class="amount">' . wc_price($shipping_total) . '</span></td>';
                    $b_discounted_total += $shipping_total;

                else :

                    $html .= '<td  style="text-align: right"><span class="amount">' . __('Free Shipping', 'woocommerce') . '</span></td>';

                endif;

                $html .= '</tr>';
                $html .= '<tr id="mwc-summ-bun-total">';
                $html .= '<td><b>' . __('Total', 'woocommerce') . '</b></td>';
                $html .= '<td style="text-align: right"><b>' . wc_price($b_discounted_total) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';

                // setup response
                $return = [
                    'status'    => true,
                    'is_bundle' => true,
                    'html'      => $html,
                    'old_total' => __('<b>Normal Price:</b> <del>' . wc_price($b_total_full) . '</del><br>', 'woocommerce'),
                    'mc_total'  => __('<b>Bundle Price:</b> ' . wc_price($b_discounted_total), 'woocommerce'),
                ];

                // send json
                wp_send_json($return);

            // ----------------------------------
            // if bundle type is "free" or "off"
            // ----------------------------------
            else :

                // get products
                $prods = array_column($bundle_data, 'post');

                // loop and get product prices and qtys
                foreach ($prods as $prod) :

                    //  get product object
                    $prod_obj = wc_get_product($prod['id']);

                    // get product type
                    $prod_type = $prod_obj->get_type();

                    // if product is variable
                    if ($prod_type == 'variable') :

                        // get first child
                        $var_id = $prod_obj->get_children()[0];

                        // get child object
                        $var_obj = wc_get_product($var_id);

                        // get regular price
                        $b_total_full = $var_obj->get_regular_price() * $product_qty;

                    // if product is simple
                    else :

                        // retrieve total regular price
                        $b_total_full = $prod_obj->get_regular_price() * $product_qty;

                    endif;

                endforeach;


                // calculate discounted total
                $b_discounted_total = $b_total_full - ($b_total_full * ($discount_perc / 100));

                // calculate discounted product price
                $p_price = $b_discounted_total / $product_qty;

                // setup default/failed response
                $return = [
                    'status' => false
                ];

                // build table HTML as needed
                $html = '<table>';

                // old price
                $html .= '<tr id="mwc-summ-old-price">';
                $html .= '<td>' . __('Old Price', 'woocommerce') . '</td>';
                $html .= '<td style="text-align: right;"><del>' . wc_price($b_total_full) . '</del></td>';
                $html .= '</tr>';

                // bundle price
                $html .= '<tr id="mwc-summ-bundle-price">';
                $html .= '<td>' . $_POST['bundle_label'] . '</td>';
                $html .= '<td style="text-align: right;">' . wc_price($b_discounted_total) . '</td>';
                $html .= '</tr>';

                // get shipping total
                $meta          = isset($_POST['bundle_id']) ? get_post_meta($_POST['bundle_id'], 'product_discount', true) : [];
                $free_shipping = isset($meta['free_shipping']) ? $meta['free_shipping'] : false;

                if ($free_shipping) :
                    WC()->cart->set_shipping_total(0);
                endif;

                $html .= '<tr id="mwc-summ-ship-cost">';
                $html .= '<td>' . __('Shipping', 'woocommerce') . '</td>';

                $shipping_total = WC()->cart->get_shipping_total();

                if ($shipping_total) :

                    $html .= '<td  style="text-align: right"><span class="amount">' . wc_price($shipping_total) . '</span></td>';
                    $b_discounted_total += $shipping_total;

                else :

                    $html .= '<td  style="text-align: right"><span class="amount">' . __('Free Shipping', 'woocommerce') . '</span></td>';

                endif;

                $html .= '</tr>';
                $html .= '<tr id="mwc-summ-bun-total">';
                $html .= '<td><b>' . __('Total', 'woocommerce') . '</b></td>';
                $html .= '<td style="text-align: right"><b>' . wc_price($b_discounted_total) . '</b></td>';
                $html .= '</tr>';
                $html .= '</table>';

                // setup response
                $return = [
                    'status'    => true,
                    'is_bundle' => false,
                    'html'      => $html,
                    'old_total' => '<del>' . wc_price($b_total_full) . '</del>',
                    'mc_total'  => wc_price($b_discounted_total),
                    'p_price'   => '<b>' . wc_price($p_price) . __('</b> / Each', 'woocommerce')
                ];

                // send json
                wp_send_json($return);

            endif;
        }
    }

endif;
