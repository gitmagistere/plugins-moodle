<?php

class Utils {
    public static function get_img_url_badge($badgeid, $contextid)
    {
        $imageurl = moodle_url::make_pluginfile_url($contextid, 'badges', 'badgeimage', $badgeid, '/', 'f1', false);
        // Appending a random parameter to image link to forse browser reload the image.
        $imageurl->param('refresh', rand(1, 10000));

        return $imageurl;
    }
    
    public static function get_url_badge($badgeid) {
        $url =  new moodle_url('/badges/overview.php', ['id' => $badgeid]);
        return $url;
    }
    
    public static function get_url_earned_badge_participant($cmid, $badgeid) {
        $url =  new moodle_url('/mod/coursebadges/overview/participants.php', ['id' => $cmid, 'badgeid' => $badgeid, 'status' => ParticipantsOverviewData::EARNED_BADGES]);
        return $url;
    }
    
    public static function get_url_selected_badge_participant($cmid, $badgeid) {
        $url =  new moodle_url('/mod/coursebadges/overview/participants.php', ['id' => $cmid, 'badgeid' => $badgeid, 'status' => ParticipantsOverviewData::SELECTED_BADGES]);
        return $url;
    }

    public static function html_input_data($mform, $name, $data){
        $data = json_encode($data);

        $mform->addElement('hidden', $name, $data);
        $mform->setType($name, PARAM_RAW);
    }
}

