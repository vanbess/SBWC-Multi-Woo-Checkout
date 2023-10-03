<?php
if (!trait_exists('ReturnLinkedProdVarDD')) :

    trait ReturnLinkedProdVarDD {

        /**
         * mwc_return_linked_variations_dropdown HTML
         *
         * @param array $args
         * @param array $var_data
         * @return html
         */
        public static function mwc_return_linked_variations_dropdown($args, $var_data, $prod_data) {

            $html = '';

            if (!empty($args)) :

                $product_id       = $args['product_id'];
                $general_settings = get_option('plgfyqdp_save_gnrl_settingsplv');

                if (!$general_settings) :

                    $general_settings = array(
                        'is_hyper' => 'true',
                        'brdractive'      => '#621bff',
                        'brdrinactv'      => '#dddddd',
                        'pdngclr'         => '#ffffff',
                        'bckgrdclr'       => '#f1f1f1',
                        'txtclr'          => '#000000'
                    );

                endif;

                $is_hyper = isset($general_settings['is_hyper']) ? 'true' : 'false';

                if ('true' == $is_hyper) :
                    $is_hyper = ' target="_blank" ';
                else :
                    $is_hyper = '';
                endif;

                $current_id = $product_id;
                $all        = [];
                $sub        = [];
                $is_applied = '';
                $all_rules  = get_option('plgfymao_all_rulesplgfyplv');

                if ('' == $all_rules) :
                    $all_rules = [];
                endif;

                $all_attributes   = wc_get_attribute_taxonomies();

                // loop
                foreach ($all_rules as $key => $value) :

                    $to_be_sent     = $all_rules[$key];
                    $previous_attrs = [];

                    if (isset($to_be_sent['selected_checks_attr'])) :

                        // loop
                        foreach ($to_be_sent['selected_checks_attr'] as $keyi => $valuei) :

                            $attribute_id               = $valuei[4];
                            $new_record_against_attr_id = isset($all_attributes['id:' . $attribute_id]) ? $all_attributes['id:' . $attribute_id] : '';

                            $previous_attrs[] = $valuei[4];

                            $all_rules[$key]['selected_checks_attr'][$keyi][0] = is_object($new_record_against_attr_id) ? $new_record_against_attr_id->attribute_name : '';
                            $all_rules[$key]['selected_checks_attr'][$keyi][3] = is_object($new_record_against_attr_id) ? $new_record_against_attr_id->attribute_label : '';

                        endforeach;

                    endif;

                    // loop
                    foreach ($all_attributes as $key11 => $value11) :

                        if (!in_array($value11->attribute_id, $previous_attrs)) :
                            $new_attribute = array($value11->attribute_name, 'false', 'false', $value11->attribute_label, $value11->attribute_id);
                            $all_rules[$key]['selected_checks_attr'][] = $new_attribute;
                        endif;

                    endforeach;

                endforeach;

                update_option('plgfymao_all_rulesplgfyplv', $all_rules);

                $all_rules = get_option('plgfymao_all_rulesplgfyplv');

                if ('' == $all_rules) :
                    $all_rules = [];
                endif;

                $all = [];

                // loop
                foreach ($all_rules as $key => $val) :

                    if ('true' == $val['plgfyplv_activate_rule']) :

                        if ('Products' == $val['applied_on']) :

                            if (in_array($current_id, $val['apllied_on_ids'])) :
                                $linked          = [];
                                $breakitbab      = true;
                                $linked          = $val['apllied_on_ids'];
                            endif;

                        else :
                            $prod_ids = [];

                            // loop
                            foreach ($val['apllied_on_ids'] as $key0po => $value0po) :

                                $all_prod_ids_q = get_posts(array(
                                    'post_type'   => array('product', 'product_variation'),
                                    'numberposts' => -1,
                                    'post_status' => 'publish',
                                    'fields'      => 'ids',
                                    'tax_query'   => array(
                                        array(
                                            'taxonomy' => 'product_cat',
                                            'terms'    => $value0po,
                                            'operator' => 'IN',
                                        )
                                    )
                                ));

                                // loop
                                foreach ($all_prod_ids_q as $idalp => $valalp) :
                                    $prod_ids[] = $valalp;
                                endforeach;

                                if (in_array($current_id, $all_prod_ids_q)) :

                                    $linked = [];

                                    // loop
                                    foreach ($prod_ids as $keypprr => $valuepprr) :
                                        $linked[] = $valuepprr;
                                    endforeach;

                                    $breakitbab      = true;

                                endif;
                            endforeach;
                        endif;

                        $sub = [];
                        $atr = [];

                        // loop
                        foreach ($val['selected_checks_attr'] as $key => $val) :
                            if ('true' == $val[1]) :
                                $atr[] = $val[0];
                            endif;
                        endforeach;

                        if (isset($breakitbab)) :
                            $sub[] = $linked;
                            $sub[] = $atr;
                            $all[] = $sub;
                            $is_applied = $key;
                            break;
                        endif;
                    endif;
                endforeach;

                if ('-1' > $is_applied) :
                    return;
                endif;

                $attr_slug_linked_prods = [];

                // loop
                foreach ($all[0][1] as $key => $attrib_slug) :

                    $uppersub = [];

                    if (count($all[0][0]) > 0) :
                        $al_grouped_p_idsyyuiop = $all[0][0];
                        $temp_val_of_0 = $al_grouped_p_idsyyuiop[0];
                        $al_grouped_p_idsyyuiop[0] = $current_id;
                        $al_grouped_p_idsyyuiop[] = $temp_val_of_0;
                        $al_grouped_p_idsyyuiop = array_unique($al_grouped_p_idsyyuiop);
                    endif;

                    // loop
                    foreach ($al_grouped_p_idsyyuiop as $key => $applied_on_id_pid) :

                        $product  = wc_get_product($applied_on_id_pid);
                        $innersub = [];
                        $attribs  = $product->get_attribute($attrib_slug);

                        if ('' != $attribs) :
                            $attribs = explode(',', $attribs);
                            $attribs = $attribs[0];
                            $innersub[] = $attribs;
                            $innersub[] = $applied_on_id_pid;
                            $uppersub[] = $innersub;
                        endif;

                    endforeach;

                    $attr_slug_linked_prods[$attrib_slug] = $uppersub;
                endforeach;

                // loop
                foreach ($attr_slug_linked_prods as $attr_slug => $all_linked_products) :

                    $istrue     = false;

                    // loop
                    foreach ($all_rules[$is_applied]['selected_checks_attr'] as $lostkey => $lost_val) :

                        if ($attr_slug == $lost_val[0]) :
                            $istrue = $lost_val[2];
                            break;
                        endif;

                    endforeach;
?>

                    <div class="variation_item">

                        <p class="variation_name"><?php echo __('Color', 'woocommerce') ?>: </p>

                        <div class="attribute-swatch" attribute-index="">
                            <div class="swatchinput">
                                <?php

                                $unique_attrs = [];

                                // loop
                                foreach ($all_linked_products as $keyplugify => $valueplugify) :

                                    $is_out_of_stock = 'false';
                                    $_backorders     = get_post_meta($valueplugify[1], '_backorders', true);
                                    $stock_status    = get_post_meta($valueplugify[1], '_stock_status', true);

                                    // Image swatch for linked products
                                    $product = wc_get_product($valueplugify[1]);

                                    if (!isset($var_data[$valueplugify[1]]) && $product->is_type('variable')) :

                                        $var_arr = [];

                                        // loop
                                        foreach ($product->get_available_variations() as $key => $value) :

                                            $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_option('woocommerce_currency');

                                            // @todo: check if the price defined here is accurate and update it if needed
                                            array_push($var_arr, [
                                                'id'         => $value['variation_id'],
                                                'price'      => isset($prod_data['custom_price'][$value['variation_id']]) ? $prod_data['custom_price'][$value['variation_id']][$current_curr] : '',
                                                'attributes' => $value['attributes'],
                                                'image'      => $value['image']['url']
                                            ]);

                                        endforeach;

                                        $var_data[$valueplugify[1]] = $var_arr;
                                    endif;

                                    // if WC exits and Riode active
                                    if (class_exists('WooCommerce') && defined('RIODE_VERSION')) :

                                        $term_ids = wc_get_product_terms($valueplugify[1], 'pa_color', array('fields' => 'ids'));

                                        // loop
                                        foreach ($term_ids as $term_id) :
                                            $attr_value = get_term_meta($term_id, 'attr_color', true);
                                            $attr_img   = get_term_meta($term_id, 'attr_image', true);
                                        endforeach;

                                    endif;

                                    // if stock status is in stock
                                    if ('instock' == $stock_status) :

                                        $stock_count   = get_post_meta($valueplugify[1], '_stock', true);
                                        $_manage_stock = get_post_meta($valueplugify[1], '_manage_stock', true);
                                        $_backorders   = get_post_meta($valueplugify[1], '_backorders', true);

                                        if ('no' != $_manage_stock && 0 >= $stock_count && 'no' == $_backorders) :
                                            $is_out_of_stock = 'true';
                                        endif;

                                    // if stock status is out of stock
                                    elseif ('outofstock' == $stock_status && 'no' == $_backorders) :
                                        $is_out_of_stock = 'true';
                                    endif;

                                    if ('' != $valueplugify[0]) :

                                        if (!in_array($valueplugify[0], $unique_attrs)) :

                                            $unique_attrs[] = $valueplugify[0];

                                            if ($valueplugify[1] == $current_id) :

                                                if ('true' == $istrue) :

                                                    $image    = wp_get_attachment_image_src(get_post_thumbnail_id($valueplugify[1]), 'single-post-thumbnail');
                                                    $img_srcy = '';

                                                    if ('' == $image) :
                                                        $image = [];
                                                    endif;

                                                    if (0 < count($image) && isset($image[0]) && '' != $image[0]) :
                                                        $img_srcy = $image[0];
                                                    else :
                                                        $img_srcy = plugins_url() . '/products-linked-by-variations-for-woocommerce/Front/Assets/woocommerce-placeholder-plugify.png';
                                                    endif;
                                ?>
                                                    <div class="imgclasssmallactive tooltipplugify" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid green;">
                                                        <img class="child_class_plugify" style="height: 40px;text-align: center;" src="<?php echo filter_var($img_srcy); ?>">
                                                        <div class="tooltiptextplugify">
                                                            <?php echo filter_var($valueplugify[0]); ?>
                                                        </div>
                                                    </div>
                                                    <?php

                                                else :
                                                    if ($attr_value) : ?>
                                                        <label class="wcvaswatchlabel wcvaround linked_product selected" title="<?php _e(filter_var($valueplugify[0]), 'woocommerce'); ?>" data-linked_id="<?php echo $valueplugify[1] ?>" img-src="<?php echo get_the_post_thumbnail_url($valueplugify[1], 'single-post-thumbnail'); ?>" style="background-color:<?php echo sanitize_hex_color($attr_value); ?>; width:32px; height:32px; "></label>
                                                        <?php
                                                    else :

                                                        if ($attr_img) :

                                                            $attr_image = '';
                                                            $attr_image = wp_get_attachment_image_src($attr_img, array(32, 32));

                                                            if ($attr_image) :
                                                                $attr_image = $attr_image[0];
                                                            endif;

                                                            if (!$attr_image) :
                                                                $attr_image = wc_placeholder_img_src(array(32, 32));
                                                            endif;
                                                        ?>
                                                            <div class="imgclasssmallactive tooltipplugify" style="margin: 0 5px;width:35px;height: 35px;border-radius: 50%;overflow: hidden;border: 1px solid green;">
                                                                <img class="child_class_plugify" style="height: 35px;text-align: center;" src="<?php echo filter_var($attr_image); ?>">
                                                                <div class="tooltiptextplugify">
                                                                    <?php echo filter_var($valueplugify[0]); ?>
                                                                </div>
                                                            </div>
                                                        <?php

                                                        else :
                                                        ?>
                                                            <div class="imgclasssmallactive" style="width:auto; border-radius: 2px;padding: 3px;border: 1px solid green;">
                                                                <div class="child_class_plugify" style="text-align: center;padding: 2px 15px;"><?php echo filter_var($valueplugify[0]); ?></div>
                                                            </div>
                                                    <?php
                                                        endif;
                                                    endif;
                                                endif;
                                            else :

                                                $style_cursor = '';
                                                $htmllpluigg  = '';
                                                $is_hyper     = isset($general_settings['is_hyper']) ? $general_settings['is_hyper'] : 'false';

                                                if ('true' == $is_hyper) :
                                                    $is_hyper = ' target="_blank" ';
                                                else :
                                                    $is_hyper = '';
                                                endif;

                                                if ('true' == $is_out_of_stock) :
                                                    $style_cursor = ' cursor:not-allowed; ';
                                                    $htmllpluigg  = ' href="javascript:void(0)" ';
                                                    $is_hyper     = '  ';
                                                endif;

                                                if ('true' == $istrue) :

                                                    $image    = wp_get_attachment_image_src(get_post_thumbnail_id($valueplugify[1]), 'single-post-thumbnail');
                                                    $img_srcy = '';

                                                    if ('' == $image) :
                                                        $image = [];
                                                    endif;

                                                    if (0 < count($image) && isset($image[0]) && '' != $image[0]) :
                                                        $img_srcy = $image[0];
                                                    else :
                                                        $img_srcy = plugins_url() . '/products-linked-by-variations-for-woocommerce/Front/Assets/woocommerce-placeholder-plugify.png';
                                                    endif;

                                                    ?>
                                                    <div class="imgclasssmall tooltipplugify <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid #ddd; <?php echo filter_var($style_cursor); ?>">
                                                        <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($is_hyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>"><img class="child_class_plugify" style="height: 40px;text-align: center;" src="<?php echo filter_var($img_srcy); ?>">
                                                        </a>
                                                        <div class="tooltiptextplugify">
                                                            <?php
                                                            if ('true' == $is_out_of_stock) :
                                                                echo esc_attr_e('Out Of Stock', 'woocommerce');
                                                            else :
                                                                echo filter_var($valueplugify[0]);
                                                            endif;
                                                            ?>
                                                        </div>
                                                    </div>

                                                    <?php
                                                else :
                                                    if ($attr_value) : ?>
                                                        <label class=" wcvaswatchlabel wcvaround linked_product <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" title="<?php _e(filter_var($valueplugify[0]), 'woocommerce'); ?>" img-src="<?php echo get_the_post_thumbnail_url($valueplugify[1], 'single-post-thumbnail'); ?>" data-linked_id="<?php echo $valueplugify[1] ?>" style="background-color:<?php echo sanitize_hex_color($attr_value); ?>; width:32px; height:32px; <?php echo $style_cursor; ?>">
                                                        </label>
                                                        <?php
                                                    else :
                                                        if ($attr_img) :

                                                            $attr_image = '';
                                                            $attr_image = wp_get_attachment_image_src($attr_img, array(32, 32));

                                                            if ($attr_image) :
                                                                $attr_image = $attr_image[0];
                                                            endif;

                                                            if (!$attr_image) :
                                                                $attr_image = wc_placeholder_img_src(array(32, 32));
                                                            endif;
                                                        ?>
                                                            <div class="linked_product imgclasssmall tooltipplugify <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="margin: 0 5px;width:35px;height:35px;border-radius: 50%;overflow: hidden;border: 1px solid #ddd; <?php echo filter_var($style_cursor); ?>">

                                                                <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($is_hyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>">
                                                                    <img class="child_class_plugify" style="height: 35px;text-align: center;" src="<?php echo filter_var($attr_image); ?>">
                                                                </a>

                                                                <div class="tooltiptextplugify">
                                                                    <?php
                                                                    if ('true' == $is_out_of_stock) :
                                                                        echo esc_attr_e('Out Of Stock', 'woocommerce');
                                                                    else :
                                                                        echo filter_var($valueplugify[0]);
                                                                    endif;
                                                                    ?>
                                                                </div>
                                                            </div>

                                                        <?php
                                                        else :
                                                        ?>
                                                            <div class="linked_product imgclasssmall <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid #ddd; background-color: <?php echo sanitize_hex_color($attr_value);
                                                                                                                                                                                                                                                            echo filter_var($style_cursor); ?>">
                                                                <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($is_hyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>">
                                                                    <div class="child_class_plugify" style="text-align: center;padding: 2px 15px;"><?php echo filter_var($valueplugify[0]); ?>
                                                                </a>
                                                            </div>
                            </div>

<?php
                                                        endif;
                                                    endif;
                                                endif;
                                            endif;
                                        endif;
                                    endif;
                                endforeach; ?>
                        </div>
                    </div>
                    </div>
<?php
                endforeach;
            endif;

            return $html;
        }
    }
endif;
