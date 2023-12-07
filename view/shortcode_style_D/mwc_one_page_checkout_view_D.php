<?php
global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;
$default_package_id    = self::$package_default_id;

// debug
// echo '<pre>';
// print_r($package_product_ids);
// echo '</pre>';

/**
 * Base JS for style D
 *
 * @return void
 */
function mwc_style_D_js()
{ ?>

    <!-- style D js -->
    <script id="mwc-template-d-debug-js" type="text/javascript">
        $ = jQuery.noConflict();

        // on load
        $(window).load(function() {

            // console.log('mwc-style-d-DD-js loaded');

            $('.col.customer_info').removeClass('large-7');
            $('.col.payment_opt').removeClass('large-5');

            $('.style_D_checkout_form').show();

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

            // debug
            // console.log('mwc-template-d-debug-js loaded');

            // on load
            $('.productRadioListItem').each(function(index, element) {

                if ($(this).hasClass('mwc_active_product')) {

                    // trigger click on radio
                    $(this).find('.radio_select').trigger('click');

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

<?php }

/**
 * JS to check for the existence of size chart and insert size chart link
 *
 * @return void
 */
function mwc_style_D_size_chart_js()
{ ?>

    <script id="mwc_style_D_size_chart_js" type="text/javascript">
        $ = jQuery.noConflict();
        $(function() {
            let attrs = $('.var_prod_attr');

            $.each(attrs, function() {
                let chart_append = $(this).siblings('.variation_name'),
                    chart_set = $(this).parents('.c_prod_item').attr('has-size-chart'),
                    pid = $(this).parents('.c_prod_item').attr('data-id');

                if (chart_set == 'true') {
                    let sbhtml_label_text = '',
                        sbhtml_link_text = $('#sbhtml_text_open_modal').val(),
                        label_text_content = '<div class="sbhtml_label_wrap">' + sbhtml_label_text + ' <span class="sbhtml_link_text" target="' + pid + '">' + sbhtml_link_text + '</span></div>';

                    chart_append.after(label_text_content);

                }

            });

            /**
             * Prepend size guide svg to size guide link
             */
            $('.sbhtml_link_text').prepend('<svg class="sbhtml-chart-svg" data-v-6b417351="" width="24" viewBox="0 -4 34 30" xmlns="http://www.w3.org/2000/svg" class="w-3 w-6"><path d="M32.5 11.1c-.6 0-1 .4-1 1v11.8h-1.9v-5.4c0-.6-.4-1-1-1s-1 .4-1 1v5.4h-3.7v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6h-3.7v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6h-4.1v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6H6.4v-5.4c0-.6-.4-1-1-1s-1 .4-1 1v5.4H2.5V12.1c0-.6-.4-1-1-1s-1 .4-1 1v12.8c0 .6.4 1 1 1h31c.6 0 1-.4 1-1V12.1c0-.6-.4-1-1-1z" fill="#666666"></path><path d="M27.1 12.4v-.6c0-.1-.1-.1-.1-.2l-2.6-3c-.4-.6-1-.6-1.5-.3-.4.4-.5 1-.1 1.4L24 11H10l1.2-1.3c.4-.4.3-1-.1-1.4-.5-.3-1.1-.3-1.5.1l-2.6 3s0 .1-.1.1l-.1.1c0 .1-.1.1-.1.2v.2c0 .1 0 .1.1.2 0 .1.1.1.1.1s0 .1.1.1l2.6 3c.2.2.5.3.8.3.2 0 .5-.1.7-.2.4-.4.5-1 .1-1.4l-1.2-1h14l-1.2 1.3c-.4.4-.3 1 .1 1.4.2.2.4.2.7.2.3 0 .6-.1.8-.3l2.6-3c0-.1.1-.1.1-.2v-.1z" fill="#666666"></path></svg>');

            // hide modal and overlay
            $('.sbhtml_chart_overlay, .sbhtml_modal_close').on('click', function(e) {
                e.preventDefault();
                $(this).closest('.sbhtml_chart_overlay, .sbhtml_chart_modal').hide();
            });

            $('.sbhtml_modal_close').on('click', function(e) {
                e.preventDefault();
                $(this).parents('.sbhtml_chart_modal').hide();
                $(this).parents().parents('.sbhtml_chart_overlay').hide()
            });

            // show modal and overlay
            $('.sbhtml_link_text').on('click', function(e) {
                e.preventDefault();

                console.log('clicked');

                let target_id = $(this).attr('target');


                console.log(target_id);

                console.log($('#sbhtml_chart_modal-' + target_id));

                $('#sbhtml_chart_modal-' + target_id).show();
                $('#sbhtml_chart_overlay-' + target_id).show();

            });
        });
    </script>

<?php }

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
                    <div class="productRadioListItem <?= ($default_package_id == $prod['bun_id']) ? 'prod_popular mwc_active_product' : '' ?>" data-bundle_id="<?php echo ($prod['bun_id']) ?>">
                        <label class="label_selection" for="product_<?php echo ($opt_i) ?>"></label>
                        <?php
                        if ($default_package_id == $prod['bun_id']) {
                        ?>
                            <img class="label_popular" src="<?php echo (MWC_PLUGIN_URL . 'images/style_D/icon_popular.png') ?>">
                        <?php
                        }
                        ?>

                        <!-- radio -->
                        <div class="radio_select_cont">
                            <input type="radio" class="radio_select" id="product_<?php echo ($opt_i) ?>" name="product" value="<?php echo ($opt_i) ?>">
                        </div>

                        <!-- bundle product summary -->
                        <div class="product_name">

                            <!-- default bundle -->
                            <?php if ($default_package_id == $prod['bun_id']) { ?>
                                <span class="opt_popular text_red"><?= __('MOST POPULAR', 'woocommerce') ?>!</span>
                            <?php } ?>

                            <!-- bundled products -->
                            <?php if ($prod['type'] == 'bun') { ?>
                                <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($bundle_price_total)) . ") - " . round($bundle_coupon, 0) . '% ') ?><span style="text-transform: uppercase;"><?= $prod['type'] ?></span></div>

                                <!-- everything else -->
                            <?php } else { ?>
                                <div class="opt_title"><?php echo ($option_title . " (" . wp_strip_all_tags(wc_price($bundle_price)) . "/" . __('ea', 'woocommerce') . ") - " . round($bundle_coupon, 0) . '% ') ?><span style="text-transform: uppercase;"><?= $prod['type'] ?></span></div>
                            <?php } ?>
                        </div>

                        <div class="product_price">
                            <span><?php echo ($currency . round($bundle_price_total, 2)) ?></span>
                        </div>

                        <!-- input statistic title, price... form -->
                        <input type="hidden" class="opc_title" value="<?php echo ($bundle_title) ?>">
                        <input type="hidden" class="opc_total_price" value="<?php echo ($currency . round($bundle_price_total, 2)) ?>">
                        <input type="hidden" class="opc_discount" value="<?php echo ($currency . round($discount, 2)) ?>">

                    </div>
                    <!-- end option item package -->

                <?php
                }

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

    <script id="style-D-ATC-JS">
        $ = jQuery;

        console.log('thou art here');

        data = {
            '_ajax_nonce': '<?php echo wp_create_nonce('lekker_by_die_see_ajax') ?>',
            'action': 'mwc_atc_template_d_products',
        }

        console.log(data);

        try {
            $.post('/wp-admin/admin-ajax.php', data, function(response) {
                console.log(response)
            })
        } catch (error) {
            console.error(error);
        }

        // on load
        $(window).load(function() {

            let product_data = [];

            // on load [default bundle]
            $('.productRadioListItem').each(function(index, element) {

                // skip if this does not have class mwc_active_product
                if (!$(this).hasClass('mwc_active_product')) return;

                let bundle_id = $(this).data('bundle_id');

                $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(index, element) {

                    let prod_id = $(this).data('id');
                    let prod_type = $(this).data('type');

                    // get selected size from size dropdown (check if select's data-attibute_name matches 'attribute_pa_size')
                    let selected_size = $(this).find('.var_prod_attr').filter(function() {
                        return $(this).data('attribute_name') === 'attribute_pa_size';
                    }).val();

                    // get selected color from color dropdown (check if select's data-attibute_name matches 'attribute_pa_color')
                    let selected_color = $(this).find('.var_prod_attr').filter(function() {
                        return $(this).data('attribute_name') === 'attribute_pa_color';
                    }).val();

                    // get variation data from any of the selects via 'data-variations' attribute
                    let variations = JSON.parse(atob($(this).find('.var_prod_attr').data('variations')));

                    // search for and return the correct variation id in variations based on selected size and color
                    let variation_id = variations.filter(function(variation) {
                        return variation.attributes.attribute_pa_size === selected_size && variation.attributes.attribute_pa_color === selected_color;
                    })[0].variation_id;

                    // debug
                    // console.log('prod_id', prod_id);
                    // console.log('prod_type', prod_type);
                    // console.log('selected_size', selected_size);
                    // console.log('selected_color', selected_color);
                    // console.log('variation_id', variation_id);

                    // push product data to array
                    product_data.push({
                        'prod_id': prod_id,
                        'prod_type': prod_type,
                        'selected_size': selected_size,
                        'selected_color': selected_color,
                        'variation_id': variation_id,
                        'qty': 1
                    });

                });

            });

            // debug
            // console.log('product_data', product_data);

            // if product_data not empty, send ajax request to add to cart
            if (product_data.length > 0) {



                // ajax request
                // $.ajax({
                //     type: 'POST',
                //     url: '<?php echo admin_url('admin-ajax.php'); ?>',
                //     data: {
                //         'action': 'lekker_by_die_see_ajax',
                //         'nonce' : '<?php echo wp_create_nonce('lekker_by_die_see_ajax'); ?>',
                //         // 'product_data': product_data
                //     },

                //     success: function(response) {

                //         // debug
                //         console.log('response', response);

                //         // redirect to checkout page
                //         // window.location.href = '<?php echo wc_get_checkout_url(); ?>';

                //     },
                //     error: function(error) {

                //         // debug
                //         console.error('error', error);

                //     }
                // });


            }

        });
    </script>



<?php
}




/**
 * Render product select dds
 *
 * @param array $package_product_ids
 * @return void
 */
function mwc_style_D_render_product_select_dds($package_product_ids)
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

                <div hidden class="mwc_product_variations mwc_product_variations_<?php echo (trim($prod_data['bun_id'])) ?> info_products_checkout" data-bundle_id="<?php echo (trim($prod_data['bun_id'])) ?>">
                    <span class="style_d_bundle_title"><?= __('Choose your product(s):', 'woocommerce') ?> </span>

                    <?php

                    //package selection variations free and off
                    if ($prod_data['type'] === 'free' || $prod_data['type'] === 'off') :

                        // ---------------------------
                        // render free and off products
                        // ---------------------------
                        mwc_render_style_D_free_off_prods($prod_data, $current_curr);


                    else :

                        // ---------------------------
                        // render bundle products
                        // ---------------------------
                        mwc_render_style_D_bundle_prods($prod_data, $current_curr);

                    endif;

                    // ----------------------------
                    // render bundle free products
                    // ----------------------------
                    mwc_render_style_D_free_prods($prod_data, $current_curr)

                    ?>

                </div>

        <?php endif;

        endforeach; ?>

    </div><!-- .mwc-tab-content -->


    <?php }

/**
 * Render style D free products
 *
 * @param array $prod_data
 * @param array $var_data
 * @param object $prod_obj
 * @return void
 */
function mwc_render_style_D_free_prods($prod_data, $current_curr)
{

    if ($prod_data['type'] == 'free' && isset($prod_data['qty_free']) && $prod_data['qty_free'] > 0) :

        // has size chart
        $has_size_chart = get_post_meta($prod_data['id'], 'sbarray_chart_data', true) ? 'true' : 'false';

        $prod_obj = wc_get_product($prod_data['id']);

        // get variation images product
        if (!isset($var_data[$prod_data['id']]) && $prod_obj->is_type('variable')) :

            $var_arr = [];

            foreach ($prod_obj->get_available_variations() as $key => $value) :

                array_push($var_arr, [
                    'id'         => $value['variation_id'],
                    'price'      => isset($prod_data['custom_price']) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
                    'attributes' => $value['attributes'],
                    'image'      => $value['image']['url']
                ]);

            endforeach;

            $var_data[$prod_data['id']] = $var_arr;

        endif;

    ?>

        <!-- section title -->
        <span class="style_d_bundle_title"><?= __('Select Free Product(s)', 'woocommerce') ?>:</span>

        <?php for ($i = 0; $i < $prod_data['qty_free']; $i++) :    ?>

            <!-- c prod item -->
            <div class="c_prod_item" data-type="bogof-free-item" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($prod_data['id']) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>

                <!-- is variable -->
                <?php if ($prod_obj->is_type('variable')) : ?>

                    <!-- index -->
                    <div class="style_d_var_index">
                        <span><?= $i + 1 ?></span>
                    </div>

                    <!-- product img -->
                    <div class="variation_img">
                        <img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
                    </div>

                    <!-- variation selectors -->
                    <div class="variation_selectors">
                        <?php

                        // show variations linked by variations
                        echo MWC::mwc_return_linked_variations_dropdown([
                            'product_id'        => $prod_data['id'],
                            'class'          => 'var_prod_attr checkout_prod_attr select-variation-' . $prod_data['type'] . 'parent_product_' . $prod_data['id'],
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

                            <!-- variation item -->
                            <div class="variation_item">
                                <span class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </span>

                                <!-- load dropdown variations -->
                                <?php
                                echo MWC::mwc_return_onepage_checkout_variation_dropdown([
                                    'product_id'     => $prod_data['id'],
                                    'options'        => $options,
                                    'attribute_name' => $attribute_name,
                                    'default_option' => $default_opt,
                                    'var_data'       => $var_data[$prod_data['id']],
                                    'class'          => 'var_prod_attr checkout_prod_attr select-variation-' . $prod_data['type'] . 'parent_product_' . $prod_data['id'] . ' free-item',
                                ]);
                                ?>

                            </div>


                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            // Size chart
            if (defined('SBHTML_VERSION')) :
                do_action('mwc_size_chart', $prod_data['id']);
            else :
                echo 'SBHTML_VERSION not defined';
            endif;
            ?>

        <?php endfor; ?>

        <?php endif;
}

/**
 * Render style D bundle products
 *
 * @param array $prod_data
 * @param array $var_data
 * @param string $current_curr
 * @return void
 */
function mwc_render_style_D_bundle_prods($prod_data, $current_curr)
{
    $_index = 1;

    foreach ($prod_data['prod'] as $i => $i_prod) :

        $p_id       = $i_prod['id'];
        $prod_obj = wc_get_product($p_id);

        // has size chart
        $has_size_chart = get_post_meta($p_id, 'sbarray_chart_data', true) ? 'true' : 'false';

        // get variation images product
        if (!isset($var_data[$p_id]) && $prod_obj->is_type('variable')) :

            $var_arr = [];

            foreach ($prod_obj->get_available_variations() as $key => $value) :

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

            <!-- c prod item -->
            <div class="c_prod_item" data-type="bundle-item" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($p_id) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>

                <?php if ($prod_obj->is_type('variable')) : ?>

                    <!-- index -->
                    <div class="style_d_var_index">
                        <span><?= $_index++ ?></span>
                    </div>

                    <!-- variation image -->
                    <div class="variation_img">
                        <img id="prod_image" class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
                    </div>

                    <!-- variation selectors -->
                    <div class="variation_selectors">
                        <?php

                        // show variations linked by variations
                        echo MWC::mwc_return_linked_variations_dropdown([
                            'product_id'        => $p_id,
                            'class'             => 'var_prod_attr checkou_prod_attr select-variation-' . $prod_data['type'],
                        ], $var_data, $prod_data);

                        $prod_variations = $prod_obj->get_variation_attributes();

                        foreach ($prod_variations as $attribute_name => $options) :
                            $default_opt = '';
                            try {
                                $default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
                            } catch (Error $th) {
                                $default_opt = '';
                            }
                        ?>
                            <!-- variation item -->
                            <div class="variation_item">
                                <span class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </span>

                                <!-- load dropdown variations -->
                                <?php
                                echo MWC::mwc_return_onepage_checkout_variation_dropdown([
                                    'product_id'     => $p_id,
                                    'options'        => $options,
                                    'attribute_name' => $attribute_name,
                                    'default_option' => $default_opt,
                                    'var_data'       => $var_data[$p_id],
                                    'class'          => 'var_prod_attr checkout_prod_attr select-variation-' . $prod_data['type'] . 'parent_product_' . $prod_data['id'],
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
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor;
    endforeach;
}

/**
 * Render style D free and off products
 *
 * @param array $prod_data
 * @return void
 */
function mwc_render_style_D_free_off_prods($prod_data, $current_curr)
{
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
        <div class="c_prod_item" data-type="<?php echo $prod_data['type'] == 'off' ? 'off' : 'bogof-paid'; ?>-item" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($prod_data['id']) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>

            <?php if ($prod_obj->is_type('variable')) : ?>

                <!-- variation index -->
                <div class="style_d_var_index">
                    <span><?= $i + 1 ?></span>
                </div>

                <!-- variation image -->
                <div class="variation_img">
                    <img class="mwc_variation_img" src="<?= wp_get_attachment_image_src($prod_obj->get_image_id())[0] ?>">
                </div>

                <!-- selectors -->
                <div class="variation_selectors">
                    <?php

                    // show variations linked by variations
                    echo MWC::mwc_return_linked_variations_dropdown([
                        'product_id'        => $prod_data['id'],
                        'class'          => 'var_prod_attr checkout_prod_attr select-variation-' . $prod_data['type'] . 'parent_product_' . $prod_data['id'],
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
                            <span class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </span>

                            <!-- load dropdown variations -->
                            <?php
                            echo MWC::mwc_return_onepage_checkout_variation_dropdown([
                                'product_id'     => $prod_data['id'],
                                'options'        => $options,
                                'attribute_name' => $attribute_name,
                                'default_option' => $default_opt,
                                'var_data'       => $var_data[$prod_data['id']],
                                'class'          => 'var_prod_attr checkout_prod_attr select-variation-' . $prod_data['type'] . 'parent_product_' . $prod_data['id'],
                            ]);
                            ?>

                        </div>
                        <?php
                        // Size chart
                        if (defined('SBHTML_VERSION')) :
                            do_action('mwc_size_chart', $prod_data['id']);
                        endif;
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
<?php
    endfor;
}
