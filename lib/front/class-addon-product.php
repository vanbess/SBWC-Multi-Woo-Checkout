<?php

/**
 * Add post type addon product
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('MWC_AddonProduct')) {

    /*
     * MWC_AddonProduct Class.
     */
    class MWC_AddonProduct {

        /**
         * Constructor
         */
        public function __construct() {

            // cpt
            add_action('init', array(__CLASS__, 'create_post_type_addon_product'));

            // metaboxes
            add_action('admin_init', array(__CLASS__, 'add_product_id_meta_boxes'));

            // save post
            add_action('save_post', array(__CLASS__, 'save_product_id_fields'));

            // post columns
            add_filter('manage_mwc-addon-product_posts_columns', array(__CLASS__, 'columns_head_only_addon_product'), 10);
            add_action('manage_mwc-addon-product_posts_custom_column', array(__CLASS__, 'columns_content_addon_product'), 10, 2);

            // hook get info checkout after order completed
            // add_action('woocommerce_order_status_completed', array(__CLASS__, 'mwc_action_woocommerce_checkout_order_completed'), 10, 1);
        }

        /**
         * create_post_type_addon_product
         *
         * @return void
         */
        public static function create_post_type_addon_product() {
            $args = array(
                'labels' => array(
                    'name'               => __('Addon product', 'woocommerce'),
                    'singular_name'      => __('Addon product', 'woocommerce'),
                    'add_new'            => __('Add New', 'woocommerce'),
                    'add_new_item'       => __('Add New Addon Product', 'woocommerce'),
                    'edit_item'          => __('Edit Addon Product', 'woocommerce'),
                    'new_item'           => __('New Addon Product', 'woocommerce'),
                    'view_item'          => __('View Addon Product', 'woocommerce'),
                    'search_items'       => __('Search Addon Product', 'woocommerce'),
                    'not_found'          => __('Nothing Found', 'woocommerce'),
                    'not_found_in_trash' => __('Nothing found in the Trash', 'woocommerce'),
                    'parent_item_colon'  => ''
                ),
                'show_in_menu'       => 'mwc',
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'query_var'          => true,
                'rewrite'            => true,
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'supports'           => array('title')
            );

            register_post_type('mwc-addon-product', $args);
        }

        /**
         * Add product ID metaboxes
         *
         * @return void
         */
        public static function add_product_id_meta_boxes() {
            add_meta_box(
                "addon_product_id_meta",
                __('Product ID', 'woocommerce'),
                array(__CLASS__, "add_product_id_addon_product_meta_box"),
                "mwc-addon-product",
                "normal",
                "low"
            );
        }

        /**
         * Add product ID metabox
         *
         * @return void
         */
        public static function add_product_id_addon_product_meta_box() {

            global $post;
            $custom = get_post_custom($post->ID, true);

            if (!is_null($custom) && is_array($custom) && !empty($custom)) : ?>

                <p>
                    <label><?php echo __('Product ID', 'woocommerce') ?></label>
                    <input type="number" name="product_id" value="<?php echo array_shift($custom["product_id"]) ?>" min="0" />
                </p>
                <p>
                    <label>
                        <?php echo __('One-time offer', 'woocommerce') ?>
                        <input type="checkbox" name="one_time_offer" value="<?php echo (array_shift($custom["one_time_offer"]) == "yes") ? 'checked' : '' ?>" />
                    </label>
                </p>
                <p>
                    <label><?php echo __('Percentage discount', 'woocommerce') ?></label>
                    <input type="number" name="percentage_discount" value="<?php echo (array_shift($custom["percentage_discount"])) ?>" min="0" />
                    <span>%</span>
                </p>
                <p>
                    <label>
                        <?php echo __('Disable WooSwatches', 'woocommerce') ?>
                        <input type="checkbox" name="disable_woo_swatches" value="<?php echo (array_shift($custom["disable_woo_swatches"]) == "yes") ? 'checked' : '' ?>" />
                    </label>
                </p>

            <?php else : ?>

                <p>
                    <label><?php echo __('Product ID', 'woocommerce') ?></label>
                    <input type="number" name="product_id" value="" min="0" />
                </p>
                <p>
                    <label>
                        <?php echo __('One-time offer', 'woocommerce') ?>
                        <input type="checkbox" name="one_time_offer" value="" />
                    </label>
                </p>
                <p>
                    <label><?php echo __('Percentage discount', 'woocommerce') ?></label>
                    <input type="number" name="percentage_discount" value="" min="0" />
                    <span>%</span>
                </p>
                <p>
                    <label>
                        <?php echo __('Disable WooSwatches', 'woocommerce') ?>
                        <input type="checkbox" name="disable_woo_swatches" value="" />
                    </label>
                </p>
<?php endif;
        }

        /**
         * save_product_id_fields
         *
         * @param int $post_id
         * @return void
         */
        public static function save_product_id_fields($post_id) {

            global $post;

            if (!$post || $post->post_type != 'mwc-addon-product' || $post_id != $post->ID) {
                return;
            }

            update_post_meta($post->ID, "product_id", $_POST["product_id"]);
            update_post_meta($post->ID, "one_time_offer", $_POST["one_time_offer"]);
            update_post_meta($post->ID, "percentage_discount", $_POST["percentage_discount"]);
            update_post_meta($post->ID, "disable_woo_swatches", $_POST["disable_woo_swatches"]);
        }


        /**
         * columns_head_only_addon_product
         *
         * @param array $defaults
         * @return void
         */
        public static function columns_head_only_addon_product($defaults) {

            $defaults['post_id'] = __('Post ID', 'woocommerce');
            $defaults['product_id'] = __('Product ID', 'woocommerce');
            $defaults['count_view'] = __('View', 'woocommerce');
            $defaults['count_click'] = __('Click', 'woocommerce');
            $defaults['count_paid'] = __('Paid', 'woocommerce');
            $defaults['conversion_rate'] = __('Conversion Rate', 'woocommerce');
            $defaults['revenue'] = __('Revenue', 'woocommerce');

            return $defaults;
        }

        /**
         * columns_content_addon_product
         *
         * @param string $column_name
         * @param int $post_ID
         * @return void
         */
        public static function columns_content_addon_product($column_name, $post_ID) {

            switch ($column_name) {
                case 'post_id':
                    echo ($post_ID);
                    break;
                case 'product_id':
                    echo (get_post_meta($post_ID, 'product_id', true) ?: '-');
                    break;
                case 'count_view':
                    echo (get_post_meta($post_ID, 'count_view', true) ?: '-');
                    break;
                case 'count_click':
                    echo (get_post_meta($post_ID, 'count_click', true) ?: '-');
                    break;
                case 'count_paid':
                    echo get_post_meta($post_ID, 'count_paid', true) ?: '-';
                    break;
                case 'conversion_rate':
                    $paid_count      = (int)get_post_meta($post_ID, 'count_paid', true);
                    $view_count      = (int)get_post_meta($post_ID, 'count_view', true);
                    $click_count     = (int)get_post_meta($post_ID, 'count_click', true);
                    $conversion_rate = 0;
                    $impressions     = $view_count + $click_count;
                    $rate            = $paid_count && $view_count ? (($paid_count * 100) / $impressions) : 0;

                    update_post_meta($post_ID, 'conversion_rate', $rate);

                    $conversion_rate = get_post_meta($post_ID, 'conversion_rate', true);
                    echo $conversion_rate > 0 ? number_format($conversion_rate, 2, '.', '') . '%' : '-';
                    break;
                case 'revenue':
                    // revenue and order currency
                    $revenue        = get_post_meta($post_ID, 'revenue', true);
                    $order_currency = get_post_meta($post_ID, 'order_currency', true);

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
            }
        }

        /**
         * mwc_update_statistics_addon_product
         *
         * @param array $addon_ids
         * @param string $type
         * @return void
         */
        public static function mwc_update_statistics_addon_product($addon_ids, $type) {
            //update or insert post meta
            foreach ($addon_ids as $addon_id) {
                $exist_meta_view = get_post_meta($addon_id, $type, true);
                if ($exist_meta_view) {
                    update_post_meta($addon_id, $type, ++$exist_meta_view);
                } else {
                    add_post_meta($addon_id, $type, 1, true);
                }
            }

            return true;
        }

        /**
         * mwc_action_woocommerce_checkout_order_completed
         *
         * @param int $order_id
         * @return void
         */
        // public static function mwc_action_woocommerce_checkout_order_completed($order_id) {

        //     $order = wc_get_order($order_id);

        //     foreach ($order->get_items() as $item_key => $item) {

        //         foreach ($item->get_meta_data() as $meta) {
        //             if ($meta->key === 'mwc_addon_id') {
        //                 // get product
        //                 if ($item['variation_id']) {
        //                     $prod = wc_get_product($item['variation_id']);
        //                 } else {
        //                     $prod = wc_get_product($item['product_id']);
        //                 }
        //                 $prod_price = $prod->get_price() * $item['qty'];

        //                 $exist_conv = get_post_meta($meta->value, 'addon_product_revenue', true);
        //                 if ($exist_conv) {
        //                     update_post_meta($meta->value, 'addon_product_revenue', ['total_paid' => ++$exist_conv['total_paid'], 'total_price' => $exist_conv['total_price'] + $prod_price]);
        //                 } else {
        //                     add_post_meta($meta->value, 'addon_product_revenue', ['total_paid' => 1, 'total_price' => $prod_price], true);
        //                 }

        //                 break;
        //             }
        //         }
        //     }
        // }
    }

    new MWC_AddonProduct();
}
