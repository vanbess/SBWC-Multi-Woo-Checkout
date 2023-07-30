// addon product id selected
var mwc_addon_selected = [];

jQuery(document).ready(function ($) {

  // add fancybox to bundle options
  $(".mwc_fancybox_open").fancybox({
    width: "90%",
    height: "90%",
    type: "inline",
    touch: false,
    autoFocus: false,
  });

  // load lazy images fancybox popup
  $(".mwc_fancybox_open").click(function (e) {
    $($(this).attr('href')).find('img').trigger('click');
  });


  // set image popup
  $(".mwc_product_intro_container .intro_img_preview").click(function (e) {
    set_img = $(this).find("img").attr("src");
    $(this).parents(".mwc_product_intro_container .left_inner_div").find(".i_wadc_full_image_div img").attr("src", set_img);
  });

  $(".mwc_product_additem_btn").click(function (e) {
    id = $(this).data("add_item");
    if (!$("#input_selected_product_" + id).is(":checked")) {
      $("#input_selected_product_" + id).trigger("click");
    }
    $.fancybox.close();
  });

  // show more addons
  $('.mwc_item_addons_div.see_more .mwc_see_more button').click(function (e) {
    e.preventDefault();
    $(this).parents('.mwc_item_addons_div').find('.mwc_addon_div').show(200);
    $(this).hide();
  });

});
