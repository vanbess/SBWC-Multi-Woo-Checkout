<?php

// prevent direct access
if (!defined('ABSPATH')) exit;

/**
 * JS to check for the existence of size chart and insert size chart link
 *
 * @return void
 */
function mwc_style_D_size_chart_js()
{ ?>

    <script id="mwc_style_D_size_chart_js" type="text/javascript">
        $ = jQuery.noConflict();

        $(function() {

            let attrs = $('.var_prod_attr');

            $.each(attrs, function() {
                let chart_append = $(this).siblings('.variation_name'),
                    chart_set = $(this).parents('.c_prod_item').attr('has-size-chart'),
                    pid = $(this).parents('.c_prod_item').attr('data-id');

                if (chart_set == 'true') {
                    let sbhtml_label_text = '',
                        sbhtml_link_text = $('#sbhtml_text_open_modal').val(),
                        label_text_content = '<div class="sbhtml_label_wrap">' + sbhtml_label_text + ' <span class="sbhtml_link_text" target="' + pid + '">' + sbhtml_link_text + '</span></div>';

                    chart_append.after(label_text_content);

                }

            });

            /**
             * Prepend size guide svg to size guide link
             */
            $('.sbhtml_link_text').prepend('<svg class="sbhtml-chart-svg" data-v-6b417351="" width="24" viewBox="0 -4 34 30" xmlns="http://www.w3.org/2000/svg" class="w-3 w-6"><path d="M32.5 11.1c-.6 0-1 .4-1 1v11.8h-1.9v-5.4c0-.6-.4-1-1-1s-1 .4-1 1v5.4h-3.7v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6h-3.7v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6h-4.1v-3.6c0-.6-.4-1-1-1s-1 .4-1 1v3.6H6.4v-5.4c0-.6-.4-1-1-1s-1 .4-1 1v5.4H2.5V12.1c0-.6-.4-1-1-1s-1 .4-1 1v12.8c0 .6.4 1 1 1h31c.6 0 1-.4 1-1V12.1c0-.6-.4-1-1-1z" fill="#666666"></path><path d="M27.1 12.4v-.6c0-.1-.1-.1-.1-.2l-2.6-3c-.4-.6-1-.6-1.5-.3-.4.4-.5 1-.1 1.4L24 11H10l1.2-1.3c.4-.4.3-1-.1-1.4-.5-.3-1.1-.3-1.5.1l-2.6 3s0 .1-.1.1l-.1.1c0 .1-.1.1-.1.2v.2c0 .1 0 .1.1.2 0 .1.1.1.1.1s0 .1.1.1l2.6 3c.2.2.5.3.8.3.2 0 .5-.1.7-.2.4-.4.5-1 .1-1.4l-1.2-1h14l-1.2 1.3c-.4.4-.3 1 .1 1.4.2.2.4.2.7.2.3 0 .6-.1.8-.3l2.6-3c0-.1.1-.1.1-.2v-.1z" fill="#666666"></path></svg>');

            // hide modal and overlay
            $('.sbhtml_chart_overlay, .sbhtml_modal_close').on('click', function(e) {
                e.preventDefault();
                $(this).closest('.sbhtml_chart_overlay, .sbhtml_chart_modal').hide();
            });

            $('.sbhtml_modal_close').on('click', function(e) {
                e.preventDefault();
                $(this).parents('.sbhtml_chart_modal').hide();
                $(this).parents().parents('.sbhtml_chart_overlay').hide()
            });

            // show modal and overlay
            $('.sbhtml_link_text').on('click', function(e) {
                e.preventDefault();

                console.log('clicked');

                let target_id = $(this).attr('target');


                console.log(target_id);

                console.log($('#sbhtml_chart_modal-' + target_id));

                $('#sbhtml_chart_modal-' + target_id).show();
                $('#sbhtml_chart_overlay-' + target_id).show();

            });
        });
    </script>

<?php }


?>