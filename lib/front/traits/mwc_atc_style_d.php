<?php

// prevent direct access
defined('ABSPATH') || exit();


if (!trait_exists('MWC_ATC_Style_D')) :
    return;
endif;

trait MWC_ATC_Style_D
{

    /**
     * AJAX to add style D products to cart
     *
     * @return void
     */
    public function mwc_atc_template_d_products()
    {

        // check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mwc_atc_template_d_products')) {
            wp_send_json_error('Nonce verification failed', 403);
        }

        // debug
        // wp_send_json($_POST);

        // add bundle id to wc session
        WC()->session->set('mwc_bundle_id', $_POST['bundle_id']);

        // add bundle flag to wc session
        WC()->session->set('is_mwc_bundle', 'yes');

        // set mwc style type
        WC()->session->set('mwc_style_type', 'D');

        // empty cart
        WC()->cart->empty_cart();

        // get product data
        $product_data = $_POST['product_data'];

        // debug
        // wp_send_json($product_data);

        // get discount text and % discount
        $discount_data = self::generate_discount_text($product_data);

        // add discount date to session
        WC()->session->set('mwc_discount_data', $discount_data);

        // debug
        // wp_send_json($discount_data);

        // holds cart keys
        $cart_keys = [];

        // debug
        // $test_var_ids = [];

        // loop to add to cart
        foreach ($product_data as $item) :

            $var_sku = isset($item['selected_color']) ? get_post_meta($item['prod_id'], '_sku', true) . '-' . $item['selected_size'] . '-' . $item['selected_color'] : get_post_meta($item['prod_id'], '_sku', true) . '-' . $item['selected_size'];

            // get variation id based on sku
            $item['variation_id'] = wc_get_product_id_by_sku($var_sku);

            // $test_var_ids[] = $item['variation_id'];

            $cart_keys[] = WC()->cart->add_to_cart(
                $item['prod_id'],
                $item['qty'],
                $item['variation_id'] ? $item['variation_id'] : 0,
                [],
                ['mwc_bun_free_prod' => $discount_data['discount_perc']]

            );

        endforeach;

        // debug
        // wp_send_json(['test_var_ids' => $test_var_ids]);

        // if cart keys are empty, send error, else send success
        if (empty($cart_keys)) {
            wp_send_json_error('Error adding products to cart', 500);
        } else {
            wp_send_json_success([
                'cart_keys'     => $cart_keys,
                'discount_data' => $discount_data
            ]);
        }
    }

    /**
     * Generate discount text + calc discount %
     *
     * @param array $prod_data
     * @return void
     */
    private static function generate_discount_text($prod_data)
    {

        // ======
        // BOGOF
        // ======

        // bogof paid items
        $bogof_paid = array_filter($prod_data, function ($item) {
            return $item['prod_type'] == 'mwc_bun_paid_prod' || false;
        });

        // bogof free items
        $bogof_free = array_filter($prod_data, function ($item) {
            return $item['prod_type'] == 'mwc_bun_free_prod' || false;
        });

        if (!empty($bogof_paid) && !empty($bogof_free)) :

            // get counts
            $bogof_paid_count = count($bogof_paid);
            $bogof_free_count = count($bogof_free);

            // calc % discount
            $discount_perc = ($bogof_free_count / ($bogof_paid_count + $bogof_free_count)) * 100;

            // format discount % to zero decimal places
            $discount_perc = number_format($discount_perc, 0);

            // setup discount fee text (buy x get y free) with discount %
            $discount_text = sprintf(
                __('Buy %s get %s free (%s%% discount)', 'mwc'),
                $bogof_paid_count,
                $bogof_free_count,
                $discount_perc
            );

            return [
                'discount_text' => $discount_text,
                'paid_count'    => $bogof_paid_count,
                'free_count'    => $bogof_free_count,
                'discount_perc' => $discount_perc,
            ];

        endif;


        // ==================
        // % DISCOUNT BUNDLE
        // ==================

        // discount bundle type
        $discount_bundle_type = array_filter($prod_data, function ($item) {
            return $item['prod_type'] == 'mwc_bun_discount' || false;
        });


        // ===============
        // PRODUCT BUNDLE
        // ===============

        // product bundle type
        $product_bundle_type = array_filter($prod_data, function ($item) {
            return $item['prod_type'] == 'mwc_off_discount' || false;
        });

        // debug
        // return ([
        //     'bogof_paid'           => $bogof_paid,
        //     'bogof_free'           => $bogof_free,
        //     'discount_bundle_type' => $discount_bundle_type,
        //     'product_bundle_type'  => $product_bundle_type,
        // ]);
    }

    /**
     * Generates and returns correct variation id based on selected size and color (if applicable)
     * Ensures that valid variation id is added to cart for given parent product id
     *
     * @param array $item
     * @return void
     */
    private static function mwc_get_correct_variation_id($item)
    {

        // setup vars
        $prod_id = $item['prod_id'];
        $selected_size = isset($item['selected_size']) ? $item['selected_size'] : false;
        $selected_color = isset($item['selected_color']) ? $item['selected_color'] : false;

        // if both size and color are selected
        if($selected_size && $selected_color) :

            // get variation id based on sku
            $var_sku = get_post_meta($prod_id, '_sku', true) . '-' . $selected_size . '-' . $selected_color;
            $variation_id = wc_get_product_id_by_sku($var_sku);

            return $variation_id;
        
        endif;

        // if only size is selected
        if($selected_size && !$selected_color) :

            // get variation id based on sku
            $var_sku = get_post_meta($prod_id, '_sku', true) . '-' . $selected_size;
            $variation_id = wc_get_product_id_by_sku($var_sku);

            return $variation_id;

        endif;

        // if only color is selected
        if(!$selected_size && $selected_color) :

            // get variation id based on sku
            $var_sku = get_post_meta($prod_id, '_sku', true) . '-' . $selected_color;
            $variation_id = wc_get_product_id_by_sku($var_sku);

            return $variation_id;

        endif;

        // if neither size or color are selected
        if(!$selected_size && !$selected_color) :

            return 0;

        endif;

        // debug
        // wp_send_json($var_skus);
    }

}
