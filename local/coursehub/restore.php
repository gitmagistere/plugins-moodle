<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

$hubcourseid = required_param('hubcourseid', PARAM_INT);
$categoryid = required_param('categoryid', PARAM_INT);
$fullname = required_param('fullname', PARAM_TEXT);
$shortname = required_param('shortname', PARAM_TEXT);

$PAGE->set_url('/local/coursehub/restore.php', array('hubcourseid'=>$hubcourseid,'categoryid'=>$categoryid,'fullname'=>$fullname,'shortname'=>$shortname));

require_login();

if ($DB->record_exists('course', array('shortname'=>$shortname))){
    die('{"error":true,"msg":"ShortnameAlreadyExists"}');
}

if (!$DB->record_exists('course_categories', array('id'=>$categoryid))){
    die('{"error":true,"msg":"CategoryDoNotExists"}');
}

$hub = CourseHub::instance();

$key = str_replace('%MMID%', get_mmid(), $CFG->restoreHubCourseIDKey);
mmcached_set($key, $hubcourseid, $CFG->restoreHubCourseIDExpireDelay);

if ( $id = $hub->fullRestoreCourse($hubcourseid, $categoryid, $fullname, $shortname)) {
    echo '{"error":false,"newid":"'.$id.'"}';
}else{
    echo '{"error":true,"msg":"Restorefailed"}';
}


