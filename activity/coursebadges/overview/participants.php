<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/mod/coursebadges/CourseBadges.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/form/participants_overview_form.php');

$id = required_param('id', PARAM_INT); // course module id
$badgeid = optional_param('badgeid', null, PARAM_INT);
$status = optional_param('status', null, PARAM_INT);

if (!$cm = get_coursemodule_from_id('coursebadges', $id)) {
    print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
}
if (!$course = $DB->get_record('course', ['id'=> $cm->course])) {
    print_error('course is misconfigured');  // NOTE As above
}

require_course_login($course, true, $cm);

if (!$course_badge = $DB->get_record('coursebadges', ['id'=> $cm->instance])) {
    print_error('course module is incorrect'); // NOTE As above
}

$params = [];
if ($id) {
    $params['id'] = $id;
}

$context = context_module::instance($cm->id);

$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/mod/coursebadges/styles.css');
$PAGE->requires->css('/mod/coursebadges/jtable_basic.css');

if(has_capability('mod/coursebadges:viewparticipantsoverview', $context)){
    $canviewparticipantsoverview = true;
} else {
    if($course_badge->showawardedresults == CourseBadges::ALWAYS_SHOW_RESULTS) {
        $canviewparticipantsoverview = true;
    } else if($course_badge->showawardedresults == CourseBadges::SHOW_RESULTS_AFTER_RESPONSE) {
        if(CourseBadges::has_selected_badges($course_badge->id)) {
            $canviewparticipantsoverview = true;
        } else {
            $canviewparticipantsoverview = false;
        }
    } else {
        $canviewparticipantsoverview = false;
    }
}

if(!$canviewparticipantsoverview){
    print_error('noparticipantoverview', 'coursebadges', $CFG->wwwroot.'/course/view.php?id='.$course->id);
}

$PAGE->set_url('/mod/coursebadges/overview/participants.php', $params);
$PAGE->set_title($course_badge->name);
$PAGE->set_heading($course->fullname);

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

$PAGE->navbar->add(get_string('participantoverviewtitle', 'mod_coursebadges'));

$ajaxbaseurl = new moodle_url('/mod/coursebadges/overview/ajax.php', ['action' => 'list_participants']);

$PAGE->requires->js_call_amd('mod_coursebadges/course_badges', 'initJtable', [
    'jtablecolumns', 'results', $ajaxbaseurl->out(false), 'lastname ASC, firstname ASC'
]);

$PAGE->requires->js_call_amd('mod_coursebadges/course_badges', 'initNameInput', [
    participants_overview_form::USERNAME_FIELD
]);

$PAGE->requires->js_call_amd('mod_coursebadges/course_badges', 'initSelectOverview');

$participantsOverviewForm = new participants_overview_form(null, ['cmid' => $cm->id, 'courseid' => $course->id]);
$participantsOverviewForm->set_data(array(participants_overview_form::BADGEID_FIELD => $badgeid, participants_overview_form::STATUS_FIELD => $status));

echo $OUTPUT->header();
echo coursebadges_encart_activity($id, $course_badge->name);

$participantsOverviewForm->display();

echo $OUTPUT->footer();
