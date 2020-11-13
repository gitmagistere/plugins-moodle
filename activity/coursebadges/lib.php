<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/coursebadges/CourseBadges.php');

/**
 * List of features supported in Course badges module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function coursebadges_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

function coursebadges_add_instance($data, $mform){
    $coursebadge = new CourseBadges(null, $data);
    return $coursebadge->set_course_badges_instance();
}

function coursebadges_update_instance($data, $mform){
    $coursebadge = new CourseBadges($data->instance, $data);
    return $coursebadge->update_course_badges_instance();
}

function coursebadges_delete_instance($id){
    $coursebadge = new CourseBadges($id);
    return $coursebadge->unset_course_badges_instance();
}

function coursebadges_encart_activity($cmid, $coursebadgename){
    global $CFG;
    $link = new moodle_url($CFG->wwwroot.'/mod/coursebadges/view.php',['id'=>$cmid]);
    return '
<div class="activity-encart">
    <a  href="'.$link->out(false).'" 
        class="activity-encart-back-button">Retour à l\'activité</a>
    <hr class="activity-encart-upline" style="display: block;">
    <p class="activity-encart-title">Activité</p>
    <h2>'.$coursebadgename.'</h2>
    <hr class="activity-encart-downline" style="display: block;">
</div>';
}

function isCourseBadgesBlocAvailable(){
    return (file_exists($GLOBALS['CFG']->dirroot.'/blocks/course_badges/version.php'));
}