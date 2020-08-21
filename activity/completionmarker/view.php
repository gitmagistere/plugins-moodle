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

/**
 * Achievement module main user interface
 *
 * @package    mod_achievement
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');


$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // URL instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $achievement = $DB->get_record('completionmarker', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('completionmarker', $url->id, $achievement->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('completionmarker', $id, 0, false, MUST_EXIST);
    $achievement = $DB->get_record('completionmarker', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course_section = $DB->get_record('course_sections', array('id'=>$cm->section), '*', MUST_EXIST);

require_login();

$url = new moodle_url('/course/view.php',array('id'=>$cm->course,'section'=>$course_section->section));
redirect($url);
