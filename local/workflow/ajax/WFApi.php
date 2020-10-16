<?php

/**
 * workflow local plugin
 *
 * Fichier duplication. Fichier appelé dès la validation du formulaire présent dans les popin d'actions sur le workflow.
 * Le traitement des données est différent selon le type d'action choisie.
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2018
 *
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once($CFG->dirroot . '/local/workflow/lib/WFManager.php');

$action_type = required_param('link_type', PARAM_TEXT);
$course_id = required_param('course_id', PARAM_INT);
$new_category = optional_param('new_category_course', '', PARAM_ALPHANUMEXT);
$publish_method = optional_param('method', 'share', PARAM_ALPHANUMEXT);
$isalocalsession = optional_param('is_local_session', 0, PARAM_BOOL);

if($action_type == 'createsessionfromparcours' || $action_type == 'recreatesessionfromparcours'){
    $new_name = required_param('new_course_name', PARAM_TEXT);
    $new_shortname = required_param('new_course_shortname', PARAM_TEXT);
    $move_type = required_param('move_type', PARAM_TEXT);
    $date = optional_param('datepicker_session', '', PARAM_TEXT);

    if($move_type == "move"){
        $courseurl = new moodle_url('/course/view.php', array('id' => $course_id));

        if($new_category == ''){
            $sessioncat = $DB->get_record('course_categories', array('name' => 'Session de formation'));

            if(!$sessioncat){
                redirect($courseurl);
            }
            $new_category = $sessioncat->id;
        }

        if($new_category && move_courses(array($course_id), $new_category)){
            $update = new stdClass();
            $update->id = $course_id;
            $update->shortname = $new_shortname;
            $update->name = $new_name;

            if($date != ''){
                $date = str_replace('/', '-', $date);
                $date = strtotime($date);
                $update->startdate = $date;
            } else {
                $update->startdate = time();
            }
            $DB->update_record('course', $update);
        }
        redirect($courseurl);
    }else if ($move_type == "duplication" && WFManager::shortname_is_unique($new_shortname, $course_id)){
        $courseurl = WFManager::course_duplication($course_id, $action_type, $new_name, $new_shortname, $new_category, $date);
        redirect($courseurl);
    }
}elseif($action_type == 'archive'){
    $archive_type = required_param('access', PARAM_ALPHANUMEXT);
    WFManager::archive_course($course_id, $new_category, $archive_type);
}elseif($action_type == 'unarchive'){
    WFManager::unarchive_course($course_id, $new_category);
}elseif($action_type == 'discard'){
    $corbeille = $DB->get_record('course_categories',array('name'=>'Corbeille'));
    if ($corbeille !== false){
        WFManager::discard_course($course_id, $corbeille->id);
    }
}elseif($action_type == 'open_session'){
    WFManager::open_session($course_id);
}elseif($action_type == 'open_auto_inscription'){
    WFManager::open_auto_inscription($course_id);
}elseif($action_type == 'restorefromtrash'){
    WFManager::restorefromtrash_course($course_id, $new_category);
}elseif($action_type == 'publish'){
    WFManager::course_publish($course_id, $publish_method, $isalocalsession);
}elseif($action_type == 'unpublish'){
    WFManager::course_unpublish($course_id, $publish_method);
}else{
    //createparcoursfromgabarit  //duplicate
    $new_name = required_param('new_course_name', PARAM_TEXT);
    $new_shortname = required_param('new_course_shortname', PARAM_TEXT);

    if(WFManager::shortname_is_unique($new_shortname, $course_id)){
        $courseurl = WFManager::course_duplication($course_id, $action_type, $new_name, $new_shortname, $new_category);
        redirect($courseurl);
    }
}
