<?php
global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;

// debug
// echo '<pre>';
// print_r($package_product_ids);
// echo '</pre>';

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


    <!-- debug js -->
    <script id="mwc-template-d-debug-js">
        $ = jQuery.noConflict();

        $(window).load(function() {

            // debug
            // console.log('mwc-template-d-debug-js loaded');

            // on load
            $('.productRadioListItem').each(function(index, element) {

                if ($(this).hasClass('mwc_active_product')) {

                    let discount_total = $('.discount-total').text();
                    let grand_total = $('.grand-total').text();

                    // regex replace everything in totals but numbers and dots
                    let regex = /[^0-9.]/g;

                    discount_total = discount_total.replace(regex, '');
                    grand_total = grand_total.replace(regex, '');

                    let discount_percentage = (discount_total / grand_total) * 100;

                    // debug
                    // console.log('discount_percentage', discount_percentage);

                    $('.label_text').text(parseFloat(discount_percentage).toFixed(0) + '% OFF');

                    $('.label_secondary_text').text('Your ' + parseFloat(discount_percentage).toFixed(0) + '% Discount Has Been Applied');
                }

            });

            // on click
            $('.productRadioListItem').click(function() {

                setTimeout(() => {
                    let discount_total = $('.discount-total').text();
                    let grand_total = $('.grand-total').text();

                    // regex replace everything in totals but numbers and dots
                    let regex = /[^0-9.]/g;

                    discount_total = discount_total.replace(regex, '');
                    grand_total = grand_total.replace(regex, '');

                    // let discount_percentage = (discount_total / grand_total) * 100;
                    let discount_percentage = parseFloat(discount_total) / (parseFloat(discount_total) + parseFloat(grand_total)) * 100;

                    // debug
                    // console.log(parseFloat(discount_total) + parseFloat(grand_total));
                    // console.log('discount_percentage', discount_percentage);

                    $('.label_text').text(parseFloat(discount_percentage).toFixed(0) + '% OFF');

                    $('.label_secondary_text').text('Your ' + parseFloat(discount_percentage).toFixed(0) + '% Discount Has Been Applied');

                }, 100);

            });

        });
    </script>

    <div class="opc_style_d_container">

        <input type="hidden" id="step_2_of_3" value="<?php pll_e("Step 2: Customer Information", "woocommerce") ?>">
        <input type="hidden" id="step_3_of_3" value="<?php pll_e("Step 3: Payment Methods", "woocommerce") ?>">

        <section class="section-1">
            <div class="container">
                <div class="row">
                    <div class="col large-7 mwc_items_div">
                        <div class="box_select">

                            <!-- discount banner box select -->
                            <div class="banner_discount">

                                <div class="label_discount_cont">
                                    <div class="label_discount">
                                        <div class="border_inside"></div>
                                        <div class="label_text" data-translated="<?= __('{discount_perc}<br> OFF', 'woocommerce') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="text_discount">
                                    <p class="text_red label_secondary_text" data-translated="<?= __('Your {discount_perc} Discount Has Been Applied', 'woocommerce') ?>"></p>
                                    <p><?= __('Order TODAY To Qualify For FREE SHIPPING', 'woocommerce') ?></p>
                                </div>
                            </div>

                            <div class="title_bundle">
                                <h3><?= __('Select your bundle below:', 'woocommerce') ?></h3>
                            </div>

                            <div class="products_list">
                                <table class="table_title">
                                    <tr>
                                        <th class="text_left"><?= __('Item', 'woocommerce') ?></th>
                                        <th class="text_right"><?= __('Price', 'woocommerce') ?></th>
                                    </tr>
                                </table>
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
                                    $product               = wc_get_product($p_id);
                                    $option_title          = $prod['title_package'] ?: $product->get_title();
                                    $product_price_html    = $product->get_price_html();
                                    $prod_price            = $product->get_price();
                                    $product_regular_price = $product->get_regular_price();
                                    $product_sale_price    = $product->get_sale_price();

                                    if ($prod['type'] == 'free') {

                                        $bundle_title       = sprintf(__('Buy %s + Get %d FREE', 'woocommerce'), $prod['qty'], $prod['qty_free']);
                                        $total_prod_qty     = $prod['qty'] + $prod['qty_free'];
                                        $bundle_price       = ($prod_price * $prod['qty']) / $total_prod_qty;
                                        $bundle_price_total = $bundle_price * $total_prod_qty;
                                        $bundle_coupon      = $prod['qty_free'] / $total_prod_qty * 100;
                                        $discount           = ($total_prod_qty * $prod_price) - $bundle_price_total;
                                    } else if ($prod['type'] == 'off') {

                                        $bundle_title       = sprintf(__('Buy %s + Get %d&#37;', 'woocommerce'), $prod['qty'], $prod['coupon']);
                                        $total_prod_qty     = $prod['qty'];
                                        $i_total            = $prod_price * $prod['qty'];
                                        $bundle_coupon      = $prod['coupon'];
                                        $bundle_price       = ($i_total - ($i_total * ($bundle_coupon / 100))) / $prod['qty'];
                                        $bundle_price_total = $bundle_price * $prod['qty'];
                                        $discount           = $i_total - $bundle_price_total;
                                    } else {

                                        $prod['type']       = 'Bundle';
                                        $bundle_price       = $prod['price'];
                                        $bundle_price_total = $prod['price'];
                                        $sum_price_regular  = 0;

                                        // $total_price_bun = 0;
                                        foreach ($prod['prod'] as $i => $i_prod) {
                                            $p_bun = wc_get_product($i_prod['id']);
                                            if ($p_bun->is_type('variable'))
                                                $sum_price_regular += $p_bun->get_variation_regular_price('min');
                                            else
                                                $sum_price_regular += $p_bun->get_regular_price();
                                        }

                                        $price_discount = $sum_price_regular - $bundle_price_total;
                                        $bundle_coupon  = ($price_discount * 100) / $sum_price_regular;
                                    }

                                ?>
                                    <!-- load option item package -->
                                    <div class="productRadioListItem <?= (self::$package_default_id == $prod['bun_id']) ? 'prod_popular mwc_active_product' : '' ?>" data-bundle_id="<?php echo ($prod['bun_id']) ?>">
                                        <label class="label_selection" for="product_<?php echo ($opt_i) ?>"></label>
                                        <?php
                                        if (self::$package_default_id == $prod['bun_id']) {
                                        ?>
                                            <img class="label_popular" src="<?php echo (MWC_PLUGIN_URL . 'images/style_D/icon_popular.png') ?>">
                                        <?php
                                        }
                                        ?>

                                        <!-- radio -->
                                        <div class="radio_select_cont">
                                            <input type="radio" class="radio_select" id="product_<?php echo ($opt_i) ?>" name="product" value="<?php echo ($opt_i) ?>">
                                        </div>

                                        <div class="product_name">

                                            <?php if (self::$package_default_id == $prod['bun_id']) { ?>
                                                <p class="opt_popular text_red"><?= __('MOST POPULAR', 'woocommerce') ?>!</p>
                                            <?php } ?>

                                            <?php if ($prod['type'] == 'bun') { ?>
                                                <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($bundle_price_total)) . ") - " . round($bundle_coupon, 0) . '% ') ?><span style="text-transform: uppercase;"><?= $prod['type'] ?></span></div>
                                            <?php } else { ?>
                                                <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($bundle_price)) . "/" . __('ea', 'woocommerce') . ") - " . round($bundle_coupon, 0) . '% ') ?><span style="text-transform: uppercase;"><?= $prod['type'] ?></span></div>
                                            <?php } ?>
                                        </div>

                                        <div class="product_price">
                                            <span><?php echo ($currency . round($bundle_price_total, 2)) ?></span>
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

                                            <!-- input statistic title, price... form -->
                                            <input type="hidden" class="opc_title" value="<?php echo ($bundle_title) ?>">
                                            <input type="hidden" class="opc_total_price" value="<?php echo ($currency . round($bundle_price_total, 2)) ?>">
                                            <input type="hidden" class="opc_discount" value="<?php echo ($currency . round($discount, 2)) ?>">

                                        </div>
                                        <!-- end products info to checkout -->

                                    </div>
                                    <!-- end option item package -->

                                <?php
                                }
                                ?>

                            </div>
                        </div>

                        <?php
                        render_product_select_dds($package_product_ids);
                        ?>

                        <script id="mwc-style-d-DD-js">
                            $ = jQuery.noConflict();

                            $(document).ready(function() {

                                console.log('mwc-style-d-DD-js loaded');

                                // get selected bundle id
                                let selected_bundle_id = $('.mwc_active_product').data('bundle_id');

                                // console.log(selected_bundle_id);

                                // show selected bundle variations
                                $('.mwc_product_variations_' + selected_bundle_id).show();

                                // on bundle click, hide all variations and show selected bundle variations
                                $('.productRadioListItem').click(function() {

                                    // get selected bundle id
                                    let selected_bundle_id = $(this).data('bundle_id');

                                    // hide all variations
                                    $('.mwc_product_variations').hide();

                                    // show selected bundle variations
                                    $('.mwc_product_variations_' + selected_bundle_id).show();

                                });

                                // swatch on click change product image
                                $('.wcvaswatchlabel').click(function(e) {
                                    e.preventDefault();

                                    let linked_id = $(this).data('linked_id');
                                    let prd_img_src = $(this).attr('img-src');
                                    let parent_container = $(this).closest('.c_prod_item');

                                    // debug
                                    // console.log(linked_id, prd_img_src, parent_container);

                                    parent_container.find('.wcvaswatchlabel').removeClass('selected');

                                    $(this).addClass('selected');

                                    parent_container.find('.mwc_variation_img').attr('src', prd_img_src);

                                });

                            });
                        </script>

                        <div data-r="" class="wysiwyg-content statistical">
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
                                        <td class="td-shipping"><?php pll_e('FREE', 'woocommerce'); ?></td>
                                    </tr>
                                </tbody>
                                <tbody>
                                    <tr height="20px"></tr>
                                </tbody>
                            </table>

                            <!-- summary -->
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
                    <!--/span-->
                    <!-- form checkout woo -->
                    <div class="row row-collapse col large-5 op_c_checkout_form" style="display: none;">
                        <div>
                            <?php
                            // Get checkout object for WC 2.0+
                            $checkout = WC()->checkout();
                            wc_get_template('checkout/form-checkout.php', array('checkout' => $checkout));
                            ?>
                        </div>
                    </div>

                </div>
                <!--/row-->

            </div>
            <!--/container-->
        </section> <!-- /row-wrapper-->

    </div> <!-- /wrapper -->
<?php
}


/**
 * Render product select dds
 *
 * @param array $package_product_ids
 * @return void
 */
function render_product_select_dds($package_product_ids)
{

    // get current currency
    $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_woocommerce_currency();

?>
    <div id="mwc-style-D" class="mwc-tab-content">
        <?php

        // Tab content (variation dropdowns)
        foreach ($package_product_ids as $opt_i => $prod_data) :

            /**
             * Code which checks for presence of variable
             */
            $bundle_ptypes_string = '';

            $bun_type = $prod_data['type'];

            if ($bun_type === 'bun') :

                $prods = $prod_data['prod'];

                foreach ($prods as $index => $pdata) :
                    $product = wc_get_product($pdata['id']);
                    $bundle_ptypes_string .= $product->get_type();
                endforeach;

            else :

                $product = wc_get_product($prod_data['id']);
                $bundle_ptypes_string .= $product->get_type();

            endif;

            // only display variations dropdown if variable product present 
            if (is_int(strpos($bundle_ptypes_string, 'variable'))) :
        ?>

                <!-- Product variations form ------------------------------>
                <div hidden class="mwc_product_variations mwc_product_variations_<?php echo (trim($prod_data['bun_id'])) ?> info_products_checkout" data-bundle_id="<?php echo (trim($prod_data['bun_id'])) ?>">
                    <h4 class="title_form"><?= __('Please choose:', 'woocommerce') ?> <h4>
                            <table class="product_variations_table">
                                <tbody>
                                    <?php

                                    //package selection variations free and off
                                    if ($prod_data['type'] === 'free' || $prod_data['type'] === 'off') :

                                        // retrieve product object
                                        $prod_obj = wc_get_product($prod_data['id']);

                                        // get variation images product
                                        if (!isset($var_data[$prod_data['id']]) && $prod_obj->is_type('variable')) :

                                            $var_arr = [];

                                            foreach ($prod_obj->get_available_variations() as $key => $value) :

                                                array_push($var_arr, [
                                                    'id'         => $value['variation_id'],
                                                    'price'      => isset($prod_data['custom_price'][$value['variation_id']]) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
                                                    'attributes' => $value['attributes'],
                                                    'image'      => $value['image']['url']
                                                ]);

                                            endforeach;

                                            $var_data[$prod_data['id']] = $var_arr;
                                        endif;

                                        // bundle/offer product loop start
                                        for ($i = 0; $i < $prod_data['qty']; $i++) :

                                            // check if has size chart
                                            $has_size_chart = get_post_meta($prod_data['id'], 'sbarray_chart_data', true) ? 'true' : 'false';

                                    ?>
                                            <!-- c_prod_item -->
                                            <tr class="c_prod_item" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($prod_data['id']) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>

                                                <?php if ($prod_obj->is_type('variable')) : ?>

                                                    <!-- variation index -->
                                                    <td class="variation_index"><?= $i + 1 ?></td>

                                                    <!-- variation image -->
                                                    <td class="variation_img">
                                                        <img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
                                                    </td>

                                                    <!-- selectors -->
                                                    <td class="variation_selectors">
                                                        <?php

                                                        // show variations linked by variations
                                                        echo MWC::mwc_return_linked_variations_dropdown([
                                                            'product_id'        => $prod_data['id'],
                                                            'class'             => 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
                                                        ], $var_data, $prod_data);

                                                        $prod_variations = $prod_obj->get_variation_attributes();

                                                        foreach ($prod_variations as $attribute_name => $options) :
                                                            // $default_opt = $prod_obj->get_variation_default_attribute($attribute_name);
                                                            $default_opt = '';
                                                            try {
                                                                $default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
                                                            } catch (Error $th) {
                                                            }
                                                        ?>

                                                            <div class="variation_item">
                                                                <p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

                                                                <!-- load dropdown variations -->
                                                                <?php
                                                                echo MWC::mwc_return_onepage_checkout_variation_dropdown([
                                                                    'product_id'        => $prod_data['id'],
                                                                    'options'             => $options,
                                                                    'attribute_name'    => $attribute_name,
                                                                    'default_option'    => $default_opt,
                                                                    'var_data'            => $var_data[$p_id],
                                                                    'class'             => 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
                                                                ]);
                                                                ?>

                                                            </div>
                                                        <?php endforeach; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php
                                        endfor;
                                    else : //package selection bundle

                                        $_index = 1;

                                        foreach ($prod_data['prod'] as $i => $i_prod) :

                                            $p_id       = $i_prod['id'];
                                            $b_prod_obj = wc_get_product($p_id);

                                            // has size chart
                                            $has_size_chart = get_post_meta($p_id, 'sbarray_chart_data', true) ? 'true' : 'false';

                                            // get variation images product
                                            if (!isset($var_data[$p_id]) && $b_prod_obj->is_type('variable')) :

                                                $var_arr = [];

                                                foreach ($b_prod_obj->get_available_variations() as $key => $value) :

                                                    array_push($var_arr, [
                                                        'id'         => $value['variation_id'],
                                                        'price'      => isset($prod_data['custom_price']) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
                                                        'attributes' => $value['attributes'],
                                                        'image'      => $value['image']['url']
                                                    ]);

                                                endforeach;

                                                $var_data[$p_id] = $var_arr;

                                            endif;

                                            for ($i = 1; $i <= $i_prod['qty']; $i++) : ?>
                                                <tr class="c_prod_item" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($p_id) ?>" <?= (!$b_prod_obj->is_type('variable')) ? 'hidden' : '' ?>>

                                                    <?php

                                                    // try {
                                                    // 	do_action('mwc_size_chart', $p_id);
                                                    // } catch (\Throwable $th) {
                                                    // 	echo $th->getMessage();
                                                    // }

                                                    ?>

                                                    <?php if ($b_prod_obj->is_type('variable')) : ?>

                                                        <td class="variation_index"><?= $_index++ ?></td>
                                                        <td class="variation_img">
                                                            <img id="prod_image" class="mwc_variation_img" src="<?= wp_get_attachment_image_src($b_prod_obj->get_image_id())[0] ?>">
                                                        </td>
                                                        <td class="variation_selectors">
                                                            <?php

                                                            // show variations linked by variations
                                                            echo MWC::mwc_return_linked_variations_dropdown([
                                                                'product_id'        => $p_id,
                                                                'class'             => 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
                                                            ], $var_data, $prod_data);

                                                            $prod_variations = $b_prod_obj->get_variation_attributes();

                                                            foreach ($prod_variations as $attribute_name => $options) :
                                                                $default_opt = '';
                                                                try {
                                                                    $default_opt =  key_exists($attribute_name, $b_prod_obj->get_default_attributes()) ? $b_prod_obj->get_default_attributes()[$attribute_name] : '';
                                                                } catch (Error $th) {
                                                                    $default_opt = '';
                                                                }
                                                            ?>
                                                                <div class="variation_item">
                                                                    <p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

                                                                    <!-- load dropdown variations -->
                                                                    <?php
                                                                    echo MWC::mwc_return_onepage_checkout_variation_dropdown([
                                                                        'product_id'        => $p_id,
                                                                        'options'             => $options,
                                                                        'attribute_name'    => $attribute_name,
                                                                        'default_option'    => $default_opt,
                                                                        'var_data'            => $var_data[$p_id],
                                                                        'class'             => 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
                                                                    ]);
                                                                    ?>

                                                                </div>
                                                                <?php
                                                                // Size chart
                                                                if (defined('SBHTML_VERSION')) :
                                                                    do_action('mwc_size_chart', $p_id);
                                                                endif;
                                                                ?>
                                                            <?php endforeach; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                    <?php endfor;
                                        endforeach;
                                    endif;
                                    ?>
                                </tbody>
                            </table>

                            <!-- ======================== -->
                            <!-- variations free products -->
                            <!-- ======================== -->
                            <?php if ($prod_data['type'] == 'free' && isset($prod_data['qty_free']) && $prod_data['qty_free'] > 0) :    ?>

                                <h5 class="title_form"><?= __('Select Free Product:', 'woocommerce') ?>:</h5>

                                <table class="product_variations_table">
                                    <tbody>

                                        <?php for ($i = 0; $i < $prod_data['qty_free']; $i++) :    ?>
                                            <tr class="c_prod_item free-item" data-id="<?php echo ($prod_data['id']) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>
                                                <?php if ($prod_obj->is_type('variable')) : ?>
                                                    <td class="variation_index"><?= $i + 1 ?></td>
                                                    <td class="variation_img">
                                                        <img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
                                                    </td>
                                                    <td class="variation_selectors">
                                                        <?php

                                                        // show variations linked by variations
                                                        echo MWC::mwc_return_linked_variations_dropdown([
                                                            'product_id'        => $prod_data['id'],
                                                            'class'             => 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
                                                        ], $var_data, $prod_data);

                                                        $prod_variations = $prod_obj->get_variation_attributes();

                                                        foreach ($prod_variations as $attribute_name => $options) :
                                                            // $default_opt = $prod_obj->get_variation_default_attribute($attribute_name);
                                                            $default_opt = '';
                                                            try {
                                                                $default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
                                                            } catch (Error $th) {
                                                                $default_opt = '';
                                                            }
                                                        ?>
                                                            <div class="variation_item">
                                                                <p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

                                                                <!-- load dropdown variations -->
                                                                <?php
                                                                echo MWC::mwc_return_onepage_checkout_variation_dropdown([
                                                                    'product_id'        => $prod_data['id'],
                                                                    'options'             => $options,
                                                                    'attribute_name'    => $attribute_name,
                                                                    'default_option'    => $default_opt,
                                                                    'var_data'            => $var_data[$prod_data['id']],
                                                                    'class'             => 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'] . ' free-prod',
                                                                ]);
                                                                ?>

                                                            </div>
                                                        <?php endforeach; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>

                            <?php endif; ?>
                </div>

        <?php endif;
            // Size chart
            if (defined('SBHTML_VERSION')) :
                do_action('mwc_size_chart', $prod_data['id']);
            endif;
        endforeach; ?>

    </div><!-- .mwc-tab-content -->
<?php }
