<?php

/**
 * Moodle Magistere_offer local plugin
 * Generate and send the XML catalog to the user
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/local/magistere_offers//lib.php');

$filter = optional_param('filter', '', PARAM_RAW);
$tab = optional_param('tab', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Catalogue parcours');
$PAGE->set_heading('Catalogue parcours');
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/magistere_offers/xmlCatalog.php',array('filter'=>$filter, 'tab'=>$tab, 'userid'=>$userid));

$filter = unserialize(base64_decode($filter));

$offerCourse = new OfferCourse($filter, $tab, $userid);
$offers = $offerCourse->get_all_course_offers();

if($offers){
    $xml_file = new OfferCourseXML($offers);
    $xml_file->download_xml_file();
}