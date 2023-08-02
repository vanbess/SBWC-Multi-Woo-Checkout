<?php

/**
 * Bundle admin selection
 */
if (!defined('ABSPATH')) :
    exit; // Exit if accessed directly.
endif;

if (!class_exists('MWCBundleSectionAdmin')) :

    /*
    * MWCBundleSectionAdmin Class
    */
    class MWCBundleSectionAdmin {
        /**
         * Constructor
         */
        public function __construct() {

            // custom post type
            add_action('init', array(__CLASS__, 'create_post_type_bundle_selection'));

            // css and js
            add_action('admin_enqueue_scripts', array(__CLASS__, 'add_style_script'));

            // cpt metaboxes
            add_action('admin_init', array(__CLASS__, 'add_form_meta_boxes'));

            // save post
            add_action('save_post', array(__CLASS__, 'save_bundle_selection_fields'));

            // post columns
            add_filter('manage_bundle_selection_posts_columns', array(__CLASS__, 'columns_head_only_bundle_selection'), 10);
            add_action('manage_bundle_selection_posts_custom_column', array(__CLASS__, 'columns_content_bundle_selection'), 10, 2);

            // action ajax get product
            add_action('wp_ajax_nopriv_mwc_bundle_get_product', array(__CLASS__, 'mwc_bundle_get_product'));
            add_action('wp_ajax_mwc_bundle_get_product', array(__CLASS__, 'mwc_bundle_get_product'));

            // action ajax get html custom product price
            add_action('wp_ajax_nopriv_mwc_get_html_custom_product_price', array(__CLASS__, 'ajax_get_html_custom_product_price'));
            add_action('wp_ajax_mwc_get_html_custom_product_price', array(__CLASS__, 'ajax_get_html_custom_product_price'));
        }

        /**
         * Register bundle post type
         *
         * @return void
         */
        public static function create_post_type_bundle_selection() {
            $args = array(
                'labels' => array(
                    'name'               => 'Multi woo checkout',
                    'singular_name'      => 'Multi woo checkout',
                    'add_new'            => 'Add New',
                    'add_new_item'       => 'Add New Bundle Selection',
                    'edit_item'          => 'Edit Bundle Selection',
                    'new_item'           => 'New Bundle Selection',
                    'view_item'          => 'View Bundle Selection',
                    'search_items'       => 'Search Bundle Selection',
                    'not_found'          => 'Nothing Found',
                    'not_found_in_trash' => 'Nothing found in the Trash',
                    'parent_item_colon'  => ''
                ),
                'show_in_menu'       => 'mwc',
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'query_var'          => true,
                'rewrite'            => true,
                'capability_type'    => 'post',
                'hierarchical'       => false,
                'menu_position'      => 0,
                'supports'           => array('title')
            );

            register_post_type('bundle_selection', $args);
        }

        /**
         * Add bundle post type columns
         *
         * @param array $defaults
         * @return array
         */
        public static function columns_head_only_bundle_selection($defaults) {

            $defaults['tracking_id']     = __('Tracking ID', 'woocommerce');
            $defaults['product_id']      = __('Product ID(s)', 'woocommerce');
            $defaults['count_view']      = __('Impressions', 'woocommerce');
            $defaults['count_click']     = __('Clicks', 'woocommerce');
            $defaults['count_paid']      = __('Conversions', 'woocommerce');
            $defaults['conversion_rate'] = __('Conversion Rate', 'woocommerce');
            $defaults['revenue']         = __('Revenue', 'woocommerce');

            unset($defaults['date']);

            return $defaults;
        }

        /**
         * Setup column data as needed
         *
         * @param string $column_name
         * @param int $post_ID
         * @return void
         */
        public static function columns_content_bundle_selection($column_name, $post_id) {

            switch ($column_name) {

                    // tracking id
                case 'tracking_id':
                    echo $post_id;
                    break;

                    // product id
                case 'product_id':

                    $bundle_data = get_post_meta($post_id, 'product_discount', true);

                    $bundle_type = $bundle_data['selValue'];

                    // bundle products
                    if ($bundle_type === 'bun') :
                        $bundle_products = $bundle_data["selValue_$bundle_type"]['post'];
                        foreach ($bundle_products as $index => $data_arr) :
                            echo $data_arr['id'] . '<br>';
                        endforeach;

                    // non bundle products
                    else :
                        $product_id = $bundle_data["selValue_$bundle_type"]['post']['id'];
                        echo $product_id;
                    endif;
                    break;

                    // view count
                case 'count_view':
                    $view_count  = get_post_meta($post_id, 'count_view', true);
                    echo $view_count ?: '-';
                    break;

                    // click count
                case 'count_click':
                    $click_count = get_post_meta($post_id, 'count_click', true);
                    echo $click_count ?: '-';
                    break;

                    // paid count
                case 'count_paid':
                    $paid_count  = get_post_meta($post_id, 'count_paid', true);
                    echo $paid_count ?: '-';
                    break;

                    // conversion rate
                case 'conversion_rate':

                    $paid_count  = (int)get_post_meta($post_id, 'count_paid', true);
                    $view_count  = (int)get_post_meta($post_id, 'count_view', true);
                    $click_count = (int)get_post_meta($post_id, 'count_click', true);

                    $conversion_rate = 0;

                    // if (is_numeric($paid_count) && is_numeric($view_count) && is_numeric($click_count)) :
                    $impressions = $view_count + $click_count;
                    $rate        = $paid_count && $view_count ? (($paid_count * 100) / $impressions) : 0;

                    update_post_meta($post_id, 'conversion_rate', $rate);
                    // endif;

                    $conversion_rate = get_post_meta($post_id, 'conversion_rate', true);
                    echo $conversion_rate > 0 ? number_format($conversion_rate, 2, '.', '') . '%' : '-';
                    break;

                    // total revenue
                case 'revenue':

                    // revenue and order currency
                    $revenue        = get_post_meta($post_id, 'revenue', true);
                    $order_currency = get_post_meta($post_id, 'order_currency', true);

                    // if ALG currency converter is installed
                    if ($revenue && $order_currency && function_exists('alg_wc_cs_get_exchange_rate')) :
                        if ($order_currency !== 'USD') :
                            $ext_rate = alg_wc_cs_get_exchange_rate($order_currency, 'USD') ? alg_wc_cs_get_exchange_rate($order_currency, 'USD') : 1;
                            $conv_revenue = $revenue * $ext_rate;
                            echo 'USD ' . number_format($conv_revenue, 2, '.', '');
                        else :
                            echo $order_currency . ' ' . number_format($revenue, 2, '.', '');
                        endif;

                    // if ALG currency converter is not installed
                    elseif ($revenue && $order_currency && !function_exists('alg_wc_cs_get_exchange_rate')) :
                        echo $order_currency . ' ' . number_format($revenue, 2, '.', '');
                    elseif (!$revenue) :
                        echo '-';
                    endif;
                    break;
            }
        }

        /**
         * CSS and JS
         *
         * @return void
         */
        public static function add_style_script() {

            // load tinymce and select2 only on bundle_selection edit screen
            $screen_data = get_current_screen();

            if ($screen_data->post_type === 'bundle_selection') :

                // this required js wasn't previously enqueued
                wp_enqueue_media();

                // tiny mce
                wp_enqueue_script('tinymce', MWC_PLUGIN_URL . 'resources/lib/tinymce/tinymce.min.js', array(), time());

                // select2
                wp_enqueue_script('select2', MWC_PLUGIN_URL . 'resources/lib/select2/select2.min.js', array(), time());
                wp_enqueue_style('select2', MWC_PLUGIN_URL . 'resources/lib/select2/select2.min.css', array(), time());

                // color picker - color picker js wasn't loaded, so color picker was failing
                wp_enqueue_script('wp-color-picker');
                wp_enqueue_style('wp-color-picker');

                // admin js + css
                wp_enqueue_script('bundle_selection_admin', MWC_PLUGIN_URL . 'resources/js/admin/bundle_selection_admin.js', array(), time(), true);
                wp_enqueue_style('bundle_selection_style', MWC_PLUGIN_URL . 'resources/style/admin/bundle_selection_admin.css', array(), time());

                // localize Js
                wp_localize_script('bundle_selection_admin', 'mwc_b_js', [
                    'ajaxurl'       => admin_url('admin-ajax.php'),
                    'nonce_prod'    => wp_create_nonce('mcb retrieve product'),
                    'nonce_general' => wp_create_nonce('mcb general ajax')
                ]);

            endif;
        }

        /**
         * AJAX to retrieve products
         *
         * @return json
         */
        public static function mwc_bundle_get_product() {

            check_ajax_referer('mcb retrieve product');

            global $wpdb;

            $posts = $wpdb->prefix . 'posts';
            $title = $_GET['product_title'];

            $db_data['results'] = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$posts` WHERE `post_type`='product'  AND `post_title` LIKE %s", "%$title%"));

            return wp_send_json($db_data);

            wp_die();
        }

        /**
         * AJAX to retrieve custom product price HTML
         *
         * @return json
         */
        public static function ajax_get_html_custom_product_price() {

            if (isset($_GET['action']) && isset($_GET['product_id']) && $_GET['action'] == 'mwc_get_html_custom_product_price') :

                $prod_id = $_GET['product_id'];
                $html    = self::get_custom_price_html($prod_id);

                // return data
                echo json_encode(
                    array(
                        'status' => true,
                        'html' => $html
                    )
                );

            endif;

            wp_die();
        }

        /**
         * function get custom price html
         *
         * @param int $product_id
         * @param array $data_custom_price
         * @return html
         */
        public static function get_custom_price_html($product_id, $data_custom_price = []) {

            $product = wc_get_product($product_id);

            if (!$product)
                return false;

            // get currencies
            $additional_currencies = self::mwc_getCurrency();

            if (!empty($additional_currencies)) :
                $default_curr = get_option('woocommerce_currency', true);
                $additional_currencies = array_merge([$default_curr], $additional_currencies);
                $additional_currencies = array_unique($additional_currencies);
            else :
                $all_currencies = get_woocommerce_currencies();
                $all_currencies = array_unique($all_currencies);
            endif;

            // get currencies rate
            $currencies_rate = [];

            // html custom product price
            $html = '<div class="collapsible custom_price_prod">
                        <span>' . __("Custom product price") . '</span>
                        <span class="i_toggle"></span>
                    </div>
                    <div class="toggle_content custom_price_prod">';

            // get price product variable
            if ($product->is_type('variable')) :

                foreach ($product->get_available_variations() as $value) :

                    $prod_price = get_post_meta($value['variation_id'], '_price', true);

                    // add variation price item html
                    $html .= '<div class="variation_item">
                                <div class="collapsible custom_price_prod">
                                    <span>' . implode(" - ", $value['attributes']) . '</span>
                                    <span class="i_toggle"></span>
                                </div>
                            <div class="toggle_content custom_price_prod">';

                    if (!empty($additional_currencies)) :

                        foreach ($additional_currencies as $currency_code) :

                            // get currencies rate
                            if (!isset($currencies_rate[$currency_code])) :
                                $currencies_rate[$currency_code] = null;
                                if (function_exists('alg_wc_cs_get_currency_exchange_rate')) :
                                    $currencies_rate[$currency_code] = alg_wc_cs_get_currency_exchange_rate($currency_code);
                                endif;
                            endif;

                            // get old custom price
                            if (!empty($data_custom_price)) :
                                $old_price = isset($data_custom_price[$value['variation_id']][$currency_code]) ? $data_custom_price[$value['variation_id']][$currency_code] : '';
                            else :
                                $old_price = '';
                            endif;

                            $html .= '<div class="item_currency">
                                        <div class="item_name">
                                            <label>' . $currency_code . ':</label>
                                        </div>
                                        <input type="text" class="input_price" name="custom_price_prod[' . $value['variation_id'] . '][' . $currency_code . ']" value="' . $old_price . '" data-value="' . $prod_price * $currencies_rate[$currency_code] . '">
                                    </div>';
                        endforeach;
                    else :
                        foreach ($all_currencies as $key => $currency_code) :

                            // get currencies rate
                            if (!isset($currencies_rate[$key])) :
                                $currencies_rate[$key] = null;
                                if (function_exists('alg_wc_cs_get_currency_exchange_rate')) :
                                    $currencies_rate[$key] = alg_wc_cs_get_currency_exchange_rate($key);
                                endif;
                            endif;

                            // get old custom price
                            if (isset($data_custom_price)) :
                                $old_price = isset($data_custom_price[$value['variation_id']][$key]) ? $data_custom_price[$value['variation_id']][$key] : '';
                            endif;

                            $html .= '<div class="item_currency">
                                        <label>' . $key . ':</label>
                                        <input type="text" name="custom_price_prod[' . $value['variation_id'] . '][' . $key . ']" value="' . $old_price . '" data-value="' . $prod_price * $currencies_rate[$currency_code] . '">
                                    </div>';
                        endforeach;
                    endif;

                    $html .= '</div>
                </div>';
                endforeach;

            // single product
            else :
                // get price product
                $prod_price = $product->get_price();

                if (!empty($additional_currencies)) :

                    foreach ($additional_currencies as $currency_code) :

                        // get currencies rate
                        if (!isset($currencies_rate[$currency_code])) :
                            $currencies_rate[$currency_code] = null;
                            if (function_exists('alg_wc_cs_get_currency_exchange_rate')) :
                                $currencies_rate[$currency_code] = alg_wc_cs_get_currency_exchange_rate($currency_code);
                            endif;
                        endif;

                        // get old custom price
                        if (isset($data_custom_price)) :
                            $old_price = isset($data_custom_price[$product_id][$currency_code]) ? $data_custom_price[$product_id][$currency_code] : '';
                        endif;

                        $html .= '<div class="item_currency">
                                    <div class="item_name">
                                        <label>' . $currency_code . ':</label>
                                    </div>
                                    <input type="text" class="input_price" name="custom_price_prod[' . $product_id . '][' . $currency_code . ']" value="' . $old_price . '" data-value="' . $prod_price * $currencies_rate[$currency_code] . '">
                                </div>';
                    endforeach;

                else :

                    foreach ($all_currencies as $key => $currency_code) :

                        // get currencies rate
                        if (!isset($currencies_rate[$key])) :
                            $currencies_rate[$key] = null;
                            if (function_exists('alg_wc_cs_get_currency_exchange_rate')) :
                                $currencies_rate[$key] = alg_wc_cs_get_currency_exchange_rate($key);
                            endif;
                        endif;

                        // get old custom price
                        if (isset($data_custom_price)) :
                            $old_price = isset($data_custom_price[$product_id][$key]) ? $data_custom_price[$product_id][$key] : '';
                        endif;

                        $html .= '<div class="item_currency">
                                    <label>' . $key . ':</label>
                                    <input type="text" name="custom_price_prod[' . $product_id . '][' . $key . ']" value="' . $old_price . '" data-value="' . $prod_price * $currencies_rate[$currency_code] . '">
                                </div>';
                    endforeach;

                endif;
            endif;
            // end html
            $html .= '</div>';

            return $html;
        }

        /**
         * Bundle post type metabox(es)
         *
         * @return void
         */
        public static function add_form_meta_boxes() {

            add_meta_box(
                "mwc_bundle_selection_meta",
                __('Bundle Selection Form', 'woocommerce'),
                array(__CLASS__, "add_bundle_selection_meta_box"),
                "bundle_selection",
                "normal",
                "low"
            );
        }

        /**
         * Load bundle selection from DB
         *
         * @return void
         */
        public static function add_bundle_selection_meta_box() {

            global $post;

            // get data bundle selection
            $db_data = get_post_meta($post->ID, 'product_discount', true);

            // echo '<pre>';
            // print_r($db_data);
            // echo '</pre>';

            // form edit
            if ($db_data) :

                if (!is_array($db_data) && !empty($db_data)) :
                    $db_data  = json_decode($db_data, true);
                endif;

                $selValue = $db_data['selValue'] ?? 'free'; ?>

                <!-- load select bundle type -->
                <select name="selValue" class="select_type">
                    <option <?php ($selValue == 'free') ? print_r('selected') : '' ?> value="free"><?= __('Buy X Get X Free') ?></option>
                    <option <?php ($selValue == 'off') ? print_r('selected') : '' ?> value="off"><?= __('Buy X Get X % Off') ?></option>
                    <option <?php ($selValue == 'bun') ? print_r('selected') : '' ?> value="bun"><?= __('Bundled Product') ?></option>
                </select>

                <?php
                /**
                 * edit option buy x get y free
                 */
                $data                  = [];

                $data['title']               = isset($db_data['title_package']) ? $db_data['title_package'] : '';
                $data['image_desk']          = isset($db_data['image_package_desktop']) ? $db_data['image_package_desktop'] : '';
                $data['image_mobile']        = isset($db_data['image_package_mobile']) ? $db_data['image_package_mobile'] : '';
                $data['hover_image']         = isset($db_data['image_package_hover']) ? $db_data['image_package_hover'] : '';
                $data['description']         = isset($db_data['feature_description']) ? $db_data['feature_description'] : '';
                $data['label']               = isset($db_data['label_item']) ? $db_data['label_item'] : '';
                $data['discount_percentage'] = isset($db_data['discount_percentage']) ? $db_data['discount_percentage'] : '';
                $data['sell_out_risk']       = isset($db_data['sell_out_risk']) ? $db_data['sell_out_risk'] : '';
                $data['popularity']          = isset($db_data['popularity']) ? $db_data['popularity'] : '';
                $data['free_shipping']       = isset($db_data['free_shipping']) ? $db_data['free_shipping'] : false;

                // buy x get x free
                if ($selValue == 'free') :

                    $data['product_name']             = isset($db_data['product_name']) ? $db_data['product_name'] : '';
                    $data['free']                     = isset($db_data['selValue_free']['post']) ? $db_data['selValue_free']['post'] : ['id' => '', 'text' => 'title'];
                    $data['free_qty']                 = isset($db_data['selValue_free']['quantity']) ? $db_data['selValue_free']['quantity'] : '';
                    $data['free_prod']                = isset($db_data['selValue_free_prod']['post']) ? $db_data['selValue_free_prod']['post'] : ['id' => '', 'text' => 'title'];
                    $data['free_prod_qty']            = isset($db_data['selValue_free_prod']['quantity']) ? $db_data['selValue_free_prod']['quantity'] : '';
                    $data['custom_price']             = isset($db_data['custom_price']) ? $db_data['custom_price'] : '';
                    $data['free_show_discount_label'] = isset($db_data['show_discount_label']) ? $db_data['show_discount_label'] : false;

                    // option buy x get x free *** main option
                    echo self::render_buy_x_get_x_free($data, true);

                    // buy x get y%
                    echo self::render_buy_x_get_x_off();

                    // buy bundle prod
                    echo self::render_bundle_prods_section();
                endif;

                /*
                ** edit option buy x get y%
                */
                if ($selValue == 'off') :

                    $data['product_name']            = isset($db_data['product_name']) ? $db_data['product_name'] : '';
                    $data['off']                     = isset($db_data['selValue_off']['post']) ? $db_data['selValue_off']['post'] : ['id' => '', 'text' => 'title'];
                    $data['off_qty']                 = isset($db_data['selValue_off']['quantity']) ? $db_data['selValue_off']['quantity'] : '';
                    $data['off_coupon']              = isset($db_data['selValue_off']['coupon']) ? $db_data['selValue_off']['coupon'] : '';
                    $data['custom_price']            = isset($db_data['custom_price']) ? $db_data['custom_price'] : '';
                    $data['off_show_discount_label'] = isset($db_data['show_discount_label']) ? $db_data['show_discount_label'] : false;
                    $data['off_show_original_price'] = isset($db_data['show_original_price']) ? $db_data['show_original_price'] : false;

                    // option buy x get x free
                    echo self::render_buy_x_get_x_free();

                    // buy x get y% *** main option
                    echo self::render_buy_x_get_x_off($data, true);

                    // buy bundle prod -->
                    echo self::render_bundle_prods_section();
                endif;

                /*
                ** edit option bundle
                */
                if ($selValue == 'bun') :

                    $data['title_header']            = isset($db_data['title_header']) ? $db_data['title_header'] : '';
                    $data['bun']                     = isset($db_data['selValue_bun']['post']) ? $db_data['selValue_bun']['post'] : ['id' => '', 'text' => 'title'];
                    $data['price_currency']          = isset($db_data['selValue_bun']['price_currency']) ? $db_data['selValue_bun']['price_currency'] : null;
                    $data['bun_show_discount_label'] = isset($db_data['show_discount_label']) ? $db_data['show_discount_label'] : false;

                    // option buy x get x free
                    echo self::render_buy_x_get_x_free();

                    // buy x get y%
                    echo self::render_buy_x_get_x_off();

                    // buy bundle prod *** main option
                    echo self::render_bundle_prods_section($data, true);

                endif;

            // form create
            else : ?>
                <!-- load select bundle type -->
                <select name="selValue" class="select_type">
                    <option value="free"><?= __('Buy X Get X Free') ?></option>
                    <option value="off"><?= __('Buy X Get X % Off') ?></option>
                    <option value="bun"><?= __('Bundled Product') ?></option>
                </select>
            <?php
                // option buy x get x free
                echo self::render_buy_x_get_x_free(null, true);

                // buy x get y%
                echo self::render_buy_x_get_x_off();

                // buy bundle prod
                echo self::render_bundle_prods_section();
            endif;
        }

        /**
         * Save all fields
         *
         * @param int $post_id
         * @return void
         */
        public static function save_bundle_selection_fields($post_id) {

            global $post;

            if (!$post || $post->post_type != 'bundle_selection' || $post_id != $post->ID) :
                return;
            endif;

            // save option buy x get x free
            if ($_POST['selValue'] == 'free') :

                $data_arr['selValue']              = $_POST['selValue'];
                $data_arr['title_package']         = $_POST['title_package_free'];
                $data_arr['image_package_desktop'] = $_POST['free_image_desk'];
                $data_arr['image_package_mobile']  = $_POST['free_image_mobile'];
                $data_arr['image_package_hover']   = $_POST['free_hover_image'];
                $data_arr['product_name']          = $_POST['free_product_name'];

                $value = explode('/%%/', $_POST['selValue_free']);

                if (isset($value[0]) && isset($value[1])) :
                    $_POST['selValue_free'] = ['id' => $value[0], 'title' => preg_replace('/[^a-zA-Z0-9_ -]/s', '', $value[1])];
                endif;

                $data_arr['selValue_free']      = ['post' => $_POST['selValue_free'], 'quantity' => $_POST['quantity_main_free']];
                $data_arr['selValue_free_prod'] = ['post' => $_POST['selValue_free'], 'quantity' => $_POST['quantity_free_free']];

                //get feature desc _POST
                $desc = array_filter($_POST['feature_free_desc']);
                $data_arr['feature_description'] = $desc;

                // show discount label
                $data_arr['show_discount_label'] = ($_POST['free_show_discount_label'] == true) ?: false;

                // sell out risk
                $data_arr['sell_out_risk'] = $_POST['free_sell_out_risk'] ?: '';

                // popularity
                $data_arr['popularity'] = $_POST['free_popularity'] ?: '';

                // free shipping
                $data_arr['free_shipping'] = ($_POST['free_shipping'] == true) ?: false;

                // get label items _POST
                $label_name  = array_filter($_POST['name_label_free']);
                $label_color = array_filter($_POST['color_label_free']);

                $data_arr['label_item'] = array_map(function ($name, $color) {
                    return array(
                        'name'  => $name,
                        'color' => $color
                    );
                }, $label_name, $label_color);

                // custom product price
                $custom_price = [];
                if ($_POST['selValue_free'] && $_POST['custom_price_prod']) :
                    foreach ($_POST['custom_price_prod'] as $post_id => $values) :
                        foreach ($values as $curr => $price) :
                            if ($price) :
                                $custom_price[$post_id][$curr] = $price;
                            endif;
                        endforeach;
                    endforeach;
                endif;
                $data_arr['custom_price'] = $custom_price;

            // save option buy x get x%
            elseif ($_POST['selValue'] == 'off') :

                $data_arr['selValue']              = $_POST['selValue'];
                $data_arr['title_package']         = $_POST['title_package_off'];
                $data_arr['image_package_desktop'] = $_POST['off_image_desk'];
                $data_arr['image_package_mobile']  = $_POST['off_image_mobile'];
                $data_arr['image_package_hover']   = $_POST['off_hover_image'];
                $data_arr['product_name']          = $_POST['off_product_name'];

                $value = explode('/%%/', $_POST['selValue_off']);

                if (isset($value[0]) && isset($value[1])) :
                    $_POST['selValue_off'] = ['id' => $value[0], 'title' => preg_replace('/[^a-zA-Z0-9_ -]/s', '', $value[1])];
                endif;

                $data_arr['selValue_off'] = [
                    'post'     => $_POST['selValue_off'],
                    'quantity' => $_POST['quantity_main_off'],
                    'coupon'   => !empty($_POST['quantity_coupon_off']) ? $_POST['quantity_coupon_off'] : ''
                ];

                $desc = is_array($_POST['feature_off_desc']) ? array_filter($_POST['feature_off_desc']) : '';

                $data_arr['feature_description'] = $desc;

                // show discount label
                $data_arr['show_discount_label'] = ($_POST['off_show_discount_label'] == true) ?: false;

                // show original price
                $data_arr['show_original_price'] = ($_POST['off_show_original_price'] == true) ?: false;

                // sell out risk
                $data_arr['sell_out_risk'] = $_POST['off_sell_out_risk'] ?: '';

                // popularity
                $data_arr['popularity'] = $_POST['off_popularity'] ?: '';

                // free shipping
                $data_arr['free_shipping'] = ($_POST['free_shipping'] == true) ?: false;

                //get label items _POST
                $bundle_name_label = $_POST['name_label_bundle'];
                $bundle_color_label = $_POST['color_label_bundle'];

                if (is_array($bundle_name_label) && is_array($bundle_color_label) && !empty($bundle_name_label)  && !empty($bundle_color_label)) :

                    $label_name  = array_filter($bundle_name_label);
                    $label_color = array_filter($bundle_color_label);

                    $data_arr['label_item'] = array_map(function ($name, $color) {
                        return array(
                            'name' => $name,
                            'color' => $color
                        );
                    }, $label_name, $label_color);
                endif;

                // custom product price
                $custom_price = [];

                if ($_POST['selValue_off'] && $_POST['custom_price_prod']) :
                    foreach ($_POST['custom_price_prod'] as $post_id => $values) :
                        foreach ($values as $curr => $price) :
                            if ($price) :
                                $custom_price[$post_id][$curr] = $price;
                            endif;
                        endforeach;
                    endforeach;
                endif;
                $data_arr['custom_price'] = $custom_price;

            // save option buy bundle products
            elseif ($_POST['selValue'] == 'bun') :

                $data_arr['selValue']              = $_POST['selValue'];
                $data_arr['title_header']          = $_POST['title_bundle_header'];
                $data_arr['title_package']         = $_POST['title_package_bundle'];
                $data_arr['image_package_desktop'] = $_POST['bundle_image_desk'];
                $data_arr['image_package_mobile']  = $_POST['bundle_image_mobile'];
                $data_arr['image_package_hover']   = $_POST['bundle_hover_image'];

                // $total_price = 0;
                foreach ($_POST['selValue_bundle'] as $key => $value) :

                    $value   = explode('/%%/', $value);
                    $new_arr = '';

                    if (isset($value[0]) && isset($value[1])) :
                        $new_arr = ['id' => $value[0], 'title' => preg_replace('/[^a-zA-Z0-9_ -]/s', '', $value[1]), 'quantity' => $_POST['bundle_quantity'][$key]  ?: 1];
                    endif;
                    $_POST['selValue_bundle'][$key] = $new_arr;
                endforeach;

                $data_arr['selValue_bun'] = ['post' => $_POST['selValue_bundle'], 'price_currency' => $_POST['bun_price_currency']];
                $desc = isset($_POST['feature_bundle_desc']) ? array_filter($_POST['feature_bundle_desc']) : '';
                $data_arr['feature_description'] = $desc;

                // discount percentage
                $data_arr['discount_percentage'] = floatval($_POST['bun_discount_percentage']) ?: '';

                // show discount label
                $data_arr['show_discount_label'] = ($_POST['bun_show_discount_label'] == true) ?: false;

                // sell out risk
                $data_arr['sell_out_risk'] = $_POST['bun_sell_out_risk'] ?: '';

                // popularity
                $data_arr['popularity'] = $_POST['bun_popularity'] ?: '';

                // free shipping
                $data_arr['free_shipping'] = ($_POST['free_shipping'] == true) ?: false;

                //get label items _POST
                $bundle_name_label = $_POST['name_label_bundle'];
                $bundle_color_label = $_POST['color_label_bundle'];

                if (is_array($bundle_name_label) && is_array($bundle_color_label) && !empty($bundle_name_label)  && !empty($bundle_color_label)) :

                    $label_name  = array_filter($bundle_name_label);
                    $label_color = array_filter($bundle_color_label);

                    $data_arr['label_item'] = array_map(function ($name, $color) {
                        return array(
                            'name' => $name,
                            'color' => $color
                        );
                    }, $label_name, $label_color);
                endif;

            endif;


            if ($data_arr) :
                update_post_meta($post->ID, 'product_discount', $data_arr);
            endif;
        }

        /**
         * Render Buy X Get X Free HTML
         *
         * @param array $data
         * @param boolean $active
         * @return html
         */
        private static function render_buy_x_get_x_free($data = null, $active = false) { ?>

            <!-- option buy x get x free -->
            <div class='product product_free <?= $active ? 'activetype' : '' ?>'>
                <table class="form-table">
                    <tbody>

                        <!-- package title -->
                        <tr valign="top">
                            <th style="width:30%" scope="row" class="titledesc">
                                <label for="title_package_free">Package title:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='title_package_free' type='text' class='title_main' value="<?= is_array($data) ? $data['title'] : ''  ?>">
                            </td>
                        </tr>

                        <!-- desktop image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="upload_image">Desktop image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='upload_image'>
                                    <input class='upload_image' type='text' name='free_image_desk' value="<?= is_array($data) ? $data['image_desk'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- mobile image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="upload_image">Mobile image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='upload_image'>
                                    <input class='upload_image' type='text' name='free_image_mobile' value="<?= is_array($data) ? $data['image_mobile'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- hover image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="hover_image">Hover image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='hover_image'>
                                    <input class='upload_image' type='text' name='free_hover_image' value="<?= is_array($data) ? $data['hover_image'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- product name -->
                        <tr valign="top">
                            <th style="width:30%" scope="row" class="titledesc">
                                <label for="title_package_free">Product name:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='free_product_name' type='text' class='product_name' value="<?= is_array($data) ? $data['product_name'] : '' ?>">
                            </td>
                        </tr>

                        <!-- main product -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="product_select">Main Product:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <select name='selValue_free' class='product_select' style="width: 400px;">
                                    <?php if (is_array($data) && isset($data['free']["id"])) : ?>
                                        <option value="<?= ($data['free']["id"] . '/%%/' . $data['free']["title"]) ?>"> <?= ($data['free']["id"] . ': ' . $data['free']["title"]) ?> </option>
                                    <?php else : ?>
                                        <option value=""></option>
                                    <?php endif; ?>
                                </select>
                                <label class="label_inline">Quantity:</label>
                                <input name='quantity_main_free' type='number' class="small-text" value="<?= is_array($data) ? $data['free_qty'] : '' ?>">
                            </td>
                        </tr>

                        <!-- free product -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Free Product:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label class="label_inline"> Quantity:</label>
                                <input name='quantity_free_free' type='number' class='small-text' value="<?= is_array($data) ? $data['free_prod_qty'] : ''; ?>">
                            </td>
                        </tr>

                        <!-- custom price -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Custom price:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <div class="custom_prod_price">
                                    <?php
                                    if (is_array($data) && isset($data['free']['id'])) :
                                        echo self::get_custom_price_html($data['free']['id'], $data['custom_price']);
                                    else :
                                        _e('<b><i>Not defined yet</b></i>', 'default');
                                    endif;
                                    ?>
                                </div>
                            </td>
                        </tr>

                        <!-- discount label -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Show discount label:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <input name='free_show_discount_label' type='checkbox' value="" <?= ($data['free_show_discount_label'] == true) ? 'checked' : '' ?>>
                                <?php else : ?>
                                    <input name='free_show_discount_label' type='checkbox' value="">
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- sellout risk -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Sell-Out Risk:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="none" <?= ($data['sell_out_risk'] == "none") ? "checked" : "" ?>>
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="high" <?= ($data['sell_out_risk'] == "high") ? "checked" : "" ?>>
                                        <span>High</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="medium" <?= ($data['sell_out_risk'] == "medium") ? "checked" : "" ?>>
                                        <span>Medium</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="low" <?= ($data['sell_out_risk'] == "low") ? "checked" : "" ?>>
                                        <span>Low</span>
                                    </label>
                                <?php else : ?>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="none">
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="high">
                                        <span>High</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="medium">
                                        <span>Medium</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_sell_out_risk" value="low">
                                        <span>Low</span>
                                    </label>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- popularity -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Popularity:</label>
                            </th>

                            <?php if (is_array($data)) : ?>
                                <td class="forminp forminp-text">
                                    <label>
                                        <input type="radio" name="free_popularity" value="none" <?= ($data['popularity'] == "none") ? "checked" : "" ?>>
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_popularity" value="best-seller" <?= ($data['popularity'] == "best-seller") ? "checked" : "" ?>>
                                        <span>Best Seller</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_popularity" value="popular" <?= ($data['popularity'] == "popular") ? "checked" : "" ?>>
                                        <span>Popular</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_popularity" value="moderate" <?= ($data['popularity'] == "moderate") ? "checked" : "" ?>>
                                        <span>Moderate</span>
                                    </label>
                                </td>
                            <?php else : ?>
                                <td class="forminp forminp-text">
                                    <label>
                                        <input type="radio" name="free_popularity" value="none">
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_popularity" value="best-seller">
                                        <span>Best Seller</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_popularity" value="popular">
                                        <span>Popular</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="free_popularity" value="moderate">
                                        <span>Moderate</span>
                                    </label>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- free shipping -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Free Shipping:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <input type="checkbox" name="free_shipping" value="true" <?= ($data['free_shipping'] == true) ? 'checked' : '' ?>>
                                <?php else : ?>
                                    <input type="checkbox" name="free_shipping" value="true">
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- add description -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="desc_input">Add package feature description:</label>
                                <input class='button description_add' type='button' value='<?php _e('Add description', 'default'); ?>' style="position: relative; bottom: 4px;" />
                            </th>
                            <td class="forminp forminp-text">
                                <div class='feature_desc_add' data-type='free'>
                                    <div class='input_zone'>
                                        <?php if ($data === null || empty($data['description'])) : ?>
                                            <div>
                                                <input name="feature_free_desc[]" class="desc_input" type="text" value="">
                                            </div><br>
                                        <?php endif; ?>
                                        <?php if (is_array($data) && isset($data['description']) && !empty($data['description'])) :
                                            foreach ($data['description'] as $key => $value) : ?>
                                                <div>
                                                    <input name="feature_free_desc[]" class="desc_input quantity_main_bundle" type="text" value="<?= ($value) ?>">
                                                    <button type="button" class="remove button button-primary">x</button>
                                                </div><br>
                                        <?php endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- add label -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="desc_input">Add package item label:</label>
                                <input class='button label_add' type='button' value='<?php _e('Add label', 'default'); ?>' style="position: relative; bottom: 4px;" />
                            </th>
                            <td class="forminp forminp-text">
                                <div class='item_label_add' data-type='free'>
                                    <div class='input_zone'>
                                        <?php if ($data === null || empty($data['label'][0]['name'])) : ?>
                                            <div>
                                                <input name="name_label_free[]" class="label_input" type="text" value="">
                                                <label class="label_inline"> <b>Color:</b> </label><input type="text" value="#bada55" name="color_label_free[]" class="my-color-field" data-default-color="#effeff" /><br>
                                            </div><br>
                                        <?php endif; ?>
                                        <?php if (is_array($data) && isset($data['label'])) :
                                            foreach ($data['label'] as $key => $value) : ?>
                                                <div>
                                                    <input name="name_label_free[]" class="label_input" type="text" value="<?= ($value['name']) ?>">
                                                    <label class="label_inline"> <b>Color:</b> </label><input type="text" value="<?= ($value['color']) ?>" name="color_label_free[]" class="my-color-field" data-default-color="<?= ($value['color']) ?>" />
                                                    <button type="button" class="remove button button-primary">x</button>
                                                </div><br>
                                        <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- end buy x get x free -->

        <?php
        }

        /**
         * Render Buy X get X Off
         *
         * @param array $data
         * @param boolean $active
         * @return html
         */
        private static function render_buy_x_get_x_off($data = null, $active = false) {

        ?>
            <!-- buy x get y% -->
            <div class='product product_off <?= $active ? 'activetype' : '' ?>'>
                <table class="form-table">
                    <tbody>

                        <!-- package title -->
                        <tr valign="top">
                            <th style="width:30%" scope="row" class="titledesc">
                                <label for="title_package_off">Package title:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='title_package_off' type='text' class='title_main' value="<?= is_array($data) ? $data['title'] : '' ?>">
                            </td>
                        </tr>

                        <!-- desktop image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="upload_image">Desktop image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='upload_image'>
                                    <input class='upload_image' type='text' name='off_image_desk' value="<?= is_array($data) ? $data['image_desk'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- mobile image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="upload_image">Mobile image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='upload_image'>
                                    <input class='upload_image' type='text' name='off_image_mobile' value="<?= is_array($data) ? $data['image_mobile'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- hover image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="hover_image">Hover image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='hover_image'>
                                    <input class='upload_image' type='text' name='off_hover_image' value="<?= is_array($data) ? $data['hover_image'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- product name -->
                        <tr valign="top">
                            <th style="width:30%" scope="row" class="titledesc">
                                <label for="title_package_free">Product name:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='off_product_name' type='text' class='product_name' value="<?= is_array($data) ? $data['product_name'] : '' ?>">
                            </td>
                        </tr>

                        <!-- products -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="product_select">Product:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <select name='selValue_off' class='product_select' style="width: 400px;">
                                    <?php if (is_array($data) && isset($data['off']["id"])) : ?>
                                        <option value="<?= ($data['off']["id"] . '/%%/' . $data['off']["title"]) ?>"> <?= ($data['off']["id"] . ': ' . $data['off']["title"]) ?> </option>
                                    <?php else : ?>
                                        <option value=""></option>
                                    <?php endif; ?>
                                </select>
                                <label class="label_inline">Quantity:</label>
                                <input name='quantity_main_off' type='number' class="small-text" value="<?= is_array($data) ? $data['off_qty'] : '' ?>">
                            </td>
                        </tr>

                        <!-- custom price -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Custom price:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <div class="custom_prod_price">
                                    <?php
                                    if (is_array($data) && isset($data['off']['id'])) :
                                        echo self::get_custom_price_html($data['off']['id'], $data['custom_price']);
                                    else :
                                        _e('<b><i>Not defined yet</b></i>', 'default');
                                    endif;
                                    ?>
                                </div>
                            </td>
                        </tr>

                        <!-- coupon -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Coupon:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='quantity_coupon_off' type='number' value="<?= is_array($data) ? $data['off_coupon'] : '' ?>">
                            </td>
                        </tr>

                        <!-- show discount label -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Show discount label:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <input name='off_show_discount_label' type='checkbox' value="true" <?= ($data['off_show_discount_label'] == true) ? 'checked' : '' ?>>
                                <?php else : ?>
                                    <input name='off_show_discount_label' type='checkbox' value="true" ?>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- show discount label -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Show original price:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <input name='off_show_original_price' type='checkbox' value="true" <?= ($data['off_show_original_price'] == true) ? 'checked' : '' ?>>
                                <?php else : ?>
                                    <input name='off_show_original_price' type='checkbox' value="true" ?>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- sellout risk -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Sell-Out Risk:</label>
                            </th>
                            <td class="forminp forminp-text">

                                <?php if (is_array($data)) : ?>

                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="none" <?= ($data['sell_out_risk'] == "none") ? "checked" : "" ?>>
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="high" <?= ($data['sell_out_risk'] == "high") ? "checked" : "" ?>>
                                        <span>High</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="medium" <?= ($data['sell_out_risk'] == "medium") ? "checked" : "" ?>>
                                        <span>Medium</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="low" <?= ($data['sell_out_risk'] == "low") ? "checked" : "" ?>>
                                        <span>Low</span>
                                    </label>
                                <?php else : ?>
                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="none">
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="high">
                                        <span>High</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="medium">
                                        <span>Medium</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_sell_out_risk" value="low">
                                        <span>Low</span>
                                    </label>
                                <?php endif; ?>

                            </td>
                        </tr>

                        <!-- popularity -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Popularity</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <label>
                                        <input type="radio" name="off_popularity" value="none" <?= ($data['popularity'] == "none") ? "checked" : "" ?>>
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_popularity" value="best-seller" <?= ($data['popularity'] == "best-seller") ? "checked" : "" ?>>
                                        <span>Best Seller</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_popularity" value="popular" <?= ($data['popularity'] == "popular") ? "checked" : "" ?>>
                                        <span>Popular</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_popularity" value="moderate" <?= ($data['popularity'] == "moderate") ? "checked" : "" ?>>
                                        <span>Moderate</span>
                                    </label>

                                <?php else : ?>

                                    <label>
                                        <input type="radio" name="off_popularity" value="none">
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_popularity" value="best-seller">
                                        <span>Best Seller</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_popularity" value="popular">
                                        <span>Popular</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="off_popularity" value="moderate">
                                        <span>Moderate</span>
                                    </label>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- free shipping -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Free Shipping:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <input type="checkbox" name="free_shipping" value="true" <?= ($data['free_shipping'] == true) ? 'checked' : '' ?>>
                                <?php else : ?>
                                    <input type="checkbox" name="free_shipping" value="true">
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- add description -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="desc_input">Add package feature description:</label>
                                <input class='button description_add' type='button' value='<?php _e('Add description', 'default'); ?>' style="position: relative; bottom: 4px;" />
                            </th>
                            <td class="forminp forminp-text">
                                <div class='feature_desc_add' data-type='off'>
                                    <div class='input_zone'>
                                        <?php if ($data === null || empty($data['description'])) : ?>
                                            <div>
                                                <input name="feature_free_desc[]" class="desc_input" type="text" value="">
                                            </div><br>
                                        <?php endif; ?>
                                        <?php if (is_array($data) && isset($data['description']) && !empty($data['description'])) :
                                            foreach ($data['description'] as $key => $value) : ?>
                                                <div>
                                                    <input name="feature_free_desc[]" class="desc_input quantity_main_bundle" type="text" value="<?= ($value) ?>">
                                                    <button type="button" class="remove button button-primary">x</button>
                                                </div><br>
                                        <?php endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- add label -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="desc_input">Add package item label:</label>
                                <input class='button label_add' type='button' value='<?php _e('Add label', 'default'); ?>' style="position: relative; bottom: 4px;" />
                            </th>
                            <td class="forminp forminp-text">
                                <div class='item_label_add' data-type='off'>
                                    <div class='input_zone'>
                                        <?php if ($data === null || empty($data['label'][0]['name'])) : ?>
                                            <div>
                                                <input name="name_label_off[]" class="label_input" type="text" value="">
                                                <label class="label_inline"> <b>Color:</b> </label><input type="text" value="#bada55" name="color_label_off[]" class="my-color-field" data-default-color="#effeff" />
                                            </div><br>
                                        <?php endif; ?>
                                        <?php if (is_array($data) && isset($data['label']) && !is_string($data['label'])) :
                                            foreach ($data['label'] as $key => $value) : ?>
                                                <div>
                                                    <input name="name_label_off[]" class="label_input" type="text" value="<?= ($value['name']) ?>">
                                                    <label class="label_inline"> <b>Color:</b> </label><input type="text" value=" <?= ($value['color']) ?>" name="color_label_off[]" class="my-color-field" data-default-color="<?= ($value['color']) ?>" />
                                                    <button type="button" class="remove button button-primary">x</button>
                                                </div><br>
                                        <?php endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <!-- end buy x get y% -->

        <?php
        }


        // fun render option bun products
        private static function render_bundle_prods_section($data = null, $active = false) {
        ?>
            <!-- buy bundle prod -->
            <div class="product product_bun <?= $active ? 'activetype' : '' ?>">
                <table class="form-table">
                    <tbody>

                        <!-- package header -->
                        <tr valign="top">
                            <th style="width:30%" scope="row" class="titledesc">
                                <label for="title_bundle_header">Title header:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='title_bundle_header' type='text' class='title_header' value="<?= is_array($data) ? $data["title_header"] : '' ?>">
                            </td>
                        </tr>

                        <!-- Package title -->
                        <tr valign="top">
                            <th style="width:30%" scope="row" class="titledesc">
                                <label for="title_package_bundle">Package title:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='title_package_bundle' type='text' class='title_main' value="<?= is_array($data) ? $data['title'] : '' ?>">
                            </td>
                        </tr>

                        <!-- desktop image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="upload_image">Desktop image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='upload_image'>
                                    <input class='upload_image' type='text' name='bundle_image_desk' value="<?= is_array($data) ? $data['image_desk'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- mobile (responsive image) -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="upload_image">Mobile image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='upload_image'>
                                    <input class='upload_image' type='text' name='bundle_image_mobile' value="<?= is_array($data) ? $data['image_mobile'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- hover image -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="hover_image">Hover image:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <label for='hover_image'>
                                    <input class='upload_image' type='text' name='bundle_hover_image' value="<?= is_array($data) ? $data['hover_image'] : '' ?>" placeholder="https://" />
                                    <input class='button upload_image_button' type='button' value='Upload Image' />
                                    <br />Enter a URL or upload an image
                                </label>
                            </td>
                        </tr>

                        <!-- product data -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Package product(s):</label>
                                <button type="button" class="button product product_add_bun activetype_button" style="position: relative; bottom: 4px;">
                                    <?php _e('Add product', 'default'); ?>
                                </button>
                            </th>
                            <td class="forminp forminp-text">
                                <div class="new_prod">
                                    <?php if ($data === null) : ?>
                                        <div style="margin-bottom: 5px;">
                                            <select name='selValue_bundle[]' class='product_select product_select_bun' style="width: 400px;"></select>
                                            <label class="label_inline">Quantity: </label>
                                            <input name='bundle_quantity[]' type='number' class='small-text'>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (is_array($data) && isset($data['bun'])) :
                                        foreach ($data['bun'] as $key => $value) : ?>
                                            <div style="margin-bottom: 5px;">
                                                <select name='selValue_bundle[]' class='product_select product_select_bun' style="width: 400px;">
                                                    <?php if (isset($value["id"])) : ?>
                                                        <option value="<?= ($value["id"] . '/%%/' . $value["title"]) ?>"><?= ($value["id"] . ': ' . $value["title"]) ?></option>
                                                    <?php else : ?>
                                                        <option value=""></option>
                                                    <?php endif; ?>
                                                </select>
                                                <label class="label_inline">Quantity: </label>
                                                <input name='bundle_quantity[]' type='number' class='small-text' value="<?= $value["quantity"] ?>">
                                                <button type="button" class="remove button button-primary">x</button>
                                            </div>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                            </td>
                        </tr>

                        <!-- total price -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <?php
                                $default_currency = get_option('woocommerce_currency');
                                ?>
                                <label>Total price(<?= $default_currency ?>):</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='bun_price_currency[<?= $default_currency ?>]' type='number' value="<?= is_array($data) ? $data['price_currency'][$default_currency] : '' ?>">
                            </td>
                        </tr>

                        <!-- open/show more currencies (?) -->
                        <tr valign="top">
                            <th scope="row">
                            </th>
                            <td class="forminp forminp-text">
                                <div class="collapsible bundle_total_price">
                                    <span>Open more currency</span>
                                    <span class="i_toggle"></span>
                                </div>
                                <div class="toggle_content">
                                    <?php
                                    $additional_currencies = self::mwc_getCurrency();

                                    if (!empty($additional_currencies) && is_array($additional_currencies)) :

                                        foreach ($additional_currencies as $currency_code) :

                                            // remove default currency in more currencies
                                            if ($currency_code != $default_currency) : ?>
                                                <div class="item_currency">
                                                    <div class="item_name">
                                                        <label><?= $currency_code ?>:</label>
                                                    </div>
                                                    <input type="number" class="input_price" name="bun_price_currency[<?= $currency_code ?>]" value="<?= is_array($data) ? $data['price_currency'][$currency_code] : '' ?>">
                                                </div>

                                            <?php
                                            endif;
                                        endforeach;
                                    else :

                                        $all_currencies = get_woocommerce_currencies();

                                        foreach ($all_currencies as $key => $currency_code) :

                                            // remove default currency in more currencies
                                            if ($currency_code != $default_currency) : ?>
                                                <div class="item_currency">
                                                    <label><?= $key ?>:</label>
                                                    <input type="number" name="bun_price_currency[<?= $key ?>]" value="<?= $data['price_currency'][$key] ?>">
                                                </div>
                                    <?php endif;
                                        endforeach;
                                    endif;
                                    ?>
                                </div>

                            </td>
                        </tr>

                        <!-- discount percentage -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Discount Percentage:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <input name='bun_discount_percentage' type='text' value="<?= is_array($data) ? $data['discount_percentage'] : '' ?>"> (%)
                            </td>
                        </tr>

                        <!-- show discount label -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label>Show discount label:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <input name='bun_show_discount_label' type='checkbox' value="true" <?= ($data['bun_show_discount_label'] == true) ? 'checked' : '' ?>>
                                <?php else : ?>
                                    <input name='bun_show_discount_label' type='checkbox' value="true">
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- sellout risk -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Sell-Out Risk:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="none" <?= ($data['sell_out_risk'] == "none") ? "checked" : "" ?>>
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="high" <?= ($data['sell_out_risk'] == "high") ? "checked" : "" ?>>
                                        <span>High</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="medium" <?= ($data['sell_out_risk'] == "medium") ? "checked" : "" ?>>
                                        <span>Medium</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="low" <?= ($data['sell_out_risk'] == "low") ? "checked" : "" ?>>
                                        <span>Low</span>
                                    </label>
                                <?php else : ?>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="none">
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="high">
                                        <span>High</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="medium">
                                        <span>Medium</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_sell_out_risk" value="low">
                                        <span>Low</span>
                                    </label>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- popularity -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Popularity:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="none" <?= ($data['popularity'] == "none") ? "checked" : "" ?>>
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="best-seller" <?= ($data['popularity'] == "best-seller") ? "checked" : "" ?>>
                                        <span>Best Seller</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="popular" <?= ($data['popularity'] == "popular") ? "checked" : "" ?>>
                                        <span>Popular</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="moderate" <?= ($data['popularity'] == "moderate") ? "checked" : "" ?>>
                                        <span>Moderate</span>
                                    </label>

                                <?php else : ?>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="none">
                                        <span>None</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="best-seller">
                                        <span>Best Seller</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="popular">
                                        <span>Popular</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="bun_popularity" value="moderate">
                                        <span>Moderate</span>
                                    </label>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- free shipping -->
                        <tr>
                            <th scope="row" class="titledesc">
                                <label>Free Shipping:</label>
                            </th>
                            <td class="forminp forminp-text">
                                <?php if (is_array($data)) : ?>
                                    <input type="checkbox" name="free_shipping" value="true" <?= ($data['free_shipping'] == true) ? 'checked' : '' ?>>
                                <?php else : ?>
                                    <input type="checkbox" name="free_shipping" value="true">
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- add feature description -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="desc_input">Add package feature description:</label>
                                <input class='button description_add' type='button' value='<?php _e('Add description', 'default'); ?>' style="position: relative; bottom: 4px;" />
                            </th>
                            <td class="forminp forminp-text">
                                <div class='feature_desc_add' data-type='bundle'>
                                    <div class='input_zone'>
                                        <?php if ($data === null || empty($data['description'])) : ?>
                                            <div>
                                                <input name="feature_free_desc[]" class="desc_input" type="text" value="">
                                            </div><br>
                                        <?php endif; ?>
                                        <?php if (is_array($data) && isset($data['description']) && !empty($data['description'])) :
                                            foreach ($data['description'] as $key => $value) : ?>
                                                <div>
                                                    <input name="feature_free_desc[]" class="desc_input quantity_main_bundle" type="text" value="<?= ($value) ?>">
                                                    <button type="button" class="remove button button-primary">x</button>
                                                </div><br>
                                        <?php endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- item/package label -->
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="label_input">Add package item label:</label>
                                <input class='button label_add' type='button' value='<?php _e('Add label', 'default'); ?>' style="position: relative; bottom: 4px;" />
                            </th>
                            <td class="forminp forminp-text">
                                <div class='item_label_add' data-type='bundle'>
                                    <div class='input_zone'>
                                        <?php if ($data === null || empty($data['label'][0]['name'])) : ?>
                                            <div>
                                                <input name="name_label_bundle[]" class="label_input" type="text" value="">
                                                <label class="label_inline"> <b>Color:</b> </label><input type="text" value="#bada55" name="color_label_bundle[]" class="my-color-field" data-default-color="#effeff" /><br>
                                            </div><br>
                                        <?php endif; ?>
                                        <?php if (is_array($data) && !isset($data['label'])) :
                                            foreach ($data['label'] as $key => $value) : ?>
                                                <div>
                                                    <input name="name_label_bundle[]" class="label_input" type="text" value="<?= ($value['name']) ?>">
                                                    <label class="label_inline"> <b>Color:</b> </label><input type="text" value="<?= ($value['color']) ?>" name="color_label_bundle[]" class="my-color-field" data-default-color="<?= ($value['color']) ?>" />
                                                    <button type="button" class="remove button button-primary">x</button>
                                                </div><br>
                                        <?php endforeach;
                                        endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            <!-- end bundle option -->
<?php
        }

        /**
         * Get and return currency
         *
         * @return string currency
         */
        private static function mwc_getCurrency() {

            $additional_currencies = [];
            $total_number          = min(get_option('alg_currency_switcher_total_number', 2), apply_filters('alg_wc_currency_switcher_plugin_option', 2));

            // loop
            for ($i = 1; $i <= $total_number; $i++) :
                if ('yes' === get_option('alg_currency_switcher_currency_enabled_' . $i, 'yes')) :
                    $additional_currencies[] = get_option('alg_currency_switcher_currency_' . $i);
                endif;
            endfor;

            // return
            return $additional_currencies;
        }
    }

    // init action class
    new MWCBundleSectionAdmin();
endif;
