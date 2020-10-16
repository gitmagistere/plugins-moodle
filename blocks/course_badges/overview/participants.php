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

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/form/participants_overview_form.php');

$id = required_param('id', PARAM_INT); // course id
$badgeid = optional_param('badgeid', null, PARAM_INT);
$status = optional_param('status', null, PARAM_INT);



$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course, true);

require_capability('block/course_badges:viewparticipantsoverview', context_course::instance($course->id));

$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/blocks/course_badges/styles.css');

$params = array(
    'context' => context_course::instance($course->id)
);

$title = get_string('badgeoverviewtitle', 'block_course_badges');
$PAGE->set_url('/blocks/course_badges/overview/participants.php', array('id' => $course->id));
$PAGE->set_title($course->shortname . ': ' . $title);
$PAGE->set_heading($course->fullname);

$PAGE->requires->jquery_plugin('jtable-css');

$ajaxbaseurl = new moodle_url('/blocks/course_badges/overview/ajax.php', ['action' => 'list_participants']);

$PAGE->requires->js_call_amd('block_course_badges/course_badges', 'init_jtable', [
    'jtablecolumns', 'results', $ajaxbaseurl->out(false), 'lastname ASC, firstname ASC'
]);

$PAGE->requires->js_call_amd('block_course_badges/course_badges', 'init_name_input', [
    participants_overview_form::USERNAME_FIELD
]);

$PAGE->requires->js_call_amd('block_course_badges/course_badges', 'init_select_overview');
$participantsOverviewForm = new participants_overview_form();
$participantsOverviewForm->set_data(array(participants_overview_form::BADGEID_FIELD => $badgeid, participants_overview_form::STATUS_FIELD => $status));

echo $OUTPUT->header();

$participantsOverviewForm->display();

echo $OUTPUT->footer();
