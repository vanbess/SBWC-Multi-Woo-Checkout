<?php

/**
 * @package mwc
 * 
 * Plugin Name: Multi Woo Checkout [Van's Fixes] w. ALG pricing
 * Plugin URI:
 * Description: Multi Woo Checkout
 * Author: Webmaster0313 w/ lots of bugfixes and tweaks by WC Bessinger
 * Version: 2.4.7
 * Author URI:
 * Text Domain: mwc
 * Domain Path: /languages
 */

define('MWCVersion', '2.4.7');
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
// add_action('wp_footer', function () {

//     echo 'thou art here';

//     // debug entire wc session
//     echo '<pre>';
//     print_r(WC()->session);
//     echo '</pre>';


//     // get cart
//     $cart = WC()->cart->get_cart();


//     echo '<pre>';
//     print_r($cart);
//     echo '</pre>';
// });

// add cart fee
add_action('woocommerce_cart_calculate_fees', function () {

    if (!WC()->session->get('mwc_style_type')) :
        return;
    endif;

    // get cart totals from session
    $cart_totals = WC()->session->get('cart_totals');

    // get sub total
    $sub_total = $cart_totals['subtotal'];

    // get cart discount text from session
    $discount_text = WC()->session->get('mwc_discount_data')['discount_text'];

    // get discount percentage from session
    $discount_perc = WC()->session->get('mwc_discount_data')['discount_perc'];

    // calculate discount amount
    $discount_amount = $sub_total * ($discount_perc / 100);

    // add discount fee
    WC()->cart->add_fee($discount_text, -$discount_amount, false);
});
