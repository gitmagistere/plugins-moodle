<?php

/**
 * Moodle Magistere_offer local plugin
 * This class describes the service used to transmit the modal data to the Frontend
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir."/externallib.php");
require_once($CFG->dirroot."/local/coursehub/CourseHub.php");
require_once($CFG->dirroot."/local/magistere_offers/lib.php");

class local_magistere_offers_external extends external_api {
    /**
     * Used for the external service
     * @return external_function_parameters
     */
    public static function get_detail_offer_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id of course offer'),
            )
        );
    }

    /**
     * Used for the external service
     * @return external_value
     */
    public static function get_detail_offer_returns(){
        return new external_value(PARAM_RAW, 'multilang compatible name, course unique');
    }

    /**
     * Return the data of a course to be displayed on the modal in JSON format
     * @param int $id id of a course
     * @return string
     */
    public static function get_detail_offer($id){
        global $CFG, $OUTPUT, $PAGE;
        $PAGE->set_context(context_system::instance());
        
        require_once($CFG->dirroot.'/local/magistere_offers/lib.php');
        $data_array = array();

        $offer = OfferCourse::get_hub_course_offer($id);
        
        if($offer){
            $is_formation = false;

            if ($offer->publish == CourseHub::PUBLISH_PUBLISHED){
                $is_formation = true;
            }


            $data_array = array(
                'id' => $id,
                'title' => OfferCourse::string_format_offers_title($offer->fullname),
                'description' => $offer->summary,
                'is_formation' => $is_formation,
                'pdf_url' => null
            );

            
            $data_array['shortlink'] = (new moodle_url('/'.($is_formation?'f':'p').$offer->fakeid))->out();

            if (OfferCourse::isIndexationAvailable())
            {
                $data_array['pdf_url'] = (new moodle_url('/local/magistere_offers/downloadpdf.php', array('id'=> $offer->fakeid)))->out(false);

                $data_array['domain'] = $offer->domain_name;
                $data_array['objective'] = $offer->objectif;
                $data_array['publics'] = $offer->publics;
                $data_array['authors'] = $offer->authors;
                $data_array['certification'] = $offer->certificat_name;
                
                if (strlen($offer->departement) > 1)
                {
                    $data_array['departement'] = ucwords(strtolower($offer->departement),' -\'');
                }
    
                if(isset($offer->origin_shortname) && $offer->origin_shortname != null) {
                    if ($offer->origin_shortname == "academie") {
                        $origin_name = OfferCourse::string_format_origine_offers($offer->aca_uri);
                        $origin_pix_url = $OUTPUT->image_url('origins/' . strtoupper($offer->aca_uri), 'theme');
                    } else {
                        $origin_name = OfferCourse::string_format_origine_offers($offer->origin_shortname);
                        $origin_pix_url = $OUTPUT->image_url('origins/' . strtoupper($offer->origin_shortname), 'theme');
                    }
                    $data_array['origin_extraclass_img'] = "";
                    if ($offer->origin_shortname != "autre") {
                        $data_array['origin_url'] = $origin_pix_url->out();
                        if($offer->origin_shortname == "dne-foad"){
                            $data_array['origin_extraclass_img'] = "dne-foad";
                        }
                    }
                    $data_array['origin_name'] = $origin_name;
                }
    
                if(isset($offer->collectionid) && $offer->collectionid != null){
                    $collection_url = $OUTPUT->image_url('collections/'.$offer->col_shortname.'_32x32_gris', 'theme')->out();
                    $data_array['collection_url'] = $collection_url;
                    $data_array['collection_name'] = $offer->col_name;
                }
    
                if(isset($offer->validateby) && $offer->validateby != null){
                    $data_array['validate_by'] = $offer->validateby;
                }
    
                if($offer->entree_metier){
                    $data_array['entry_profession'] = 1;
                }
    
                if(isset($offer->tps_en_presence) && $offer->tps_en_presence != null){
                    $data_array['lead_time_attendance'] = OfferCourse::string_format_time($offer->tps_en_presence);
                }
    
                if(isset($offer->tps_a_distance) && $offer->tps_a_distance != null){
                    $data_array['lead_time_remote'] = OfferCourse::string_format_time($offer->tps_a_distance);
                }
    
                if (OfferCourse::isCentralizedRessourcesAvailable())
                {
                    if(isset($offer->videoid) && $offer->videoid != null){
                        $ressource = get_centralized_db_connection()->get_record('cr_resources', array('resourceid' => $offer->videoid));
                        // $video = multimedia_jwplayer_video($ressource->id);
                        try {
                            
                            require_once($CFG->dirroot."/mod/centralizedresources/lib/CentralizedMedia.php");
                            require_once($CFG->dirroot.'/mod/centralizedresources/lib/cr_lib.php');
                            $media = new CentralizedMedia($ressource);
                            // $media->initJS($PAGE);
        
                            $data_array['video'] = $media->getHTML();
                            $data_array['video'] .= html_writer::tag('script', $media->get_raw_js());
                        } catch(Exception $e) {
                            $data_array['video'] = 'Ressource corrompue ou introuvable';
                        }
                    }
                }
            }

            if($is_formation){
                $data_array['inscription_url'] = $offer->course_url;
                if (OfferCourse::isIndexationAvailable()){
                    $data_array['rythme_formation'] = $offer->rythme_formation;
                    $data_array['keywords'] = $offer->keywords;
                }

                if(isset($offer->startdate) && $offer->startdate != null && $offer->startdate != 0){
                    $data_array['start_date_formation'] = date('d/m/Y', $offer->startdate);
                }

                if(isset($offer->enddate) && $offer->enddate != null && $offer->enddate != 0){
                    $data_array['end_date_formation'] = date('d/m/Y', $offer->enddate);
                }
            } else {
                $data_array['demo_url'] = $offer->course_demourl;
                $data_array['time_published'] = date('d/m/Y', $offer->timepublished);
                
                if(OfferCourse::isIndexationAvailable()){
                    $data_notes = null;
                    if($offer->notes){
                        $notes = explode('{{{}}}', $offer->notes);
                        $data_notes = array();
                        foreach ($notes as $note){
                            $note_array = explode('[[[]]]', $note);
                            $note_obj =  new stdClass();
                            if($note_array[3]){
                                $note_obj->id = $note_array[0];
                                $note_obj->version = $note_array[1];
                                $note_obj->timemodified = date("d/m/Y", $note_array[2]);
                                $note_obj->note = nl2br($note_array[3]);
                                $data_notes[] = $note_obj;
                            }
                        }
                    }

                    $data_array['notes'] = $data_notes;
                    $data_array['matricule'] = $offer->matricule;
                    $data_array['course_shortname'] = $offer->course_shortname;
                }

                if (isset($CFG->academie_name)){
                    $params = array('hubcourseid' => $offer->publishid);
                    $mainaca = user_get_mainacademy() ;
                    $magistere_academy = get_magistere_academy_config();
                    require_once($CFG->dirroot."/local/magisterelib/frontalFeatures.php");
                    if(isloggedin() && $mainaca != false
                        && FrontalFeatures::has_capability($mainaca, 'moodle/restore:restorecourse', context_system::instance()->id)
                        && FrontalFeatures::has_capability($mainaca, 'local/coursehub:restore', context_system::instance()->id)){
                        $restore_url = $CFG->magistere_domaine.$magistere_academy[$mainaca]['accessdir'];
                        $restore_url = new moodle_url($restore_url."/local/coursehub/restore.php", $params);
                        $data_array['restore_url'] = $restore_url->out(false);
                    }else if(OfferCourse::user_has_specific_loggedin('efe')){
                    	$restore_url = new moodle_url($CFG->magistere_domaine.$magistere_academy['efe']['accessdir']."/local/coursehub/restore.php", $params);
                    	$data_array['restore_url'] = $restore_url->out(false);
                    }else if(OfferCourse::user_has_specific_loggedin('dne-foad')){
                        $restore_url = new moodle_url($CFG->magistere_domaine.$magistere_academy['dne-foad']['accessdir']."/local/coursehub/restore.php", $params);
                        $data_array['restore_url'] = $restore_url->out(false);
                    }
                }

                if (OfferCourse::isIndexationAvailable()) {
                    $data_array['support'] = $offer->accompagnement;
                    $stats = OfferCourse::get_stats_index_course($offer);
                    $data_array['stats_course_number'] = $stats->course_number;
                    $data_array['stats_archived_sessions_number'] = $stats->archived_sessions_number;
                    $data_array['stats_active_sessions_number'] = $stats->active_sessions_number;
                }
            }
        }
        
        return json_encode($data_array);
    }
}