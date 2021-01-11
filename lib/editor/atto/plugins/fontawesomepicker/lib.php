<?php


/**
* Set params for this plugin
* @param string $elementid
*/
function atto_fontawesomepicker_params_for_js() {

    $availableions = get_config('atto_fontawesomepicker', 'availableicons');
    $icons = explode(";", $availableions);

    return array(
        'icons' => $icons
    );
}

