<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/BadgesOverviewData.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/form/badges_overview_form.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/form/participants_overview_form.php');

require_login();

$action = required_param('action', PARAM_TEXT);

if($action == 'list_badges'){
    $so = required_param('so', PARAM_TEXT);
    $si = required_param('si', PARAM_INT);
    $ps = required_param('ps', PARAM_INT);
    $id = required_param('id', PARAM_INT);
    $courseid = required_param('courseid', PARAM_INT);

    if($si < 0){
        die;
    }

    if($ps < 1){
        die;
    }

    $badgeOverviewData = new BadgesOverviewData($id, $courseid, $si, $ps, $so);

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
    $courseid = required_param('courseid', PARAM_INT);

    if($si < 0){
        die;
    }

    if($ps < 1){
        die;
    }

    $badgeid = required_param(participants_overview_form::BADGEID_FIELD, PARAM_INT);
    $status = required_param(participants_overview_form::STATUS_FIELD, PARAM_INT);
    $groupid = required_param(participants_overview_form::GROUPID_FIELD, PARAM_INT);
    $username = required_param(participants_overview_form::USERNAME_FIELD, PARAM_TEXT);

    $participantsOverviewData = new ParticipantsOverviewData($id, $courseid, $si, $ps, $so);

    if($badgeid > 0){
        $participantsOverviewData->setBadgeId($badgeid);
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

    $data = $participantsOverviewData->executeSQL();

    $jTableResult = [];
    $jTableResult['Result'] = "OK";
    $jTableResult['TotalRecordCount'] = $participantsOverviewData->getResultCount();
    $jTableResult['Records'] = array_values($data);

    echo json_encode($jTableResult);
    die;
}