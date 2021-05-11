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

defined('MOODLE_INTERNAL') || die();

function wordcloud_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}


function wordcloud_get_instance($wordcloudid) {
    global $DB;
    return $DB->get_record('wordcloud', array('id' => $wordcloudid));
}

function wordcloud_add_instance($wordcloud) {
    global $DB, $CFG;


    $cmid        = $wordcloud->coursemodule;
    $draftitemid        = $wordcloud->instructionseditor['itemid'];
    
    $wc = new stdClass();
    $wc->course = $wordcloud->course;
    $wc->name = $wordcloud->name;
    $wc->intro = $wordcloud->intro;
    $wc->introformat = $wordcloud->introformat;
    $wc->instructions = $wordcloud->instructionseditor['text'];
    $wc->wordsallowed = $wordcloud->wordsallowed;
    $wc->wordsrequired = $wordcloud->wordsrequired;
    $wc->completionwords = (isset($wordcloud->completionwordssenabled)?1:0);
    $wc->timestart = $wordcloud->timestart;
    $wc->timeend = $wordcloud->timeend;
    $wc->timecreate = time();
    $wc->timemodified = time();
    
    $wcid = $DB->insert_record('wordcloud', $wc, true);
    $context = context_module::instance($cmid);

    if ($draftitemid) {
        $options =  array('subdirs'=>false, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
        $newwc = new stdClass();
        $newwc->instructions = file_save_draft_area_files($draftitemid, $context->id, 'mod_wordcloud', 'instructions', 0, $options, $wc->instructions);
        $newwc->id = $wcid;
        $DB->update_record('wordcloud', $newwc);
    }
    return $wcid;
}

// Given an object containing all the necessary data,
// (defined by the form in mod.html) this function
// will update an existing instance with new data.
function wordcloud_update_instance($wordcloud) {
    global $DB, $CFG;
    
    $wc = $DB->get_record('wordcloud', array('id'=>$wordcloud->instance), '*', MUST_EXIST);
    $cmid        = $wordcloud->coursemodule;
    $draftitemid        = $wordcloud->instructionseditor['itemid'];
    
    // Updating all mod values
    $wc->name = $wordcloud->name;
    $wc->intro = $wordcloud->intro;
    $wc->introformat = $wordcloud->introformat;
    $wc->instructions = $wordcloud->instructionseditor['text'];
    $wc->wordsallowed = $wordcloud->wordsallowed;
    $wc->wordsrequired = $wordcloud->wordsrequired;
    $wc->completionwords = (isset($wordcloud->completionwordssenabled)?1:0);
    $wc->timestart = $wordcloud->timestart;
    $wc->timeend = $wordcloud->timeend;
    $wc->timemodified = time();


    $DB->update_record("wordcloud", $wc);

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $options =  array('subdirs'=>false, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
        
        $wc->instructions = file_save_draft_area_files($draftitemid, $context->id, 'mod_wordcloud', 'instructions', 0, $options, $wc->instructions);
        $DB->update_record('wordcloud', $wc);
    }

    return true;
}

// Given an ID of an instance of this module,
// this function will permanently delete the instance
// and any data that depends on it.
function wordcloud_delete_instance($id) {
    global $DB;

    if (! $wordcloud = $DB->get_record('wordcloud', array('id' => $id))) {
        return false;
    }

    $result = true;

    if ($events = $DB->get_records('event', array("modulename" => 'wordcloud', "instance" => $wordcloud->id))) {
        foreach ($events as $event) {
            $event = calendar_event::load($event);
            $event->delete();
        }
    }

    if (! $DB->delete_records('wordcloud', array('id' => $wordcloud->id))) {
        $result = false;
    }

    if ($DB->count_records('wordcloud_words', array('wcid' => $wordcloud->id)) > 0) {
        $result = $result && $DB->delete_records('wordcloud_words',array('wcid'=>$wordcloud->id));
    }

    return $result;
}


// Print a detailed representation of what a  user has done with
// a given particular instance of this module, for user activity reports.
/**
 * $course and $mod are unused, but API requires them. Suppress PHPMD warning.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function wordcloud_user_complete($course, $user, $mod, $wordcloud) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/wordcloud/locallib.php');

    if ($responses = wordcloud_get_user_responses($wordcloud->id, $user->id, false)) {
        foreach ($responses as $response) {
            if ($response->complete == 'y') {
                echo get_string('submitted', 'wordcloud').' '.userdate($response->submitted).'<br />';
            } else {
                echo get_string('attemptstillinprogress', 'wordcloud').' '.userdate($response->submitted).'<br />';
            }
        }
    } else {
        print_string('noresponses', 'wordcloud');
    }

    return true;
}


/**
 * Obtains the automatic completion state for this forum based on any conditions
 * in wordcloud settings.
 *
 * @global object
 * @global object
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function wordcloud_get_completion_state($course,$cm,$userid,$type) {
    global $DB;
    
    // Get wordcloud details
    if (!($wordcloud=$DB->get_record('wordcloud',array('id'=>$cm->instance)))) {
        throw new Exception("Can't find wordcloud {$cm->instance}");
    }
    
    $result=$type; // Default return value
    
    $wordcountparams=array('userid'=>$userid,'wordcloudid'=>$wordcloud->id);
    $wordcountsql=
"SELECT COUNT(ww.id)
FROM  {wordcloud_words} ww
WHERE ww.userid=:userid AND ww.wcid=:wordcloudid";
    
    if ($wordcloud->completionwords) {
        $value = $wordcloud->wordsrequired <= $DB->get_field_sql($wordcountsql,$wordcountparams);
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }
    
    return $result;
}


/**
 * Serves the page files.
 *
 * @package  mod_page
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function wordcloud_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/resourcelib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea !== 'instructions') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_wordcloud/$filearea/0/$relativepath";
    $file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory();

    // finally send the file
    send_stored_file($file, null, 0, $forcedownload, $options);

}


function process_wordcloud_submit($wordcloud,$g=0) {
    global $USER, $PAGE;
    
    require_once(__DIR__.'/wordsubmit_form.php');
    
    $form = new mod_wordcloud_wordsubmit_form($PAGE->url,
        array('wordsallowed' => $wordcloud->activity->wordsallowed, 'wordsrequired' => $wordcloud->activity->wordsrequired, 'group'=>$g), 'post');
    
    if (!$form->is_submitted()){
        return false;
    }
    
    if ($data = $form->get_data()) {
        
        $words = array();
        for($i=1;$i<=$wordcloud->activity->wordsallowed;$i++){
            if (isset($data->{'word_'.$i})) {
                $word = trim($data->{'word_'.$i});
                if ($word != ''){
                    $words[$i] = $word;
                }
            }
        }
        
        if (count($words) >= $wordcloud->activity->wordsrequired){
            foreach($words AS $word) {
                $wordcloud->add_word($USER->id, $word,$g);
            }
            // Update completion state
            $completion=new completion_info($wordcloud->course);
            $modinfo = get_fast_modinfo($wordcloud->course);
            $cm = $modinfo->instances['wordcloud'][$wordcloud->activity->id];
            if($completion->is_enabled($cm) && $wordcloud->activity->completionwords) {
                $completion->update_state($cm,COMPLETION_COMPLETE);
            }
            redirect($PAGE->url);
        }else{
            echo 'Error: Missing words';
        }
        return $form->render();
    }else if (!$form->is_validated()){
        return $form->render();
    }
    
    return false;
}