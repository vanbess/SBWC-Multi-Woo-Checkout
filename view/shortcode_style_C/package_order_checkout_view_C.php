<?php

global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;

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

    <div class="col" id="package_order_c">
        <div class="row">

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
                    $bundle_title = sprintf(__('Buy %s + Get %d FREE', 'woocommerce'), $prod['qty'], $prod['qty_free']);

                    $total_prod_qty= $prod['qty'] + $prod['qty_free'];
                    $bundle_price = ($prod_price * $prod['qty']) / $total_prod_qty;
                    $bundle_price_total = $bundle_price * $total_prod_qty;
                    $bundle_coupon= ((($prod_price * $total_prod_qty) - $bundle_price_total) / $bundle_price_total) * 100;
                    $discount = ($total_prod_qty* $prod_price) - $bundle_price_total;
                } else if ($prod['type'] == 'off') {
                    $bundle_title = sprintf(__('Buy %s + Get %d&#37;', 'woocommerce'), $prod['qty'], $prod['coupon']);

                    $total_prod_qty= $prod['qty'];
                    $i_total = $prod_price * $prod['qty'];
                    $bundle_coupon= $prod['coupon'];
                    $bundle_price = ($i_total - ($i_total * $bundle_coupon/ 100)) / $prod['qty'];
                    $bundle_price_total = $bundle_price * $prod['qty'];
                    $discount = $i_total - $bundle_price_total;
                } else {
                    $bundle_title = __('Bundle option', 'woocommerce');

                    $bundle_coupon= $prod['coupon'];
                    $bundle_price = $prod['price'];
                    $bundle_price_total = $prod['price'];
                    $discount = 0;
                }
            ?>

                <div class="col large-4 col_package_item <?php echo (($opt_i == 1) ? 'most_popular' : '') ?>" data-bundle_id="<?php echo ($prod['bun_id']) ?>">

                    <?php if ($opt_i == 1) { ?>
                        <div class="corner-ribbon top-right sticky">
                            <p><?php pll_e('Most Popular', 'woocommerce') ?></p>
                        </div>
                    <?php } ?>

                    <div class="col-inner">
                        <div class="w_wrapper">
                            <div class="text_col not_mb">
                                <p><strong><?php echo ($opt_i + 1) ?></strong> - <?php pll_e('Option', 'woocommerce') ?></p>
                            </div>

                            <div class="w_content">
                                <div class="w_radio" hidden>
                                    <input type="checkbox" id="product_<?php echo ($opt_i) ?>" name="product" value="<?php echo ($opt_i) ?>">
                                    <span class="checkmark"></span>
                                </div>
                                <div class="w_content_image">
                                    <?php
                                    if (wp_is_mobile() && $prod['image_package_mobile']) {
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
                                <div class="w_text">
                                    <div class="w_content_title">
                                        <p><strong><span style="font-size: 25px;"><?php echo ($bundle_title) ?></span></strong></p>
                                    </div>
                                    <div class="w_content_price">
                                        <span><?php pll_e('Only', 'woocommerce') ?>: </span> <span><?php echo ($currency . round($bundle_price_total, 2)) ?></span>
                                    </div>
                                    <div class="w_content_desc">
                                        <p><?php pll_e('100 Day Money Back Guarantee', 'woocommerce') ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="w_btn_center not_mb">
                                <button class="btn_submit"><?php pll_e('Order Now', 'woocommerce') ?></button>
                            </div>
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
    <div data-r="" id="clone_statistic_option_form" class="wysiwyg-content statistical" hidden>
        <table>
            <thead>
                <tr>
                    <th><?php pll_e('Item', 'woocommerce') ?></th>
                    <th><?php pll_e('Amount', 'woocommerce') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="td-name"><span></span></td>
                    <td class="td-price"><span></span></td>
                </tr>
                <tr>
                    <td class="td-shipping-text"><?php pll_e('Shipping', 'woocommerce') ?>:</td>
                    <td class="td-shipping"></td>
                </tr>
            </tbody>
            <tbody>
                <tr height="20px"></tr>
            </tbody>
        </table>

        </p>
        <table style="border: 1px dashed #EA0013;">
            <tfoot>
                <tr style="margin-top: 9px">
                    <td style="padding-bottom: 20px;"><img class="no-lazy" src="<?php echo (MWC_PLUGIN_URL . 'images/today-you-saved.png') ?>" width="200px">
                    </td>
                    <td style="padding-bottom: 20px;">
                        <p style="color: red; font-size: 18px; line-height: 22px; margin-right: 10px"><?php pll_e('Discount', 'woocommerce') ?>:
                            <span class="discount-total"></span>
                        <p style="font-size: 18px; line-height: 22px; margin-right: 10px"><?php pll_e('Grand Total', 'woocommerce') ?>:
                            <span class="grand-total"></span>
                        </p>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>


    <!-- get checkout form -->
    <div class="checkout_form_woo op_custom_checkout_form" hidden>
        <?php
        // Get checkout object for WC 2.0+
        $checkout = WC()->checkout();
        wc_get_template('checkout/form-checkout.php', array('checkout' => $checkout));
        ?>
    </div>

<?php
}
