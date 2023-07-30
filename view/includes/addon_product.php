<?php

class viewAddonProduct {

    public static function load_view($addon_product_ids, $see_more = false) {

        if ($addon_product_ids) :

            // ********************************************
            // IMPRESSIONS TRACKING CACHE WP CACHE & REDIS
            // ********************************************

            // retrieve current impressions cache/transient
            $curr_impressions = maybe_unserialize(get_transient('mwco_addon_impressions'));

            // if impressions exist
            if ($curr_impressions !== false) :

                // setup new impressions
                $new_impressions = [];

                // update impressions
                foreach ($curr_impressions as $uid => $views) :
                    $new_impressions[$uid] = $views + 1;
                endforeach;

                // set cache/transient
                $set = set_transient('mwco_addon_impressions', maybe_serialize($new_impressions), 360);

            // if impressions do not exist
            else :

                // setup initial impressions array
                $impressions = [];

                // push impressions
                foreach ($addon_product_ids as $addon_id) :

                    // retrieve product id
                    $prod_id = get_post_meta($addon_id, 'product_id', true);

                    // push impressions
                    $impressions[$prod_id] = 1;
                endforeach;

                // set cache/transient
               $set = set_transient('mwco_addon_impressions', maybe_serialize($impressions), 360);

            endif;

            // create array variations data
            $var_data = MWC::$mwc_product_variations;

            // load fancybox
            $req = array('jquery');
            wp_dequeue_style('sb_bundle_sell_fancybox_css');
            wp_dequeue_script('sb_bundle_sell_fancybox_js');
            wp_enqueue_style('mwc_fancybox_css', MWC_PLUGIN_URL . 'resources/lib/fancybox/jquery.fancybox.min.css', array(), null);
            wp_enqueue_script('mwc_fancybox_js', MWC_PLUGIN_URL . 'resources/lib/fancybox/jquery.fancybox.min.js', $req, null, true);

            // load style, script
            wp_enqueue_style('mwc_upsell_product_style', MWC_PLUGIN_URL . 'resources/style/includes/upsell_product/front.css', array(), time());
            wp_enqueue_script('mwc_upsell_product_script', MWC_PLUGIN_URL . 'resources/js/includes/upsell_product/front.js', array(), time()); ?>

            <div class="mwc_upsell_product_wrap" data-label="<?= __('Addon product', 'woocommerce') ?>">

                <div class="item_title">

                    <?php
                    $theme = wp_get_theme(); // gets the current theme
                    if ('Riode' == $theme->name || 'Riode' == $theme->parent_theme) :
                    ?>
                        <i aria-hidden="true" class="  d-icon-gift"></i>
                    <?php else : ?>
                        <img src="<?= (MWC_PLUGIN_URL . 'images/addon_label/icon-gift.png') ?>" class="icon_gift_title">
                    <?php endif; ?>
                    <h3 class="text_title" style="font-size: calc(1.8rem * var(--rio-typo-ratio,1));"><?php echo __('Addon Special', 'woocommerce'); ?></h3>
                </div>

                <div class="mwc_item_addons_div <?= ($see_more) ? 'see_more' : '' ?>">

                    <?php if (is_array($addon_product_ids) && !empty($addon_product_ids)) : ?>

                        <?php foreach ($addon_product_ids as $addon_id) :

                            // $addon_meta = get_post_meta($addon_id, 'product_discount', true);
                            // $addon_meta = get_post_meta($addon_id);

                            $addon_meta = [];

                            // product id
                            // $p_id = array_shift($addon_meta["product_id"]);
                            $p_id = get_post_meta($addon_id, 'product_id', true);
                            $addon_meta['product_id'] = $p_id;

                            // One-time offer
                            // $one_time_offer = array_shift($addon_meta["one_time_offer"]);
                            $one_time_offer = get_post_meta($addon_id, 'one_time_offer', true);

                            // Percentage discount
                            // $discount_percent = array_shift($addon_meta['percentage_discount']);
                            $discount_percent = get_post_meta($addon_id, 'percentage_discount', true);
                            $addon_meta['discount_perc'] = $discount_percent;

                            // Disable WooSwatches
                            // $disable_woo_swatches = array_shift($addon_meta["disable_woo_swatches"]);
                            $disable_woo_swatches = get_post_meta($addon_id, 'disable_woo_swatches', true);

                            //get product current language
                            if (function_exists('pll_get_post')) :
                                $p_id = pll_get_post($p_id, pll_current_language());
                            endif;

                            // get product data
                            $prod_obj = wc_get_product($p_id);

                            // remove when none product
                            if (!$prod_obj) :
                                continue;
                            endif;

                            $p_title    = $prod_obj->get_title();
                            $price_html = $prod_obj->get_price_html();
                            $price      = $prod_obj->get_price();

                            // get reg price, sale price
                            if ($prod_obj->is_type('variable')) :

                                $regular_price = $prod_obj->get_variation_regular_price('max');
                                $sale_price    = $prod_obj->get_variation_sale_price('min');

                                // get variation images product
                                if (!isset($var_data[$p_id])) :

                                    $var_arr = [];

                                    foreach ($prod_obj->get_available_variations() as $key => $value) :

                                        // has discount
                                        if ($discount_percent > 0) :
                                            $var_price = mwc_price_discounted($value['display_regular_price'], $discount_percent);
                                            $h_price   = '<del>' . wc_price($value['display_regular_price']) . '</del><span> - </span>' . wc_price($var_price);
                                        else :
                                            $var_price = $value['display_price'];
                                            $h_price   = $price_html;
                                        endif;

                                        array_push($var_arr, [
                                            'attributes' => $value['attributes'],
                                            'image'      => $value['image']['url'],
                                            'price'      => $var_price,
                                            'price_html' => $h_price
                                        ]);

                                    endforeach;

                                    $var_data[$p_id] = $var_arr;

                                endif;

                            else :
                                $regular_price = $prod_obj->get_regular_price();
                                $sale_price    = $prod_obj->get_sale_price();
                            endif;

                            // calculator price when has discount
                            $after_discount_price = $price;

                            if ($discount_percent > 0) :
                                $after_discount_price = mwc_price_discounted($regular_price, $discount_percent);
                            endif;

                            $product_featured_image_id = get_post_thumbnail_id($p_id);
                            $thumb_image               = wp_get_attachment_image_src(get_post_thumbnail_id($p_id), 'shop_thumbnail', true);
                            $thumb_url                 = $thumb_image[0];
                        ?>

                            <!-- REVISED CHECKOUT ADDON HTML -->
                            <div class="mwc_addon_div">

                                <!-- checkout addon outer cont -->
                                <div class="mwc_item_addon" id="smartency_wadc_offered_item_<?php echo $p_id; ?>" data-id="<?= $p_id ?>" data-addon_id="<?= $addon_id ?>">

                                    <!-- mwc addon price hidden -->
                                    <input type="hidden" class="mwc_addon_price_hidden" value="<?= $after_discount_price ?>">

                                    <!-- checkbox -->
                                    <div id="" class="cao_checkbox_cont">

                                        <?php if ($prod_obj->get_type() === 'variable') : ?>
                                            <input type="checkbox" addon-meta="<?php echo base64_encode(json_encode($addon_meta)); ?>" data-variations="<?php echo base64_encode(json_encode($prod_obj->get_available_variations())); ?>" id="input_selected_product_<?php echo $p_id; ?>" class="mwc_checkbox_addon" data-product_id="<?php echo $p_id; ?>">
                                        <?php else : ?>
                                            <input type="checkbox" addon-meta="<?php echo base64_encode(json_encode($addon_meta)); ?>" id="input_selected_product_<?php echo $p_id; ?>" class="mwc_checkbox_addon" data-product_id="<?php echo $p_id; ?>">
                                        <?php endif; ?>

                                    </div>

                                    <!-- img -->
                                    <div id="" class="cao_img_cont img_option">
                                        <img id="i_item_img_<?php echo $p_id; ?>" src="<?php echo $thumb_url; ?>" alt="" style="border-radius:10%" class="upsell_thumb_<?php echo $p_id; ?>">
                                    </div>

                                    <!-- title and options cont -->
                                    <div id="" class="cao_title_options_cont">
                                        <!-- title -->
                                        <div id="" class="cao_title">
                                            <span><?php echo $p_title; ?></span>
                                            <!-- add-on info -->
                                            <div id="" class="addon_popup_button">
                                                <a class="i_mwc_product_info_badge mwc_fancybox_open" href="#mwc_product_intro_<?php echo $p_id; ?>">i</a>
                                            </div>
                                        </div>

                                        <!-- One-time offer img -->
                                        <?php if ($one_time_offer == "yes") : ?>
                                            <div class="cao_one_time_offer">
                                                <img src="<?= (MWC_PLUGIN_URL . 'images/addon_label/one-time-offer.png') ?>">
                                            </div>
                                        <?php endif; ?>

                                        <!-- pricing -->
                                        <div id="" class="cao_price">

                                            <!-- when has discount % -->
                                            <?php if ($discount_percent > 0) : ?>
                                                <span class="i_product_price price_change">

                                                    <?php
                                                    // get price first variation when is product variable
                                                    if ($prod_obj->is_type('variable')) : ?>
                                                        <span><?= $var_data[$p_id][0]['price_html'] ?></span>
                                                    <?php
                                                    // get price not product variable
                                                    else : ?>
                                                        <del><?= wc_price($regular_price) ?></del>
                                                        <span> - </span>
                                                        <span><?= wc_price($after_discount_price) ?></span>
                                                    <?php
                                                    endif; ?>

                                                </span>

                                                <!-- label discount -->
                                                <span class="cao_off_label"><?= sprintf(__('%s&#37; OFF', 'woocommerce'), round($discount_percent, 2)) ?></span>

                                            <?php else : ?>
                                                <span class="i_product_price price_change"><?php echo $price_html; ?></span>

                                            <?php endif; ?>

                                        </div>

                                        <!-- options -->
                                        <div id="" class="cao_options product_info">

                                            <!-- variation options -->
                                            <div id="" class="cao_var_options info_variations">

                                                <?php
                                                /* IF ADDON HAS VARIATIONS */
                                                if ($prod_obj->is_type('variable')) :

                                                    if (empty($prod_obj->get_available_variations()) && false !== $prod_obj->get_available_variations()) : ?>
                                                        <p class="stock out-of-stock"><?php echo __('This product is currently out of stock and unavailable.', 'woocommerce'); ?></p>
                                                        <?php
                                                    else :
                                                        foreach ($prod_obj->get_variation_attributes() as $attribute_name => $options) :
                                                            $default_opt =  key_exists($attribute_name, $prod_obj->get_default_attributes()) ? $prod_obj->get_default_attributes()[$attribute_name] : '';
                                                        ?>
                                                            <div class="i_dropdown variation_item" data-id="<?php echo $prod_obj->get_id(); ?>" data-variation-id="">
                                                                <label for="<?php echo sanitize_title($attribute_name); ?>"><?php echo wc_attribute_label($attribute_name); ?></label>
                                                                <!-- load dropdown variations -->
                                                                <?php
                                                                echo MWC::return_mwc_onepage_checkout_variation_dropdown([
                                                                    'product_id'            => $p_id,
                                                                    'options'               => $options,
                                                                    'attribute_name'        => $attribute_name,
                                                                    'default_option'        => $default_opt,
                                                                    'var_data'              => $var_data[$p_id],
                                                                    'class'                 => 'addon_var_select',
                                                                    'disable_woo_swatches'  => $disable_woo_swatches,
                                                                    'type'                  => 'dropdown'
                                                                ]);
                                                                ?>
                                                            </div>
                                                <?php endforeach;
                                                    endif;
                                                endif;
                                                ?>
                                                <!-- qty -->
                                                <div id="" class="i_dropdown cao_qty">
                                                    <label for="sepu_qty"><?= __('Qty', 'woocommerce') ?></label>
                                                    <select id="sepu_qty_<?php echo $p_id; ?>" class="addon_prod_qty">
                                                        <?php for ($i = 1; $i <= 10; $i++) : ?>
                                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div><!-- options end -->
                                    </div>

                                </div>

                                <?php
                                $gallery_images = $prod_obj->get_gallery_image_ids();
                                ?>
                                <div style="display: none;">
                                    <div id="mwc_product_intro_<?php echo $p_id; ?>" class="mwc_product_intro_container">
                                        <div class="col large-6 mwc_col_6 left_inner_div">

                                            <?php $p_img_url = wp_get_attachment_image_src($product_featured_image_id, 'shop_single')[0]; ?>

                                            <div class="i_wadc_full_image_div fn_img_div">
                                                <img id="full_img" src="<?php echo $p_img_url; ?>" class="i_wadc_full_image">
                                            </div>

                                            <?php if (!empty($gallery_images)) : ?>
                                                <div class="i_row i_clearfix intro_images_div">

                                                    <div class="intro_img_preview fn_img_div col-md-2" data-image_url="<?php echo $p_img_url; ?>">
                                                        <img src="<?php echo $p_img_url; ?>" class="">
                                                    </div>

                                                    <?php foreach ($gallery_images as $gallery_image_id) : ?>
                                                        <div class="intro_img_preview fn_img_div col-md-2" data-image_url="<?php echo wp_get_attachment_image_src($gallery_image_id, 'shop_single')[0]; ?>">
                                                            <?php echo wp_get_attachment_image($gallery_image_id, 'shop_single'); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                        <div class="col large-6 mwc_col_6 right_inner_div">
                                            <div class="product_title">
                                                <span class="preview_title"><?php echo $p_title; ?></span>
                                            </div>
                                            <p class="sepu_product_intro_desc"><?php echo mb_strimwidth(wp_strip_all_tags($prod_obj->get_short_description()), 0, 110, '...'); ?></p>

                                            <div id="intro_product_price_container" class="wadc_product_intro_price_div wadc_product_intro_price_div_<?php echo $p_id; ?>">
                                                <span class="i_product_price"><?php echo $price_html; ?></span>
                                            </div>

                                            <div class="mwc_product_additem_div">
                                                <button id="intro_add_item_btn_<?php echo $p_id; ?>" data-add_item="<?php echo $p_id; ?>" class="mwc_product_additem_btn"><?php echo __('Add Item', 'woocommerce'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- button show more -->
                    <div class="mwc_see_more">
                        <button class="btn btn-sm btn-primary btn-link btn-underline"><?= __('See more', 'woocommerce') ?></button>
                    </div>

                </div>
            </div>

<?php
        endif;
    }
}
