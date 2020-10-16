<?php

/**
 * This page generate the HTML code that while be used to create the cover of the PDF catalog 
 *
 * @package    local_magistere_offers
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once('../lib.php');

$PAGE->set_context(context_system::instance());

$filter = optional_param('filter', '', PARAM_RAW);
$tab = optional_param('tab', '', PARAM_ALPHA);

$filters = unserialize(base64_decode($filter));

if($tab == 'formation'){
    $title = "Catalogue des offres de formation";
} else {
    $title = "Catalogue des offres de parcours";
}

$critere = "";

if($filters != null){
    $DBC = get_centralized_db_connection();

    $domains = $DBC->get_records('local_indexation_domains');
    $publics = $DBC->get_records('local_indexation_publics');

    if($tab == 'formation'){
        $origins = OfferCourse::get_formation_indexation_origins();
    } else {
        $origins = OfferCourse::get_course_indexation_origins();
    }

    foreach($filters as $key => $filter){
        if($key == 'domains'){
            $critere .= "Domaine : ";
            foreach($filter as $key => $domain){
                $critere .= $domains[$key]->name;
            }
            $critere .= "</br>";
        }

        if($key == 'publics'){
            $critere .= "Public(s) : ";
            foreach($filter as $key => $public){
                $critere .= $publics[$key]->name. ", ";
            }
            $critere .= "</br>";
        }

        if($key == 'natures'){
            $critere .= "Nature(s) : ";
            foreach($filter as $key => $nature){
                $critere .= get_string($key, 'local_magistere_offers') . ", ";
            }
            $critere .= "</br>";
        }

        if($key == 'origins'){
            $critere .= "Origine(s) : ";
            foreach($filter as $key => $origin){
                if($tab == 'formation'){
                    foreach ($origins as $org){
                        if($org->shortname == $key){
                            $critere .= $org->name.", ";
                        }
                    }
                } else {
                    $critere .= $origins[$key].", ";
                }
            }
            $critere .= "</br>";
        }
        if($key == 'durations'){
            $critere .= "Durée(s) : ";
            foreach($filter as $key => $duree){
                $critere .= get_string('duration_'.$key, 'local_magistere_offers').", ";
            }
        }
    }
    if($critere != ''){
        $critere = "<h2>Critère(s) : </h2>" . $critere;
    }
}


echo '
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>'.$title.'</title>
        <link rel="stylesheet" href="'.$CFG->wwwroot."/local/magistere_offers/pdfCatalog/pdfCatalog.css".'">
    </head>
    <body class="cover">
        <div class="icon-header"><img src="'.$OUTPUT->image_url('offers/cover_catalog_pdf', 'theme').'"></div>
        <div class="icon-magistere"><img src="'.$OUTPUT->image_url('general/magistere_logo_trans', 'theme').'"></div>
        <div class="content">
            <h1>'.$title.'</h1>
            <div class="critere">'. $critere .'</div>
        </div>
        <div class="date">
            <h2>'.date('d/m/Y').'</h2>
        </div>
        <div class="footer">
            <img class="icon-men" src="'.$OUTPUT->image_url('general/logo_ministere_education_nationale', 'theme').'">
            <span class="product-by">Produit par la Direction du Numérique pour l\'Éducation</span>
            <span class="licence">
                <a href="https://creativecommons.org/licenses/by-nc-sa/2.0/deed.fr">
                    <span class="cc-license-icons">
                        <img class="cc-logo" src="'.$OUTPUT->image_url('offers/cc_icon_black_x2', 'theme').'">
                        <img class="cc-attribution" src="'.$OUTPUT->image_url('offers/attribution_icon_black_x2', 'theme').'">
                        <img class="cc-icon-nc" src="'.$OUTPUT->image_url('offers/nc_black_x2', 'theme').'">
                        <img class="cc-icon-sa" src="'.$OUTPUT->image_url('offers/sa_black_x2', 'theme').'">
                    </span>
                </a> 
            </span>
        </div>    
    </body>
</html>';