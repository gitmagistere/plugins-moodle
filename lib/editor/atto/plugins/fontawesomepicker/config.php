<?php

// Config string to associate all the css classes used by FontAwesome with their svg file path
// The string form is : {css_class_1}:{path_1}||{css_class_2}:{path_2}||{css_class_3}:{path_3}
// The key index represents the css class used by FontAwesome
// Be careful: FontAwesome v4 uses fa
//             FontAwesome v5 uses fab, fas, far, ...


$pathfontsawesome = 'fab:' . $CFG->dirroot . '/lib/fonts/fonts/fa-brands-400.svg||far:' . $CFG->dirroot . '/lib/fonts/fonts/fa-regular-400.svg||fas:' . $CFG->dirroot . '/lib/fonts/fonts/fa-solid-900.svg';