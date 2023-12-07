jQuery(document).ready( function( $ ) {


    $( window ).load(function() {
        // click default item
        $('.mwc_active_product').find('.radio_select').click();
    });

    $('.radio_select').change(function() {

        $('.productRadioListItem').removeClass('mwc_active_product');
        let div_parent = $(this).parents('.productRadioListItem');
        div_parent.addClass('mwc_active_product');

        let bundle_title = div_parent.find('.opc_title').val();
        let bundle_price = div_parent.find('.opc_total_price').val();
        let bundle_discount = div_parent.find('.opc_discount').val();

        // debug
        // console.log(bundle_title);
        // console.log(bundle_price);
        // console.log(bundle_discount);

        $('span.totals-price.discount-total').text(bundle_discount);
        $('span.totals-price.grand-total').text(bundle_price);

        
    });

})