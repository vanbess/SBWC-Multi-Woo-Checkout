<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

function mwc_style_D_add_to_cart_js()
{ ?>
    <script id="style-D-ATC-JS">
        $ = jQuery.noConflict();

        /**
         * Add to cart ajax request
         * 
         * @param {array} product_data
         * @param {int} bundle_id
         */
        function mwc_style_d_atc_ajax(product_data, bundle_id) {

            // console.log('product_data', product_data);

            if (product_data.length > 0) {

                // ajax request
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        'action': 'mwc_atc_template_d_products',
                        'nonce': '<?php echo wp_create_nonce('mwc_atc_template_d_products'); ?>',
                        'product_data': product_data,
                        'bundle_id': bundle_id
                    },

                    success: function(response) {

                        // debug
                        console.log('response', response);

                        // trigger fragment refresh
                        $(document.body).trigger('wc_fragment_refresh');

                    },
                    error: function(error) {

                        // debug
                        console.error('error', error);

                    }
                });

                // stop execution > 1
                return false;

            }
        }

        /**
         * Generate product data for ATC ajax request
         * @param {object} element
         * @returns {array} prod_data
         */
        function mwc_style_d_return_product_data(bundle_id) {

            let prod_data = [];

            $('.mwc_product_variations_' + bundle_id).find('.c_prod_item').each(function(i, e) {

                let prod_id = $(e).attr('data-id');
                let prod_type = $(e).data('type');

                // get selected size from size dropdown (check if select's data-attibute_name matches 'attribute_pa_size')
                let selected_size = $(e).find('.var_prod_attr').filter(function() {
                    return $(this).data('attribute_name') === 'attribute_pa_size';
                }).val();

                // get selected color from color dropdown (check if select's data-attibute_name matches 'attribute_pa_color')
                let selected_color = $(e).find('.var_prod_attr').filter(function() {
                    return $(this).data('attribute_name') === 'attribute_pa_color';
                }).val();

                // get variation data from any of the selects via 'data-variations' attribute
                let variations = JSON.parse(atob($(e).find('.var_prod_attr').data('variations')));

                // search for and return the correct variation id in variations based on selected size and color
                let variation_id = variations.filter(function(variation) {
                    return variation.attributes.attribute_pa_size === selected_size && variation.attributes.attribute_pa_color === selected_color;
                })[0].variation_id;

                // debug
                // console.log('prod_id', prod_id);
                // console.log('prod_type', prod_type);
                // console.log('selected_size', selected_size);
                // console.log('selected_color', selected_color);
                // console.log('variation_id', variation_id);

                // push product data to array
                prod_data.push({
                    'prod_id': prod_id,
                    'prod_type': prod_type,
                    'selected_size': selected_size,
                    'selected_color': selected_color,
                    'variation_id': variation_id,
                    'qty': 1
                });

                // debug
                // console.log('prod_data if', prod_data);


            });

            return prod_data;
        };

        // ========
        // on load
        // ========
        $(window).load(function() {

            let product_data = '';
            let target = $('.mwc_active_product');
            let bundle_id = target.data('bundle_id');

            // ======================================================
            // on load [default bundle] AND on click [other bundles]
            // ======================================================

            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // '.productRadioListItem' on mousedown
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            $('.productRadioListItem').mousedown(function(e) {

                bundle_id = $(this).data('bundle_id');

                // hide all variations
                $('.mwc_product_variations').hide();

                // show selected bundle variations
                $('.mwc_product_variations_' + bundle_id).show();

                // debug
                console.log('mousedown bundle_id', bundle_id);
                // console.log('clicked');

                product_data = mwc_style_d_return_product_data(bundle_id);

                // debug
                // console.log('product_data clicked', product_data);

                // =================================
                // send ajax request to add to cart
                // =================================
                mwc_style_d_atc_ajax(product_data, bundle_id);

            });

            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // .wcvaswatchlabel on mousedown
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // $(document).on('mousedown', '.mwc_product_variations_' + bundle_id + ' .wcvaswatchlabel', function() {
            $(document).on('mousedown', '.wcvaswatchlabel', function() {

                // console.log('mousedown swatch');

                let linked_id = $(this).data('linked_id');
                let prd_img_src = $(this).attr('img-src');
                let parent_container = $(this).closest('.c_prod_item');
                let outer_parent_container = $(this);

                parent_container.find('.wcvaswatchlabel').removeClass('selected');
                $(this).addClass('selected');
                parent_container.find('.mwc_variation_img').attr('src', prd_img_src);

                // set variations data for dropdowns
                let linked_variations = $('#variations_prod_' + linked_id).val();

                // console.log('linked_variations', linked_variations);

                // find all selects inside parent container and set data-variations attribute to linked_variations
                parent_container.find('.var_prod_attr').attr('data-variations', linked_variations);

                // set product id on parent container
                parent_container.attr('data-id', linked_id);

                setTimeout(() => {
                    // setup product data
                    product_data = mwc_style_d_return_product_data(bundle_id);

                    // debug
                    // console.log('product_data swatch clicked', product_data);

                    // =================================
                    // send ajax request to add to cart
                    // =================================
                    mwc_style_d_atc_ajax(product_data, bundle_id);
                }, 1000);

            });

            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // size or color select on change
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            $(document).on('change', '.var_prod_attr', function() {

                product_data = mwc_style_d_return_product_data(bundle_id);

                // debug
                // console.log('product_data select changed', product_data);

                // =================================
                // send ajax request to add to cart
                // =================================
                mwc_style_d_atc_ajax(product_data, bundle_id);

            });

            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            // this on load [default bundle]
            // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            if (!target.hasClass('mwc_active_product')) return;
            bundle_id = target.data('bundle_id');
            product_data = mwc_style_d_return_product_data(bundle_id);

            // debug
            // console.log('product_data', product_data);

            // =================================
            // send ajax request to add to cart
            // =================================
            mwc_style_d_atc_ajax(product_data, bundle_id);

        });
    </script>
<?php }

?>