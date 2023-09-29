<?php

class MWC
{

    private static $initiated = false;
    public static $mwc_products_variations = array();
    public static $mwc_products_variations_prices = array();
    public static $addon_products = '';
    public static $mwc_product_variations = array();

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        self::$initiated = true;

        add_action('wp_enqueue_scripts', array('MWC', 'load_resources'));

        // action ajax add products to cart
        add_action('wp_ajax_mwc_add_to_cart_multiple', array('MWC', 'mwc_add_to_cart_multiple'));
        add_action('wp_ajax_nopriv_mwc_add_to_cart_multiple', array('MWC', 'mwc_add_to_cart_multiple'));

        // action get price summary table
        add_action('wp_ajax_mwc_get_price_summary_table', array('MWC', 'mwc_get_price_summary_table'));
        add_action('wp_ajax_nopriv_mwc_get_price_summary_table', array('MWC', 'mwc_get_price_summary_table'));

        // action get price variation product mwc
        add_action('wp_ajax_mwc_get_price_variation_product', array('MWC', 'mwc_get_price_variation_product'));
        add_action('wp_ajax_nopriv_mwc_get_price_variation_product', array('MWC', 'mwc_get_price_variation_product'));

        // action get price mwc package
        add_action('wp_ajax_mwc_get_price_package', array('MWC', 'mwc_get_price_package'));
        add_action('wp_ajax_nopriv_mwc_get_price_package', array('MWC', 'mwc_get_price_package'));
        

        add_action('woocommerce_update_cart_action_cart_updated', array('MWC', 'on_action_cart_updated'), 20, 1);
        add_action('woocommerce_cart_calculate_fees', array('MWC', 'add_calculate_bundle_fee'), PHP_INT_MAX);
        add_action('woocommerce_before_calculate_totals', array('MWC', 'add_calculate_product_addon_fee'), 9999);

        // action ajax add products to cart
        add_action('wp_ajax_mwc_update_addon_product_statistics', array(__CLASS__, 'mwc_update_addon_product_statistics'));
        add_action('wp_ajax_nopriv_mwc_update_addon_product_statistics', array(__CLASS__, 'mwc_update_addon_product_statistics'));

        // action add referer to order note
        add_action('woocommerce_order_status_processing', array(__CLASS__, 'add_referer_url_order_note'), 10, 1);

        // action add mwc addon id to order items
        add_action('woocommerce_add_order_item_meta', array(__CLASS__, 'mwc_add_addon_id_in_order_items_meta'), 10, 3 );
    }

    public static function plugin_activation()
    {
    }  

    // calculate bundle discount fee
    public static function add_calculate_bundle_fee($cart)
    {
        $bundle_id = null;
        $cart_prod = [];
        $cart_prod_post_id = [];
        $cart_qty = 0;
        $subtotal = 0;
		$is_mwc = false;

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['multi_woo_checkout']) && isset($cart_item['product_id']) && isset($cart_item['quantity'])) {
                // get bundle mwc id
                $bundle_id = $cart_item['bundle_id'];
                // get total qty
                $cart_qty += $cart_item['quantity'];
                // get subtotal cart
                $subtotal += $cart_item['data']->get_price() * $cart_item['quantity'];
                if (isset($cart_prod[$cart_item['product_id']])) {
                    $cart_prod[$cart_item['product_id']] += $cart_item['quantity'];
                    $cart_prod_post_id[] = $cart_item['mwc_prod_post_id'];
                }
                else {
                    $cart_prod[$cart_item['product_id']] = $cart_item['quantity'];
                    $cart_prod_post_id[] = $cart_item['mwc_prod_post_id'];
                }
				
				$is_mwc = true;
            }
        }
		
		if ($is_mwc){
			// current currency
			$current_curr = get_woocommerce_currency();

			$bundle_selection = get_post_meta($bundle_id, 'product_discount', true);
			$bundle_selection = is_array($bundle_selection) ? $bundle_selection : json_decode($bundle_selection, true);
					
			//file_put_contents("/home/nordace/web/nordace.com/public_html/dlogs/mwc.log", "\nDiscount: " . print_r($bundle_selection, true) . "\n", FILE_APPEND);

			// apply discount FREE
			if ($bundle_selection['selValue'] == 'free') {
				if ($cart_qty >= ($bundle_selection['selValue_free']['quantity'] + $bundle_selection['selValue_free_prod']['quantity'])) {
					$free_prod = $bundle_selection['selValue_free_prod'];
					if (wc_get_product($free_prod['post']['id'])) {

						// get custom price mwc product
						if(isset($bundle_selection['custom_price'][end($cart_prod_post_id)][$current_curr])) {
							$free_price = $bundle_selection['custom_price'][end($cart_prod_post_id)][$current_curr];
						}else {
							$free_price = wc_get_product($free_prod['post']['id'])->get_price();
						}

						$discount = $free_price * $free_prod['quantity'];
					}
					if ($discount > 0) {
						$disc_name = sprintf(__('Buy %s + Get %d FREE', 'mwc'), $bundle_selection['selValue_free']['quantity'], $free_prod['quantity']);
						$cart->add_fee($disc_name, -$discount, true);
					}
				}
			}
			// apply discount OFF
			elseif ($bundle_selection['selValue'] == 'off') {
				if ($cart_qty >= $bundle_selection['selValue_off']['quantity']) {
					$discount = ($subtotal * $bundle_selection['selValue_off']['cupon']) / 100;
					
					//file_put_contents("/home/nordace/web/nordace.com/public_html/dlogs/mwc.log", "apply discount OFF: " . $bundle_selection['selValue_off']['cupon'] . "\n", FILE_APPEND);
					
					if ($discount > 0) {
						$disc_name = sprintf(__('Buy %s + Get %d&#37; Off', 'mwc'), $bundle_selection['selValue_off']['quantity'], $bundle_selection['selValue_off']['cupon']);
						$cart->add_fee($disc_name, -$discount, true);
						foreach ( WC()->cart->get_coupons() as $code => $coupon ){
							WC()->cart->remove_coupon( $code );
						}
					}
				}
			}
			// apply discount Bundle products
			else {

				$bun_tt_qty = count($bundle_selection->selValue_bun->post);

				if ($cart_qty >= $bun_tt_qty && $bundle_selection['discount_percentage'] > 0) {
					//file_put_contents("/home/nordace/web/nordace.com/public_html/dlogs/mwc.log", "Bundle Discount Percentage: " . $bundle_selection['discount_percentage'] . "\n", FILE_APPEND);
					
					$discount = round($subtotal * $bundle_selection['discount_percentage'] / 100, 2);
					
					//file_put_contents("/home/nordace/web/nordace.com/public_html/dlogs/mwc.log", "Bundle Discount Subtotal: " . $discount . "\n\n", FILE_APPEND);
					
					if ($discount > 0) {
						$disc_name = sprintf(__('Discount bundle %d&#37;', 'mwc'), round($bundle_selection['discount_percentage'], 0));
						$cart->add_fee($disc_name, -$discount, true);
					}
				}
			}
		}
    }

    // calculate price addon product when has discount
    public static function add_calculate_product_addon_fee($cart)
    {
        // This is necessary for WC 3.0+
        if ( is_admin() && ! defined( 'DOING_AJAX' ) )
            return;

        // Avoiding hook repetition (when using price calculations for example)
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
            return;

        // current currency
        $current_curr = get_woocommerce_currency();

        // get cart item bundle
        $cart_item_bun_arr = [];

        foreach ($cart->get_cart() as $cart_item) {

            // apply price addon product
            if (isset($cart_item['mwc_addon'])) {
                // get MWC addon id
                $addon_id = $cart_item['mwc_addon_id'];
                // get discount percentage addon product
                $addon_discount = get_post_meta($addon_id, 'percentage_discount', true);
                if($addon_discount) {
                    // get regular price default currency
					$addon_product_id = $cart_item['data']->get_id();
					$addon_product = wc_get_product($addon_product_id);

					$addon_price = '';

					if($addon_product->is_on_sale()){
						$addon_price = $addon_product->get_sale_price();
					} else {
						$addon_price = $addon_product->get_regular_price();
					}
					
                    //$reg_price = get_post_meta( $cart_item['data']->get_id(), '_regular_price', true);
                    // calculator price after discount
                    $discounted_price = $addon_price - ($addon_discount * $addon_price) / 100;
                    // apply discounted price
                    $cart_item['data']->set_price($discounted_price);
                }
            }
			
			
            //if(isset($cart_item['multi_woo_checkout'])) {
			//	file_put_contents("/home/nordace/web/nordace.com/public_html/dlogs/mwc.log", time() . " Price Whack \n", FILE_APPEND);
			//}

            // apply price bundle option products
			/*
            if(isset($cart_item['multi_woo_checkout'])) {

                $mwc_bundle_id = $cart_item['bundle_id'];
				
				if ($mwc_bundle_id){
					// set price regular
					$bundle_product_id = $cart_item['data']->get_id();
					$bundle_product = wc_get_product($bundle_product_id);

					$bundle_price = '';

					if($bundle_product->is_on_sale()){
						$bundle_price = $bundle_product->get_sale_price();
					} else {
						$bundle_price = $bundle_product->get_regular_price();
					}
					
					//$cart_item['data']->set_price(get_post_meta( $cart_item['data']->get_id(), '_regular_price', true));
					$cart_item['data']->set_price($bundle_price);

					// get data bundle option
					$bundle_opt = get_post_meta($cart_item['bundle_id'], 'product_discount', true);
					// $bundle_opt = json_decode($bundle_opt, true);

					if(isset($bundle_opt['custom_price'][$cart_item['mwc_prod_post_id']][$current_curr])) {
						// get price rate currency
						$curr_rate = 1;
						if (function_exists('alg_wc_cs_get_currency_exchange_rate')) {
							$curr_rate = alg_wc_cs_get_currency_exchange_rate($current_curr);
						}
						$cart_item['data']->set_price($bundle_opt['custom_price'][$cart_item['mwc_prod_post_id']][$current_curr] / $curr_rate);
					}


					// get price custom bundle mwc type bun
					if($bundle_opt['selValue'] == 'bun') {

						if (
							isset($bundle_opt['selValue_bun']['price_currency']) && current($bundle_opt['selValue_bun']['price_currency']) > 0 || 
							isset($bundle_opt['selValue_bun']['price_currency'][$current_curr]) && $bundle_opt['selValue_bun']['price_currency'][$current_curr] > 0
						) {

							if (!isset($cart_item_bun_arr[$mwc_bundle_id])) {
								$bun_total_price = current($bundle_opt['selValue_bun']['price_currency']);
								// get price rate currency
								$curr_rate = 1;
								if (function_exists('alg_wc_cs_get_currency_exchange_rate')) {
									$curr_rate = alg_wc_cs_get_currency_exchange_rate($current_curr);
								}
								if(isset($bundle_opt['selValue_bun']['price_currency'][$current_curr]) && $bundle_opt['selValue_bun']['price_currency'][$current_curr] > 0) {
									$bun_total_price = $bundle_opt['selValue_bun']['price_currency'][$current_curr] / $curr_rate;
								} else {
									if (function_exists('alg_wc_cs_get_currency_exchange_rate')) {
										$bun_total_price = $bun_total_price;
									}
								}
								
								$cart_item_bun_arr[$mwc_bundle_id] = [
									'cart_items' => [['product' => $cart_item['data'], 'quantity' => $cart_item['quantity']]],
									'custom_bun_price' => $bun_total_price,
									'total_quantity' => $cart_item['quantity']
								];
							} else {
								$cart_item_bun_arr[$mwc_bundle_id]['cart_items'][] = ['product' => $cart_item['data'], 'quantity' => $cart_item['quantity']];
								$cart_item_bun_arr[$mwc_bundle_id]['total_quantity'] += $cart_item['quantity'];

							}

						}
						
					}
				}
            }
			*/
        }

        // fix price item product
        if(!empty($cart_item_bun_arr)) {
            foreach ($cart_item_bun_arr as $key => $mwc_item) {

                if(isset($mwc_item['cart_items']) && isset($mwc_item['custom_bun_price']) && isset($mwc_item['total_quantity'])) {
                    // get unit price mwc cart
                    $unit_price = $mwc_item['custom_bun_price'] / $mwc_item['total_quantity'];

                    foreach ($mwc_item['cart_items'] as $item) {
                        $item['product']->set_price($unit_price * $item['quantity']);
                    }

                }
            }
        }
    }

    //remove discount when update cart qty
    public static function on_action_cart_updated($cart_updated)
    {
        if ($cart_updated) {
            $cart_qty = WC()->cart->get_cart_contents_count();
            $bundle_id = null;
            $mwc_qty = 0;
			$is_mwc = false;
			
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (isset($cart_item['multi_woo_checkout'])) {
                    $bundle_id = $cart_item['bundle_id'];
                    $mwc_qty = $cart_item['quantity'];
					$is_mwc = true;
                }
                // else
                //     return;
            }
			
			if ($is_mwc){
				$bundle_selection = json_decode(get_post_meta($bundle_id, 'product_discount', true));

				if (isset($bundle_selection)) {
					if ($bundle_selection->selValue == 'free') {
						if ($cart_qty >= ($bundle_selection->selValue_free->quantity + $bundle_selection->selValue_free_prod->quantity)) {
							return false;
						}
					} elseif ($bundle_selection->selValue == 'off') {
						if ($mwc_qty == $bundle_selection->selValue_off->quantity) {
							return false;
						}
					} else {
						if ($mwc_qty == count($bundle_selection->selValue_bun->post)) {
							return false;
						}
					}
					remove_action('woocommerce_cart_calculate_fees', array('MWC', 'add_calculate_bundle_fee'), PHP_INT_MAX);
				}
			}
        }
    }

    public static function load_resources()
    {
        $req = array('jquery');
        wp_enqueue_style('mwc_style', MWC_PLUGIN_URL . 'resources/style/front_style.css', array(), MWCVersion . time(), 'all');

        wp_enqueue_script('mwc_front_script_js', MWC_PLUGIN_URL . 'resources/js/front_js.js', $req, time(), true);

        global $woocommerce;
        $cart_url = '/cart/';
        $checkout_url = '/checkout/';
        if (!empty($woocommerce)) {
            $cart_url = wc_get_cart_url();
            $checkout_url = wc_get_checkout_url();
        }

        wp_localize_script(
            'mwc_front_script_js',
            'mwc_infos',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'home_url' => home_url(),
                'cart_url' => $cart_url,
                'checkout_url' => $checkout_url
            )
        );
    }

    //Similiar function like wc_dropdown_variation_attribute_options(), which are return view instead of print
    public static function return_wc_dropdown_variation_attribute_options($args = array())
    {
        $args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), array(
            'options'          => false,
            'attribute'        => false,
            'product'          => false,
            'selected'         => false,
            'n_item'           => false,
            'img_variations'   => '',
            'name'             => '',
            'id'               => '',
            'class'            => '',
            'show_option_none' => __('Choose an option', 'woocommerce'),
        ));

        $options               = $args['options'];
        $product               = $args['product'];
        $attribute             = $args['attribute'];
        $name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title($attribute);
        $id                    = $args['id'] ? $args['id'] : sanitize_title($attribute);
        $class                 = $args['class'];
        $show_option_none      = $args['show_option_none'] ? true : false;
        $show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __('Choose an option', 'woocommerce'); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

        if (empty($options) && !empty($product) && !empty($attribute)) {
            $attributes = $product->get_variation_attributes();
            $options    = $attributes[$attribute];
        }

        $html  = '<select class="' . esc_attr($class) . ' mwc_product_attribute sel_product_' . esc_attr($id) . '" name="i_variation_' . esc_attr($name) . '" data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute)) . '" data-item="' . $args['n_item'] . '">';

        if (!empty($options)) {
            if ($product && taxonomy_exists($attribute)) {
                // Get terms if this is a taxonomy - ordered. We need the names too.
                $terms = wc_get_product_terms($product->get_id(), $attribute, array(
                    'fields' => 'all',
                ));

                foreach ($terms as $i => $term) {
                    if (in_array($term->slug, $options, true)) {
                        $html .= '<option data-item="' . $args['n_item'] . '" data-img="' . $args['img_variations'][$i] . '" value="' . esc_attr($term->slug) . '" ' . selected(sanitize_title($args['selected']), $term->slug, false) . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '</option>';
                    }
                }
            } else {
                foreach ($options as $option) {
                    // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                    $selected = sanitize_title($args['selected']) === $args['selected'] ? selected($args['selected'], sanitize_title($option), false) : selected($args['selected'], $option, false);
                    $html    .= '<option data-item="' . $args['n_item'] . '" value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $option)) . '</option>';
                }
            }
        }

        $html .= '</select>';

        return apply_filters('woocommerce_dropdown_variation_attribute_options_html', $html, $args); // WPCS: XSS ok.
    }

    public static function return_mwc_onepage_checkout_variation_dropdown($args = [])
    {
        $html = '';

        if ($args['options']) {
            $product_id            = $args['product_id'];
            $options               = $args['options'];
            $attribute_name        = $args['attribute_name'];
            $default_option        = $args['default_option'];
            $disable_woo_swatches  = $args['disable_woo_swatches'];
            $var_data              = isset($args['var_data']) ? $args['var_data'] : null;
            $name                  = isset($args['name']) ? $args['name'] : '';
            $id                    = isset($args['id']) ? $args['id'] : '';
            $class                 = isset($args['class']) ? $args['class']: '';
            
            $_hidden = false;

            // 
            $product = wc_get_product($product_id);

            // load label woothumb(Wooswatch)
            $woothumb_products = get_post_meta($product_id, '_coloredvariables', true);
            if ($var_data && !empty($woothumb_products[$attribute_name])) {

                // get woothumb attribute name
                $woothumb = $woothumb_products[$attribute_name];

                $fields   = new wcva_swatch_form_fields();

                $taxonomies = array($attribute_name);
                            $args = array(
                               'hide_empty' => 0
                            );
                    
                $newvalues = get_terms( $taxonomies, $args);

                // woothumb type color of image
                if ($disable_woo_swatches != 'yes' && $woothumb['display_type'] == 'colororimage') {
                    // hidden dropdown
                    $_hidden = true;

                    $extra = array(
                        "display_type" => $woothumb['display_type']
                    );

                    $fields->wcva_load_colored_select($product, $attribute_name, $options, $woothumb_products, $newvalues, $default_option, $extra, 2);


                    // $html .= '<div class="select_woothumb">';
                    // foreach ($options as $key => $option) {

                    //     // get slug attribute
                    //     $term_obj  = get_term_by('slug', $option, $attribute_name);
                    //     if ($term_obj) {
                    //         $option = $term_obj->slug;
                    //     }

                    //     // show option image
                    //     if ($woothumb['values'][$option]['type'] == 'Image') {
                    //         // get image option
                    //         $label_image = wp_get_attachment_thumb_url($woothumb['values'][$option]['image']);

                    //         $html .= '<label class="label_woothumb attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . '"
                    //         data-option="' . $option . '" style="background-image:url(' . $label_image . ');  width:40px; height:40px; "></label>';
                    //     }
                    //     // show option color
                    //     elseif ($woothumb['values'][$option]['type'] == 'Color') {
                    //         // get color option
                    //         $label_color = $woothumb['values'][$option]['color'];
                    //         $html .= '<label class="label_woothumb attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . '"
                    //         data-option="' . $option . '" ' . (isset($label_color) ? 'style="background-color:'.$label_color : 'style="background-color:#ffffff') . ';  width:40px; height:40px; "></label>';
                    //     }
                    //     // show option text block
                    //     else {
                    //         // get text block option
                    //         $label_text = $woothumb['values'][$option]['textblock'];
                    //         $html .= '<label class="label_woothumb attribute_' . $attribute_name . '_' . $option . ' label_text ' . (($default_option == $option) ? 'selected' : '') . '"
                    //         data-option="' . $option . '">' . $label_text . '</label>';
                    //     }
                    // }
                    // $html .= '</div>';
                }
                // woothumb type variation image
                elseif ($disable_woo_swatches != 'yes' && $woothumb['display_type'] == 'variationimage') {
                    // hidden dropdown
                    $_hidden = true;

                    $html .= '<div class="select_woothumb">';
                    foreach ($options as $key => $option) {

                        // get slug attribute
                        $term_obj  = get_term_by('slug', $option, $attribute_name);
                        if ($term_obj) {
                            $option = $term_obj->slug;
                        }

                        $html .= '<label class="label_woothumb attribute_' . $attribute_name . '_' . $option . ' ' . (($default_option == $option) ? 'selected' : '') . '"
                        data-option="' . $option . '" style="background-image:url(' . $var_data[$key]['image'] . ');  width:40px; height:40px; "></label>';
                    }
                    $html .= '</div>';
                }
                // default dropdown
            }

            // add post_id ACF
            add_filter( 'acf/pre_load_post_id', function() use($product_id) {
                return $product_id;
            }, 1, 2 );

            // load select option
            $html .= '<select id="' . $id . '" class="' . $class . '" name="' . $name . '" data-attribute_name="attribute_' . $attribute_name . '" ' . (($_hidden) ? 'style="display:none"' : '') . '>';
            $options = wc_get_product_terms( $product_id, $attribute_name);
            foreach ($options as $key => $option) {
                $html .= '<option value="' . $option->slug . '" ' . (($default_option == $option->slug) ? 'selected' : '') . '>' . apply_filters( 'woocommerce_variation_option_name', $option->name ) . '</option>';
            }
            $html .= '</select>';
        }

        return $html;
    }

    // Ajax Requests add to cart
    function mwc_add_to_cart_multiple()
    {
        if (!(isset($_REQUEST['action']) || 'mwc_add_to_cart_multiple' != $_POST['action']))
            return;

        $return = array(
            'status' => false,
            'html' => '<h3> There is no any Product request!!! </h3>'
        );

        $mwc_first_check_ajax = $_POST['mwc_first_check_ajax'];
        $mwc_dont_empty_cart = isset($_POST['mwc_dont_empty_cart']) ? $_POST['mwc_dont_empty_cart'] : 0;

        $mwc_products = $_POST['add_to_cart_items_data']['products'];
        $mwc_addon_products = $_POST['addon_products']['products'];

        if ($mwc_first_check_ajax) {
            $return = array(
                'status' => true,
                'html' => '<h3> 1 </h3>'
            );
        }

        // remove all cart
        // if (!$mwc_dont_empty_cart)
        //     WC()->cart->empty_cart();

        //remove all mwc products and mwc_addon product
        if (!empty(WC()->cart) && count(WC()->cart->cart_contents)) {
            WC()->cart->empty_cart();
        }

        //remove all mwc products and mwc_addon product
        if (!empty(WC()->cart) && count(WC()->cart->cart_contents)) {
            foreach (WC()->cart->cart_contents as $cart_item_key => $cart_item) {
                if (isset($cart_item['multi_woo_checkout']) || isset($cart_item['mwc_addon'])) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
        }

        if ($mwc_products || $mwc_addon_products) {

            if (!session_id()) {
                session_start();
            }

            // add mwc_bundle products to cart
            $mwc_bundle_var_data = ['multi_woo_checkout' => 'true', 'bundle_id' => $_POST['bundle_id']];
            self::mwc_add_to_cart($mwc_products, $mwc_bundle_var_data);

            // add mwc_addon products to cart
            $mwc_addon_var_data = ['mwc_addon' => 'true'];
            self::mwc_add_to_cart($mwc_addon_products, $mwc_addon_var_data);

            $return = array(
                'status' => true,
                'shipping' => WC()->cart->get_cart_shipping_total(),
                'html' => '<h3>Product added!!! </h3>'
            );
        }

        echo json_encode($return);
        exit;
    }

    // fuction add product to cart
    private static function mwc_add_to_cart($products, $bundle_selection_data)
    {
        foreach ($products as $product_data) {
            $product_id = $product_data['product_id'];
            $variation_id = $product_data['variation_id'];
            $variations_vals = $product_data['i_product_attribute'];
            $c_product = wc_get_product($product_id);

            if ($c_product->is_type('variable')) {
                if (empty($variations_vals))
                    $variations_vals = array();

                $product = new WC_Product_Variable($product_id);

                if ($product_data['qty'] > 1) {
                    setcookie("woocommerce_want_multiple", "yes", time() +  DAY_IN_SECONDS, "/", COOKIE_DOMAIN);
                }

                $variations = $product->get_available_variations();
                foreach ($variations as $variation) {
                    if(!array_diff($variations_vals, $variation['attributes'])) {
                        $variation_id = $variation['variation_id'];
                        $variations_vals = $variation['attributes'];
                    }
                }
            }

            // add variation id
            if ($product_data['variation_id']) {
                $bundle_selection_data['mwc_prod_post_id'] = $product_data['variation_id'];
            } else {
                $bundle_selection_data['mwc_prod_post_id'] = $product_data['product_id'];
            }

            // check addon product id
            if ($product_data['mwc_addon_id']) {
                $bundle_selection_data['mwc_addon_id'] = $product_data['mwc_addon_id'];
            }

            $variation_val = ($variations_vals) ? $variations_vals : '';

            if (!WC()->session->has_session()) {
                WC()->session->set_customer_session_cookie(true);
            }
			
			// Check if the item is already in the cart
			foreach( WC()->cart->get_cart() as $cart_item ) {
				if($cart_item['variation_id'] === $variation_id){
					// Get the existing quantity and add the new quantity
					$new_quantity = $product_data['qty'] + $cart_item['quantity'];
					$product_data['qty'] = $new_quantity; // Update quantity in product_data
					// remove the item from cart first
					WC()->cart->remove_cart_item($cart_item['key']);
				}
			}

			//file_put_contents("/home/nordace/web/nordace.com/public_html/dlogs/mwc.log", time() . " Product ID: " . $product_id . " (Var ID: " . $variation_id . ") x " . $product_data['qty'] . " - " . print_r($variation_val, true) . "\n", FILE_APPEND);

			// then add it back with new quantity
			WC()->cart->add_to_cart($product_id, intval($product_data['qty']), $variation_id, $variation_val, $bundle_selection_data);
            //unset($variation_attributes);
            // } else {
            //     if (!WC()->session->has_session()) {
            //         WC()->session->set_customer_session_cookie(true);
            //     }
            //     WC()->cart->add_to_cart(intval($product_id), intval($product_data['qty']), 0, [], $bundle_selection_data);
            // }
        }
    }

    // function add mwc addon id to order items
    public static function mwc_add_addon_id_in_order_items_meta( $item_id, $values, $cart_item_key ) {
        if ( isset($values['mwc_addon_id']) ) {
            // add MWC addon id to order items
            wc_add_order_item_meta($item_id, 'mwc_addon_id', $values['mwc_addon_id'] );
        }
    }


    // function get price summary table
    public static function mwc_get_price_summary_table()
    {
        if (!(isset($_REQUEST['action']) || 'mwc_get_price_summary_table' != $_POST['action']))
            return;

        $return = array(
            'status' => false,
            'html' => 'no data!!!'
        );

        $price_list = $_GET['price_list'];
        if ($price_list) {
            $p_total = 0;
            $html = '<table>';
            foreach ($price_list as $i_price) {
                if ($i_price['label'] && $i_price['price']) {
                    if ($i_price['sum']  == 1) {
                        $html .= '<tr>';
                        $html .= '<td>' . $i_price['label'] . '</td>';
                        $html .= '<td style="text-align: right;">' . wc_price($i_price['price']) . '</td>';
                        $html .= '</tr>';

                        $p_total += $i_price['price'];
                    } else {
                        $html .= '<tr>';
                        $html .= '<td>' . $i_price['label'] . '</td>';
                        $html .= '<td style="text-align: right; text-decoration: line-through;">' . wc_price($i_price['price']) . '</td>';
                        $html .= '</tr>';
                    }
                }
            }

            // get shipping total
            $html .= '<tr>';
            $html .= '<td>' . __('Shipping', 'mwc') . '</td>';
            $shipping_total = WC()->cart->get_shipping_total();
            if($shipping_total) {
                $html .= '<td  style="text-align: right"><span class="amount">'.wc_price($shipping_total).'</span></td>';
                $p_total += $shipping_total;
            } else {
                $html .= '<td  style="text-align: right"><span class="amount">'.__('Free Shipping', 'mwc').'</span></td>';
            }
            $html .= '</tr>';
            
            $html .= '<tr>';
            $html .= '<td>' . __('Total', 'mwc') . '</td>';
            $html .= '<td style="text-align: right">' . wc_price($p_total) . '</td>';
            $html .= '</tr>';

            $html .= '</table>';

            $return = array(
                'status' => true,
                'html' => $html
            );
        }

        echo json_encode($return);
        exit;
    }

    // function get price variation product
    public static function mwc_get_price_variation_product()
    {
        if (!(isset($_REQUEST['action']) || 'mwc_get_price_variation_product' != $_GET['action']))
            return;

        $return = array(
            'status' => false,
            'html' => 'no data!!!'
        );

        $price_list = $_GET['price_list'];
        $cupon = $_GET['cupon'];
        if($price_list) {
            $total_price = 0;
            foreach ($price_list as $key => $value) {
                $total_price += floatval($value['price']);
            }
            // caculator cupon
            $discounted_price = $total_price;
            if($cupon > 0) {
                $discounted_price = $total_price - ($total_price * $cupon) / 100;
            }            
            
            $return = array(
                'status' => true,
                'total_price' => $discounted_price,
                'total_price_html' => wc_price($discounted_price),
                'single_price' => ($discounted_price / count($price_list)),
                'single_price_html' => wc_price($discounted_price / count($price_list))
            );
        }
        
        echo json_encode($return);
        exit;
    }


    // function MWC get price package
    public static function mwc_get_price_package()
    {
        if (!(isset($_REQUEST['action']) || 'mwc_get_price_package' != $_GET['action']))
            return;

        $return = array(
            'status' => false,
            'html' => 'no data!!!'
        );

        $arr_discount = $_GET['discount'];
        $arr_product_ids = $_GET['product_ids'];

        if( !empty($arr_product_ids) && !empty($arr_discount['type']) && isset($arr_discount['qty']) && isset($arr_discount['value'])) {

            // get total price
            $loop_prod = array();
            $total_price = 0;
            $old_price = 0;
            foreach ($arr_product_ids as $key => $prod_id) {
                
                // get product data
                if($loop_prod[$prod_id]) {
                    $product = $loop_prod[$prod_id];
                } else {
                    $product = wc_get_product($prod_id);
                    $loop_prod[$prod_id] = $product;
                }
				
				if($product->is_on_sale()){
					$package_product_price = $product->get_sale_price();
				} else {
					$package_product_price = $product->get_regular_price();
				}

                $total_price += $package_product_price;
                $old_price += $package_product_price;
            }
            
            // get discount
            if($arr_discount['type'] == 'percentage') {
                $total_price -= ($total_price * $arr_discount['value']) / 100;
            } elseif($arr_discount['type'] == 'free' && in_array($arr_discount['value'], $arr_product_ids)) {
                $free_price = wc_get_product($arr_discount['value'])->get_regular_price();
                $total_price -= $free_price;
            }


            $return = array(
                'status' => true,
                'total_price' => $total_price,
                'total_price_html' => wc_price($total_price),
                'old_price' => $old_price,
                'old_price_html' => wc_price($old_price),
                'each_price' => ($total_price / count($arr_product_ids)),
                'each_price_html' => wc_price(($total_price / count($arr_product_ids)))
            );
        }

        echo json_encode($return);
        exit;
    }


    // function hook ajax update statistics addon product
    public static function mwc_update_addon_product_statistics()
    {
        if (!(isset($_REQUEST['action']) || 'mwc_update_addon_product_statistics' != $_POST['action']))
            return;

        if ($_POST['addon_ids'] && $_POST['type']) {
            MWC_AddonProduct::mwc_update_statistics_addon_product(array($_POST['addon_ids']), $_POST['type']);

            echo json_encode(array('status' => true));
            exit;
        } else {
            echo json_encode(array('status' => false));
            exit;
        }
    }

    // function hook add referer url to order note
    public static function add_referer_url_order_note( $order_id )
    {
        $order = wc_get_order($order_id);
        $order->add_order_note('Checkout url: ' .$_SERVER['HTTP_REFERER']);
    }


    /**
     * Removes all connection options
     * @static
     */
    public static function plugin_deactivation()
    {
        //flush_rewrite_rules();
    }
}
