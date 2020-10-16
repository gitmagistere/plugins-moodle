<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/coursehub/CourseHubApi.php');



$hub = CourseHub::instance();

if ($hub->isMaster()) {
    
    $function = required_param('function', PARAM_ALPHA);
    
    if ($function != 'registerSlave'){
        $ctoken = CourseHubApi::checkToken();
        if ($ctoken !== true){
            die(CourseHubApi::sendReponse($ctoken));
        }
    }
    
    switch ($function){
        case 'registerSlave':
            CourseHubApi::registerSlave();
            break;
        case 'publishCourse':
            CourseHubApi::publishCourse();
            break;
        case 'shareCourse':
            CourseHubApi::shareCourse();
            break;
        case 'unpublishCourse':
            CourseHubApi::unpublishCourse();
            break;
        case 'getSlaveConfig':
            CourseHubApi::getSlaveConfig();
            break;
        case 'getPublishedCourse':
            CourseHubApi::getPublishedCourse();
            break;
        case 'getPublishedCourseById':
            CourseHubApi::getPublishedCourseById();
            break;
        case 'searchPublishedCourses':
            CourseHubApi::searchPublishedCourses();
            break;
        default:
            die(CourseHubApi::formatReturn(array('error'=>true,'msg'=>'FUNCTION_NOT_FOUND')));
    }
    
}else if($hub->isRemoteSlave()){
    

    $ctoken = CourseHubApi::checkMasterToken();
    if ($ctoken !== true){
        die(CourseHubApi::sendReponse($ctoken));
    }
    
    $function = required_param('function', PARAM_ALPHA);
    
    switch ($function){
        case 'setPermission':
            CourseHubApi::setPermission();
            break;
        case 'removeSlave':
            CourseHubApi::removeSlave();
            break;
        default:
            die(CourseHubApi::sendReponse(array('error'=>true,'msg'=>'FUNCTION_NOT_FOUND')));
    }
    
    
}else{
    echo CourseHubApi::sendReponse(array('error'=>true,'msg'=>'NO_API_AVAILABLE'));
}
