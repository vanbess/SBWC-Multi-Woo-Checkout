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
    foreach ($package_product_ids as $opt_i => $prod) :

        //get product id
        if ($prod['type'] == 'free') :
            $p_id = $prod['id'];
        elseif ($prod['type'] == 'off') :
            $p_id = $prod['id'];
        else :
            $p_id = $prod['prod'][0]['id'];
        endif;

        // debug currency
        // echo 'currency: '. $currency . '<br>';
        // echo 'alg current currency: '. alg_get_current_currency_code() . '<br>';

        // debug product id
        // echo 'product id: '. $p_id . '<br>';

        //get product info
        $product               = wc_get_product($p_id);
        $option_title          = $prod['title_package'] ?: $product->get_title();
        $prod_price            = number_format($product->is_type('variable') ? $product->get_variation_regular_price('min') : $product->get_regular_price(), 2, '.', '');

        // debug product price
        // echo 'product price: '. $prod_price . '<br>';
        // return;

        // debug product
        // echo '<pre>';
        // print_r($product);
        // echo '</pre>';

        // =====
        // free
        // =====
        if ($prod['type'] == 'free') :

            // debug
            // echo '<pre>';
            // print_r($prod);
            // echo '</pre>';

            $bundle_title              = sprintf(__('Buy %s + Get %d FREE', 'woocommerce'), $prod['qty'], $prod['qty_free']);
            $total_prod_qty            = $prod['qty'] + $prod['qty_free'];
            $discount_perc             = number_format($prod['qty_free'] / $total_prod_qty * 100, 0);
            $bundle_price_pre_discount = number_format(($total_prod_qty * $prod_price), 2, '.', '');
            $bundle_price_total        = number_format($bundle_price_pre_discount - ($bundle_price_pre_discount * ($discount_perc / 100)), 2, '.', '');
            $product_bundle_price      = $bundle_price_total / $total_prod_qty;

        // debug
        // echo 'bundle_price_pre_discount: ' . $bundle_price_pre_discount . '<br>';
        // echo 'bundle_price_total: ' . $bundle_price_total . '<br>';
        // echo 'product_bundle_price: ' . $product_bundle_price . '<br>';

        // =====
        // off
        // =====
        elseif ($prod['type'] == 'off') :

            echo '<pre>';
            print_r($prod);
            echo '</pre>';

            $bundle_title         = sprintf(__('Buy %s + Get %d&#37;', 'woocommerce'), $prod['qty'], $prod['coupon']);
            $total_prod_qty       = $prod['qty'];
            $i_total              = $prod_price * $prod['qty'];
            $discount_perc        = $prod['coupon'];
            $product_bundle_price = ($i_total - ($i_total * ($discount_perc / 100))) / $prod['qty'];
            $bundle_price_total   = number_format($product_bundle_price * $prod['qty'], 2, '.', '');
            $discount             = $i_total - $bundle_price_total;

        // =======
        // bundle (todo: rework to support custom price)
        // =======
        else :

            $prod['type']       = 'Bundle';
            $product_bundle_price       = $prod['price'];
            $bundle_price_total = $prod['price'];
            $sum_price_regular  = 0;

            // $total_price_bun = 0;
            foreach ($prod['prod'] as $i => $i_prod) :
                $p_bun = wc_get_product($i_prod['id']);
                if ($p_bun->is_type('variable')) :
                    $sum_price_regular += $p_bun->get_variation_regular_price('min');
                else :
                    $sum_price_regular += $p_bun->get_regular_price();
                endif;
            endforeach;

            $price_discount = $sum_price_regular - $bundle_price_total;
            $discount_perc  = ($price_discount * 100) / $sum_price_regular;
        endif;

?>
        <!-- load option item package -->
        <div class="productRadioListItem <?= $default_package_id == $prod['bun_id'] ? 'prod_popular mwc_active_product' : '' ?>" data-bundle_id="<?= $prod['bun_id'] ?>">
            <label class="label_selection" for="product_<?= $opt_i ?>"></label>

            <?php if ($default_package_id == $prod['bun_id']) : ?>
                <img class="label_popular" src="<?= MWC_PLUGIN_URL . 'images/style_D/icon_popular.png' ?>">
            <?php endif; ?>

            <!-- radio -->
            <div class="radio_select_cont">
                <input type="radio" class="radio_select" id="product_<?php echo $opt_i ?>" name="product" value="<?php echo $opt_i ?>">
            </div>

            <!-- bundle product summary -->
            <div class="product_name">

                <!-- default bundle -->
                <?php if ($default_package_id == $prod['bun_id']) : ?>
                    <span class="opt_popular text_red"><?= __('MOST POPULAR', 'woocommerce') ?>!</span>
                <?php endif; ?>

                <!-- bundled products -->
                <?php if ($prod['type'] == 'bun') : ?>
                    <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($bundle_price_total)) . ") - " . $discount_perc . '% ') ?><span style="text-transform: uppercase;"><?= $prod['type'] ?></span></div>

                    <!-- everything else -->
                <?php else : ?>
                    <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($product_bundle_price)) . "/" . __('ea', 'woocommerce') . ") - " . $discount_perc . '% ') ?><span style="text-transform: uppercase;"><?= $prod['type'] ?></span></div>
                <?php endif; ?>
            </div>

            <div class="product_price">
                <span id="price_old_<?= $prod['bun_id'] ?>" class="price_old"><del><?php echo $currency . $bundle_price_pre_discount ?></del></span><br>
                <span id="price_new_<?= $prod['bun_id'] ?>" class="price_new"><?php echo $currency . $bundle_price_total ?></span>
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


?>