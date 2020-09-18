<?php

/**
 * workflow local plugin
 *
 * Fichier API qui gere le nettoyage des parcours en passant par le workflow.
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2019
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/workflow/lib.php');

if(!isOptimizerAvailable()){
    die('{"error":true,"msg":"Unavailable"}');
}

require_once($CFG->dirroot.'/local/magisterelib/CourseFilesOptimizer.php');

define('CFO_SESSION_KEY','_course_files_optimiser_sessionkey');
define('CFO_SESSION_DATA','_course_files_optimiser_sessionkey_data');

@ini_set('display_errors', '0'); // NOT FOR PRODUCTION SERVERS!
$CFG->debug = false;         // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = false;


$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/workflow/api.php');
require_login();

$json_query = file_get_contents('php://input');
header('Receive: '.$json_query);
$params = json_decode($json_query, false, 512, JSON_HEX_QUOT);

if ($params === null && !is_object($params))
{
    die('{"error":true}');
}

$canaccess = false;
// Check system capability
if (has_capability('local/workflow:optimize', context_system::instance())){
    $canaccess = true;
}

// Check course capability
if ($canaccess == false && isset($params->courseid)) {
    if (has_capability('local/workflow:optimize', context_course::instance($params->courseid))){
        $canaccess = true;
    }
}

if ($canaccess == false){
    die('{"error":true,"msg":"Access Denied"}');
}

// {"module":"getunusedfiles","courseid":1234,"session":"CQ45cds5cez"}                              // retourne la liste des fichiers inutilises
// {"module":"getusedfilestoconvert","courseid":1234,"session":"CQ45cds5cez"}                       // retourne la liste des fichiers pouvant etre centralises
// {"module":"deleteunusedfiles","courseid":1234,"session":"CQ45cds5cez","files":[1,2,...]}       // supprime les fichiers inutilises selectionnes
// {"module":"convertunusedfilestocr","courseid":1234,"session":"CQ45cds5cez","files":[1,2,...]}  // centralise les fichiers utilises selectionnes
// {"module":"getresults","courseid":1234,"session":"CQ45cds5cez"}                                  // retourne les resultat de la suppression et de la centralisation


if ( isset($params->module) && isset($params->courseid) && isset($params->session) &&
    $params->module == 'getunusedfiles' && is_int($params->courseid) && $params->courseid > 0 && strlen($params->session) == 25 )
{
    
    $course = $DB->get_record('course', array('id'=>$params->courseid));
    if ($course === false) {
        die('{"error":true}');
    }
    
    $optimizer = new CourseFilesOptimizer($params->courseid);
    $files = $optimizer->get_unused_files();
    
    $session = new stdClass();
    $session->session = $params->session;
    $session->courseid = $params->courseid;
    $session->unusedfiles = $files;
    $session->step = 1;
    mmcached_set(get_mmid().CFO_SESSION_KEY, $params->session);
    mmcached_set(get_mmid().CFO_SESSION_DATA, serialize($session));
    
    $files_json = new stdClass();
    $files_json->error = false;
    $files_json->files = array();
    foreach($files AS $file) {
        
        $file_json = new stdClass();
        $file_json->id = $file->fid;
        $file_json->name = $file->filename;
        
        $files_json->files[] = $file_json;
    }
    
    echo json_encode($files_json);
    
}else if ( isset($params->module) && isset($params->courseid) && isset($params->session) && isset($params->files) &&
    $params->module == 'deleteunusedfiles' && is_int($params->courseid) && $params->courseid > 0 && strlen($params->session) == 25 && is_array($params->files) )
{
    
    $sessionid = mmcached_get(get_mmid().CFO_SESSION_KEY);
    
    if ($sessionid != $params->session){
        die('{"error":true,"msg":"Invalid Session"}');
    }
    
    // Get the current session
    $session = mmcached_get(get_mmid().CFO_SESSION_DATA);
    $session = unserialize($session);
    
    // Check if the session is at the good step
    if ($session->step != 1){
        die('{"error":true,"msg":"Invalid Step '.$session->step.'/1"}');
    }
    
    // Initialize result array
    $session->unusedfiles_succeed = array();
    $session->unusedfiles_failed = array();
    
    $optimizer = new CourseFilesOptimizer($params->courseid);
    
    // We check each file id in the query
    foreach($params->files AS $fileid){
        if (array_key_exists($fileid, $session->unusedfiles)) {
            if ($optimizer->remove_unused_file($fileid)){
            //if (rand(1,50)<25){
                $session->unusedfiles_succeed[$fileid] = $session->unusedfiles[$fileid];
            }else{
                $session->unusedfiles_failed[$fileid] = $session->unusedfiles[$fileid];
            }
        }
    }
    
    rebuild_course_cache($params->courseid);
    
    mmcached_set(get_mmid().CFO_SESSION_DATA, serialize($session));
    
    die('{"error":false}');
    
}else if ( isset($params->module) && isset($params->courseid) && isset($params->session) &&
    $params->module == 'getusedfilestoconvert' && is_int($params->courseid) && $params->courseid > 0 && strlen($params->session) == 25 )
{
    
    $sessionid = mmcached_get(get_mmid().CFO_SESSION_KEY);
    
    if ($sessionid != $params->session){
        die('{"error":true,"msg":"Invalid Session"}');
    }
    
    // Get the current session
    $session = mmcached_get(get_mmid().CFO_SESSION_DATA);
    $session = unserialize($session);
    
    $course = $DB->get_record('course', array('id'=>$params->courseid));
    if ($course === false) {
        die('{"error":true}');
    }
    
    $optimizer = new CourseFilesOptimizer($params->courseid);
    $files = $optimizer->get_used_files_bigger_than();
    
    $session->step = 2;
    $session->filestoconvert = $files;
    
    $files_json = new stdClass();
    $files_json->error = false;
    $files_json->files = array();
    foreach($files AS $file) {
        
        $file_json = new stdClass();
        $file_json->id = $file->fid;
        $file_json->name = $file->filename;
        
        $files_json->files[] = $file_json;
    }
    
    mmcached_set(get_mmid().CFO_SESSION_DATA, serialize($session));
    
    echo json_encode($files_json);
    
    
}else if ( isset($params->module) && isset($params->courseid) && isset($params->session) && isset($params->files) &&
    $params->module == 'convertunusedfilestocr' && is_int($params->courseid) && $params->courseid > 0 && strlen($params->session) == 25 && is_array($params->files) )
{
    
    
    $sessionid = mmcached_get(get_mmid().CFO_SESSION_KEY);
    
    if ($sessionid != $params->session){
        die('{"error":true,"msg":"Invalid Session"}');
    }
    
    // Get the current session
    $session = mmcached_get(get_mmid().CFO_SESSION_DATA);
    $session = unserialize($session);
    
    // Check if the session is at the good step
    if ($session->step != 2){
        die('{"error":true,"msg":"Invalid Step '.$session->step.'/2"}');
    }
    
    // Initialize result array
    $session->filestoconvert_succeed = array();
    $session->filestoconvert_failed = array();
    
    $optimizer = new CourseFilesOptimizer($params->courseid);
    $filesSortedBySection = $optimizer->sort_files_by_section($params->files);
    
    // We check each file id in the query
    foreach($filesSortedBySection AS $fileid){
        if (array_key_exists($fileid, $session->filestoconvert)) {
            if ($optimizer->centralize_used_file_and_replace($fileid)){
            //if (rand(1,50)<25){
                $session->filestoconvert_succeed[$fileid] = $session->filestoconvert[$fileid];
            }else{
                $session->filestoconvert_failed[$fileid] = $session->filestoconvert[$fileid];
            }
        }
    }
    
    rebuild_course_cache($params->courseid);
    
    mmcached_set(get_mmid().CFO_SESSION_DATA, serialize($session));
    
    die('{"error":false}');
    
}else if ( isset($params->module) && isset($params->courseid) && isset($params->session) &&
    $params->module == 'getresults' && is_int($params->courseid) && $params->courseid > 0 && strlen($params->session) == 25 )
{
    
    $sessionid = mmcached_get(get_mmid().CFO_SESSION_KEY);
    
    if ($sessionid != $params->session){
        die('{"error":true,"msg":"Invalid Session"}');
    }
    
    // Get the current session
    $session = mmcached_get(get_mmid().CFO_SESSION_DATA);
    $session = unserialize($session);
    
    // Check if the session is at the good step
    if ($session->step != 2){
        die('{"error":true,"msg":"Invalid Step '.$session->step.'/2"}');
    }
    
    $res = new stdClass();
    $res->file_deleted = array();
    $res->file_converted = array();
    $res->file_failed = array();
    
    if (isset($session->unusedfiles_succeed)) {
        foreach($session->unusedfiles_succeed AS $file){
            $json_file = new stdClass();
            $json_file->name = $file->filename;
            $res->file_deleted[] = $json_file;
        }
    }
    
    if (isset($session->filestoconvert_succeed)) {
        foreach($session->filestoconvert_succeed AS $file){
            $json_file = new stdClass();
            $json_file->name = $file->filename;
            $res->file_converted[] = $json_file;
        }
    }
    
    if (isset($session->unusedfiles_failed)) {
        foreach($session->unusedfiles_failed AS $file){
            $json_file = new stdClass();
            $json_file->name = $file->filename;
            $res->file_failed[] = $json_file;
        }
    }
    
    if (isset($session->filestoconvert_failed)) {
        foreach($session->filestoconvert_failed AS $file){
            $json_file = new stdClass();
            $json_file->name = $file->filename;
            $res->file_failed[] = $json_file;
        }
    }
    
    $res->error = false;
    
    echo json_encode($res);
    
}else{
    die('{"error":true}');
}



