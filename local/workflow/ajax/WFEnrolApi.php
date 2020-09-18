<?php

/**
 * workflow local plugin
 *
 * Fichier ajax. Fichier qui contient le traitement ajax permettant d'ajouter des utilisateurs au travers d'un fichier CSV
 * sur le parcours lié au workflow.
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2018
 *
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/filelib.php');
require_login();

$fileurl = required_param("url", PARAM_URL);
$courseid = required_param("courseid", PARAM_INT);
$msg = get_string('ajax_no_encountered_problem', 'local_workflow');
$type_msg = 'success';
$content = "";
$type = "";

$data_url = explode("/",$fileurl);

$fs = get_file_storage();

// Prepare file record object
$fileinfo = array(
		'component' => $data_url[6], // usually = table name
		'filearea' => $data_url[7],  // usually = table name
		'itemid' => $data_url[8],    // usually = ID of row in table
		'contextid' => $data_url[5], // ID of context
		'filepath' => '/',           // any path beginning and ending in /
		'filename' => urldecode($data_url[9])); // any filename

// Get file
$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
		$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

// Test format du fichier
if($file){
	if($file->get_mimetype() != "text/csv"){
		$msg = get_string('ajax_error_no_csv_file', 'local_workflow');
        $type_msg = 'error';
		$data = json_encode(array(
				'url' => $fileurl,
				'msg' => $OUTPUT->notification($msg, $type_msg),
				'type' => $type
		));
		echo $data;
		exit();
	}
	// Read contents
	$content = $file->get_content();
	
	$iid = csv_import_reader::get_new_iid('uploaduser');
	$cir = new csv_import_reader($iid, 'uploaduser');
	
	$cir->load_csv_content($content, 'UTF-8', 'semicolon');

	$columns = $cir->get_columns();
	if (empty($columns)) {
		$cir->close();
		$cir->cleanup();
	}

	$v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/";
	$vIgnored = "/^[a-zA-Z0-9_\-\.]+@(?i)ac-normandie\.fr$/";
	$lines = explode("\n",$content);
	foreach ($lines as $key=>$line) {
		if(trim($line)=="") continue;
		if (count($columns) > 1) { // Fichier complexe
			$type = "complex";
			$rawuser = explode(";",$line);
			if(trim($rawuser[0])=="") continue;
			// Test Email
			
			$test_email = preg_match($v, $rawuser[0]);
			if($key == 0 && !($test_email)) continue; // Cas où le fichier contient une entete
					
			if(!($test_email)){
				$msg = get_string('ajax_error_email_entry', 'local_workflow');
                $type_msg = 'warning';
				break;
			}
			// Test rôle
			$test_role = $DB->get_record('role', array('shortname' => $rawuser[1]));
			if(!($test_role)){
				$msg = get_string('ajax_error_role_entry', 'local_workflow');
                $type_msg = 'warning';
				break;
			}
			// Gestion des groupes
			for($i = 2; $i <= count($rawuser)-1; ++$i) { 
				if(trim($rawuser[$i])=="") continue;
				$group = $DB->get_record('groups', array('name' => trim($rawuser[$i]), 'courseid' => $courseid));
				if(!($group)){ 
					$msg = get_string('ajax_error_no_group_entry', 'local_workflow');
                    $type_msg = 'warning';
					break;
				}
			}
		}else{ // Fichier simple
			$type = "simple";
			// Test Email
			$test_email = preg_match($v, trim($line));
			if($key == 0 && !($test_email)) continue; // Cas où le fichier contient une entete
			if(!($test_email) ){
				$msg = get_string('ajax_error_email_entry', 'local_workflow');
                $type_msg = 'warning';
				break;
			}
		}
	}
	
	$cir->close();
}else{
    $msg = get_string('ajax_error_format_file', 'local_workflow');
    $type_msg = 'error';
}

$data = json_encode(array(
    'url' => $fileurl,
    'msg' => $OUTPUT->notification($msg, $type_msg),
    'type' => $type
));
echo $data;
