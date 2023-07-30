jQuery(document).ready(function ($) {

    var custom_uploader;

    /**
     * Init Tiny MCE
     */
    tinymce.init({
        selector: '.title_main',
        theme: 'modern',
        plugins: [
            'paste textcolor colorpicker code'
        ],
        toolbar1: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
        toolbar2: 'forecolor backcolor code',
    });

    /**
     * Init WP color picker field
     */
    try {
        $('.my-color-field').wpColorPicker();
    } catch (error) {
        console.log('wpColorPicker not found!');
    }

    /**
     * Upload images from WP Media Libary
     */
    $('.upload_image_button').click(function (e) {

        e.preventDefault();
        parent = $(this).parent('td label');

        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }

        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: true
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function () {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            parent.find('.upload_image').val(attachment.url);
            custom_uploader.close();
        });

        //Open the uploader dialog
        // custom_uploader.open();

    });

    /**
     * Add description
     */
    $('.description_add').on('click', function () {

        var parents = $(this).parents('tr').find('td .feature_desc_add');
        var type = parents.data('type');

        var html = '<div><input name="feature_' + type + '_desc[]" class="desc_input quantity_main_bundle" type="text" value="">';
        html += ' <button type="button" class="remove button button-primary">x</button></div><br>';

        parents.find('.input_zone').append(html);

    });

    /**
     * Add label
     */
    $('.label_add').on('click', function () {

        var parents = $(this).parents('tr').find('td .item_label_add');
        var type = parents.data('type');

        var html = '<div><input name="name_label_' + type + '[]" class="label_input quantity_main_bundle" type="text" value="">';
        html += '<label class="label_inline"> <b>Color:</b> </label>';
        html += '<input type="text" value="#bada55" name="color_label_' + type + '[]" class="my-color-field" data-default-color="#effeff" />';
        html += '<button type="button" class="remove button button-primary">x</button>';
        html += '</div><br>';

        parents.append(html);

        $('.my-color-field').wpColorPicker();

    });

    /**
     * Remove label/description input set
     */
    $(document).on('click', '.remove', function (event) {
        $(this).parent().remove();
    });


    /**
     * Select2 fetch product
     */
    $('.product_select').select2({

        minimumInputLength: 3,
        tags: [],
        ajax: {
            type: "GET",
            url: mwc_b_js.ajaxurl,
            dataType: 'json',
            data: function (term) {

                if (term && term.term)
                    return 'action=mwc_bundle_get_product&_ajax_nonce=' + mwc_b_js.nonce_prod + '&product_title=' + term.term;
                else
                    return 'action=mwc_bundle_get_product&_ajax_nonce=' + mwc_b_js.nonce_prod + 'product_title=' + '';
            },
            processResults: function (data) {
                return {
                    results: data.results.map((item) => {
                        return {
                            prod_id: item.ID,
                            text: item.ID + ': ' + item.post_title,
                            id: item.ID + '/%%/' + item.post_title
                        }
                    })
                };
            }
        }


    });

    /**
     * Disable inactive inputs (based on selected bundle type)
     */
    $('#mwc_bundle_selection_meta .product').each(function (i, el) {
        if (!$(this).hasClass('activetype')) {
            $(this).find('input').attr('disabled', 'disabled');
        }
    });


    /**
     * Bundle select on change
     */
    $('.select_type').on('change', function () {

        var type = $('.select_type').val();

        $('.activetype').removeClass('activetype');
        $('.activetype_button').removeClass('activetype_button');

        // disable input
        $('#mwc_bundle_selection_meta .product input').attr('disabled', 'disabled');

        $('.product_' + type).addClass('activetype');

        // remove disable current option
        $('#mwc_bundle_selection_meta .product.product_' + type + ' input').removeAttr("disabled");

        if (type == 'bun') {
            $('.product_add_bun').addClass('activetype_button');
            $('.product_bun_coupon').addClass('activetype_button');
        }
    });

    /**
     * Add bundle on click
     */
    $('.product_add_bun').on('click', function () {

        var html = '<div style="margin-bottom: 5px;">';
        html += '<select name="selValue_bundle[]" class="product_select product_select_bun" style="width: 400px;"></select> ';
        html += '<label class="label_inline">Quantity: </label> <input name="bundle_quantity[]" type="number" class="quantity_main_bundle small-text"> ';
        html += '<button type="button" class="remove button button-primary">x</button> ';
        html += '</div>';

        var target = $(this).parent().parent().find('.new_prod');

        target.append(html);
        
        // if (create)
        //     $('.product_bun .new_prod').append(
        //         '<div>' +
        //         ' <select name="selValue_bundle[]" class="product_select product_select_bun" style="width: 400px;"></select>' +
        //         '  <label class="label_inline">Quantity </label> <input name="bundle_quantity[]" type="number" class="quantity_main_bundle small-text"> ' +
        //         ' <button type="button" class="remove button">x</button> ' +
        //         '</div><br>');

        // product select
        $('.product_select').select2({
            
            minimumInputLength: 3,
            tags: [],
            ajax: {
                type: "GET",
                url: mwc_b_js.ajaxurl,
                dataType: 'json',
                data: function (term) {

                    if (term && term.term)
                        return 'action=mwc_bundle_get_product&_ajax_nonce=' + mwc_b_js.nonce_prod + '&product_title=' + term.term;
                    else
                        return 'action=mwc_bundle_get_product&_ajax_nonce=' + mwc_b_js.nonce_prod + 'product_title=' + '';
                },
                processResults: function (data) {
                    return {
                        results: data.results.map((item) => {
                            return {
                                prod_id: item.ID,
                                text: item.ID + ': ' + item.post_title,
                                id: item.ID + '/%%/' + item.post_title
                            }
                        })
                    };
                }
            }
        });
    });


    // dropdown collapse price
    $(document).on('click', '#mwc_bundle_selection_meta .collapsible', function (event) {
        $(this).toggleClass("active");
        if ($(this).next().css('display') === "block") {
            $(this).next().slideUp();
        } else {
            $(this).next().slideDown();
        }
    });

    // get html custom product price
    $('.product_select').on('select2:select', function (e) {
        var parent_bundle = $(this).parents('.product.activetype');
        var prod_id = e.params.data.prod_id;

        var info = {};
        info['action'] = 'mwc_get_html_custom_product_price';
        info['product_id'] = prod_id;

        //ajax update cart
        $.get('/wp-admin/admin-ajax.php', info).done(function (data) {
            data = JSON.parse(data);
            if (data.status) {
                parent_bundle.find('.custom_prod_price').empty();
                parent_bundle.find('.custom_prod_price').append(data.html);
            }
        });
    });

    // focus input custom price
    $(document).on("focus", ".custom_price_prod .input_price", function () {
        if ($(this).val() == '') {
            $(this).attr('value', $(this).attr('data-value'));
        }
    });

});