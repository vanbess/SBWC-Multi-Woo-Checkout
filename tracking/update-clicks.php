<?php

/**
 * Update upsell clicks via AJAX
 */

add_action('wp_footer', function () {

    // create nonce for ajax
    $nonce = wp_create_nonce('mwc update clicks');

    // create session nonce
    $session_nonce = wp_create_nonce('mwc products to session');

    // setup ajax url
    $ajax_url = admin_url('admin-ajax.php');
?>

    <script id="mwc_register_clicks">
        $ = jQuery;

        $(document).ready(function() {

            // bundle items on click
            $('.mwc_item_div').on('click', function() {

                var bundle_id = $(this).data('bundle_id');

                var data = {
                    '_ajax_nonce': '<?php echo $nonce; ?>',
                    'action': 'mwc_clicks_bundles_addons',
                    'bundle_id': bundle_id
                }

                $.post('<?php echo $ajax_url ?>', data);

            });

            // add-on items on click
            $('.mwc_item_addon').on('click', function() {

                var addon_id = $(this).data('addon_id');

                var data = {
                    '_ajax_nonce': '<?php echo $nonce; ?>',
                    'action': 'mwc_clicks_bundles_addons',
                    'addon_id': addon_id
                }

                $.post('<?php echo $ajax_url ?>', data, function(response){
                    // console.log(response);
                });

                return false;
            });

            // add/remove add-on products to tracking session if checked
            var addon_prod_ids = [];

            $('.mwc_item_addon').on('change', function() {

                if ($(this).hasClass('i_selected')) {
                    addon_prod_ids.push($(this).data('id'));
                } else {
                    addon_prod_ids.pop($(this).data('id'));
                }

                var data = {
                    '_ajax_nonce': '<?= $session_nonce ?>',
                    'action': 'mwc_add_products_to_session',
                    'addon_prods': addon_prod_ids,
                    'addons_to_session': true
                }

                $.post('<?= $ajax_url ?>', data, function(response) {
                    // console.log(response);
                });

            });

            // add/remove bundle products to tracking session if checked
            $('.mwc_item_div').on('change', function() {
                
                var bundle_prod_ids = [];

                bundle_prod_ids.push($(this).find('.c_prod_item').data('id'));

                var data = {
                    '_ajax_nonce': '<?= $session_nonce ?>',
                    'action': 'mwc_add_products_to_session',
                    'bundle_prod': bundle_prod_ids,
                    'bundle_to_session': true
                }

                $.post('<?= $ajax_url ?>', data, function(response) {
                    // console.log(response);
                });

            });
        });
    </script>

<?php });

/**
 * Register clicks AJAX
 */
add_action('wp_ajax_nopriv_mwc_clicks_bundles_addons', 'mwc_clicks_bundles_addons');
add_action('wp_ajax_mwc_clicks_bundles_addons', 'mwc_clicks_bundles_addons');

function mwc_clicks_bundles_addons()
{

    check_ajax_referer('mwc update clicks');

    // *********************
    // UPDATE BUNDLE CLICKS
    // *********************
    if (isset($_POST['bundle_id'])) :

        // retrieve bundle id
        $bundle_id = $_POST['bundle_id'];

        // query bundle_selection posts
        $posts = new WP_Query([
            'post_type' => 'bundle_selection',
            'p'         => (int)$bundle_id
        ]);

        // update clicks as needed
        if ($posts->have_posts()) :

            while ($posts->have_posts()) : $posts->the_post();

                // retrieve current clicks
                $curr_clicks = get_post_meta(get_the_ID(), 'count_click', true);

                // if current clicks, increment and update
                if ($curr_clicks) :
                    $new_clicks = (int)$curr_clicks += 1;
                    update_post_meta(get_the_ID(), 'count_click', $new_clicks);

                // if no current clicks, insert initial click
                else :
                    update_post_meta(get_the_ID(), 'count_click', 1);
                endif;

            endwhile;
            wp_reset_postdata();
        endif;

    endif;

    // ********************
    // UPDATE ADDON CLICKS
    // ********************
    if (isset($_POST['addon_id'])) :

        $addon_id = $_POST['addon_id'];

        // query bundle_selection posts
        $posts = new WP_Query([
            'post_type' => 'mwc-addon-product',
            'p'         => (int)$addon_id
        ]);

        // update clicks as needed
        if ($posts->have_posts()) :

            while ($posts->have_posts()) : $posts->the_post();

                // retrieve current clicks
                $curr_clicks = get_post_meta(get_the_ID(), 'count_click', true);

                // if current clicks, increment and update
                if ($curr_clicks) :
                    $new_clicks = (int)$curr_clicks += 1;
                    update_post_meta(get_the_ID(), 'count_click', $new_clicks);

                // if no current clicks, insert initial click
                else :
                    update_post_meta(get_the_ID(), 'count_click', 1);
                endif;

            endwhile;
            wp_reset_postdata();

        endif;

    endif;

    wp_die();
}

/**
 * Add add-on and bundle products to $_SESSION
 */
add_action('wp_ajax_nopriv_mwc_add_products_to_session', 'mwc_add_products_to_session');
add_action('wp_ajax_mwc_add_products_to_session', 'mwc_add_products_to_session');

function mwc_add_products_to_session()
{

    check_ajax_referer('mwc products to session');

    // add add-on products to session
    if (isset($_POST['addons_to_session'])) :

        if (!session_id()) :
            session_start();
        endif;

        if (isset($_POST['addon_prods'])) :
            $_SESSION['mwc_addon_prod_ids'] = $_POST['addon_prods'];
        else :
            $_SESSION['mwc_addon_prod_ids'] = null;
        endif;

        wp_send_json($_SESSION);

    endif;

    // add bundle products to session
    if (isset($_POST['bundle_to_session'])) :

        if (!session_id()) :
            session_start();
        endif;

        if (isset($_POST['bundle_prod'])) :
            $_SESSION['mwc_bundle_prod_ids'] = $_POST['bundle_prod'];
        else :
            $_SESSION['mwc_bundle_prod_ids'] = null;
        endif;

        wp_send_json($_SESSION);

    endif;

    wp_die();
}
