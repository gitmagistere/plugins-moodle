<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('block_summary/slasheye', get_string('slasheye', 'block_summary'),
        get_string('slasheyedesc', 'block_summary'), 'fa fa-eye-slash', PARAM_RAW,40 ));

    $settings->add(new admin_setting_configtext('block_summary/lock', get_string('lock', 'block_summary'),
        get_string('lockdesc', 'block_summary'), 'fa fa-lock', PARAM_RAW,40 ));

    $settings->add(new admin_setting_configtext('block_summary/minus', get_string('minus', 'block_summary'),
        get_string('minusdesc', 'block_summary'), 'fa fa-minus-square-o', PARAM_RAW,40 ));

    $settings->add(new admin_setting_configtext('block_summary/plus', get_string('plus', 'block_summary'),
        get_string('plusdesc', 'block_summary'), 'fa fa-plus-square-o', PARAM_RAW,40 ));


}