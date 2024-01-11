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

        // clear WC() session
        WC()->session->destroy_session();

        // debug
        // wp_send_json($_POST);

        // add bundle id to wc session
        WC()->session->set('mwc_bundle_id', $_POST['bundle_id']);

        // add bundle flag to wc session
        WC()->session->set('is_mwc_bundle', 'yes');

        // set mwc style type
        WC()->session->set('mwc_style_type', 'D');

        // set discount text
        WC()->session->set('mwc_discount_text', $_POST['discount_text']);

        // set discount fee
        WC()->session->set('mwc_discount_fee', $_POST['discount_amount']);

        // set discount percentage
        WC()->session->set('mwc_discount_perc', $_POST['discount_percentage']);


        // empty cart
        WC()->cart->empty_cart();

        // get product data
        $product_data = $_POST['product_data'];

        // debug
        // wp_send_json($product_data);

        // holds cart keys
        $cart_keys = [];

        // debug
        $test_var_ids = [];

        // loop to add to cart
        foreach ($product_data as $item) :

            // get variation id based on sku
            $item['variation_id'] = self::mwc_get_correct_variation_id($item);

            // wp_send_json($item);

            // add product type to session
            WC()->session->set('mwc_prod_type', $item['prod_type']);

            $test_var_ids[] = $item['variation_id'];

            $cart_keys[] = WC()->cart->add_to_cart(
                $item['prod_id'],
                $item['qty'],
                $item['variation_id'] ? $item['variation_id'] : 0,
                [],
                [$item['prod_type'] => $_POST['discount_percentage']]

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
            ]);
        }
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
        $prod_id        = $item['prod_id'];
        $selected_size  = $item['selected_size'];
        $selected_color = $item['selected_color'];

        // get sku
        $sku = get_post_meta($prod_id, '_sku', true);

        // debug
        // return $sku . '-' . $selected_size . '-' . $selected_color;

        $var_sku = $sku . '-' . $selected_size . '-' . $selected_color;

        // debug
        // return $var_sku;

        // Remove trailing dashes from $var_sku
        if (substr($var_sku, -1) === '-') {
            $var_sku = substr($var_sku, 0, -1); // Remove the last character
        }

        // Replace double dashes with single dashes
        $var_sku = strpos($var_sku, '--') ? str_replace('--', '-', $var_sku) : $var_sku;

        // debug
        // return strtoupper($var_sku);

        // get variation id based on sku
        $variation_id = wc_get_product_id_by_sku($var_sku);

        return $variation_id;
    }
}
