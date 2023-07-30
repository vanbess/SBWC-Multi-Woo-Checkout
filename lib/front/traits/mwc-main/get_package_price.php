<?php
if (!trait_exists('GetPackagePrice')) :

    trait GetPackagePrice {

        /**
         * mwc_get_price_package AJAX action
         * 
         * update/improved on 9 November 2022
         *
         * @return json
         */
        public static function mwc_get_price_package() {

            check_ajax_referer('get update bundle pricing');

            // grab vars
            $arr_discount    = $_POST['discount'];
            $arr_product_ids = $_POST['product_ids'];

            if (!empty($arr_product_ids) && !empty($arr_discount['type']) && isset($arr_discount['qty']) && isset($arr_discount['value'])) :

                // get total price
                $loop_prod   = [];
                $total_price = 0;
                $old_price   = 0;

                foreach ($arr_product_ids as $key => $prod_id) :

                    $product = wc_get_product($prod_id);
                    $loop_prod[$prod_id] = $product;
                    $total_price += $product->get_regular_price();
                    $old_price += $product->get_regular_price();

                endforeach;

                // get discount
                if ($arr_discount['type'] == 'percentage') :
                    $total_price -= ($total_price * $arr_discount['value']) / 100;
                elseif ($arr_discount['type'] == 'free' && in_array($arr_discount['value'], $arr_product_ids)) :
                    $free_price = wc_get_product($arr_discount['value'])->get_regular_price();
                    $total_price -= $free_price;
                endif;

                $return = array(
                    'status'           => true,
                    'total_price'      => $total_price,
                    'total_price_html' => wc_price($total_price),
                    'old_price'        => $old_price,
                    'old_price_html'   => wc_price($old_price),
                    'each_price'       => $total_price / count($arr_product_ids),
                    'each_price_html'  => wc_price($total_price / count($arr_product_ids))
                );

            endif;

            wp_send_json($return);

            wp_die();
        }
    }

endif;
