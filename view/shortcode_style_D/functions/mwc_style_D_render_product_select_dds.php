<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

// ==================
// REQUIRED FUNCTIONS
// ==================
require_once __DIR__ . '/mwc_render_style_D_bundle_prods_dds.php';
require_once __DIR__ . '/mwc_render_style_D_free_prods_dds.php';
require_once __DIR__ . '/mwc_render_style_D_free_off_prods_dds.php';

/**
 * Render product select dds
 *
 * @param array $package_product_ids
 * @return void
 */
function mwc_style_D_render_product_select_dds($package_product_ids)
{

    // get current currency
    $current_curr = function_exists('alg_get_current_currency_code') ? alg_get_current_currency_code() : get_woocommerce_currency();

    // empty cart on page load
    WC()->cart->empty_cart();

?>
    <div id="mwc-style-D" class="mwc-tab-content">
        <?php

        // Tab content (variation dropdowns)
        foreach ($package_product_ids as $opt_i => $prod_data) :

            /**
             * Code which checks for presence of variable
             */
            $bundle_ptypes_string = '';

            $bun_type = $prod_data['type'];

            if ($bun_type === 'bun') :

                $prods = $prod_data['prod'];

                foreach ($prods as $index => $pdata) :

                    $product_id = $pdata['id'];

                    $product = wc_get_product($product_id);
                    $bundle_ptypes_string .= $product->get_type();

                endforeach;

            else :

                $product_id = $prod_data['id'];

                $product = wc_get_product($product_id);
                $bundle_ptypes_string .= $product->get_type();

            endif;

            // only display variations dropdown if variable product present 
            if (is_int(strpos($bundle_ptypes_string, 'variable'))) :
                ?>

                <div hidden class="mwc_product_variations mwc_product_variations_<?php echo (trim($prod_data['bun_id'])) ?> info_products_checkout" data-bundle_id="<?php echo (trim($prod_data['bun_id'])) ?>">
                    <span class="style_d_bundle_title"><?= __('Choose your product(s):', 'woocommerce') ?> </span>

                    <?php

                    //package selection variations free and off
                    if ($prod_data['type'] === 'free' || $prod_data['type'] === 'off') :

                        // ---------------------------
                        // render free and off products
                        // ---------------------------
                        mwc_render_style_D_free_off_prods_dds($prod_data, $current_curr);


                    else :

                        // ---------------------------
                        // render bundle products
                        // ---------------------------
                        mwc_render_style_D_bundle_prods_dds($prod_data, $current_curr);

                    endif;

                    // ----------------------------
                    // render bundle free products
                    // ----------------------------
                    mwc_render_style_D_free_prods_dds($prod_data, $current_curr)

                    ?>

                </div>

        <?php endif;

        endforeach; ?>

    </div><!-- .mwc-tab-content -->

<?php }

?>