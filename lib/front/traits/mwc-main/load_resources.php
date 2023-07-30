<?php
if (!trait_exists('LoadResources')) :

    trait LoadResources {

        /**
         * load_resources
         *
         * @return void
         */
        public static function load_resources() {

            global $woocommerce;

            // setup cart and checkout urls
            $cart_url = '/cart/';
            $checkout_url = '/checkout/';

            if (!empty($woocommerce)) :
                $cart_url = wc_get_cart_url();
                $checkout_url = wc_get_checkout_url();
            endif;

            wp_enqueue_style('mwc_common_style', MWC_PLUGIN_URL . 'resources/style/common.css', array(), MWCVersion, 'all');
            wp_enqueue_style('mwc_style', MWC_PLUGIN_URL . 'resources/style/front_style.css', array(), MWCVersion . time(), 'all');
            wp_enqueue_script('mwc_front_script_js', MWC_PLUGIN_URL . 'resources/js/front_js.js', ['jquery'], time(), true);

            wp_localize_script(
                'mwc_front_script_js',
                'mwc_ajax_obj',
                array(
                    'ajax_url'              => admin_url('admin-ajax.php'),
                    'home_url'              => home_url(),
                    'cart_url'              => $cart_url,
                    'checkout_url'          => $checkout_url,
                    'summary_price_nonce'   => wp_create_nonce('get set summary prices'),
                    'variation_price_nonce' => wp_create_nonce('get set variation prices'),
                    'atc_nonce'             => wp_create_nonce('add multiple products to cart')
                )
            );

            /**
             * New ATC scripts
             */
            wp_enqueue_script('mwc_atc_reworked', self::mwc_atc_js(), ['jquery'], time(), true);
            wp_enqueue_script('mwc_atc_linked_prods', self::mwc_atc_js_linked_prods(), ['jquery'], time(), true);
        }
    }

endif;
