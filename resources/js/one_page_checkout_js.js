jQuery(document).ready(function ($) {

    //override input customer form
    $('.op_c_checkout_form').find('.large-7').removeClass('large-7').addClass('customer_info');
    $('.op_c_checkout_form').find('.large-5').removeClass('large-5').addClass('payment_opt');
    $('.op_c_checkout_form').find('.customer_info #billing_city_field').removeClass('form-row-wide').addClass('form-row-last');
    $('.op_c_checkout_form #customer_details .form-row select').prev('label').addClass('label_select');
    $('.op_c_checkout_form').find('.woocommerce-form-coupon-toggle').hide();
    $('.op_c_checkout_form').find('.woocommerce-shipping-fields').hide();
    $('.op_c_checkout_form').find('.woocommerce-additional-fields').hide();
    $('.op_c_checkout_form').find('.payment_opt .payment_icon').first().attr('src', '/wp-content/plugins/mwc/images/payment_icon.png');
    $('.op_c_checkout_form').show();
    $('#op_c_loading').hide();

    $('.mwc_active_product').trigger('click');

    var attrs = $('.var_prod_attr');

    $.each(attrs, function () {
        var chart_append = $(this).siblings('.variation_name'),
            chart_set = $(this).parents('.c_prod_item').attr('has-size-chart'),
            pid = $(this).parents('.c_prod_item').attr('data-id');

        if (chart_set == 'true') {
            var sbhtml_label_text = '',
                sbhtml_link_text = $('#sbhtml_text_open_modal').val(),
                label_text_content = '<div class="sbhtml_label_wrap">' + sbhtml_label_text + ' <span class="sbhtml_link_text" target="' + pid + '">' + sbhtml_link_text + '</span></div>';

            chart_append.after(label_text_content);
        }
    });

    // hide modal and overlay
    $('.sbhtml_chart_overlay, .sbhtml_modal_close').on('click', function (e) {
        e.preventDefault();
        $(this).closest('.sbhtml_chart_overlay, .sbhtml_chart_modal').hide();
    });

    $('.sbhtml_modal_close').on('click', function (e) {
        e.preventDefault();
        $(this).parents('.sbhtml_chart_modal').hide();
        $(this).parents().parents('.sbhtml_chart_overlay').hide()
    });

    // show modal and overlay
    $('.sbhtml_link_text').on('click', function (e) {
        e.preventDefault();

        var target_id = $(this).attr('target');
        $('#sbhtml_chart_modal-' + target_id).show();
        $('#sbhtml_chart_overlay-' + target_id).show();

    });

    // stop modal
    // $('.sbhtml_chart_modal').on('click', function (e) {
    //     e.preventDefault();
    //     e.stopPropagation();
    // });

    /**
     * Fixes for product bundles
     */


});