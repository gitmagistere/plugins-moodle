<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree && has_capability('local/coursehub:manage', context_system::instance())) {
    
    // Admin settings.
    $ADMIN->add('localplugins', new admin_category('local_coursehub_manager',
        get_string('pluginname', 'local_coursehub')));
    
    
    $ADMIN->add('local_coursehub_manager', new admin_externalpage('local_coursehub_manage',
        get_string('manage', 'local_coursehub'),
        new moodle_url('/local/coursehub/manage.php'), 'local/coursehub:manage'));
    
}
