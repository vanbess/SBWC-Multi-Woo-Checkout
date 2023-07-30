<?php
if (!trait_exists('GetPriceSummaryTable')) :

    trait GetPriceSummaryTable {


        /**
         * mwc_get_price_summary_table AJAX action
         *
         * @return json
         */
        public static function mwc_get_price_summary_table() {

            check_ajax_referer('get set summary prices');

            // retrieve price list
            $price_list = $_POST['price_list'];

            // uncomment to debug
            // file_put_contents(MWC_PLUGIN_DIR.'summ-pricelists.log', print_r($price_list, true));

            // current currency
            $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_woocommerce_currency();

            // uncomment to debug specific currency conversion
            // $current_curr = 'EUR';

            // setup default/failed response
            $return = [
                'status' => false
            ];

            // build table HTML as needed
            if ($price_list) :

                $p_total = 0;

                $html = '<table>';

                foreach ($price_list as $bundle_price) :

                    if (isset($bundle_price['label']) && isset($bundle_price['price']) && (int)$bundle_price['price'] !== 0) :

                        if ($bundle_price['sum']  == 1) :

                            $html .= '<tr>';
                            $html .= '<td>' . $bundle_price['label'] . '</td>';
                            $html .= '<td style="text-align: right;">' . wc_price($bundle_price['price'], ['ex_tax_label' => false, 'currency' => $current_curr]) . '</td>';
                            $html .= '</tr>';

                            $p_total += $bundle_price['price'];

                        else :

                            $html .= '<tr>';
                            $html .= '<td>' . $bundle_price['label'] . '</td>';
                            $html .= '<td id="summ-old-price" style="text-align: right; text-decoration: line-through;">' . wc_price($price_list['old_price']['price'], ['ex_tax_label' => false, 'currency' => $current_curr]) . '</td>';
                            $html .= '</tr>';

                        endif;
                    endif;
                endforeach;

                // get shipping total
                $meta          = isset($_POST['bundle_id']) ? get_post_meta($_POST['bundle_id'], 'product_discount', true) : [];
                $free_shipping = isset($meta['free_shipping']) ? $meta['free_shipping'] : false;

                if ($free_shipping) :
                    WC()->cart->set_shipping_total(0);
                endif;

                $html .= '<tr>';
                $html .= '<td>' . __('Shipping', 'woocommerce') . '</td>';

                $shipping_total = WC()->cart->get_shipping_total();

                if ($shipping_total) :

                    $html .= '<td  style="text-align: right"><span class="amount">' . wc_price($shipping_total, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</span></td>';
                    $p_total += $shipping_total;

                else :

                    $html .= '<td  style="text-align: right"><span class="amount">' . __('Free Shipping', 'woocommerce') . '</span></td>';

                endif;

                $html .= '</tr>';
                $html .= '<tr>';
                $html .= '<td>' . __('Total', 'woocommerce') . '</td>';
                $html .= '<td style="text-align: right">' . wc_price($p_total, ['ex_tax_label' => false, 'currency' => $current_curr]) . '</td>';
                $html .= '</tr>';
                $html .= '</table>';

                $return = [
                    'status' => true,
                    'html'   => $html
                ];

            endif;

            // send json
            wp_send_json($return);

            wp_die();
        }
    }

endif;
