<?php

// add Polyang support for custom tracking post types
if (defined('POLYLANG')) :

    // get Polylang options/settings
    $polylang_opts = get_option('polylang');

    // add bundle_selection tracking cpt support
    if (!in_array('bundle_selection', $polylang_opts['post_types'])) :
        array_push($polylang_opts['post_types'], 'bundle_selection');
    endif;
    
    // add mwc-addon-product tracking cpt support
    if (!in_array('mwc-addon-product', $polylang_opts['post_types'])) :
        array_push($polylang_opts['post_types'], 'mwc-addon-product');
    endif;

    // update Polylang options/settings
    update_option('polylang', $polylang_opts);

endif;
