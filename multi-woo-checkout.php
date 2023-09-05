<?php

/**
 * @package mwc
 * 
 * Plugin Name: Multi Woo Checkout [Van's Fixes] w. ALG pricing
 * Plugin URI:
 * Description: Multi Woo Checkout
 * Author: Webmaster0313 w/ lots of bugfixes and tweaks by WC Bessinger
 * Version: 2.4.4
 * Author URI:
 * Text Domain: mwc
 * Domain Path: /languages
 */

define('MWCVersion', '2.4.4');
define('MWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MWC_PROTECTION_H', plugin_basename(__FILE__));
define('MWC_NAME', 'woocommerce');
define('MWC_PAGE_LINK', 'woocommerce');

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

// Require main class and init
require_once(MWC_PLUGIN_DIR . 'lib/front/class.mwc.php');
add_action('init', array('MWC', 'init'));

// Load correct classes based on location
if (is_admin()) {
    require_once(MWC_PLUGIN_DIR . 'lib/admin/bundle-selection-admin.php');
} else {
    require_once(MWC_PLUGIN_DIR . 'lib/front/class-add-shortcode.php');
}

// Require addon product class
require_once(MWC_PLUGIN_DIR . 'lib/front/class-addon-product.php');

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

add_action('wp_footer', function () {

    global $post;

    if (is_object($post) && has_shortcode($post->post_content, 'mwc_one_page_checkout')) : ?>
        <script>
            console.log('has mwc shortcode');

            $ = jQuery;

            let data = {
                'action': 'mwc_fetch_gateways',
                '_ajax_nonce': '<?php echo wp_create_nonce('mwc fetch payment gateways'); ?>',
            }

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {


    setTimeout(() => {
        $('#payment').prepend(response);
    }, 6000);

            });
        </script>
    <?php endif;

    

});

add_action('wp_ajax_nopriv_mwc_fetch_gateways', function () {

    check_ajax_referer('mwc fetch payment gateways');

    // start woocommerce session
    if (!session_id()) {
        session_start();
    }

    // debug
    // wp_send_json($_POST);

    // get available payment gateways
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

    // debug
    // wp_send_json($available_gateways);

    // html output start 
    ?>
    <ul class="wc_payment_methods payment_methods methods">

        <?php
        // available payment gateway html
        foreach ($available_gateways as $gateway) : ?>
            <li class="wc_payment_method payment_method_<?php echo esc_attr($gateway->id); ?>">
                <input id="payment_method_<?php echo esc_attr($gateway->id); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr($gateway->id); ?>" <?php checked($gateway->chosen, true); ?> data-order_button_text="<?php echo esc_attr($gateway->order_button_text); ?>" />

                <label for="payment_method_<?php echo esc_attr($gateway->id); ?>">
                    <?php echo $gateway->get_title(); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?> <?php echo $gateway->get_icon(); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
                </label>
                <?php if ($gateway->has_fields() || $gateway->get_description()) : ?>
                    <div class="payment_box payment_method_<?php echo esc_attr($gateway->id); ?>" <?php if (!$gateway->chosen) : /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>style="display:none;" <?php endif; /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>>
                        <?php $gateway->payment_fields(); ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach;

        ?>
    </ul>
<?php

    wp_die();
});
