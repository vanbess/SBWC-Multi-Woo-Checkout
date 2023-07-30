<?php
?>
<div class="loadingMessageContainerWrapper">
    <div class="counter">
        <div style="border: 1px solid #333; padding: 20px; border-radius: 20px;box-shadow: 0px 6px 9px -5px #000000;background:rgba(255,255,255,.5);">
            <img src="<?php echo (isset($image_logo[0]) ? $image_logo[0] : (MWC_PLUGIN_URL . 'images/progress_logo.gif')) ?>" class="" style="width:200px;">
            <p class="steps1" style="font-weight: bold; padding-top: 15px;"><?php pll_e('Checking if you Qualify for Special Offers...', 'woocommerce') ?> </p>
            <p style="font-weight: bold; padding-top: 15px; display: none;" class="steps2"><?php pll_e('Congratulations You Qualified!', 'woocommerce') ?></p>
            <p style="font-weight: bold; padding-top: 15px; display: none;" class="steps3"><?php pll_e('Checking 2 Warehouses For Available Stock...', 'woocommerce') ?></p>
            <p style="font-weight: bold; padding-top: 15px; display: none;" class="steps4"><?php pll_e('Stock Available In Warehouse 1! Reserving Your Units...', 'woocommerce') ?></p>
            <div class="baroutter">
                <hr class="bar">
            </div>
        </div>
    </div>
</div>