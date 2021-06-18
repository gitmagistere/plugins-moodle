<?php


/**
 * Moodle MyIndex local plugin
 * This API use the class MyIndexApi to transmit data to the Frontend
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_SESSION_UPDATE', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once($CFG->dirroot.'/mod/wordcloud/wordcloud.php');

@ini_set('display_errors', '0');
$CFG->debug = false; //E_ALL | E_STRICT;   // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = false;


require_login();

//if (has_capability($capability, $context))

$json_query = file_get_contents('php://input');

//header('Receive: '.$json_query);

$params = json_decode($json_query, false, 512, JSON_HEX_QUOT);

if ($params === null && !is_object($params))
{
    die('{"error":true,"msg":"BAD request"}');
}

if (isset($params->cmid)) {
    $cm = get_coursemodule_from_id('wordcloud', $params->cmid);
    
    $wc = $DB->get_record('wordcloud', array('id'=>$cm->instance));
    if ($wc === false) {
        die('{"error":true,"msg":"Wrong ID"}');
    }
    $context = context_course::instance($wc->course);
    $PAGE->set_context($context);
    $wordcloud = new wordcloud($wc->id);
}else{
    die('{"error":true,"msg":"Missing params"}');
}

if ((($wc->timestart>0 && time() < $wc->timestart) || ($wc->timeend > 0 & time() > $wc->timeend)) && !$wordcloud->is_editor()) {
    die('{"error":true,"msg":"This activity is not openned"}');
}

$PAGE->set_url('/mod/wordcloud/api.php');

// {'cmid':2,'action':'getwords','groupid':0,'lastupdate':1615616555}   // 


if ( isset($params->action) && isset($params->groupid) && isset($params->lastupdate) &&
    $params->action == 'getdata' && $params->groupid >= 0 && $params->lastupdate >= 0 )
{
    if (!has_capability('mod/wordcloud:submitword', $context)) {
        die('{"error":true,"msg":"Access denied"}');
    }
    if ($params->lastupdate > 0 && $params->lastupdate == $wc->timemodified) {
        die('{"error":false,"mod":"c","noupdate":true}');
    }
    
    // Editors
    $member = false;
    $groups = $wordcloud->get_user_groups();
    foreach($groups AS $group) {
        if ($group->id == $params->groupid) {
            $member = $group->member;
        }
    }
    $cmcontext = context_module::instance($cm->id);

    // Student
    $readonly = true;
    if (!$wordcloud->is_editor()){
        if ($wordcloud->cm->groupmode == 0){
            $readonly = false;
        }else if ($member){
            $readonly = false;
        }
    }
    
    if (count($wordcloud->get_user_words($USER->id,$params->groupid)) > 0 || $wordcloud->is_editor() || $readonly) {
        $words = $wordcloud->get_cloud_words($params->groupid);
        $users = $wordcloud->get_cloud_users($params->groupid);
        die('{"error":false,"mod":"c","noupdate":false,"editor":'.($wordcloud->is_editor() && ($member || has_capability('moodle/site:accessallgroups', $cmcontext))?'true':'false').',"timemodified":'.$wc->timemodified.',"subs":'.count($users).',"words":'.json_encode($words).'}');
    }else{
        $renderer = $PAGE->get_renderer('mod_wordcloud');
        $form = $renderer->display_wordcloud_submit_form($wordcloud,$params->groupid);
        die('{"error":false,"mod":"f","form":'.json_encode($form).'}');
    }
}

else if ( isset($params->action) && isset($params->groupid) && isset($params->word) &&
    $params->action == 'getwordinfo' && $params->groupid >= 0 && $params->word >= 0 )
{
    if (!has_capability('mod/wordcloud:manageword', $context)) {
        die('{"error":true,"msg":"Access denied"}');
    }
    
    $infos = $wordcloud->get_cloud_word_info($params->word,$params->groupid);
    
    if($infos===false){
        die('{"error":true,"msg":"Word not found"}');
    }
    
    die('{"error":false,"word":"'.$params->word.'","weight":'.$infos->weight.',"users":'.json_encode($infos->usershtml).'}');
}

else if ( isset($params->action) && isset($params->groupid) && isset($params->word) &&
    $params->action == 'addword' && $params->groupid >= 0 && strlen($params->word) > 0 )
{
    if (!has_capability('mod/wordcloud:manageword', $context)) {
        die('{"error":true,"msg":"Access denied"}');
    }
    
    $result = $wordcloud->add_word($USER->id,$params->word,$params->groupid);
    
    $msg = '';
    $error = 'true';
    if($result===true){
        $msg = get_string('wordadded','wordcloud');
        $error = 'false';
    }else if($result==wordcloud::ERROR_WORD_ALREADY_EXIST){
        $msg = get_string('wordalreadyexist','wordcloud');
    }else if($result==wordcloud::ERROR_WORD_TOO_LONG){
        $msg = get_string('wordistoolong','wordcloud');
    }else{
        $msg = get_string('wordisnotvalid','wordcloud');
    }
    die('{"error":'.$error.',"msg":"'.$msg.'"}');
    
}

else if ( isset($params->action) && isset($params->groupid) && isset($params->word) && isset($params->newword) &&
    $params->action == 'updateword' && $params->groupid >= 0 && strlen($params->word) && strlen($params->newword) )
{
    if (!has_capability('mod/wordcloud:manageword', $context)) {
        die('{"error":true,"msg":"Access denied"}');
    }
    
    $result = $wordcloud->rename_word($params->word,$params->newword,$params->groupid);
    
    $msg = '';
    $error = 'true';
    if($result===true){
        $msg = get_string('wordupdated','wordcloud');
        $error = 'false';
    }else if($result==wordcloud::ERROR_NEW_WORD_IS_THE_SAME){
        $msg = get_string('newwordisthesame','wordcloud');
    }else if($result==wordcloud::ERROR_NO_WORD_FOUND){
        $msg = get_string('oldwordnotfound','wordcloud');
    }else{
        $msg = get_string('wordisnotvalid','wordcloud');
    }
    die('{"error":'.$error.',"msg":"'.$msg.'"}');
    
}

else if ( isset($params->action) && isset($params->groupid) && isset($params->word) && isset($params->newword) &&
    $params->action == 'simupdateword' && $params->groupid >= 0 && strlen($params->word) && strlen($params->newword) )
{
    if (!has_capability('mod/wordcloud:manageword', $context)) {
        die('{"error":true,"msg":"Access denied"}');
    }
    
    list($fusion,$newweight) = $wordcloud->simulate_rename_word($params->word,$params->newword,$params->groupid);
    
    $msg = '';
    $error = 'true';
    if($result===true){
        $msg = get_string('wordupdated','wordcloud');
        $error = 'false';
    }else if($result==wordcloud::ERROR_NO_WORD_FOUND){
        $msg = get_string('oldwordnotfound','wordcloud');
    }else{
        die('{"error":false,"subs":'.$newweight.',"fusion":'.($fusion?'true':'false').'}');
    }
    die('{"error":'.$error.',"msg":"'.$msg.'"}');
    
}

else if ( isset($params->action) && isset($params->groupid) && isset($params->word) &&
    $params->action == 'removeword' && $params->groupid >= 0 && $params->word >= 0 )
{
    if (!has_capability('mod/wordcloud:manageword', $context)) {
        die('{"error":true,"msg":"Access denied"}');
    }
    
    $nbwords = $wordcloud->remove_word($params->word,$params->groupid);
    
    $msg = '';
    if($nbwords > 1){
        $msg = get_string('nwordsdeleted','wordcloud',$nbwords);
    }elseif($nbwords == 1){
        $msg = get_string('oneworddeleted','wordcloud');
    }else{
        $msg = get_string('noworddeleted','wordcloud');
    }
    
    die('{"error":false,"msg":"'.$msg.'"}');
    
}

else if ( isset($params->action) && isset($params->groupid) &&
    $params->action == 'exportdata' && $params->groupid >= 0 )
{
    if (!has_capability('mod/wordcloud:manageword', $context)) {
        die('{"error":true,"msg":"Access denied"}');
    }
    $delimiter = get_string('listsep','core_langconfig');
    
    $users_words = $wordcloud->get_cloud_users_words($params->groupid);
    
    $filename = tempnam(sys_get_temp_dir(),'magexp');
    $tfile = fopen($filename,'w+');
    
    fputs($tfile, (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    $headers = array(get_string('csv_word','wordcloud'),get_string('csv_user','wordcloud'),get_string('csv_date','wordcloud'));
    fputcsv($tfile, $headers, $delimiter);
    
    foreach($users_words AS $word) {
        fputcsv($tfile, array($word->word,$word->user,date("d/m/Y H:i:s",$word->timecreated)), $delimiter);
    }
    
    fclose($filename);
    $csvfile = base64_encode(file_get_contents($filename));
    
    die('{"error":false,"data":"'.$csvfile.'"}');
    
}else{
    die('{"error":true}');
}



