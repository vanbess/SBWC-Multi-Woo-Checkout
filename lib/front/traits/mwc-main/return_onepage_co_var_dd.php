<?php
if (!trait_exists('ReturnOnePageCoVarDD')) :

    trait ReturnOnePageCoVarDD {

        /**
         * return_mwc_onepage_checkout_variation_dropdown HTML
         *
         * @param array $args
         * @return html
         */
        public static function return_mwc_onepage_checkout_variation_dropdown($args = []) {

            $html = '';

            if ($args['options']) :

                $product_id            = $args['product_id'];
                $options               = $args['options'];
                $attribute_name        = $args['attribute_name'];
                $default_option        = $args['default_option'];
                $disable_woo_swatches  = !empty($args['disable_woo_swatches']) ? $args['disable_woo_swatches'] : 'no';
                $var_data              = isset($args['var_data']) ? $args['var_data'] : null;
                $name                  = isset($args['name']) ? $args['name'] : '';
                $id                    = isset($args['id']) ? $args['id'] : '';
                $class                 = isset($args['class']) ? $args['class'] : '';
                $type                  = isset($args['type']) ? $args['type'] : 'dropdown';

                $_hidden = false;

                // retrieve product object
                $product = wc_get_product($product_id);

                // retrieve ALG pricing for current currency, for each variation/child and push to array for ref on dd select
                $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_woocommerce_currency();
                $default_curr = get_woocommerce_currency();

                // uncomment to debug
                // $current_curr = 'USD';

                $ex_rate              = get_option("alg_currency_switcher_exchange_rate_USD_$current_curr") ? get_option("alg_currency_switcher_exchange_rate_USD_$current_curr") : 1;
                $children             = !empty($product->get_children()) ? $product->get_children() : false;
                $alg_currency_pricing = [];

                if ($children !== false) :
                    foreach ($children as $vid) :
                        $product_price = get_post_meta($vid, '_regular_price', true) ? get_post_meta($vid, '_regular_price', true) : get_post_meta($vid, '_price', true);
                        $alg_currency_pricing[$vid] = get_post_meta($vid, '_alg_currency_switcher_per_product_regular_price_' . $current_curr, true) ? get_post_meta($vid, '_alg_currency_switcher_per_product_regular_price_' . $current_curr, true) : $product_price * $ex_rate;
                    endforeach;
                endif;

                file_put_contents(MWC_PLUGIN_DIR . 'alg_pricing.txt', print_r($alg_currency_pricing, true));

                // load label woothumb(Wooswatch)
                $woothumb_products = get_post_meta($product_id, '_coloredvariables', true);

                // get woothumb attribute name
                $woothumb = !empty($woothumb_products[$attribute_name]) ? $woothumb_products[$attribute_name] : '';

                if ($var_data && !empty($woothumb_products[$attribute_name])) :

                    // get woothumb attribute name
                    $woothumb = $woothumb_products[$attribute_name];

                    $taxonomies = array($attribute_name);
                    $args = array(
                        'hide_empty' => 0
                    );

                    $newvalues = get_terms($taxonomies, $args);

                    // woothumb type color of image
                    if ($disable_woo_swatches != 'yes' && $woothumb['display_type'] == 'colororimage') :

                        // hidden dropdown
                        $_hidden = true;

                        $extra = array(
                            "display_type" => $woothumb['display_type']
                        );

                        if (class_exists('wcva_swatch_form_fields')) :
                            $swatch_fields = new wcva_swatch_form_fields();
                            $swatch_fields->wcva_load_colored_select($product, $attribute_name, $options, $woothumb_products, $newvalues, $default_option, $extra, 2);
                        else :
                            $html .= '<div class="attribute-swatch" attribute-index>
                        <div class="swatchinput">';
                            foreach ($options as $key => $option) :

                                // get slug attribute
                                $term_obj  = get_term_by('slug', $option, $attribute_name);
                                if ($term_obj) :
                                    $option = $term_obj->slug;
                                endif;

                                // show option image
                                if ($woothumb['values'][$option]['type'] == 'Image') :
                                    // get image option
                                    $label_image = wp_get_attachment_thumb_url($woothumb['values'][$option]['image']);

                                    $html .= '<label selectid="' . $attribute_name . '" class="attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . ' wcvaswatchlabel  wcvaround" data-option="' . $option . '" style="background-image:url(' . $label_image . '); width:32px; height:32px; "></label>';
                                // show option color
                                elseif ($woothumb['values'][$option]['type'] == 'Color') :
                                    // get color option
                                    $label_color = $woothumb['values'][$option]['color'];

                                    $html .= '<label selectid="' . $attribute_name . '" class="attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . ' wcvaswatchlabel  wcvaround" data-option="' . $option . '" style="background-color:' . $label_color . '; width:32px; height:32px; "></label>';
                                // show option text block
                                else :
                                    // get text block option
                                    $label_text = $woothumb['values'][$option]['textblock'];
                                    $html .= '<label selectid="' . $attribute_name . '" class="attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . ' wcvaswatchlabel  wcvaround" data-option="' . $option . '" style="width:32px; height:32px; ">' . $label_text . '</label>';
                                endif;
                            endforeach;
                            $html .= '</div></div>';
                        endif;
                    endif;
                // woothumb type variation image
                elseif ($disable_woo_swatches != 'yes' && !empty($woothumb) && $woothumb['display_type'] == 'variationimage') :
                    // hidden dropdown
                    $_hidden = true;

                    $html .= '<div class="select_woothumb">';
                    foreach ($options as $key => $option) :

                        // get slug attribute
                        $term_obj  = get_term_by('slug', $option, $attribute_name);
                        if ($term_obj) :
                            $option = $term_obj->slug;
                        endif;

                        $html .= '<label class="label_woothumb attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . '"
                        data-option="' . $option . '" style="background-image:url(' . $var_data[$key]['image'] . ');  width:40px; height:40px; "></label>';
                    endforeach;
                    $html .= '</div>';
                endif;


                // default dropdown

                // add post_id ACF
                add_filter('acf/pre_load_post_id', function () use ($product_id) {
                    return $product_id;
                }, 1, 2);

                // load select option
                if ('dropdown' === $type) :

                    $html .= '<select data-def-currency="' . get_woocommerce_currency() . '" data-curr-symbol="' . get_woocommerce_currency_symbol($current_curr) . '" data-currency="' . $current_curr . '" data-ex-rate="' . $ex_rate . '" data-alg-pricing="' . base64_encode(json_encode($alg_currency_pricing)) . '" class="' . $class . '" name="" data-variations="' . base64_encode(json_encode($product->get_available_variations())) . '" data-attribute_name="attribute_' . $attribute_name . '" ' . (($_hidden) ? 'style="display:none"' : '') . '>';

                    $options = wc_get_product_terms($product_id, $attribute_name);

                    foreach ($options as $key => $option) :
                        $html .= '<option value="' . $option->slug . '" ' . (($default_option == $option->slug) ? 'selected' : '') . '>' . apply_filters('woocommerce_variation_option_name', $option->name) . '</option>';
                    endforeach;

                    $html .= '</select>';

                else :

                    $options = $product->get_variation_attributes()[$attribute_name];
                    $html .= riode_wc_product_listed_attributes_html($attribute_name, $options, $product, 'label', true);

                endif;

            endif;
            return $html;
        }
    }

endif;
