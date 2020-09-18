<?php

/**
 * workflow local plugin
 *
 * Fichier ajax. Fichier qui contient le traitement ajax permettant de vérifier la validité des adresses mails lors des inscriptions CSV
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2020
 *
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot . '/local/workflow/lib/WFEnrolManual.php');
require_login();

define('STATUS_VALID', 'Valide');
define('STATUS_ERROR', 'Invalide'); 
define('STATUS_IGNORED', 'Ignoré'); 

$filesurls = optional_param_array("urls", [], PARAM_URL);
$emailsLists = optional_param_array("manual_emails", [], PARAM_TEXT);
$filesRoleList = optional_param_array("files_role", [], PARAM_TEXT);
$manualEmailsRoleList = optional_param_array("manual_emails_role", [], PARAM_TEXT);

$csvGroupsLists = optional_param_array("csv_groups_lists", [], PARAM_TEXT);
$manualGroupsLists = optional_param_array("manual_groups_lists", [], PARAM_TEXT);

$data = array(); 
$rowclasses = array();
$errorCount = $ignoreCount  = 0;

$v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/"; // regex for badly formed email
$vIgnored = "/^[a-zA-Z0-9_\-\.]+@(?i)ac-normandie\.fr$/"; // regex for ignored email 


$i = 0;
// list csv files
foreach($filesurls as $fileurl) {
	if (isset($fileurl)) {
		$data_url = explode("/",$fileurl);
		// Prepare file record object
		$fileinfo = array(
				'component' => $data_url[6], // usually = table name
				'filearea' => $data_url[7],  // usually = table name
				'itemid' => $data_url[8],    // usually = ID of row in table
				'contextid' => $data_url[5], // ID of context
				'filepath' => '/',           // any path beginning and ending in /
				'filename' => urldecode($data_url[9])); // any filename
	
		$fs = get_file_storage();
		// Get file
		$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
				$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
	
		// Test format du fichier
		if($file && $file->get_mimetype() == "text/csv"){

			// role
			$roleName = $roleStr = $filesRoleList[$i];
			$roleRecord = $DB->get_record('role', array('shortname' => $roleStr));
			if ($roleRecord) {
				$roleName = $roleRecord->name;
			}
			
			// content
			$content = $file->get_content();
			
			$iid = csv_import_reader::get_new_iid('uploaduser');
			$cir = new csv_import_reader($iid, 'uploaduser');
			
			$cir->load_csv_content($content, 'UTF-8', 'semicolon');

			$columns = $cir->get_columns();
			if (empty($columns)) {
				$cir->close();
				$cir->cleanup();
			}
	
			$lines = explode("\n",$content);
			
			foreach ($lines as $key=>$line) {
				if(trim($line)=="") continue;

				$email = null;

				// Create entry
				$rowcols = array();
				$groupList = '';
				if (count($columns) > 1) { // Fichier complexe
					$rawuser = explode(";",$line);
					if(trim($rawuser[0])=="") continue;
					$email = $rawuser[0];
					$groups = [];
					for($j = 2; $j <= count($rawuser)-1; ++$j) { // on démarre à 2 car les 2 premiers sont l'email et le role.
						if(trim($rawuser[$j])=="") continue;
						$groups[] = $rawuser[$j];
					}
					$groupList = implode(', ', $groups);

				}else{ // Fichier simple
					$email = $line;
					$groupList= $csvGroupsLists[$i];
				}
				$email = trim($email);
				$rowcols['email'] = $email ;
				$rowcols['status'] = STATUS_VALID;
				$rowcols['role'] = $roleName;
				$rowcols['groups'] = $groupList;

				// Test Email
				$test_email = preg_match($v, $email);
				$ignored_email = preg_match($vIgnored, $email);
				if($key == 0 && !($test_email)) continue; // Cas où le fichier contient une entete
						
				if(!($test_email)){
					$errorCount++;
					$rowcols['status'] = STATUS_ERROR;
					$rowclasses[] = 'text-error';
				} else if($ignored_email) {
					$ignoreCount++;
					$rowcols['status'] = STATUS_IGNORED;
					$rowclasses[] = 'text-warning';
				} else {
					$rowclasses[] = '';
				}
				$data[] = $rowcols;
			}
			
			$cir->close();
		}else{
			// an error message for the file type has already been shown when selecting it with the filepicker
			// nothing to show if there is no mail
		}
	}
	$i++;
}


$i = 0;
// lists of manual emails
foreach($emailsLists as $emailList) {
	if (isset($emailList)) {
		$emails = WFEnrolManual::multiexplode(array(",",";","|"),$emailList);

		// role
		$roleName = $roleStr = $manualEmailsRoleList[$i];
		$roleRecord = $DB->get_record('role', array('shortname' => $roleStr));
		if ($roleRecord) {
			$roleName = $roleRecord->name;
		}

		foreach ($emails as $email) {
			$email = trim($email);
			if ($email=="") continue;
			$rowcols = array();
			$rowcols['email']= $email;
			$rowcols['status'] = STATUS_VALID;;
			$rowcols['role'] = $roleName;
			$rowcols['groups'] = $manualGroupsLists[$i];
	
			$test_email = preg_match($v, $email);
			$ignored_email = preg_match($vIgnored, $email);
			if(!($test_email) ){
				$errorCount++;
				$rowcols['status'] = STATUS_ERROR;
				$rowclasses[] = 'text-error';
			} else if($ignored_email) {
				$ignoreCount++;
				$rowcols['status'] = STATUS_IGNORED;
				$rowclasses[] = 'text-warning';
			} else {
				$rowclasses[] = '';
			}
			$data[] = $rowcols;
		}
	}
	$i++;
}

$table = createMailPreviewTable($data, $rowclasses);

// affichage des resultats
$outputStr = '';
if ($errorCount > 0) {
	$outputStr .= html_writer::div(get_string('errors_found', 'local_workflow', $errorCount));
}
if ($ignoreCount > 0) {
	$outputStr .= html_writer::div(get_string('ignored_found', 'local_workflow', $ignoreCount));
	$outputStr .= html_writer::empty_tag('br');
	$outputStr .= html_writer::div(get_string('ac-normandie_not_allow', 'local_workflow'));
}
$outputStr .= html_writer::tag('div', html_writer::table($table, array('class'=>'flexible-wrap')));

echo $outputStr;



function createMailPreviewTable($entries, $rowclasses) {
	$table = new html_table();
	$table->id = "preview_mail";
	$table->attributes['class'] = 'generaltable';
	$table->summary = 'Mail preview table';
	$table->head = array(get_string('mailadress', 'local_workflow'),get_string('statuslabel', 'local_workflow'), get_string('role', 'moodle'), get_string('groups', 'moodle'));
	$table->data = $entries;
	$table->rowclasses = $rowclasses;
	return $table;
}
