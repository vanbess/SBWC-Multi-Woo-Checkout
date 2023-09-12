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

            // get product ids and variation attributes
            $prod_ids    = $_POST['product_ids'];
            $var_attribs = $_POST['var_attribs'];

            // discount percentage
            $discount_perc = $_POST['disc_perc'];

            // holds individual prices
            $b_total_arr = [];

            // get individual prices
            foreach ($prod_ids as $index => $prod_id) :

                $attrib     = $var_attribs[$index];
                $prod       = wc_get_product($prod_id);
                $variations = $prod->get_available_variations();

                // get individual price
                foreach ($variations as $variation) :

                    $v_attribs = $variation['attributes'];

                    if (in_array($attrib, $v_attribs)) :
                        $b_total_arr[] = $variation['display_regular_price'] ? $variation['display_regular_price'] : $variation['display_price'];
                    endif;

                endforeach;

            endforeach;

            // current currency
            $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_option('woocommerce_currency');

            // calculate $b_total_arr total
            $b_total_arr_sum = array_sum($b_total_arr);

            $b_total_full = $b_total_arr_sum;

            // calculate discounted total
            $b_discounted_total = $b_total_full - ($b_total_full * ($discount_perc / 100));

            // calculate discounted product price
            $p_price = $b_discounted_total / intval($_POST['product_qty']);

            // if only setting base pricing
            if($_POST['setting_bundle_pricing']):

                $b_pricing = [
                    'is_b_pricing' => true,
                    'old_total'    => '<del>' . wc_price($b_total_full, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</del>',
                    'mc_total'     => wc_price($b_discounted_total, ['ex_tax_label' => false, 'currency' => $current_curr]),
                    'p_price'      => '<b>' . wc_price($p_price, ['ex_tax_label' => false, 'currency' => $current_curr]) . __('</b> / Each', 'woocommerce')
                ];

                // send json
                wp_send_json($b_pricing);
                
            endif;

            // add mwc data to session for later ref
            $_SESSION['mwc_bundle_total_full']       = $b_total_full;
            $_SESSION['mwc_bundle_discounted_total'] = $b_discounted_total;
            $_SESSION['mwc_product_price']           = $p_price;
            $_SESSION['mwc_bundle_discount_perc']    = $discount_perc;
            $_SESSION['mwc_bundle_label']            = $_POST['bundle_label'];

            // setup default/failed response
            $return = [
                'status' => false
            ];

            // build table HTML as needed
            $html = '<table>';

            // old price
            $html .= '<tr id="mwc-summ-old-price">';
            $html .= '<td>' . __('Old Price', 'woocommerce') . '</td>';
            $html .= '<td style="text-align: right;"><del>' . wc_price($b_total_full, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</del></td>';
            $html .= '</tr>';

            // bundle price
            $html .= '<tr id="mwc-summ-bundle-price">';
            $html .= '<td>' . $_POST['bundle_label'] . '</td>';
            $html .= '<td style="text-align: right;">' . wc_price($b_discounted_total, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</td>';
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

                $html .= '<td  style="text-align: right"><span class="amount">' . wc_price($shipping_total, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</span></td>';
                $b_discounted_total += $shipping_total;

            else :

                $html .= '<td  style="text-align: right"><span class="amount">' . __('Free Shipping', 'woocommerce') . '</span></td>';

            endif;

            $html .= '</tr>';
            $html .= '<tr id="mwc-summ-bun-total">';
            $html .= '<td><b>' . __('Total', 'woocommerce') . '</b></td>';
            $html .= '<td style="text-align: right"><b>' . wc_price($b_discounted_total, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</b></td>';
            $html .= '</tr>';
            $html .= '</table>';

            // setup response
            $return = [
                'status'   => true,
                'html'     => $html,
                'old_total' => '<del>' . wc_price($b_total_full, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</del>',
                'mc_total' => wc_price($b_discounted_total, ['ex_tax_label' => false, 'currency' => $current_curr]),
                'p_price'  => '<b>' . wc_price($p_price, ['ex_tax_label' => false, 'currency' => $current_curr]) . __('</b> / Each', 'woocommerce')
            ];


            // send json
            wp_send_json($return);

            wp_die();
        }
    }

endif;
