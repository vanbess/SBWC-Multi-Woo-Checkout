<?php

/**
 * Process conversions for upsells
 */

add_action('woocommerce_thankyou', function ($order_id) {

    // start session if not started, otherwise we can grab our data from $_SESSION
    if (!session_id()) :
        session_start();
    endif;
    
    // retrieve order object
    $order_object = wc_get_order($order_id);

    // retreive ALG currency if set, else order currency
    $order_currency = isset($_SESSION['alg_currency']) ? $_SESSION['alg_currency'] : $order_object->get_currency();

    // mwc addon ids
    $addon_pids = isset($_SESSION['mwc_addon_prod_ids']) ? array_unique($_SESSION['mwc_addon_prod_ids']) : null;

    // mwc bundle ids
    $bundle_pids = isset($_SESSION['mwc_bundle_prod_ids']) ? array_unique($_SESSION['mwc_bundle_prod_ids']) : null;

    // ****************************************************
    // 2. retrieve product ids, pricing and qty from order
    // ****************************************************

    // retrieve order items object
    $items_object = $order_object->get_items();

    // loop through $items_object and update tracking as needed
    foreach ($items_object as $order_item_id => $item) :

        // retrieve product id
        $product_id = $item->get_product_id();

        // retrieve item total
        $item_total = $item->get_total();

        // retrieve item qty
        $item_qty = $item->get_quantity();

        // ==============================
        // update mwc bundle conversions
        // ==============================
        if (is_array($bundle_pids) && in_array($product_id, $bundle_pids)) :

            // query tracking data
            $mwc_bundle_query = get_posts([
                'numberposts' => -1,
                'post_type'   => 'bundle_selection',
            ]);

            // if query data, loop and add impressions + clicks
            if ($mwc_bundle_query) :

                // loop through post object and build tracking data
                foreach ($mwc_bundle_query as $post) :

                    setup_postdata($post);

                    // retrieve tracking data
                    $count_paid  = get_post_meta($post->ID, 'count_paid', true) ? get_post_meta($post->ID, 'count_paid', true) : 0;
                    $revenue     = get_post_meta($post->ID, 'revenue', true) ? get_post_meta($post->ID, 'revenue', true) : 0.00;

                    // retrieve bundle type and products
                    $bundle_data = get_post_meta($post->ID, 'product_discount', true);

                    $bundle_type = $bundle_data['selValue'];

                    // **************************
                    // if bundle type === bundle
                    // **************************
                    if ($bundle_type === 'bun') :

                        // retrieve bundle products
                        $bundle_products = $bundle_data["selValue_$bundle_type"]['post'];

                        // loop through bundle products and find a match for $product_id
                        foreach ($bundle_products as $index => $data_arr) :

                            $bundle_pid = $data_arr['id'];

                            // if match found, update
                            if ($bundle_pid == $product_id) :

                                // update paid count
                                $new_count_paid = $count_paid + $item_qty;
                                update_post_meta($post->ID, 'count_paid', $new_count_paid);

                                // update revenue
                                $new_rev = $item_total + $revenue;
                                update_post_meta($post->ID, 'revenue', $new_rev);

                            endif;

                        endforeach;

                    // **************************
                    // if bundle type !== bundle
                    // **************************
                    else :

                        $bundle_pid = $bundle_data["selValue_$bundle_type"]['post']['id'];

                        if ($bundle_pid == $product_id) :
                            
                            // update paid count
                            $new_count_paid = $count_paid + $item_qty;
                            update_post_meta($post->ID, 'count_paid', $new_count_paid);

                            // update revenue
                            $new_rev = $item_total + $revenue;
                            update_post_meta($post->ID, 'revenue', $new_rev);

                        endif;

                    endif;

                    // add currency to tracking item for correct display in backend
                    update_post_meta($post->ID, 'order_currency', $order_currency);

                endforeach;

            else :
                continue;
            endif;

        endif;

        // =============================
        // update mwc add-on conversions
        // =============================
        if (is_array($addon_pids) && in_array($product_id, $addon_pids)) :

            // query tracking data
            $mwc_addon_query = get_posts([
                'numberposts' => -1,
                'post_type'   => 'mwc-addon-product',
                'meta_key'    => 'product_id',
                'meta_value'  => $product_id,
            ]);

            // if query data, loop and add impressions + clicks
            if ($mwc_addon_query) :

                // loop through post object and build tracking data
                foreach ($mwc_addon_query as $post) :
                    
                    setup_postdata($post);

                    // retrieve tracking data
                    $count_paid  = get_post_meta($post->ID, 'count_paid', true) ? get_post_meta($post->ID, 'count_paid', true) : 0;
                    $revenue     = get_post_meta($post->ID, 'revenue', true) ? get_post_meta($post->ID, 'revenue', true) : 0.00;

                    // update paid count
                    $new_count_paid = $count_paid + $item_qty;
                    update_post_meta($post->ID, 'count_paid', $new_count_paid);
                    
                    // update revenue
                    $new_rev = $item_total + $revenue;
                    update_post_meta($post->ID, 'revenue', $new_rev);

                    // add currency to tracking item for correct display in backend
                    update_post_meta($post->ID, 'order_currency', $order_currency);

                endforeach;

            else :
                continue;
            endif;

        endif;

    endforeach;

    // destroy session to avoid duplicate entries
    // session_destroy();
});
