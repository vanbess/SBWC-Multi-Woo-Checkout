<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

global $woocommerce, $mwc_style_D_package_product_ids;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;
$default_package_id    = self::$package_default_id;

$mwc_style_D_package_product_ids = $package_product_ids;

// debug
// echo '<pre>';
// print_r($package_product_ids);
// echo '</pre>';

// *******************
// REQUIRED FUNCTIONS
// *******************
require_once __DIR__ . '/functions/mwc_style_D_setup_products_list.php';
require_once __DIR__ . '/functions/mwc_style_D_add_to_cart_js.php';
require_once __DIR__ . '/functions/mwc_style_D_js.php';
require_once __DIR__ . '/functions/mwc_style_D_size_chart_js.php';

if (!empty($package_product_ids)) {

    // ********************************************
    // IMPRESSIONS TRACKING CACHE WP CACHE & REDIS
    // ********************************************

    // retrieve current impressions cache
    $curr_impressions = get_transient('mwco_bundle_impressions');

    // if impressions exist
    if ($curr_impressions) :

        // setup new impressions
        $new_impressions = [];

        // update impressions
        foreach ($curr_impressions as $uid => $views) :
            $new_impressions[$uid] = $views + 1;
        endforeach;

        set_transient('mwco_bundle_impressions', $new_impressions);

    // if impressions do not exist
    else :

        // setup initial impressions array
        $impressions = [];

        // push impressions
        foreach ($package_product_ids as $opt_i => $prod) :

            // retrieve correct product id
            if ($prod['type'] == 'free') :
                $p_id = $prod['id'];
            elseif ($prod['type'] == 'off') :
                $p_id = $prod['id'];
            else :
                $p_id = $prod['prod'][0]['id'];
            endif;

            $impressions[$p_id] = 1;
        endforeach;

        set_transient('mwco_bundle_impressions', $impressions);

    endif;

    // base js
    mwc_style_D_js();

    // size chart js
    mwc_style_D_size_chart_js();

?>

    <div id="opc_style_d_container">
        <div class="mwc_items_div">

            <?php

            // setup products list
            mwc_style_D_setup_products_list($package_product_ids, $currency, $default_package_id);

            ?>

            <!-- form checkout woo -->
            <div class="style_D_checkout_form">
                <?php
                // Get checkout object for WC 2.0+
                $checkout = WC()->checkout();
                wc_get_template('checkout/form-checkout.php', array('checkout' => $checkout));
                ?>
            </div>
        </div>


    </div>

    <!-- =========== -->
    <!-- ADD TO CART -->
    <!-- =========== -->
    <?php mwc_style_D_add_to_cart_js(); ?>

<?php
}
