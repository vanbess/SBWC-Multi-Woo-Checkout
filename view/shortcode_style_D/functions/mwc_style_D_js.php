<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * Base JS for setting bundle discount % and pricing on click/load, showing/hiding variations, and removing default woo styles
 *
 * @return void
 */
function mwc_style_D_js()
{ ?>

    <!-- style D js -->
    <script id="mwc-template-d-debug-js" type="text/javascript">
        jQuery(document).ready(function($) {
            // on load
            $(window).on('load', function() {

                // console.log('mwc-style-d-DD-js loaded');

                $('.col.customer_info').removeClass('large-7');
                $('.col.payment_opt').removeClass('large-5');
                $('.style_D_checkout_form').show();

                // get selected bundle id
                let selected_bundle_id = $('.mwc_active_product').data('bundle_id');

                // console.log(selected_bundle_id);

                // show selected bundle variations
                $('.mwc_product_variations_' + selected_bundle_id).show();

                // debug
                // console.log('mwc-template-d-debug-js loaded');

                // ********************************
                // on load trigger click on radio
                // ********************************
                $('.mwc_active_product').find('.radio_select').trigger('click');

                let bundle_id = $('.mwc_active_product').data('bundle_id');

                let price_old = $('#price_old_' + bundle_id).text();
                let price_new = $('#price_new_' + bundle_id).text();
                let discount_total = $('.discount-total').text();
                let grand_total = $('.grand-total').text();

                // regex replace everything in totals but numbers and dots
                let regex = /[^0-9.]/g;

                price_old = price_old.replace(regex, '');
                discount_total = discount_total.replace(regex, '');
                grand_total = grand_total.replace(regex, '');

                let discount_percentage = (discount_total / grand_total) * 100;

                // debug
                // console.log('discount_percentage', discount_percentage);

                $('.label_text').text(parseFloat(discount_percentage).toFixed(0) + '% OFF');

                $('.label_secondary_text').text('Your ' + parseFloat(discount_percentage).toFixed(0) + '% Discount Has Been Applied');

                // *********
                // on click
                // *********
                $('.productRadioListItem').click(function() {

                    setTimeout(() => {
                        let discount_total = $('.discount-total').text();
                        let grand_total = $('.grand-total').text();

                        // regex replace everything in totals but numbers and dots
                        let regex = /[^0-9.]/g;

                        discount_total = discount_total.replace(regex, '');
                        grand_total = grand_total.replace(regex, '');

                        // let discount_percentage = (discount_total / grand_total) * 100;
                        let discount_percentage = parseFloat(discount_total) / (parseFloat(discount_total) + parseFloat(grand_total)) * 100;

                        // debug
                        // console.log(parseFloat(discount_total) + parseFloat(grand_total));
                        // console.log('discount_percentage', discount_percentage);

                        $('.label_text').text(parseFloat(discount_percentage).toFixed(0) + '% OFF');

                        $('.label_secondary_text').text('Your ' + parseFloat(discount_percentage).toFixed(0) + '% Discount Has Been Applied');

                    }, 100);

                });

            });
        });
    </script>

<?php }


?>