<?php

defined('ABSPATH') ?: exit();

if (!trait_exists('MWC_Remove_Other_Coupons')) :

    trait MWC_Remove_Other_Coupons
    {

        /**
         * Invalidates all other discounts/coupons if MWC bundle in cart
         *
         * @param bool $is_valid
         * @param string $coupon
         * @param float $discount
         * @return void
         */
        public static function mwc_remove_other_coupons($is_valid, $coupon, $discount)
        {
            $is_valid = false;
            return $is_valid;
        }

        /**
         * Disable other coupons if MWC bundle is present on any page
         * 
         * @return void
         */
        public static function mwc_disable_other_coupons()
        {
            // debug - var dump wc session data
            var_dump(wc()->session->get('is_mwc_bundle'));

            // if current page or post content contains text "mwc_one_page_checkout" and is post type 'offer' or 'landing' or 'collection', set wc session to "is_mwc_bundle" = "yes"
            if (is_singular(['offer', 'landing', 'collection']) && strpos(get_the_content(), 'mwc_one_page_checkout') !== false) :
                wc()->session->set('is_mwc_bundle', 'yes');
            endif;

            // if session is set to "is_mwc_bundle" = "yes", remove all other coupons
            if (wc()->session->get('is_mwc_bundle') === 'yes') :
                wc()->cart->remove_coupons();
            endif;
        }
    }

endif;
