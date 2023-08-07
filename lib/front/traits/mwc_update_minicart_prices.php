<?php

// if accessing this file directly, abort
if (!defined('ABSPATH')) :
    exit;

endif;

// if trait does not exist, create it
if (!trait_exists('MWC_Update_MiniCart')) :

    trait MWC_Update_MiniCart
    {

        /** Applies bundle item regular pricing to mini cart
         *
         * @param string $price_html
         * @param array $cart_item
         * @param string $cart_item_key
         * @return void
         */
        public static function mwc_apply_regular_price_mini_cart($price_html, $cart_item, $cart_item_key)
        {

            if (isset($cart_item['mwc_bun_discount']) || isset($cart_item['mwc_off_discount']) || isset(($cart_item['mwc_bun_free_prod'])) || isset($_SESSION['mwc_bundle_id'])) :

                $product = wc_get_product($cart_item['product_id']);

                // variable prods
                if (isset($cart_item['variation_id']) && $product->is_type('variable')) :

                    $vars = $product->get_available_variations();

                    foreach ($vars as $var_data) :
                        if ($var_data['variation_id'] == $cart_item['variation_id']) :
                            $cart_item['data']->set_price($var_data['display_regular_price']);
                            return wc_price($var_data['display_regular_price']);
                        endif;
                    endforeach;

                // simple prods
                else :
                    $product = wc_get_product($cart_item['product_id']);
                    $cart_item['data']->set_price($product->get_regular_price());
                    return wc_price($product->get_regular_price());
                endif;

            endif;

            // default
            return $price_html;
        }

        /**
         * Get cart data
         *  
         * @since 2.4.3
         */
        public function mwc_get_cart_data()
        {

            global $woocommerce;

            // check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mwc_get_cart_data')) {
                wp_send_json_error('Invalid nonce');
                exit;
            }

            $cart_data = array(
                'total' => $woocommerce->cart->get_cart_total(),
                'items' => $woocommerce->cart->get_cart_contents()
            );

            wp_send_json($cart_data);
        }
    }

endif;
