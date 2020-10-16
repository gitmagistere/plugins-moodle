<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

require_login();

$hub = CourseHub::instance();

if ($hub->isMaster()) {
    
    $action = required_param('action', PARAM_ALPHA);
    
    
    switch ($action) {
        case 'changepermission' :
            
            $slaveid = required_param('slave', PARAM_INT);
            $permission = required_param('permission', PARAM_ALPHA);
            $value = required_param('value', PARAM_INT);
            
            $slave = $hub->getSlaveById($slaveid);
            if ( $slave !== false ) {
                $slave->setPermission($permission, $value);
            }
            break;
        
        default:
            die('unknown request');
    }
    
    
    
    
}else{
    echo 'Acces Denied';
}