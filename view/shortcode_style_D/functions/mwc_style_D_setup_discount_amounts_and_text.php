<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Generate discount text + calc discount % / amounts 
 *
 * @param array $package_product_ids
 * @param int/string $default_package_id
 * @param string $currency
 * @return void
 */
function mwc_style_D_setup_discount_amounts_and_text($package_product_ids, $default_package_id, $currency)
{
    foreach ($package_product_ids as $opt_i => $bundle) :

        //get product id
        if ($bundle['type'] == 'free') :
            $p_id = $bundle['id'];
        elseif ($bundle['type'] == 'off') :
            $p_id = $bundle['id'];
        else :
            $p_id = $bundle['prod'][0]['id'];
        endif;

        // debug currency
        // echo 'currency: '. $currency . '<br>';
        // echo 'alg current currency: '. alg_get_current_currency_code() . '<br>';

        // debug product id
        // echo 'product id: '. $p_id . '<br>';

        //get product info
        $product               = wc_get_product($p_id);
        $option_title          = $bundle['title_package'] ?: $product->get_title();

        // debug product
        // echo '<pre>';
        // print_r($product);
        // echo '</pre>';

        // =====
        // free
        // =====
        if ($bundle['type'] == 'free') :

            // debug
            // echo '<pre>';
            // print_r($bundle);
            // echo '</pre>';

            $bundle_title              = sprintf(__('Buy %s + Get %d FREE', 'woocommerce'), $bundle['qty'], $bundle['qty_free']);
            $total_prod_qty            = $bundle['qty'] + $bundle['qty_free'];
            $discount_perc             = number_format($bundle['qty_free'] / $total_prod_qty * 100, 0);
            $bundle_price_pre_discount = number_format((mwc_style_D_generate_total_normal_price($product, $total_prod_qty)), 2, '.', '');
            $bundle_price_total        = number_format($bundle_price_pre_discount - ($bundle_price_pre_discount * ($discount_perc / 100)), 2, '.', '');
            $product_bundle_price      = $bundle_price_total / $total_prod_qty;

        // debug
        // echo 'bundle_price_pre_discount: ' . $bundle_price_pre_discount . '<br>';
        // echo 'bundle_price_total: ' . $bundle_price_total . '<br>';
        // echo 'product_bundle_price: ' . $product_bundle_price . '<br>';

        // =====
        // off
        // =====
        elseif ($bundle['type'] == 'off') :

            // echo '<pre>';
            // print_r($bundle);
            // echo '</pre>';

            $bundle_title              = sprintf(__('Buy %s + Get %d&#37;', 'woocommerce'), $bundle['qty'], $bundle['coupon']);
            $total_prod_qty            = $bundle['qty'];
            $bundle_price_pre_discount = mwc_style_D_generate_total_normal_price($product, $total_prod_qty);
            $discount_perc             = $bundle['coupon'];
            $bundle_price_total        = number_format($bundle_price_pre_discount - ($bundle_price_pre_discount * ($discount_perc / 100)), 2, '.', '');
            $product_bundle_price      = $bundle_price_total / $total_prod_qty;

        // debug all
        // echo 'bundle_price_pre_discount: ' . $bundle_price_pre_discount . '<br>';
        // echo 'bundle_price_total: ' . $bundle_price_total . '<br>';
        // echo 'product_bundle_price: ' . $product_bundle_price . '<br>';
        // echo 'bundle_title: ' . $bundle_title . '<br>';
        // echo 'discount_perc: ' . $discount_perc . '<br>';


        // =======
        // bundle
        // =======
        else :

            // debug bundle
            // echo '<pre>';
            // print_r($bundle);
            // echo '</pre>';
            // return;

            // get bundle id
            $bundle_id = $bundle['bun_id'];

            // get bundle discount meta
            $discount_meta = get_post_meta($bundle_id, 'product_discount', true);

            // get defined discount percentage
            $discount_perc = $discount_meta['discount_percentage'];

            // get pricing
            $pricing = $discount_meta['selValue_bun']['price_currency'];

            // debug
            // echo '<pre>';
            // print_r($pricing);
            // echo '</pre>';

            // filter empty values
            $pricing = array_filter($pricing);

            // if pricing array isn't empty
            if (!empty($pricing)) :

                // if current currency in $pricing array, return value if set, else return default currency value
                if (array_key_exists(alg_get_current_currency_code(), $pricing) && $pricing[alg_get_current_currency_code()] != '') :
                    $bundle_price_total = number_format($pricing[alg_get_current_currency_code()], 2, '.', '');
                else :
                    $bundle_price_total = $pricing['USD'];

                    // convert to current currency
                    $bundle_price_total = number_format(alg_convert_price(['price' => $bundle_price_total, 'currency' => alg_get_current_currency_code(), 'format_price' => 'false']), 2, '.', '');

                endif;

                // calc bundle price normal
                $bundle_price_pre_discount = 0;

                // total product count
                $total_prod_count = 0;

                foreach ($bundle['prod'] as $i => $i_prod) :
                    $product = wc_get_product($i_prod['id']);
                    $bundle_price_pre_discount += mwc_style_D_generate_total_normal_price($product, $i_prod['qty']);
                    $total_prod_count += $i_prod['qty'];
                endforeach;

                // calc per product price
                $product_bundle_price = $bundle_price_total / $total_prod_count;

                // calc discount percentage
                $discount_perc = number_format(100 - (($bundle_price_total / $bundle_price_pre_discount) * 100), 0);

            // else if discount percentage is set, calc discount
            elseif ($discount_perc != '') :

                // calc bundle price total
                $bundle_price_pre_discount = 0;

                // total product count
                $total_prod_count = 0;

                foreach ($bundle['prod'] as $i => $i_prod) :
                    $product = wc_get_product($i_prod['id']);
                    $bundle_price_pre_discount += mwc_style_D_generate_total_normal_price($product, $i_prod['qty']);
                    $total_prod_count += $i_prod['qty'];
                endforeach;

                // calc bundle price total
                $bundle_price_total = number_format($bundle_price_pre_discount - ($bundle_price_pre_discount * ($discount_perc / 100)), 2, '.', '');

                // calc per product price
                $product_bundle_price = $bundle_price_total / $total_prod_count;

            else :
            endif;


            // setup bundle title
            $bundle_title = strip_tags($option_title);

        // debug all
        // echo 'bundle_price_pre_discount: ' . $bundle_price_pre_discount . '<br>';
        // echo 'bundle_price_total: ' . $bundle_price_total . '<br>';
        // echo 'product_bundle_price: ' . $product_bundle_price . '<br>';
        // echo 'bundle_title: ' . $bundle_title . '<br>';
        // echo 'discount_perc: ' . $discount_perc . '<br>';

        endif;

?>
        <!-- load option item package -->
        <div class="productRadioListItem <?= $default_package_id == $bundle['bun_id'] ? 'prod_popular mwc_active_product' : '' ?>" data-bundle_id="<?= $bundle['bun_id'] ?>">
            <label class="label_selection" for="product_<?= $opt_i ?>"></label>

            <?php if ($default_package_id == $bundle['bun_id']) : ?>
                <img class="label_popular" src="<?= MWC_PLUGIN_URL . 'images/style_D/icon_popular.png' ?>">
            <?php endif; ?>

            <!-- radio -->
            <div class="radio_select_cont">
                <input type="radio" class="radio_select" id="product_<?php echo $opt_i ?>" name="product" value="<?php echo $opt_i ?>">
            </div>

            <!-- bundle product summary -->
            <div class="product_name">

                <!-- default bundle -->
                <?php if ($default_package_id == $bundle['bun_id']) : ?>
                    <span class="opt_popular text_red"><?= __('MOST POPULAR', 'woocommerce') ?>!</span>
                <?php endif; ?>

                <!-- bundled products -->
                <?php if ($bundle['type'] == 'bun') : ?>
                    <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($product_bundle_price)) . __('/ea', 'woocommerce') . ") - " . $discount_perc . '% ') ?><span style="text-transform: uppercase;"><?php _e('OFF', 'woocommerce'); ?></span></div>

                    <!-- everything else -->
                <?php else : ?>
                    <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($product_bundle_price)) . __('/ea', 'woocommerce') . ") - " . $discount_perc . '% ') ?><span style="text-transform: uppercase;"><?= $bundle['type'] ?></span></div>
                <?php endif; ?>
            </div>

            <div class="product_price">
                <span id="price_old_<?= $bundle['bun_id'] ?>" class="price_old"><del><?php echo $currency . $bundle_price_pre_discount ?></del></span><br>
                <span id="price_new_<?= $bundle['bun_id'] ?>" class="price_new"><?php echo $currency . $bundle_price_total ?></span>
            </div>

            <!-- input statistic title, price... form -->
            <input type="hidden" class="opc_title" value="<?= $bundle_title ?>">
            <input type="hidden" class="opc_total_discount" value="<?= $currency . ($bundle_price_pre_discount - $bundle_price_total) ?>">
            <input type="hidden" class="opc_total_price" value="<?= $currency . $bundle_price_total ?>">
            <input type="hidden" class="opc_discounted_perc" value="<?= $discount_perc ?>">

        </div>
        <!-- end option item package -->

<?php
    endforeach;
}

/**
 * Get and return regular total product price
 *
 * @param object $product - product object
 * @param int $qty - product quantity
 * @return string $alg_price - converted price
 */
function mwc_style_D_generate_total_normal_price($product, $qty = 1)
{

    // variable product
    if ($product->is_type('variable')) :
        $reg_price = $product->get_variation_regular_price('min') * $qty;
        return $reg_price;
    endif;

    // simple product
    if ($product->is_type('simple')) :
        $reg_price = $product->get_regular_price() * $qty;
        return $reg_price;
    endif;

    // product type not supported
    return 'product type not supported.';
}


?>