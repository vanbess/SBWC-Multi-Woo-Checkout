<?php

/**
 * Custom bulk action to reset tracking data for addon product
 */
add_filter('bulk_actions-edit-mwc-addon-product', function ($bulk_actions) {
    $bulk_actions['reset-tracking'] = __('Reset Tracking', 'woocommerce');
    return $bulk_actions;
});

/**
 * Actually reset tracking via bulk action
 */
add_filter('handle_bulk_actions-edit-mwc-addon-product', function ($redirect_url, $action, $post_ids) {
    if ($action == 'reset-tracking') {
        foreach ($post_ids as $post_id) {

            // reset all tracking meta
            delete_post_meta($post_id, 'view');
            delete_post_meta($post_id, 'click');
            delete_post_meta($post_id, 'count_paid');
            delete_post_meta($post_id, 'conversion_rate');
            delete_post_meta($post_id, 'revenue');
        }
        $redirect_url = add_query_arg('reset-tracking', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);

/**
 * Custom bulk action to reset tracking data for bundle product
 */
add_filter('bulk_actions-edit-bundle_selection', function ($bulk_actions) {
    $bulk_actions['reset-tracking'] = __('Reset Tracking', 'woocommerce');
    return $bulk_actions;
});

/**
 * Actually reset tracking via bulk action
 */
add_filter('handle_bulk_actions-edit-bundle_selection', function ($redirect_url, $action, $post_ids) {
    if ($action == 'reset-tracking') {
        foreach ($post_ids as $post_id) {

            // reset all tracking meta
            delete_post_meta($post_id, 'count_view');
            delete_post_meta($post_id, 'count_click');
            delete_post_meta($post_id, 'count_paid');
            delete_post_meta($post_id, 'conversion_rate');
            delete_post_meta($post_id, 'revenue');
        }
        $redirect_url = add_query_arg('reset-tracking', count($post_ids), $redirect_url);
    }
    return $redirect_url;
}, 10, 3);
