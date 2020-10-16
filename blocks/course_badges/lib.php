<?php

function get_img_url_badge($badgeid, $contextid)
{
    $imageurl = moodle_url::make_pluginfile_url($contextid, 'badges', 'badgeimage', $badgeid, '/', 'f1', false);
    // Appending a random parameter to image link to forse browser reload the image.
    $imageurl->param('refresh', rand(1, 10000));

    return $imageurl;
}

function get_url_badge($badgeid) {
    $url =  new moodle_url('/badges/overview.php', ['id' => $badgeid]);
    return $url;
}

function get_url_coursebadge_overview($cmid) {
    $url = new moodle_url('/mod/coursebadges/overview/badges.php', ['id' => $cmid]);
    return $url;
}

function get_url_earned_badge_participant($courseid, $badgeid) {
    $url =  new moodle_url('/blocks/course_badges/overview/participants.php', ['id' => $courseid, 'badgeid' => $badgeid, 'status' => ParticipantsOverviewData::EARNED_BADGES]);
    return $url;
}

function get_url_selected_badge_participant($courseid, $badgeid) {
    $url =  new moodle_url('/blocks/course_badges/overview/participants.php', ['id' => $courseid, 'badgeid' => $badgeid, 'status' => ParticipantsOverviewData::SELECTED_BADGES]);
    return $url;
}

function html_input_data($mform, $name, $data){
    $data = json_encode($data);

    $mform->addElement('hidden', $name, $data);
    $mform->setType($name, PARAM_RAW);
}

function isInteractiveActionAvailable(){
    return (file_exists($GLOBALS['CFG']->dirroot.'/local/interactive_map/InteractiveMap.php')
        && get_config('block_course_badges','enable_interactive_map') == true);
}