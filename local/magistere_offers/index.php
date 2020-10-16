<?php

/**
 * Moodle Magistere_offer local plugin
 * This file is the main acces to the plugin, it display either the course offer or the formation offer
 * 
 *
 * @package    local_magistere_offers
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filelib.php');

require_once($CFG->dirroot.'/local/magistere_offers/lib.php');
require_once($CFG->dirroot.'/local/magistere_offers/form/course_form.php');
require_once($CFG->dirroot.'/local/magistere_offers/form/formation_form.php');
require_once($CFG->dirroot.'/local/magistere_offers/form/publics_form.php');

$display = optional_param('v', "course", PARAM_ALPHA);  // View
$search_result = optional_param('search_name', "", PARAM_TEXT);

//require_login();

$PAGE->set_url('/local/magistere_offers/index.php', ['v' => $display]);
$PAGE->set_course($SITE);
$PAGE->navbar->add('Offre de parcours');
// Prevent caching of this page to stop confusion when changing page after making AJAX changes
$PAGE->set_cacheable(false);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');

if (!isloggedin() && ((isset($_SERVER['HTTP_SHIB_APPLICATION_ID']) && !empty($_SERVER['HTTP_SHIB_APPLICATION_ID']))
        || (isset($_SERVER['Shib-Application-ID']) && !empty($_SERVER['Shib-Application-ID']))
        || (isset($_SERVER['HTTP_COOKIE']) && preg_match('/_shibsession_/i', $_SERVER['HTTP_COOKIE'])))){
    mmcached_add('mmid_session_'.get_mmid().'_hub_redirection', qualified_me());
    // On redirige l'utilisateur vers le script de login shibboleth
    redirect($CFG->wwwroot.'/auth/shibboleth/index.php');
}

$PAGE->set_pagetype('site-index');

$PAGE->add_body_class("offerpage");

if ($display == VIEW_FORMATION) {
    $PAGE->add_body_class("offerformation");
} else {
    $PAGE->add_body_class("offercourse");
}

if (OfferCourse::isMag()){
    $PAGE->set_pagelayout('magistere_offers');
}else{
    $PAGE->set_pagelayout('base');
}

$PAGE->blocks->show_only_fake_blocks();
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->requires->js("/local/magistere_offers/js/easyPaginate.js/js/jquery.easyPaginate.js");
$PAGE->requires->js_call_amd("local_magistere_offers/offers", "validateform");
$PAGE->requires->js_call_amd("local_magistere_offers/modal_offer", "init");
$PAGE->requires->js_call_amd("local_magistere_offers/export_pdf", "init");
$PAGE->requires->css("/local/magistere_offers/styles/jquery.loadingModal.min.css");

$regions = $PAGE->blocks->get_regions();
$firstregion = reset($regions);

switch ($display) {
    case VIEW_COURSE:
    default:
        if (OfferCourse::isIndexationAvailable()){
            $publics = new PublicsByFunctionUser($USER->id, $display);
            $publics->set_page($PAGE->bodyclasses);
            $form_publics = new publics_form(null);
            if($form_publics->get_data()){
                $data = $form_publics->get_data();
                $publics->set_course_first_connection(true);
                if(isset($data->publics_fav)){
                    $publics->set_favorite_course_publics(array_keys($data->publics_fav));
                } else {
                    $publics->set_favorite_course_publics("");
                }
                if(isset($data->get_notif)){
                    $publics->set_course_notification($data->get_notif);
                } else {
                    $publics->set_course_notification(0);
                }
            }
            $publics = new PublicsByFunctionUser($USER->id, $display);
            $courseForm = new course_form(null, array('v'=>$display, 'publics'=>$publics->prepare_publics_for_checkboxes_form()));
        }else{
            $courseForm = new course_form(null, array('v'=>$display));
        }

    if (OfferCourse::isMag()){
        $bc = new block_contents();
        $bc->title = null;
        $bc->content = $courseForm->render();

        $PAGE->blocks->add_fake_block($bc, $firstregion);
        echo $OUTPUT->header();
    } else {
        echo $OUTPUT->header();
        echo $courseForm->render();
    }

        echo html_writer::div(
            html_writer::tag('i','',['class' => 'fa fa-search icon']).
            html_writer::tag('input',null, ['type'=>'text',
                'id'=>'search-input',
                'placeholder' => get_string('course_search', 'local_magistere_offers')]),
            'search course');

        $filters = null;
        if($courseForm->get_data()){
            $filters = $courseForm->get_data();
        }
        $offerCourse = new OfferCourse($filters, 'course', $USER->id);
        echo $offerCourse->get_course_offers();

        if (isset($CFG->academie_name)){
            if(($USER && $USER->id > 0)
            || OfferCourse::user_has_specific_loggedin('efe')
            || OfferCourse::user_has_specific_loggedin('dne-foad')){
                echo $offerCourse->dialog_restore_course();
            }
        }
        break;
		
    case VIEW_FORMATION:
        if (OfferCourse::isIndexationAvailable()){
            $publics = new PublicsByFunctionUser($USER->id, $display);
            $publics->set_page($PAGE->bodyclasses);
            $form_publics = new publics_form(null);
            if($form_publics->get_data()){
                $data = $form_publics->get_data();
                $publics->set_formation_first_connection(true);
                if(isset($data->publics_fav)){
                    $publics->set_favorite_formation_publics(array_keys($data->publics_fav));
                } else {
                    $publics->set_favorite_formation_publics("");
                }
                if(isset($data->get_notif)){
                    $publics->set_formation_notification($data->get_notif);
                } else {
                    $publics->set_formation_notification(0);
                }
            }
    
            if($USER && $USER->id > 0){
                $publics = new PublicsByFunctionUser($USER->id, $display);
                $formationForm = new formation_form(null, ['v'=>$display,
                    'search_name'=>$search_result,
                    'publics'=>$publics->prepare_publics_for_checkboxes_form()]);
            } else {
                $formationForm = new formation_form(null, ['v'=>$display,
                    'search_name'=>$search_result,
                    'publics'=>null]);
            }
        }else{
            $formationForm = new formation_form(null, ['v'=>$display, 'search_name'=>$search_result]);
        }

        if (OfferCourse::isMag()){
            $bc = new block_contents();
            $bc->title = null;
            $bc->content = $formationForm->render();

            $PAGE->blocks->add_fake_block($bc, $firstregion);
            echo $OUTPUT->header();
        } else {
            echo $OUTPUT->header();
            echo $formationForm->render();
        }

        echo html_writer::div(
            html_writer::tag('i','', ['class' => 'fa fa-search icon']).
            html_writer::tag('input',null, ['type'=>'text',
                'id'=>'search-input',
                'placeholder' => get_string('formation_search', 'local_magistere_offers')]),
            'search');
        $filters = null;
        if($formationForm->get_data()){
            $filters = $formationForm->get_data();
        }
        $offerCourse = new OfferCourse($filters, 'formation', $USER->id);
        echo $offerCourse->get_course_offers();
        break;
}

echo $OUTPUT->footer();