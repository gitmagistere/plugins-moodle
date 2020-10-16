<?php
/**
 * Moodle Magistere_offer local plugin
 * This API use the class PublicsByFunctionUser to transmit user's publics data to the Frontend
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/magistere_offers/lib.php');

global $USER;

@ini_set('display_errors', '0'); // NOT FOR PRODUCTION SERVERS!
$CFG->debug = false;         // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = false;


$PAGE->set_context(context_system::instance());
require_login();
$json_query = file_get_contents('php://input');

$params = json_decode($json_query, false, 512, JSON_HEX_QUOT);

if ($params === null && !is_object($params)){
    die('{"error":true}');
}

if(isset($params->display)){
    $json = new stdClass();
    $json->publics = null;
    $publics = new PublicsByFunctionUser($USER->id, $params->display);

    $has_publics = false;


    if(count($publics->get_publics_user()) != 0){
        $has_publics = true;
        $json->publics = $publics->prepare_publics_for_checkboxes_form();
    }

    $json->has_publics = $has_publics;
    echo json_encode($json);
}else{
    die('{"error":true}');
}

