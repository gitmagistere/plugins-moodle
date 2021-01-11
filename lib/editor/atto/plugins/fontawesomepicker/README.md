# Font Awesome Icons for Atto

Add Font Awesome Icon. Configurable by the Moodle admin to define suggested icons

Add Filter Fontawesome plugin on https://moodle.org/plugins/filter_fontawesome for Moodle 3.5

## Installation

Install the plugin directory as usual in lib/editor/atto/plugins.

Edit the $pathfontsawesome variable located in config.php file. It's a string which associate all the css classes used by FontAwesome with their svg file path
The string form is : {css_class_1}:{path_1}||{css_class_2}:{path_2}||{css_class_3}:{path_3}
Example:
    for FontAwesome version 5: 
    $pathfontsawesome = 'fab:' . $CFG->dirroot . '/lib/fonts/fonts/fa-brands-400.svg||far:' . $CFG->dirroot . '/lib/fonts/fonts/fa-regular-400.svg||fas:' . $CFG->dirroot . '/lib/fonts/fonts/fa-solid-900.svg';

or  for FontAwesome version 4: 
    $pathfontsawesome = 'fa:' . $CFG->dirroot . '/lib/fonts/fontawesome-webfont.svg';


Then visit Site Administration > Plugins > Atto > Font Awesome. to configure icons

Finally, enable the plugin by adding 'fontawesomepicker' (without the quotes) in the Atto toolbar settings ( Site administration > Plugins > Text editors > Atto HTML editor > Atto toolbar settings ).

