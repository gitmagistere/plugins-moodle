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
 * Topics course format.  Display the whole course as "topics" made of modules.
 *
 * @package format_topics
 * @copyright 2006 The Open University
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// TCS START - 20190219 - NNE - MOVE FROM view.php BY ARO FOR "marque blanche"
if($section == 0){
    $section = 1;

    // trick to override section 0...
    $_POST['section'] = $_GET['section'] = 1;
}
// TCS END - 20190219 - NNE & ARO

// TCS - 13/07/2016
if($course->format == 'modular'){
    require_once($CFG->dirroot.'/course/format/modular/format_modular_helper.php');
    $formathelper = new format_modular_helper($course->id);

    $current = $formathelper->get_section($section);

    if(!$current->hasContent){
        $nextsection = $section+1;
        $canseehiddensection = has_capability('block/summary:canseehiddensections', $context);

        $nearestsection = $formathelper->get_nearest_section($section, $nextsection, $canseehiddensection, 1);

        if($nearestsection){
            $redirect = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $nearestsection->numsection));
        }else {
            $redirect = new moodle_url('/course/view.php', array('id' => $course->id));
        }

        redirect($redirect);
    }
}
// TCS - 13/07/2016

$PAGE->set_pagelayout('format_modular');

$section = optional_param('section', 0, PARAM_INT);

$context = context_course::instance($course->id);

if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

$courseformat = course_get_format($course);
$course = $courseformat->get_course();

$renderer = $PAGE->get_renderer('format_modular');
$renderer->print_single_section_page($course, null, null, null, null, $section);

// Include course format js module
$PAGE->requires->js('/course/format/topics/format.js');
