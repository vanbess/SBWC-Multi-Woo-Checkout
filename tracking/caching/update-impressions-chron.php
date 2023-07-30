<?php

// ******************************************************************
// ACTION SCHEDULER JOB TO UPDATE UPSELL IMPRESSIONS EVERY 5 MINUTES
// ******************************************************************

// schedule AS action
add_action('init', function () {
    if (function_exists('as_next_scheduled_action') && false === as_next_scheduled_action('mwco_update_upsell_tracking_impressions')) {
        as_schedule_recurring_action(strtotime('now'), 300, 'mwco_update_upsell_tracking_impressions');
    }
});

// AS function to run to update impressions
add_action('mwco_update_upsell_tracking_impressions', function () {

    // retrieve mwco bundle impressions
    $mwco_bundle_sell_imps = maybe_unserialize(get_transient('mwco_bundle_impressions'));

    if ($mwco_bundle_sell_imps) :
        file_put_contents(MWC_PLUGIN_DIR . 'tracking/caching/mwco-bundle-upsells.txt', print_r($mwco_bundle_sell_imps, true));
    else :
        file_put_contents(MWC_PLUGIN_DIR . 'tracking/caching/mwco-bundle-upsells.txt', print 'no mwco bundle upsells present');
    endif;

    // ***************************************************
    // update multi woo checkout bundle sells impressions
    // ***************************************************
    if ($mwco_bundle_sell_imps) :

        // retrieve tracking posts
        $mwc_bundle_tracking_posts = get_posts(array(
            'post_type'   => 'bundle_selection',
            'numberposts' => -1,
        ));

        // update impressions as needed
        if ($mwc_bundle_tracking_posts) :

            foreach ($mwc_bundle_tracking_posts as $post) :

                setup_postdata($post);

                // retrieve current impressions
                $curr_imps = get_post_meta($post->ID, 'count_view', true);

                // retrieve product id
                $prod_discount_data = get_post_meta($post->ID, 'product_discount', true);
                $package_type       = $prod_discount_data['selValue'];
                $product_data       = $prod_discount_data["selValue_$package_type"];

                file_put_contents(MWC_PLUGIN_DIR . 'tracking/caching/prod-bundle-discount-data.txt', print_r($prod_discount_data, true), FILE_APPEND);

                if ($package_type === 'bun') :
                    $products = $product_data['post'];
                    foreach ($products as $key => $data) :

                        // impressions as found in cache
                        $cached_imps = $mwco_bundle_sell_imps[$data['id']];

                        // updated impressions
                        $new_imps = (int)$curr_imps + (int)$cached_imps;

                        // if impressions found in json for product id, update impressions
                        update_post_meta($post->ID, 'count_view', $new_imps);

                    endforeach;
                else :

                    // impressions as found in cache
                    $cached_imps = $mwco_bundle_sell_imps[$product_data['post']['id']];

                    // updated impressions
                    $new_imps = (int)$curr_imps + (int)$cached_imps;

                    // if impressions found in json for product id, update impressions
                    update_post_meta($post->ID, 'count_view', $new_imps);

                endif;

            endforeach;
            wp_reset_postdata();
        endif;

        // delete cached impressions
        delete_transient('mwco_bundle_impressions');

    endif;

    // retrieve mwco addon impressions
    $mwco_addon_imps = maybe_unserialize(get_transient('mwco_addon_impressions'));

    if ($mwco_addon_imps) :
        file_put_contents(MWC_PLUGIN_DIR . 'tracking/caching/mwco-addon-upsells.txt', print_r($mwco_addon_imps, true));
    else :
        file_put_contents(MWC_PLUGIN_DIR . 'tracking/caching/mwco-addon-upsells.txt', print 'no mwco addon upsells present');
    endif;

    // ***************************************************
    // update multi woo checkout bundle sells impressions
    // ***************************************************
    if ($mwco_addon_imps) :

        // retrieve tracking posts
        $mwc_bundle_tracking_posts = get_posts(array(
            'post_type'   => 'mwc-addon-product',
            'numberposts' => -1,
        ));

        // update impressions as needed
        if ($mwc_bundle_tracking_posts) :

            foreach ($mwc_bundle_tracking_posts as $post) :

                setup_postdata($post);

                // retrieve current impressions
                $curr_imps = get_post_meta($post->ID, 'count_view', true);

                // retrieve product id
                $product_id = get_post_meta($post->ID, 'product_id', true);

                // impressions as found in json file
                $cached_imps = $mwco_addon_imps[$product_id];

                // updated impressions
                $new_imps = (int)$curr_imps + $cached_imps;

                // if impressions found in json for product id, update impressions
                update_post_meta($post->ID, 'count_view', $new_imps);

            endforeach;
            wp_reset_postdata();
        endif;

        // delete cached impressions transient
        delete_transient('mwco_addon_impressions');

    endif;
});