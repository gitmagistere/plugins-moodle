<?php

/**
 * This file while generate the M@gistere's catalog as a PDF file and send it to the user
 *
 *
 * @package    local_magistere_offers
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');;
require_once('../lib.php');

error_reporting(-1);
//ini_set("display_errors", 0);

$generate = optional_param('gen', false, PARAM_BOOL);
$dlg = optional_param('dlg', 0, PARAM_INT);
$filter = optional_param('filter', '', PARAM_RAW);
$tab = optional_param('tab', '', PARAM_ALPHA);
$offers_count = optional_param('offers_count', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Catalogue parcours');
$PAGE->set_heading('Catalogue parcours');
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/magistere_offers/pdfCatalog/pdfCatalog.php',array('filter'=>$filter, 'tab'=>$tab, 'offers_count'=>$offers_count, 'userid'=>$userid));

if ($dlg > 0)
{
    
    $filename = "pdf_catalog_" . date('Y-m-d');
    $filepath = $CFG->tempdir.'/test_'.$dlg.'.pdf';
    if (file_exists($filepath))
    {
        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header('Content-Disposition: attachment; filename="'.$filename.'.pdf"');
        
        readfile($filepath);
        unlink($filepath);
    }else{
        echo 'File not found';
    }
    exit(0);
}


$filename = "pdf_catalog_" . date('Y-m-d');
$tmpfileid = time().rand(1,99999);
$tmpfile = 'test_'.$tmpfileid.'.pdf';
$tmp_pdf = $CFG->tempdir.'/'.$tmpfile;

$toc = "";
if($offers_count > 8){
    $toc = 'toc --xsl-style-sheet '.$CFG->dirroot.'/local/magistere_offers/pdfCatalog/toc.xsl';
}

$cmd = $CFG->wkhtmltopdf_path.' --encoding "utf-8" --header-html '.$CFG->dirroot.'/local/magistere_offers/pdfCatalog/pdfCatalogHeader.html --footer-html '.$CFG->dirroot.'/local/magistere_offers/pdfCatalog/pdfCatalogFooter.html cover "'.$CFG->wwwroot.'/local/magistere_offers/pdfCatalog/pdfCatalogCover.php?tab='.$tab.'&filter='.$filter.'" '.$toc.' "'.$CFG->wwwroot.'/local/magistere_offers/pdfCatalog/pdfCatalogSources.php?tab='.$tab.'&filter='.$filter.'&userid='.$userid.'" '.$tmp_pdf;
//echo $cmd;

$exec_output = array();
$exec_return = 0;
//exec($cmd,$exec_output,$exec_return);
exec($cmd,$exec_output);

if ($generate)
{
    $error = true;
    if (file_exists($tmp_pdf) && filesize($tmp_pdf) > 1000)
    {
        $error = false;
    }
    $url = new moodle_url('/local/magistere_offers/pdfCatalog/pdfCatalog.php',array('dlg'=>$tmpfileid));
    
    echo '{"url":"'.$url.'","error":"'.($error?'true':'false').'"}';
    exit(0);
}

/*
echo '<pre>';
print_r($exec_output);
print_r($exec_return);
die;
*/
if ($exec_return == 0)
{
    header("Content-Description: File Transfer");
    header("Content-Type: application/octet-stream");
    header('Content-Disposition: attachment; filename="'.$filename.'.pdf"');

    readfile($tmp_pdf);
    unlink($tmp_pdf);
}
else
{
    echo 'Error';
}
