<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

// ==================
// REQUIRED FUNCTIONS
// ==================
require_once __DIR__ . '/mwc_style_D_setup_discount_amounts_and_text.php';
require_once __DIR__ . '/mwc_style_D_render_product_select_dds.php';

/**
 * Setup products list
 *
 * @param array $package_product_ids
 * @param string $currency
 * @return void
 */
function mwc_style_D_setup_products_list($package_product_ids, $currency, $default_package_id)
{ ?>

    <div class="box_select">

        <div class="box_select_inner">

            <div class="banner_discount">

                <div class="label_discount_cont">
                    <div class="label_discount">
                        <div class="border_inside"></div>
                        <div class="label_text" data-translated="<?= __('{discount_perc}<br> OFF', 'woocommerce') ?>">
                        </div>
                    </div>
                </div>

                <div class="text_discount">
                    <span class="text_red label_secondary_text" data-translated="<?= __('Your {discount_perc} Discount Has Been Applied', 'woocommerce') ?>"></span>
                    <span><?= __('Order TODAY To Qualify For FREE SHIPPING', 'woocommerce') ?></span>
                </div>
            </div>

            <div class="title_bundle">
                <h3><?= __('Select your bundle below:', 'woocommerce') ?></h3>
            </div>

            <div class="products_list">

                <!-- title bar -->
                <div class="table_title">
                    <div class="text_left"><?= __('Item', 'woocommerce') ?></div>
                    <div class="text_right"><?= __('Price', 'woocommerce') ?></div>
                </div>

                <?php
                // calculate discount text + calc discount % / amounts
                mwc_style_D_setup_discount_amounts_and_text($package_product_ids, $default_package_id, $currency);


                // render product select dds
                mwc_style_D_render_product_select_dds($package_product_ids);
                ?>
            </div>
        </div>

        <!-- order summary -->
        <div class="order-summary-cont-outer">

            <div id="mwc_template_d_summary">

                <!-- summary image -->
                <div id="mwc_template_d_img">
                    <img class="no-lazy" src="<?php echo (MWC_PLUGIN_URL . 'images/today-you-saved.png') ?>" width="200px">
                </div>

                <!-- totals div -->
                <div id="mwc_template_d_totals">

                    <!-- discount -->
                    <div class="totals discount">
                        <span class="totals-title"><?php pll_e('Discount', 'woocommerce') ?>: </span>
                        <span class="totals-price discount-total"></span>
                    </div>

                    <!-- grand total -->
                    <div class="totals grand">
                        <span class="totals-title grand"><?php pll_e('Grand Total', 'woocommerce') ?>: </span>
                        <span class="totals-price grand-total"></span>
                    </div>

                </div>
            </div>

        </div>
    </div>
<?php }


?>