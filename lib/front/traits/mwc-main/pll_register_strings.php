<?php

defined('ABSPATH') ?: exit();


if (!trait_exists('MWC_PLL_Register_Strings')) :

    trait MWC_PLL_Register_Strings {

        /**
         * Register PLL strings
         *
         * @return void
         */
        public static function mwc_pll_register_strings() {

            if(function_exists('pll_register_string')):
                pll_register_string('mwc_'.time(), 'Addon Product', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'One-time offer', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Percentage discount', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Buy %s + Get %d FREE', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Buy %s + Get %d&#37; Off', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Discount bundle', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Choose an option', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Total', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'You must be logged in to checkout.', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Have a Question?', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'See Our FAQS', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Addon Special', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'This product is currently out of stock and unavailable.', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Qty', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Add Item', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'See more', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Checking if you Qualify for Special Offers...', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Congratulations You Qualified!', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Checking 2 Warehouses For Available Stock...', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Stock Available In Warehouse 1! Reserving Your Units...', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step 2 of 3: Customer Information', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step 3 of 3: Payment Option', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step 1 of 3: Select Package', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Save %s&#37;', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'select', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'selected', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Bundle price', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Old Price', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Same as', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'each', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Discounted', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Sell-Out Risk', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'High', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Medium', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Low', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'FREE SHIPPING', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Discount', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Best Seller', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Popular', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Moderate', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Please choose:', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Select Free Product:', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Order Summary', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Item', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Price', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Packages', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Package', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), '+ FREE SHIPPING', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step 2: Customer Information', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step 3: Payment Methods', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Free', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Off', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Bundle', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Recommended Deal', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Amount', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Shipping', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Grand Total', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Option', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Most Popular', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Only', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), '100 Day Money Back Guarantee', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Order Now', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'OFF', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Your 50% Discount Has Been Applied', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Your Order Qualifies For FREE SHIPPING When Ordered TODAY', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step #1: Select Quantity', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step 1', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Select Pre-Order Quantity - Guaranteed Delivery - Factory Direct', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Step 1: Select Package', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Save', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Before', 'Multi Woo Checkout');
                pll_register_string('mwc_'.time(), 'Now', 'Multi Woo Checkout');
               
            endif;

        }
    }

endif;
