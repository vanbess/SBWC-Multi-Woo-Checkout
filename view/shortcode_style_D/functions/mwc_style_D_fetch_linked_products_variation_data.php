<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Fetch and return linked product ids
 * @var string $product_id - product id
 * @return array $linked_products - array of linked product ids
 * @since 1.0.0
 * @package mwc_style_D
 */

function mwc_style_D_fetch_linked_product_ids($product_id)
{

    // get linked products rules
    $all_rules = get_option('plgfymao_all_rulesplgfyplv');

    // Check if $product_id is present in any of the 'apllied_on_ids' arrays
    $linked_products = [];

    foreach ($all_rules as $rule_data) :
        if (in_array($product_id, $rule_data['apllied_on_ids'])) :
            $linked_products = $rule_data['apllied_on_ids'];
            break;
        endif;
    endforeach;

    foreach ($linked_products as $linked_id) :

        $prod = wc_get_product($linked_id);

        if ($prod->is_type('variable')) :

            $variations = base64_encode($prod->get_available_variations()); ?> 
            
            <input type="hidden" id="mwc_variations_<?php echo $linked_id; ?>" class="mwc_hidden_linked_prod_var_data" value="<?php echo $variations; ?>">

        <?php endif;

    endforeach;
}
