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
 * Magistere Topics course format.  Display the whole course as "magistere_topics" made of modules.
 *
 * @package format_magistere_topics
 * @copyright 2006 The Open University
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// TCS START - 20190219 - NNE - MOVE FROM view.php BY ARO
$canseesection0 = has_capability('block/summary:canseesectionzero', $context);
$showsection0 = optional_param('szero', null, PARAM_INT);


if($course->coursedisplay = 1 && $displaysection == 0 && !($showsection0 == 1 && $canseesection0)){
    // make sure all sections are created
    $lastsectionnumber = course_get_format($course)->get_last_section_number();
    $course = course_get_format($course)->get_course();
    course_create_sections_if_missing($course, range(0, $lastsectionnumber));

    $displaysection = 1;

    // trick to override section 0...
    $_POST['section'] = $_GET['section'] = 1;
}
// TCS END - 20190219 - NNE & ARO


// Horrible backwards compatible parameter aliasing..
if ($topic = optional_param('topic', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing..

$context = context_course::instance($course->id);
// Retrieve course format option fields and add them to the $course object.
$course = course_get_format($course)->get_course();

if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

// Make sure section 0 is created.
course_create_sections_if_missing($course, 0);

$renderer = $PAGE->get_renderer('format_magistere_topics');

if (!empty($displaysection)) {
    $renderer->print_single_section_page($course, null, null, null, null, $displaysection);
} else {
    // Start Mantis 1741 : JBL 12/07/2017
    if(!(has_capability('moodle/site:manageblocks', $context) && has_capability('moodle/course:update', $context))){
        $renderer->print_single_section_page($course, null, null, null, null, 1);
    } else{
        $renderer->print_multiple_section_page($course, null, null, null, null);
    }
    // End Mantis 1741 : JBL 12/07/2017
}

// Include course format js module
$PAGE->requires->js('/course/format/magistere_topics/format.js');
