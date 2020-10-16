<?php

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/BadgesOverviewData.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/form/badges_overview_form.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/form/participants_overview_form.php');

require_login();

$action = required_param('action', PARAM_TEXT);

if($action == 'list_badges'){
    $so = required_param('so', PARAM_TEXT);
    $si = required_param('si', PARAM_INT);
    $ps = required_param('ps', PARAM_INT);
    $id = required_param('id', PARAM_INT);

    if($si < 0){
        die;
    }

    if($ps < 1){
        die;
    }

    $coursebadgesid = required_param(badges_overview_form::MODNAME_SEARCH_FIELD, PARAM_INT);

    $badgeOverviewData = new BadgesOverviewData($id, $si, $ps, $so);

    if($coursebadgesid > 0){
        $badgeOverviewData->setCourseBadgeModId($coursebadgesid);
    }

    $data = $badgeOverviewData->executeSQL();

    $jTableResult = [];
    $jTableResult['Result'] = "OK";
    $jTableResult['TotalRecordCount'] = $badgeOverviewData->getResultCount();
    $jTableResult['Records'] = array_values($data);

    echo json_encode($jTableResult);
    die;
}

if($action == 'list_participants'){
    $so = required_param('so', PARAM_TEXT);
    $si = required_param('si', PARAM_INT);
    $ps = required_param('ps', PARAM_INT);
    $id = required_param('id', PARAM_INT);
    

    if($si < 0){
        die;
    }

    if($ps < 1){
        die;
    }

    $badgeid = required_param(participants_overview_form::BADGEID_FIELD, PARAM_INT);
    $coursebadgesid = required_param(participants_overview_form::MODID_FIELD, PARAM_INT);
    $status = required_param(participants_overview_form::STATUS_FIELD, PARAM_INT);
    $groupid = required_param(participants_overview_form::GROUPID_FIELD, PARAM_INT);
    $username = required_param(participants_overview_form::USERNAME_FIELD, PARAM_TEXT);
    $roleIds = required_param_array(participants_overview_form::ROLEID_FIELD, PARAM_INT);

    $participantsOverviewData = new ParticipantsOverviewData($id, $si, $ps, $so);

    if($badgeid > 0){
        $participantsOverviewData->setBadgeId($badgeid);
    }

    if($coursebadgesid > 0){
        $participantsOverviewData->setCourseBadgeModId($coursebadgesid);
    }

    if($status > 0){
        $participantsOverviewData->setStatus($status);
    }

    if($groupid > 0){
        $participantsOverviewData->setGroupId($groupid);
    }

    if($username){
        $participantsOverviewData->setUserName($username);
    }

    if($status != -1){
        $participantsOverviewData->setStatus($status);
    }
    
    $participantsOverviewData->setRoleIds($roleIds);
    
    $data = $participantsOverviewData->executeSQL();

    $jTableResult = [];
    $jTableResult['Result'] = "OK";
    $jTableResult['TotalRecordCount'] = $participantsOverviewData->getResultCount();
    $jTableResult['Records'] = array_values($data);

    echo json_encode($jTableResult);
    die;
}