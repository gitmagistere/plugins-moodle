<?php

/**
 * Settings for the course badges block
 *
 * @copyright 2020 TCS
 * @package   block_course_badges
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    if (has_capability('block/course_badges:config', context_system::instance())
        && has_capability('block/course_badges:interactivemapconfig', context_system::instance())) {

        $settings->add(new admin_setting_heading('block_course_badges_interactive_map_settings_module_head',
            '',
            get_string('block_course_badges_interactive_map_settings_module_head', 'block_course_badges')));
        $settings->add(new admin_setting_configcheckbox('block_course_badges/enable_interactive_map',
            get_string('settings_interactive_map_label', 'block_course_badges'),
            get_string('settings_interactive_map_description', 'block_course_badges'),
            '0'));
    }
}
