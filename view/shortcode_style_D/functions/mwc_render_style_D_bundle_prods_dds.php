<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Render style D bundle products dropdowns
 *
 * @param array $prod_data
 * @param array $var_data
 * @param string $current_curr
 * @return void
 */
function mwc_render_style_D_bundle_prods_dds($prod_data, $current_curr)
{
    $_index = 1;

    foreach ($prod_data['prod'] as $i => $i_prod) :

        $p_id     = $i_prod['id'];
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
            <div class="c_prod_item" data-type="mwc_bun_discount" has-size-chart="<?php echo $has_size_chart; ?>" data-id="<?php echo ($p_id) ?>" <?= (!$prod_obj->is_type('variable')) ? 'hidden' : '' ?>>

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

?>