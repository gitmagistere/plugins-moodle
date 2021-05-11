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

// Library of functions and constants for module wordcloud.

/**
 * @package mod_wordcloud
 * @copyright  2021 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/wordcloud.php');

$id = required_param('id', PARAM_INT);
$g  = optional_param('g', 0, PARAM_INT);

$cm     = get_coursemodule_from_id('wordcloud', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);
//require_capability('mod/workshop:view', $PAGE->context);

$params = array();
if ($g > 0) {
    $params['g'] = $g;
}
$params['id'] = $id;

$PAGE->set_url('/mod/wordcloud/view.php',$params);



$PAGE->set_heading($course->fullname);

$wordcloud = new wordcloud($cm->instance);
$PAGE->set_title($wordcloud->activity->name);

$subform = process_wordcloud_submit($wordcloud,$g);


$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_wordcloud');

$output = '';

if (method_exists($OUTPUT,'add_encart_activity')) {
    $heading = $OUTPUT->heading(format_string($wordcloud->activity->name), 2);
    echo $OUTPUT->add_encart_activity($heading);
}else{
    $output .= '<div class="wc_name">'.$wordcloud->get_name().'</div>';
}
/*
$intro = $wordcloud->get_intro();
if (strlen($intro) > 0  ) {
    $output .= '<div class="wc_description_header">'.get_string('description','mod_wordcloud').'</div>';
    $output .= '<div class="wc_description">'.$intro.'</div>';
}
*/
$instructions = $wordcloud->get_instructions();
if (strlen($instructions) > 0  ) {
    //$output .= '<div class="wc_instructions_header">'.get_string('instructions','mod_wordcloud').'</div>';
    $output .= '<div class="wc_instructions">'.$instructions.'</div>';
}

$cmcontext = context_module::instance($cm->id);

$closed = false;
if (!$wordcloud->is_started()) {
    $output .= get_string('activitenotstarted','mod_wordcloud',strftime(get_string('strftimedaydatetime','core_langconfig'),$wordcloud->activity->timestart));
    $closed = true;
}

if ($wordcloud->has_ended()) {
    $output .= get_string('activityclosed','mod_wordcloud',strftime(get_string('strftimedaydatetime','core_langconfig'),$wordcloud->activity->timeend));
    $closed = true;
}

if ($closed && !$wordcloud->is_editor()) {
    echo $output;
    echo $OUTPUT->footer();
    exit;
}


$apiurl = (new moodle_url('/mod/wordcloud/api.php'))->out();

$PAGE->requires->js_call_amd("mod_wordcloud/wordcloud","showWordcloud",array('cmid'=>$wordcloud->cm->id,'selector'=>'#region-main .wordcloudcontainer','apiurl'=>$apiurl,'editor'=>$wordcloud->is_editor()));

$d3url = new moodle_url('/mod/wordcloud/js/d3.v3.min.js');
$d3cloudurl = new moodle_url('/mod/wordcloud/js/d3.layout.cloud.js');
echo '<script src="'.$d3url.'"></script><script src="'.$d3cloudurl.'"></script>';


if (has_capability('mod/wordcloud:submitword', $cmcontext) || $wordcloud->is_editor()){
    $cloudmod = (count($wordcloud->get_user_words($USER->id,$g)) > 0);
    
    $output .= $renderer->display_wordcloud_groupselector($wordcloud,$g);
    $output .= $renderer->display_wordcloud_submit($wordcloud,$g,!$cloudmod,($subform?$subform:''));
    $output .= $renderer->display_wordcloud_cloud($wordcloud,$cloudmod);
    
    // Editor interface
    if ($wordcloud->is_editor()){
        $output .= $renderer->display_wordcloud_editor($wordcloud,$cloudmod);
    }
    
}else{
    print_error('You can\'t see this activity!');
}


echo $output;

echo $OUTPUT->footer();
