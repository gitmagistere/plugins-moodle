<?php

/**
 * Moodle Magistere_offer local plugin
 * This file generate a PDF for the offer of the given course and save it as a file
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once($CFG->libdir.'/tcpdf/tcpdf.php');
require_once('lib.php');

$id = optional_param('id', '', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Description parcours');
$PAGE->set_heading('Description parcours');
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/magistere_offers/downloadpdf.php',array('id'=>$id));

$course = OfferCourse::get_hub_course_offer($id);

// Génération du html
$html  = '<h1>';
$html .= $course->fullname;
$html .= '</h1><h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Descriptif</h2>';
$html .= '<div style="padding:10px; background-color:#ECF5F5;">'.$course->summary.'</div>';
$html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Objectifs visés</h2>';
$html .= '<div style="padding:10px; background-color:#ECF5F5;">'.$course->objectif.'</div>';

$tpsadistanceexist = ($course->tps_a_distance != '' && $course->tps_a_distance != '0');
$tpsenpresenceexist = ($course->tps_en_presence != '' && $course->tps_en_presence != '0');

$miseenoeuvre = '';

if($tpsadistanceexist){
    $tps_a_distance_min = (int) $course->tps_a_distance % 60;
    $tps_a_distance_hour = (int) floor($course->tps_a_distance / 60);

    if($tps_a_distance_hour != 0){
        $miseenoeuvre .= $tps_a_distance_hour.' heures';
    }

    if($tps_a_distance_hour != 0 && $tps_a_distance_min != 0){
        $miseenoeuvre .= ' et ';
    }

    if($tps_a_distance_min != 0){
        $miseenoeuvre .= $tps_a_distance_min.' minutes';
    }

    if($tps_a_distance_hour != 0 || $tps_a_distance_min != 0){
        $miseenoeuvre .= ' à distance<br><br>';
    }

} else {
    $miseenoeuvre .= 'Aucun temps à distance requis<br/>';
}

if($tpsenpresenceexist){
    $tps_en_presence_min = (int) $course->tps_en_presence % 60;
    $tps_en_presence_hour = (int) floor($course->tps_en_presence / 60);

    if($tps_en_presence_hour != 0){
        $miseenoeuvre .= $tps_en_presence_hour.' heures';
    }

    if($tps_en_presence_hour != 0 && $tps_en_presence_min != 0){
        $miseenoeuvre .= ' et ';
    }

    if($tps_en_presence_min != 0){
        $miseenoeuvre .= $tps_en_presence_min.' minutes';
    }

    if($tps_en_presence_hour != 0 || $tps_en_presence_min != 0){
        $miseenoeuvre .= ' en présence';
    }

} else {
    $miseenoeuvre .= 'Aucun temps de pr&eacute;sence requis';
}

if(!empty($miseenoeuvre)){
    $html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Mise en oeuvre</h2>';

    $html .= '<div style="padding:10px; background-color:#ECF5F5;">';
    $html .= $miseenoeuvre;
    $html .= '</div>';
}

$html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Collection</h2>';
$html .= '<div style="padding:10px; background-color:#ECF5F5;">'.$course->col_name.'</div>';

$html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Public cible</h2>';
$html .= '<div style="padding:10px; background-color:#ECF5F5;">'. $course->publics. '</div>';


/* Origine */
$html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Origine</h2>';
$html .= '<div style="padding:10px; background-color:#ECF5F5;">'.OfferCourse::string_format_origine_offers($course->aca_name).'</div>';

if(!empty($course->authors)){
    $html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Auteurs</h2>';
    $html .= '<div style="padding:10px; background-color:#ECF5F5;">'.nl2br($course->authors).'</div>';
}

if(!empty($course->validateby)){
    $html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Validé par</h2>';
    $html .= '<div style="padding:10px; background-color:#ECF5F5;">'.$course->validateby.'</div>';
}

if(!empty($course->accompagnement)){
    $html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Accompagnement</h2>';
    $html .= '<div style="padding:10px; background-color:#ECF5F5;">'.$course->accompagnement.'</div>';
}

$html .= '<h2 style="background-color: #978579; padding: 10px 0 10px 10px; color:#FFF; margin:20px 0 0">Date de dernière mise à jour</h2>';
$html .= '<div style="padding:10px; background-color:#ECF5F5;">'.date('d/m/Y',$course->updatedate).'</div>';

//echo $html;

// Génération du pdf
// Nouveau document pdf
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Paramètres du document
$pdf->SetCreator("M@gistère");
$pdf->SetAuthor("M@gistère");
$pdf->SetTitle($course->fullname);
$pdf->SetSubject($course->objectif);
//$pdf->SetKeywords("")

// Paramétrage de la police
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetFont('helvetica', '', 10);

// Paramétrage des données d'entete
$pdf->setHeaderData('', PDF_HEADER_LOGO_WIDTH, $course->fullname, "M@gistère");
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

// Paramétrage des marges
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Paramétrage de la langue
//$pdf->setLanguageArray($language)

// Autres paramétrages
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);


/* Traitement pdf */
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->lastPage();

$filename = str_replace(' ', '_', $course->fullname);
$filename = "pdf_" . $filename . ".pdf";

$pdf->Output($filename, 'D');
/*Fin du traitement PDF*/


