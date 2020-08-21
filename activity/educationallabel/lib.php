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
 * Library of functions and constants for module label
 *
 * @package mod_educationallabel
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

///////////////
// CONSTANTS //
///////////////

/** LABEL_MAX_NAME_LENGTH = 50 */
define("EDUCLBL_MAX_NAME_LENGTH", 50);

define('LBL_TRAINING_FORMATION', 1);
define('LBL_HOW_SUCCEED_TRAINING', 2);
define('LBL_REALISE_ACTIVITY', 3);
define('LBL_FORMER_NOTE',4);
define('LBL_IMPORTANT',5);

/**
 * @uses EDUCLBL_MAX_NAME_LENGTH
 * @param object $educlabel
 * @return string
 */
function get_educationallabel_name($type) {

    $name = get_string('displaytype'. $type, 'educationallabel');

    return $name;
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $educlabel
 * @return bool|int
 */
function educationallabel_add_instance($educlabel) {
    global $DB;
    if (isset($educlabel->customize_title_cb) && $educlabel->customize_title_cb && isset($educlabel->custom_title) && $educlabel->custom_title !== '') {
        $educlabel->name = $educlabel->custom_title;
    } else {
        $educlabel->name = get_educationallabel_name($educlabel->config_selecttype);
    }
    $educlabel->type = $educlabel->config_selecttype;
    $educlabel->timemodified = time();

    return $DB->insert_record("educationallabel", $educlabel);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $educlabel
 * @return bool
 */
function educationallabel_update_instance($educlabel) {
    global $DB;
    if (isset($educlabel->customize_title_cb) && $educlabel->customize_title_cb && isset($educlabel->custom_title) && $educlabel->custom_title !== '') {
        $educlabel->name = $educlabel->custom_title;
    } else {
        $educlabel->name = get_educationallabel_name($educlabel->config_selecttype);
    }
    $educlabel->type = $educlabel->config_selecttype;
    $educlabel->timemodified = time();
    $educlabel->id = $educlabel->instance;

    return $DB->update_record("educationallabel", $educlabel);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function educationallabel_delete_instance($id) {
    global $DB;

    if (! $educlabel = $DB->get_record("educationallabel", array("id"=>$id))) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records("educationallabel", array("id"=>$educlabel->id))) {
        $result = false;
    }

    return $result;
}

function educationallabel_get_coursemodule_info($cm) {
    global $DB;
    $educlabel = $DB->get_record('educationallabel', array('id'=>$cm->instance), 'id, name, intro, introformat, type');
    $info = new cached_cm_info();
    // type is important to know if we must show the activity to everyone or no
    $info->customdata = array('type' => $educlabel->type); 
    return $info;
}

function educationallabel_cm_info_view(cm_info $cm){
    global $PAGE, $DB,$USER;
    $educlabel = $DB->get_record('educationallabel', array('id'=>$cm->instance), 'id, name, intro, introformat, type');

    $context = context_course::instance($cm->course);

    $tuteur_role = $DB->get_record('role',array("shortname"=>"tuteur"));
    $formateur_role = $DB->get_record('role',array("shortname"=>"formateur"));

    //si l'etiquette pedagogique est de type "note aux formateurs"
    if($educlabel->type == 4 && has_capability('mod/educationallabel:noteblockview', $context)){
        $LBL_FORMER_NOTE = get_string('displaytype'. LBL_FORMER_NOTE, 'educationallabel');
        if ($educlabel->name ===  $LBL_FORMER_NOTE) { 
            $roles = get_user_roles($context, $USER->id, false);
            $firstRole = reset($roles);
            // si le titre n'est pas personnalisé
            if ($firstRole) {
                $renamed = $DB->get_record_sql("SELECT rn.name FROM {role_names} rn WHERE rn.contextid = :contextid AND rn.roleid = :roleid ", array('contextid'=>$context->id,"roleid" => $firstRole->roleid));
                if($renamed)
                    $educlabel->name= "Note aux ".strtolower($renamed->name)."s";
            }
        } else {
            // titre personnalise
            $roles = get_roles_with_capability('mod/educationallabel:noteblockview', CAP_ALLOW, $context);
            $roles = role_fix_names($roles, $context, ROLENAME_ORIGINAL);
            foreach ($roles as $role) {
                $renamedRole = role_get_name($role, $context, ROLENAME_ALIAS);
                if ($renamedRole) {
                    $educlabel->name = preg_replace(
                        array('/\b'.$role->localname.'(s?)\b/i'),
                        array(strtolower($renamedRole).'$1'),
                        $educlabel->name);
                }
            }
        }
    }

    // display educational label
    $renderer = $PAGE->get_renderer('mod_educationallabel');
    $cm->set_extra_classes(" modtype_educationallabel_type".$educlabel->type);
    $cm->set_content($renderer->display_label($educlabel, $cm));
}


function educationallabel_cm_info_dynamic(cm_info $cm){
    $context = context_course::instance($cm->course);
    // set available only works for role who don't have the viewhiddenactivities capability
    if (
        ($cm->customdata['type'] == '1' && !(has_capability('mod/educationallabel:presentationblockview', $context)))
        || ($cm->customdata['type'] == '2' && !(has_capability('mod/educationallabel:succedblockview', $context)))
        || ($cm->customdata['type'] == '3' && !(has_capability('mod/educationallabel:activityblockview', $context)))
        || ($cm->customdata['type'] == '4' && !(has_capability('mod/educationallabel:noteblockview', $context)))
        || ($cm->customdata['type'] == '5' && !(has_capability('mod/educationallabel:importantblockview', $context)))
    ) {
        $cm->set_available(false,0);
        $cm->set_user_visible(false);
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function educationallabel_reset_userdata($data) {
    return array();
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function educationallabel_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function educationallabel_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return false;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_NO_VIEW_LINK:            return true;

        default: return null;
    }
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function educationallabel_dndupload_register() {
    $strdnd = get_string('dnduploadeducationallabel', 'mod_educationallabel');
    if (get_config('label', 'dndmedia')) {
        $mediaextensions = file_get_typegroup('extension', 'web_image');
        $files = array();
        foreach ($mediaextensions as $extn) {
            $extn = trim($extn, '.');
            $files[] = array('extension' => $extn, 'message' => $strdnd);
        }
        $ret = array('files' => $files);
    } else {
        $ret = array();
    }

    $strdndtext = get_string('dnduploadeducationallabeltext', 'mod_educationallabel');
    return array_merge($ret, array('types' => array(
        array('identifier' => 'text/html', 'message' => $strdndtext, 'noname' => true),
        array('identifier' => 'text', 'message' => $strdndtext, 'noname' => true)
    )));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function educationallabel_dndupload_handle($uploadinfo) {
    global $USER;

    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '';
    $data->introformat = FORMAT_HTML;
    $data->coursemodule = $uploadinfo->coursemodule;

    // Extract the first (and only) file from the file area and add it to the label as an img tag.
    if (!empty($uploadinfo->draftitemid)) {
        $fs = get_file_storage();
        $draftcontext = context_user::instance($USER->id);
        $context = context_module::instance($uploadinfo->coursemodule);
        $files = $fs->get_area_files($draftcontext->id, 'user', 'draft', $uploadinfo->draftitemid, '', false);
        if ($file = reset($files)) {
            if (file_mimetype_in_typegroup($file->get_mimetype(), 'web_image')) {
                // It is an image - resize it, if too big, then insert the img tag.
                $config = get_config('educationallabel');
                $data->intro = educationallabel_generate_resized_image($file, $config->dndresizewidth, $config->dndresizeheight);
            } else {
                // We aren't supposed to be supporting non-image types here, but fallback to adding a link, just in case.
                $url = moodle_url::make_draftfile_url($file->get_itemid(), $file->get_filepath(), $file->get_filename());
                $data->intro = html_writer::link($url, $file->get_filename());
            }
            $data->intro = file_save_draft_area_files($uploadinfo->draftitemid, $context->id, 'mod_educationallabel', 'intro', 0,
                null, $data->intro);
        }
    } else if (!empty($uploadinfo->content)) {
        $data->intro = $uploadinfo->content;
        if ($uploadinfo->type != 'text/html') {
            $data->introformat = FORMAT_PLAIN;
        }
    }

    $data->config_selecttype = LBL_TRAINING_FORMATION;
    return educationallabel_add_instance($data, null);
}

/**
 * Resize the image, if required, then generate an img tag and, if required, a link to the full-size image
 * @param stored_file $file the image file to process
 * @param int $maxwidth the maximum width allowed for the image
 * @param int $maxheight the maximum height allowed for the image
 * @return string HTML fragment to add to the label
 */
function educationallabel_generate_resized_image(stored_file $file, $maxwidth, $maxheight) {
    global $CFG;

    $fullurl = moodle_url::make_draftfile_url($file->get_itemid(), $file->get_filepath(), $file->get_filename());
    $link = null;
    $attrib = array('alt' => $file->get_filename(), 'src' => $fullurl);

    if ($imginfo = $file->get_imageinfo()) {
        // Work out the new width / height, bounded by maxwidth / maxheight
        $width = $imginfo['width'];
        $height = $imginfo['height'];
        if (!empty($maxwidth) && $width > $maxwidth) {
            $height *= (float)$maxwidth / $width;
            $width = $maxwidth;
        }
        if (!empty($maxheight) && $height > $maxheight) {
            $width *= (float)$maxheight / $height;
            $height = $maxheight;
        }

        $attrib['width'] = $width;
        $attrib['height'] = $height;

        // If the size has changed and the image is of a suitable mime type, generate a smaller version
        if ($width != $imginfo['width']) {
            $mimetype = $file->get_mimetype();
            if ($mimetype === 'image/gif' or $mimetype === 'image/jpeg' or $mimetype === 'image/png') {
                require_once($CFG->libdir.'/gdlib.php');
                $tmproot = make_temp_directory('mod_label');
                $tmpfilepath = $tmproot.'/'.$file->get_contenthash();
                $file->copy_content_to($tmpfilepath);
                $data = generate_image_thumbnail($tmpfilepath, $width, $height);
                unlink($tmpfilepath);

                if (!empty($data)) {
                    $fs = get_file_storage();
                    $record = array(
                        'contextid' => $file->get_contextid(),
                        'component' => $file->get_component(),
                        'filearea'  => $file->get_filearea(),
                        'itemid'    => $file->get_itemid(),
                        'filepath'  => '/',
                        'filename'  => 's_'.$file->get_filename(),
                    );
                    $smallfile = $fs->create_file_from_string($record, $data);

                    // Replace the image 'src' with the resized file and link to the original
                    $attrib['src'] = moodle_url::make_draftfile_url($smallfile->get_itemid(), $smallfile->get_filepath(),
                        $smallfile->get_filename());
                    $link = $fullurl;
                }
            }
        }

    } else {
        // Assume this is an image type that get_imageinfo cannot handle (e.g. SVG)
        $attrib['width'] = $maxwidth;
    }

    $img = html_writer::empty_tag('img', $attrib);
    if ($link) {
        return html_writer::link($link, $img);
    } else {
        return $img;
    }
}
