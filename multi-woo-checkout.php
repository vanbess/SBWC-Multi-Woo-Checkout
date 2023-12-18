<?php

/**
 * @package mwc
 * 
 * Plugin Name: Multi Woo Checkout [Van's Fixes] w. ALG pricing - Added Style D Support
 * Plugin URI:
 * Description: Multi Woo Checkout
 * Author: Webmaster0313 w/ lots of bugfixes and tweaks by WC Bessinger
 * Version: 2.4.8
 * Author URI:
 * Text Domain: mwc
 * Domain Path: /languages
 */

use Elementor\Core\Logger\Items\PHP;

define('MWCVersion', '2.4.8');
define('MWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MWC_PLUGIN_DIR', plugin_dir_path(__FILE__));

try {
    // if WooCommerce is active, require the main class
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        require_once(MWC_PLUGIN_DIR . 'lib/front/class.mwc.php');
    }
} catch (\Throwable $th) {
    error_log('Error loading MWC plugin: ' . $th->getMessage() . $th->getTraceAsString());
}

/**
 * Create admin menu dashboard
 *
 * @return void
 */
add_action('admin_menu', function () {
    add_menu_page(
        __('Multi woo checkout', 'woocommerce'),
        __('Multi woo checkout', 'woocommerce'),
        'read',
        'mwc',
        null,
        MWC_PLUGIN_URL . 'images/mwc_logo.png',
        '55'
    );
});


// Require template and template functions
require_once(MWC_PLUGIN_DIR . 'lib/front/class-add-template.php');
require_once(MWC_PLUGIN_DIR . 'functions.php');

// add_action('init', array('MWC', 'init'));

// Load correct classes based on location
if (is_admin()) {
    require_once(MWC_PLUGIN_DIR . 'lib/admin/bundle-selection-admin.php');
} else {
    require_once(MWC_PLUGIN_DIR . 'lib/front/class-add-shortcode.php');
}

// Require addon product class
// require_once(MWC_PLUGIN_DIR . 'lib/front/class-addon-product.php');

// Define the locale for this plugin for internationalization
add_action('plugins_loaded', function () {
    $plugin_rel_path = basename(dirname(__FILE__)) . '/languages';
    load_plugin_textdomain('mwc', false, $plugin_rel_path);
});

// *********
// TRACKING
// *********

// add Polylang support out of the box
require_once MWC_PLUGIN_DIR . 'tracking/add-pll-support.php';

// update clicks
require_once MWC_PLUGIN_DIR . 'tracking/update-clicks.php';

// cron to update impressions every 5 minutes
require_once MWC_PLUGIN_DIR . 'tracking/caching/update-impressions-chron.php';

// update conversions via thank you page hook
require_once MWC_PLUGIN_DIR . 'tracking/thank-you-page.php';

// reset tracking data for addons and bundles
require_once MWC_PLUGIN_DIR . 'tracking/reset-tracking.php';

// debug cart
add_action('wp_footer', function () {

    // echo 'thou art here';

    // destroy session
    // WC()->session->destroy_session();

    // debug entire wc session
    // echo '<pre>';
    // print_r(WC()->session);
    // echo '</pre>';

    // get cart
    // $cart = WC()->cart->get_cart();

    // echo '<pre>';
    // print_r($cart);
    // echo '</pre>';
});

/**
 * Add discount fee to cart
 * IMPORTANT NOTE: Because ALG assumes and pricing in WC() session is USD and converts it again to current currency, we need to revert this conversion before adding it to the cart, else the
 * discount fee will be converted twice and the discount will be wrong, i.e. waaaaay too high.      
 */
add_action('woocommerce_cart_calculate_fees', function () {

    if (!WC()->session->get('mwc_style_type')) :
        return;
    endif;

    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // don't run this more than once
    if (did_action('woocommerce_cart_calculate_fees') >= 2) {
        return;
    }

    // get current currency
    $currency = alg_get_current_currency_code() ? alg_get_current_currency_code() : get_woocommerce_currency();

    // setup exchange rate
    $exchange_rate = alg_wc_cs_get_exchange_rate($currency, 'USD');

    // getting product type
    $product_type = WC()->session->get('mwc_prod_type');

    // if is type product bundle, use discount amount instead of discount percentage
    if ($product_type == 'mwc_bun_discount') :

        // get discount fee
        $discount_fee = WC()->session->get('mwc_discount_fee');

    else :
        // get discount percentage
        $discount_perc = WC()->session->get('mwc_discount_perc');

        // calculate discount fee
        $discount_fee = (WC()->cart->subtotal * $discount_perc) / 100;
    endif;

    // get discount text
    $discount_text = WC()->session->get('mwc_discount_text');

    // add discount fee
    WC()->cart->add_fee($discount_text, -$discount_fee, false);
}, PHP_INT_MAX);
