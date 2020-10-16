<?php

/**
 * This page generate the HTML code that while be used to create each pages of the PDF catalog
 *
 * @package    local_magistere_offers
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

error_reporting(-1);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
require_once('../lib.php');

$filter = optional_param('filter', '', PARAM_RAW);
$tab = optional_param('tab', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('magistere_offers');


$link_font_1 = $CFG->magistere_domaine.$PAGE->theme->post_process('[[font:theme|fa-solid-900.eot]]');
$link_font_2 = $CFG->magistere_domaine.$PAGE->theme->post_process('[[font:theme|fa-solid-900.eot?#iefix]]');
$link_font_3 = $CFG->magistere_domaine.$PAGE->theme->post_process('[[font:theme|fa-solid-900.woff2]]');
$link_font_4 = $CFG->magistere_domaine.$PAGE->theme->post_process('[[font:theme|fa-solid-900.woff]]');
$link_font_5 = $CFG->magistere_domaine.$PAGE->theme->post_process('[[font:theme|fa-solid-900.ttf]]');
$link_font_6 = $CFG->magistere_domaine.$PAGE->theme->post_process('[[font:theme|fa-solid-900.svg#fontawesome]]');

setlocale(LC_TIME, 'fr_FR.utf8','fra');

$filters = unserialize(base64_decode($filter));

$offerCourse = new OfferCourse($filters, $tab, $userid);
$offers = $offerCourse->get_all_course_offers();


if($offers){
    $offers = sort_by_domain($offers);

    $DBC = get_centralized_db_connection();
    $domains = $DBC->get_records('local_indexation_domains', null, 'name');

    $domainid = '';
    $first = false;
    $content = '';
    $domain_name = '';
    $i = 0;
    foreach ($offers as $offer) {
        if($i == 0){
            $domainid = $offer->domainid;
            $first = true;
        } else {
            $first = false;
        }

        $header = '<header>';
        if($domainid != $offer->domainid || $first == true){
            $domainid = $offer->domainid;
            if($domainid != null){
                $domain_name = $domains[$offer->domainid]->name;
            } else {
                $domain_name = "Parcours sans domaine";
            }
            $header .= '<h1 class="header">'.$domain_name.'</h1>';
        } else {
            $header .= '<span class="header">'.$domain_name.'</span>';
        }

        $header .= '<div class="title"><h2>'.$offer->fullname.'</h2></div>';

        $img = '&nbsp;';
        if(isset($offer->origin_shortname) && $offer->origin_shortname != null) {
            if ($offer->origin_shortname == "academie") {
                $origin_name = OfferCourse::string_format_origine_offers($offer->aca_uri);
                $origin_pix_url = $OUTPUT->image_url('origins/'. strtoupper($offer->aca_uri), 'theme');
            } else {
                $origin_name = OfferCourse::string_format_origine_offers($offer->origin_shortname);
                $origin_pix_url = $OUTPUT->image_url('origins/'. strtoupper($offer->origin_shortname), 'theme');
            }
            if ($offer->origin_shortname != "autre") {
                $img = '<img width="100%" align="right" src="'.$origin_pix_url.'">';
            }
        }

        $extra_class = '';
        if($offer->origin_shortname == "reseau-canope"){
            $extra_class = "reseau-canope";
        }

        $header .= '<div class="title-img '.$extra_class.'">'.$img.'</div>';
        $header .= '</header>';

        $collection = '';
        if($offer->col_shortname){
            $logo_collection = $OUTPUT->image_url('collections/'. $offer->col_shortname.'_48x48', 'theme');
            $collection = '<div class="collection"><img width="auto" src="'.$logo_collection.'"><span>'.$offer->col_name.'</span></div>';
        }

        $left_column = '<div class="left-column">';
        if($offer->objectif){
            $img = '<i class="fa fa-bullseye fa-2x" aria-hidden="true"></i>';
            $title_objectifs = '<span class="title">Objectifs visés</span>';
            $header_objectifs = '<div class="header">'.$img.$title_objectifs.'</div>';
            $content_objectifs = '<div class="content">'.format_text_pdf($offer->objectif).'</div>';
            $left_column .= '<div class="objectifs">'.$header_objectifs.$content_objectifs.'</div>';
        }

        if($offer->publics){
            $img = '<i class="fa fa-users fa-2x" aria-hidden="true"></i>';
            $title_publics = '<span class="title">Public cible</span>';
            $header_publics = '<div class="header">'.$img.$title_publics.'</div>';
            $content_publics = '<div class="content">'.$offer->publics.'</div>';
            $left_column .= '<div class="publics">'.$header_publics.$content_publics.'</div>';
        }

        $extra_class = '';
        if((isset($offer->tps_en_presence) && $offer->tps_en_presence != null)
            ||(isset($offer->tps_a_distance) && $offer->tps_a_distance != null)){
            $img = '<i class="fa fa-clock fa-2x" aria-hidden="true"></i>';
            $title_mise_oeuvre = '<span class="title">Mise en oeuvre</span>';
            $content_mise_oeuvre = '';

            if(isset($offer->tps_a_distance) && $offer->tps_a_distance != null){
                if(isset($offer->tps_en_presence) && $offer->tps_en_presence != null){
                    $extra_class = "column";
                }
                $title_tps_a_distance = '<span class="title">A distance</span>';
                $content_tps_a_distance = '<div class="content">'.OfferCourse::string_format_time($offer->tps_a_distance).'</div>';
                $content_mise_oeuvre .= '<div class="tps-a-distance '.$extra_class.'">'.$title_tps_a_distance.$content_tps_a_distance.'</div>';
            }
            if(isset($offer->tps_en_presence) && $offer->tps_en_presence != null){
                if(isset($offer->tps_a_distance) && $offer->tps_a_distance != null){
                    $extra_class = "column";
                }
                $title_tps_en_presence = '<span class="title">En présence</span>';
                $content_tps_en_presence = '<div class="content">'.OfferCourse::string_format_time($offer->tps_en_presence).'</div>';
                $content_mise_oeuvre .= '<div class="tps-en-presence '.$extra_class.'">'.$title_tps_en_presence.$content_tps_en_presence.'</div>';
            }

            $header_mise_oeuvre = '<div class="header">'.$img.$title_mise_oeuvre.'</div>';
            $left_column .= '<div class="mise-oeuvre">'.$header_mise_oeuvre.$content_mise_oeuvre.'</div>';
        }


        $left_column .= '</div>';

        $right_column = '<div class="right-column">';
        if($offer->summary){
            $img = '<i class="fa fa-search-plus fa-2x" aria-hidden="true"></i>';
            $title_description = '<span class="title">Descriptif</span>';
            $header_description = '<div class="header">'.$img.$title_description.'</div>';
            $content_description = '<div class="content">'.format_text_pdf($offer->summary).'</div>';
            $right_column .= '<div class="description">'.$header_description.$content_description.'</div>';
        }

        if($offer->accompagnement){
            $img = '<i class="fa fa-comments fa-2x" aria-hidden="true"></i>';
            $title_accompagnement = '<span class="title">Accompagnement</span>';
            $header_accompagnement = '<div class="header">'.$img.$title_accompagnement.'</div>';
            $content_accompagnement = '<div class="content">'.format_text_pdf($offer->accompagnement).'</div>';
            $right_column .= '<div class="accompagnement">'.$header_accompagnement.$content_accompagnement.'</div>';
        }

        $extra_class = '';
        if($offer->origin_shortname){
            if ($offer->origin_shortname == "academie") {
                $origin_name = OfferCourse::string_format_origine_offers($offer->aca_uri);
            } else {
                $origin_name = OfferCourse::string_format_origine_offers($offer->origin_shortname);
            }
            if($offer->updatedate && $offer->updatedate != null){
                $extra_class = "column";
            }
            $img = '<i class="fa fa-at fa-2x" aria-hidden="true"></i>';
            $title_origine = '<span class="title">Origine</span>';
            $header_origine = '<div class="header">'.$img.$title_origine.'</div>';
            $content_origine = '<div class="content">'.$origin_name.'</div>';
            $right_column .= '<div class="origine '.$extra_class.'">'.$header_origine.$content_origine.'</div>';
        }

        $extra_class = '';
        if($offer->updatedate){
            if($offer->origin_shortname && $offer->origin_shortname != null){
                $extra_class = "column";
            }
            $title_updatedate = '<span class="title">Dernière mise à jour</span>';
            $header_updatedate = '<div class="header">'.$title_updatedate.'</div>';
            $content_updatedate = '<div class="content">le '.strftime("%d %B %Y", $offer->updatedate).'</div>';
            $right_column .= '<div class="updatedate '.$extra_class.'">'.$header_updatedate.$content_updatedate.'</div>';
        }

        $extra_class = '';
        if($offer->authors){
            if($offer->validateby && $offer->validateby != null){
                $extra_class = "column";
            }
            $title_authors = '<span class="title">Auteurs</span>';
            $header_authors = '<div class="header">'.$title_authors.'</div>';
            $content_authors = '<div class="content">'.format_text_pdf($offer->authors).'</div>';
            $right_column .= '<div class="authors '.$extra_class.'">'.$header_authors.$content_authors.'</div>';
        }

        $extra_class = '';
        if($offer->validateby){
            if($offer->authors && $offer->authors != null){
                $extra_class = "column";
            }
            $title_validateby = '<span class="title">Validé par</span>';
            $header_validateby = '<div class="header">'.$title_validateby.'</div>';
            $content_validateby = '<div class="content">'.format_text_pdf($offer->validateby).'</div>';
            $right_column .= '<div class="validateby '.$extra_class.'">'.$header_validateby.$content_validateby.'</div>';
        }

        $right_column .= '</div>';

        $content .= $header.$collection.$left_column.$right_column;
        $content .= '<div style="page-break-before: always;"></div>';
        $i++;
    }

    echo '
<!DOCTYPE HTML>
<html>
    <head>
        <title>Catalogue</title>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="'.$CFG->wwwroot."/local/magistere_offers/pdfCatalog/pdfCatalog.css".'">       

        <style>
            @font-face {
                  font-family: "Font Awesome 5 Free";
                  font-style: normal;
                 
                  src: url("'.$CFG->wwwroot."/filter/fontawesome/fonts/fa-solid-900.ttf".'") format("truetype");
}
        </style>

    </head>
    <body>
        '.$content.'        
    </body>
</html>';

}


function cmp_title($a, $b){
    return strcmp($a->fullname, $b->fullname);
}

/**
 * Sort the courses by domain
 * @param array $array_data
 * @return array|boolean Return the sorted array or return false if the input data is no valid.
 */
function sort_by_domain($array_data){
    $DBC = get_centralized_db_connection();

    if($array_data){
        $domains = $DBC->get_records('local_indexation_domains', null, 'name');
        $data_sorted = array();
        $datawithoutdomain = array();
        foreach($domains as $domain){
            $temporary_array = array();
            foreach($array_data as $data){
                if($data->domainid == $domain->id){
                    $temporary_array[$data->fakeid] = $data;
                }
                if($data->domainid == null){
                    $datawithoutdomain[$data->fakeid] = $data;
                }
            }
            if(count($temporary_array) != 0){
                $data_sorted = $data_sorted + $temporary_array;
            }
        }
        $data_sorted = $data_sorted + $datawithoutdomain;
        return $data_sorted;
    }
    return false;
}

/**
 * Remove the tags for the given text and return it
 * @param string $text Text to clean
 * @return string Cleaned text
 */
function format_text_pdf($text){
    $list = array("<h1","<h2","<h3","<h4","<h5");
    $text = str_replace($list, "<p", $text);

    $list = array("</h1>","</h2>","</h3>","</h4>","</h5>");
    $text = str_replace($list, "</p>", $text);

    $text = strip_tags($text, '<p><a><b><h1><h2><h3><h4><h5><b><ul><ol><li><br><i><span>');

    return $text;
}