<?php

global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;
$addon_product_ids     = self::$addon_product_ids;

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

?>

    <div class="col" id="package_order_f">

        <!-- custom title form checkout -->
        <input type="hidden" id="step_2_of_3" value="<?php pll_e('Step 2: Customer Information', 'woocommerce') ?>">
        <input type="hidden" id="step_3_of_3" value="<?php pll_e('Step 3: Payment Methods', 'woocommerce') ?>">

        <div class="row row-collapse">
            <div class="col">
                <h3 class="mwc_title_package"><?= __('Step 1: Select Package', 'woocommerce') ?></h3>
            </div>
        </div>

        <div class="row slider" data-flickity='{"freeScroll": false, "contain": true, "prevNextButtons": true, "pageDots": false, "groupCells": "2" }'>

            <?php
            foreach ($package_product_ids as $opt_i => $prod) {
                //get product id
                if ($prod['type'] == 'free') {
                    $p_id = $prod['id'];
                } else if ($prod['type'] == 'off') {
                    $p_id = $prod['id'];
                } else {
                    $p_id = $prod['prod'][0]['id'];
                }

                //get product info
                $product = wc_get_product($p_id);
                $product_title = $prod['title_package'] ?: $product->get_title();
                $product_price_html = $product->get_price_html();
                $prod_price = $product->get_price();
                $product_regular_price = $product->get_regular_price();
                $product_sale_price = $product->get_sale_price();

                if ($prod['type'] == 'free') {
                    if ($prod['qty_free'] == 0) {
                        $bundle_title = sprintf(__('Buy %s', 'woocommerce'), $prod['qty']);
                    } else {
                        $bundle_title = sprintf(__('Buy %s + Get %d FREE', 'woocommerce'), $prod['qty'], $prod['qty_free']);
                    }

                    // get regular price
                    if ($product->is_type('variable')) {
                        $product_regular_price = $product->get_variation_regular_price('min');
                        $product_sale_price = $product->get_variation_sale_price('min');
                    } else {
                        $product_regular_price = $product->get_regular_price();
                        $product_sale_price = $product->get_sale_price();
                    }

                    // get now price
                    $now_price = ($prod_price * $prod['qty']) / ($prod['qty'] + $prod['qty_free']);

                    $total_prod_qty= $prod['qty'] + $prod['qty_free'];
                    $bundle_price = ($prod_price * $prod['qty']) / $total_prod_qty;
                    $bundle_price_total = $bundle_price * $total_prod_qty;
                    $bundle_coupon= ((($prod_price * $total_prod_qty) - $bundle_price_total) / $bundle_price_total) * 100;
                    $discount = ($total_prod_qty* $prod_price) - $bundle_price_total;
                } else if ($prod['type'] == 'off') {
                    $bundle_title = sprintf(__('Buy %s + Get %d&#37;', 'woocommerce'), $prod['qty'], $prod['coupon']);

                    // get regular price
                    if ($product->is_type('variable')) {
                        $product_regular_price = $product->get_variation_regular_price('min');
                        $product_sale_price = $product->get_variation_sale_price('min');
                    } else {
                        $product_regular_price = $product->get_regular_price();
                        $product_sale_price = $product->get_sale_price();
                    }

                    $total_prod_qty= $prod['qty'];
                    $i_total = $prod_price * $prod['qty'];
                    $bundle_coupon= $prod['coupon'];
                    // get now price
                    $now_price = ($i_total - ($i_total * $bundle_coupon/ 100)) / $prod['qty'];
                    $bundle_price_total = $now_price * $prod['qty'];
                    $discount = $i_total - $bundle_price_total;
                } else {
                    $bundle_title = $prod['title_header'] ?: __('Bundle option', 'woocommerce');

                    // get regular price bun
                    $product_regular_price = 0;
                    // get now price
                    $now_price = 0;

                    $sum_price_regular = 0;
                    // $total_price_bun = 0;
                    foreach ($prod['prod'] as $i => $i_prod) {
                        $p_bun = wc_get_product($i_prod['id']);
                        if ($p_bun->is_type('variable')) {
                            $sum_price_regular += $p_bun->get_variation_regular_price('min');
                            // $product_regular_price += $p_bun->get_variation_regular_price('min');
                        } else {
                            $sum_price_regular += $p_bun->get_regular_price();
                            // $product_regular_price += $p_bun->get_regular_price();
                        }
                    }

                    $bundle_coupon= $prod['coupon'];
                    $bundle_price = $prod['price'];
                    $bundle_price_total = $prod['price'];

                    $price_discount = $sum_price_regular - $bundle_price_total;
                    $bundle_coupon= ($price_discount * 100) / $sum_price_regular;

                    $discount = 0;
                }
            ?>

                <div class="col large-4 medium-6 col_package_item mwc_item_div" data-bundle_id="<?php echo ($prod['bun_id']) ?>">
                    <div class="col-inner">
                        <div class="w_wrapper">
                            <div class="label_save">
                                <p> <?= __('Save', 'woocommerce') ?> <?= round($i_coupon, 0) ?: 0 ?>%</p>
                            </div>

                            <div class="w_content">
                                <div class="w_radio" hidden>
                                    <input type="checkbox" id="product_<?php echo ($opt_i) ?>" name="product" value="<?php echo ($opt_i) ?>">
                                    <span class="checkmark"></span>
                                </div>
                                <div class="mwc_package_title">
                                    <p><?= $bundle_title ?></p>
                                </div>
                                <div class="w_content_image_top">
                                    <?php
                                    if ($prod['image_package_mobile']) {
                                    ?>
                                        <img src="<?php echo ($prod['image_package_mobile']) ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
                                    <?php
                                    } elseif ($prod['image_package_desktop']) {
                                    ?>
                                        <img src="<?php echo ($prod['image_package_desktop']) ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
                                    <?php
                                    } else {
                                        echo ($product->get_image("woocommerce_thumbnail"));
                                    }
                                    ?>
                                </div>
                                <div class="mwc_image_main_wrap">
                                    <div class="content_img">
                                        <div class="op_c_package_image">
                                            <?php
                                            if ($prod['image_package_desktop']) {
                                            ?>
                                                <img src="<?php echo ($prod['image_package_desktop']) ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
                                            <?php
                                            } else {
                                                echo ($product->get_image("woocommerce_thumbnail"));
                                            }
                                            ?>
                                            <div class="label_bundle_type">
                                                <div class="border_inside"><?= $prod['type'] ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w_wrap_price">
                                    <?php if ($product_regular_price > 0) { ?>
                                        <div class="w_content_price_before">
                                            <span><?php pll_e('Before', 'woocommerce') ?>: </span> <span class="price"><?= wc_price($product_regular_price) ?>/<?= __('each', 'woocommerce') ?></span>
                                        </div>
                                    <?php } ?>
                                    <?php if ($now_price > 0) { ?>
                                        <div class="w_content_price_now">
                                            <span><?php pll_e('Now', 'woocommerce') ?>: </span> <span class="price"><?= wc_price($now_price) ?>/<?= __('each', 'woocommerce') ?></span>
                                        </div>
                                    <?php } ?>
                                    <div class="w_content_price_total">
                                        <span><?php pll_e('Total', 'woocommerce') ?>: </span> <span class="price"><?= wc_price($bundle_price_total) ?></span>
                                    </div>
                                </div>
                            </div>

                            <button class="w_btn_select">
                                <span class="text_select"><?= __('select', 'woocommerce') ?></span>
                                <span class="text_selected"><?= __('selected', 'woocommerce') ?></span>
                            </button>
                        </div>
                    </div>


                    <!-- info products add to cart ajax -->
                    <div class="info_products_checkout" hidden>
                        <?php
                        //package selection free and off
                        if ($prod['type'] == 'free' || $prod['type'] == 'off') {
                            for ($i = 0; $i < $total_prod_qty; $i++) {
                        ?>
                                <div class="c_prod_item" data-id="<?php echo ($p_id) ?>">
                                    <?php
                                    if ($product->is_type('variable')) {
                                        $prod_variations = $product->get_variation_attributes();
                                        foreach ($prod_variations as $attribute_name => $options) {
                                            // $default_opt = $product->get_variation_default_attribute($attribute_name);
                                            try {
                                                $default_opt =  $product->default_attributes[$attribute_name];
                                            } catch (\Throwable $th) {
                                                $default_opt = '';
                                            }
                                    ?>
                                            <select class="checkout_prod_attr" data-attribute_name="attribute_<?php echo ($attribute_name) ?>">
                                                <?php
                                                foreach ($options as $key => $option) {
                                                ?>
                                                    <option value="<?php echo ($option) ?>" <?php echo (($default_opt == $option) ? 'selected' : '') ?>><?php echo ($option) ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                    <?php
                                        }
                                    }
                                    ?>
                                </div>

                                <!-- get prices bundle -->
                                <input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $bundle_title ?>" value="<?= $bundle_price_total ?>">
                                <input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'woocommerce') ?>" value="<?= $product_regular_price ?>">

                            <?php
                            }
                        } else { //package selection bundle
                            foreach ($prod['prod'] as $i => $i_prod) {
                                $p_id = $i_prod['id'];
                                $b_product = wc_get_product($p_id);

                                // add price to discount of each product
                                $discount += $b_product->get_price();
                            ?>
                                <div class="c_prod_item" data-id="<?php echo ($p_id) ?>">
                                    <?php
                                    if ($b_product->is_type('variable')) {
                                        $prod_variations = $b_product->get_variation_attributes();
                                        foreach ($prod_variations as $attribute_name => $options) {
                                            // $default_opt = $b_product->get_variation_default_attribute($attribute_name);
                                            try {
                                                $default_opt =  $b_product->default_attributes[$attribute_name];
                                            } catch (\Throwable $th) {
                                                $default_opt = '';
                                            }
                                    ?>
                                            <select class="checkout_prod_attr" data-attribute_name="attribute_<?php echo ($attribute_name) ?>">
                                                <?php
                                                foreach ($options as $key => $option) {
                                                ?>
                                                    <option value="<?php echo ($option) ?>" <?php echo (($default_opt == $option) ? 'selected' : '') ?>><?php echo ($option) ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                    <?php
                                        }
                                    }
                                    ?>
                                </div>

                                <!-- get prices bundle -->
                                <input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $bundle_title ?>" value="<?= $bundle_price_total ?>">
                                <input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'woocommerce') ?>" value="<?= $product_regular_price ?>">
                        <?php
                            }

                            // get discount bundle selection
                            $discount = $discount - $bundle_price_total;
                        }
                        ?>

                    </div>
                    <!-- end products info to checkout -->


                    <!-- input statistic title, price... form -->
                    <input type="hidden" class="opc_title" value="<?php echo ($bundle_title) ?>">
                    <input type="hidden" class="opc_total_price" value="<?php echo ($currency . round($bundle_price_total, 2)) ?>">
                    <input type="hidden" class="opc_discount" value="<?php echo ($currency . round($discount, 2)) ?>">


                </div> <!-- /col_package_item -->

            <?php
            }
            ?>

        </div>
    </div>


    <!-- form statistical order one checkout -->
    <!-- <div data-r="" id="clone_statistic_option_form" class="wysiwyg-content statistical" hidden>
        <h3 class="checkout_title"><?= __('Order Summary', 'woocommerce') ?></h3>
        <table>
            <tbody>
                <tr>
                    <td class="td-name"><span></span></td>
                    <td class="td-price">
                        <p class="price_before"></p>
                        <p class="price_now"></p>
                    </td>
                </tr>
                <tr>
                    <td class="td_total"><?php pll_e('Total', 'woocommerce') ?></td>
                    <td class="td_total_price"></td>
                </tr>
            </tbody>
        </table>
    </div> -->


    <!-- get checkout form -->
    <div class="row">
        <div class="col large-7 mwc_items_div">
            <div class="row">
                <?php
                // add shortcode silver back wc upsell addon
                if (!empty($addon_product_ids)) {
                ?>
                    <div class="col">
                        <?php
                        viewAddonProduct::load_view($addon_product_ids);
                        ?>
                    </div>
                <?php
                }
                ?>
            </div>

            <div class="row" id="order_summary" style="display: none;">
                <div class="col large-6 small-12" id="s_image" style="padding-left: 30px; text-align: center;">
                    <img width="247" height="296" src="" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
                </div>

                <div class="col large-6 small-12">
                    <div class="col-inner box-shadow-2 box-shadow-3-hover box-item">
                        <div class="op_c_summary mwc_collapser_inner i_row i_clearfix">
                            <div class="op_c_package_header">
                                <div class="op_c_package_title" style="width: 100%;text-align:center">
                                    <p><?php pll_e('Order Summary', 'woocommerce') ?></p>
                                </div>
                            </div>

                            <div class="op_c_package_content" style="display: block; text-align: left">
                                <div style="width: 100%;border-bottom: solid 1px #E9E9E9;">
                                    <strong><?php pll_e("Item", 'woocommerce') ?></strong>
                                    <strong style="float: right;"><?php pll_e('Price', 'woocommerce') ?></strong>
                                </div>

                                <div class="mwc_summary_table"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end col order summary -->

        <!-- checkout form woo -->
        <div class="row row-collapse col large-5 op_c_checkout_form" style="display: none;">
            <?php
            // Get checkout object for WC 2.0+
            $checkout = WC()->checkout();
            wc_get_template('checkout/form-checkout.php', array('checkout' => $checkout));
            ?>
        </div>

    </div>

<?php
}
