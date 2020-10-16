<?php
/**
 * Moodle Magistere_offer local plugin
 * This is the lib file where all functions are declared
 *
 * @package    local_myindex
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

///////////////
// CONSTANTS //
///////////////

define('VIEW_COURSE', 'course');
define('VIEW_FORMATION', 'formation');
define('CAT_SESSION_LOCALE_AUTOINSCRIPTION', 'Session locale en auto-inscription');
define('CAT_SESSION_FORMATION', 'Session de formation');
define('CAT_PARCOURS_FORMATION', 'Parcours de formation');




/////////////////////////
// CLASS COURSE OFFERS //
/////////////////////////

/**
 * Class OfferCourse. Class qui gère la liste et l'affichage des offres de parcours et de formation.
 */
class OfferCourse{

    private $filters = '';
    private $tab;
    private $userid;
    private $mainaca;
    private $rawdata_local = array();
    private $rawdata_hub = array();
    private $offers = array();
    private $buttons = '';
    private $user_publics;
    private $notification;

    /**
     * OfferCourse constructor.
     * @param null $filters
     * @param $tab
     * @param int $userid
     * @param bool $notification
     */
    function __construct($filters = null, $tab, $userid = 0, $notification = false){
        $this->filters = $filters;
        $this->tab = $tab;
        $this->userid = $userid;
        $this->notification = $notification;
        if (OfferCourse::isMag())
        {
            $this->mainaca = $this->get_mainacademy_user();
            $this->user_publics = $this->get_user_favorite_publics();
            $this->get_hub_course_offers_sql($this->filters, $this->tab, $this->notification);
            $this->merge_course_offers();
        }else{
            $this->loadCourseHub($this->filters, $this->tab);
        }
    }
    
    public static function isIndexationAvailable(){
        return file_exists($GLOBALS['CFG']->dirroot.'/local/indexation/lib.php');
    }
    
    public static function isCentralizedRessourcesAvailable(){
        return file_exists($GLOBALS['CFG']->dirroot.'/local/centralizedresources/version.php');
    }

    public static function isMag(){
        return isset($GLOBALS['CFG']->academie_name);
    }

    protected function loadCourseHub($filter,$page){
        
        $search = '';
        if (isset($filter->search_name)){
            $search = $filter->search_name;
        }
        
        $hub = CourseHub::instance(CourseHub::LOGSMOD_FILE);
        
        $offers = $hub->searchPublishedCourse($search, ($page=='course'?CourseHub::PUBLISH_SHARED:CourseHub::PUBLISH_PUBLISHED));
        
        $this->offers = array();
        
        foreach ($offers AS $value){
            $value->fakeid = $value->id;
            $this->offers[$value->id] = $value;
        }
    }

    /**
     * Fonction permettant d'avoir la liste des offres venant du hub. Selon le contenu de la variable $tab, la liste
     * obtenu sera une liste pour l'offre de parcours ou pour l'offre de formation.
     * @param $indexData
     * @param $tab
     * @param $notification
     */
    protected function get_hub_course_offers_sql($indexData, $tab, $notification){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        $courseHub = CourseHub::instance();
        if((databaseConnection::instance()->get($courseHub->getMasterIdentifiant())) === false){return;}
        $this->rawdata_hub = $this->get_course_offers_sql($courseHub->getMasterIdentifiant(), $indexData, $tab, $notification);
    }

    /**
     * Fonction permettant de fusionner la liste des offres venant du hub et celle venant de la plateforme
     * de rattachement de l'utilisateur. Cas particulier dans cette fonction : la fusion est limite à 8 résultats
     * dans le cas de l'utilisation de cette fonction pour le bloc mycourseoffer.
     */
    protected function merge_course_offers(){
        if(count($this->rawdata_local) != 0 && $this->tab == 'formation'){
            $this->build_data_list($this->rawdata_hub);
            krsort($this->offers);
            // Ajout d'une limit pour le bloc mycourseoffer
            if (isset($this->filters->limit_for_block) && $this->filters->limit_for_block = true){
                if(count($this->offers) != 0 ){
                    $this->offers = array_slice($this->offers, 0, 8);
                }
            }
        } else {
            // Ajout d'une limit pour le bloc mycourseoffer
            if (isset($this->filters->limit_for_block)
                && $this->filters->limit_for_block = true
                    && $this->tab == 'formation'){
                $this->build_data_list($this->rawdata_hub);
                krsort($this->offers);
                if(count($this->offers) != 0 ){
                    $this->offers = array_slice($this->offers, 0, 8);
                }
            } else {
                $this->build_data_list($this->rawdata_hub);
                krsort($this->offers);
            }
        }
    }

    /**
     * Fonction qui créé une clé de liste spécifique pour chaque offre. Cette clé est générée en amond de la fusion
     * des listes des offres venant du hub et de la plateforme de rattachement de l'utilisateur. Cette clé
     * a une structure différent pour l'offre de parcours et pour l'offre de formation ainsi que lorsque une recherche
     * sur les publics a été effectué.
     * @param $list
     */
    protected function build_data_list($list){
        foreach($list as $ind){
            $key = '';
            $filters = $this->filters;
            if(isset($filters->publics)){
                $key .= (isset($ind->public_match)?$ind->public_match:'0');
                $key .= (isset($ind->nbpublic)?'_'.$ind->nbpublic:'_0');
            }
            if($this->tab == 'formation'){
                $key .= (isset($ind->startdate)?'_'.date('YmdHis',$ind->startdate):'_0');
                $key .= (isset($ind->enrolstartdate)?'_'.date('YmdHis',$ind->enrolstartdate):'_0');
                $key .= (isset($ind->timepublished)?'_'.date('YmdHis',$ind->timepublished):date('YmdHis',$ind->updatedate));
            } else {
                $key .= '0_0';
                $key .= (isset($ind->timepublished)?'_'.date('YmdHis',$ind->timepublished):date('YmdHis',$ind->updatedate));
            }
            $this->offers[$key] = $ind;
        }
    }

    /**
     * Fonction permettant de récupérer la liste complète des offres après fusion. Cette liste est ensuite utilisée
     * pour les offres de parcours et de formation, les exportations PDF et XML ainsi que sur le bloc mmycourseoffer.
     * @return array
     */
    public function get_all_course_offers(){
        return $this->offers;
    }

    /**
     * Fonction permettant de générer la structure HTML générale de la page de l'offre de parcours ou de formation.
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_course_offers(){
        global $USER;
        if($this->userid != 0){
            $userid = $this->userid;
        } else {
            $userid = $USER->id;
        }
        $publics_favorite = '';
        $publics_modal = '';
        if(OfferCourse::isIndexationAvailable() && $USER->id != 0 && isloggedin()){
            $publics_favorite = html_writer::tag('button',html_writer::tag('i','',array('class' => 'fas fa-pencil-alt icon','style'=>'font-size: 20px;')),array('id' => 'edit', 'data-toggle'=>"modal",'data-target'=>"#publics-modal"));
            $publics = new PublicsByFunctionUser($USER->id, $this->tab);
            $publics_modal = $publics->create_modal("publics-modal");
        }
        $content = "";
        if(count($this->offers) == 0){
            $result = html_writer::div(get_string('result', 'local_magistere_offers', count($this->offers)), 'result');
            $header = html_writer::div($this->buttons.$publics_favorite,'buttons_filter');
            $content .= html_writer::div($result.$header, 'header');

        } else {
            $result = html_writer::div(get_string('result', 'local_magistere_offers', count($this->offers)).$this->generate_export_link($userid), 'result');
            $header = html_writer::div($this->buttons.$publics_favorite,'buttons_filter');
            $content .= html_writer::div($result.$header, 'header');
        }
        $content .= html_writer::div("", 'clear');
        $content .= $this->generate_tiles();
        $content .= html_writer::div(null, 'create-modal', array('id' => 'detailModal'));
        $content .= $publics_modal;
        return $content;
    }

    /**
     * Fonction qui génère la liste déroulante des actions d'exportation possibles sur l'offre de parcours et de formation.
     * @param $userid
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function generate_export_link($userid){
        $pdf_url = new moodle_url('/local/magistere_offers/pdfCatalog/pdfCatalog.php', array('gen' => 'true', 'filter' => base64_encode(serialize($this->filters)), 'tab' => $this->tab, 'offers_count' => count($this->offers), 'userid' => $userid));
        $xml_url = new moodle_url('/local/magistere_offers/xmlCatalog.php', array('filter' => base64_encode(serialize($this->filters)), 'tab' => $this->tab, 'userid' => $userid));
        $pdf_href = html_writer::link($pdf_url, html_writer::tag('i','',array('class'=>'fa fa-file-pdf-o')) . get_string('pdf_catalogue_link', 'local_magistere_offers'), array('class'=> 'dropdown-item pdf-catalog'));
        $xml_href = html_writer::link($xml_url, html_writer::tag('i','',array('class'=>'fa fa-file-export fa-file-code-o')) . get_string('xml_catalogue_link', 'local_magistere_offers'), array('class'=> 'dropdown-item xml-catalog'));

        $carret = "";
        $list_link = $xml_href;
        if (OfferCourse::isMag()) {
            $carret = html_writer::tag('i','',array('class'=>'fa fa-caret-down'));
            $list_link = $pdf_href.$xml_href;
        }

        $menu_href = html_writer::link('', get_string('export_catalogue_link', 'local_magistere_offers').$carret , array('class' => 'btn btn-secondary dropdown-toggle', 'role' => 'button', 'id' => 'dropdownMenuLink', 'data-toggle' => 'dropdown', 'aria-haspopup' => 'true', 'aria-expanded' => 'false'));

        $dropdown_menu = html_writer::div($list_link, 'dropdown-menu', array('aria-labelledby' => 'dropdownMenuLink'));

        return html_writer::div($menu_href.$dropdown_menu, 'export-catalog');
    }

    /**
     * Fonction qui génère le tableau de tuiles en HTML correspondant aux offres disponibles sur l'offre de parcours
     * et de formation.
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function generate_tiles(){
        global $CFG, $OUTPUT;
        $modals = '';
        $script = "
            $(document).ready(function(){      
                $('#pagination-tiles').easyPaginate({
                    paginateElement: 'li',
                    elementsPerPage: 48,
                    firstButtonText: '&lt;&lt;',
                    lastButtonText: '&gt;&gt;',        
                    prevButtonText: '&lt;',        
                    nextButtonText: '&gt;'
                });
            });";
        $grid_items = html_writer::script($script);
        $grid_items .= html_writer::start_tag('ul', array('class' => 'tiles', 'id'=>'pagination-tiles'));

        foreach($this->offers as $offer){
            
            if (isset($offer->domainid)){
                $img = 'offers/' . $offer->domainid . '_domains_2x';
                $resource_link = $OUTPUT->image_url($img, 'theme');
            }
            
            if (OfferCourse::isCentralizedRessourcesAvailable()){
                if(isset($offer->thumbnailid) && $offer->thumbnailid != null){
                    $DBC = get_centralized_db_connection();
                    $cr_resource = $DBC->get_record('cr_resources', array('resourceid'=>$offer->thumbnailid));
                    if ($cr_resource != false)
                    {
                        $url_resource = '/'.$CFG->centralizedresources_media_types['indexthumb'].'/'.$cr_resource->cleanname;
                        $resource_link = get_resource_centralized_secure_url($url_resource,
                            $cr_resource->hashname.$cr_resource->createdate,
                            $CFG->secure_link_timestamp_image);
                    }
                }
            }

            $content = '';
            $grid_items .= html_writer::start_tag('li',
                ['class'=> 'grid__item item-row large--one-quarter post-large--one-third large--one-half medium--one-half']);
            if (isset($resource_link)){
                $content = html_writer::tag('img', '', [
                    'src'=>$resource_link,
                    'width'=>'100%']);
            }
            $content .= html_writer::div($this->string_format_offers_title($offer->fullname), 'title');
            if (OfferCourse::isIndexationAvailable()){
                if($offer->origin_shortname == "academie"){
                    $content .= html_writer::div($this->string_format_origine_offers($offer->aca_uri), 'origin');
                } else {
                    $content .= html_writer::div($this->string_format_origine_offers($offer->origin_shortname), 'origin');
                }
            }
            $content .= html_writer::div(get_string('more', 'local_magistere_offers'), 'more');
            $content = html_writer::div($content, 'content');
            $grid_items .= html_writer::link('#offer='.$offer->fakeid,
                $content,
                ['ref-modal' => 'modalOffer_'.$offer->fakeid,
                    'ref-modal-id' => $offer->fakeid,
                    'ref-bs-element' => 'modal'
                ]);
            $grid_items .= html_writer::end_tag('li');
        }
        $grid_items .= html_writer::end_tag('ul');

        return $grid_items.$modals;
    }

    /**
     * Fonction qui génère la requête SQL nécessaire à la création de la liste des offres de parcours et de formation.
     * Cette fonction permet également de définir quels sont les filtres utilisés par l'utilisateur sur l'offre
     * dans laquelle il se situe.
     * @param $dbconn instance name
     * @param null $indexData Indexation data
     * @param $tab 'formation' or 'course'
     * @param bool $notification
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function get_course_offers_sql($dbconn, $indexData = null, $tab, $notification = false){
        global $CFG;
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        $DBC = get_centralized_db_connection();

        $sql_public_inner = '';
        $sql_public_order = '';
        $sql_public_field = '';
        $sql_params = array();

        // Filtre sur les publics (CUMULABLE)
        if ((isset($indexData->publics) && $indexData->publics != null && $indexData->publics != '')
            || ($indexData == null && $this->user_publics != false)) {
            if ($indexData == null && $this->user_publics != false){
                $publicids = $this->user_publics;
            }
            if (isset($indexData->publics) && $indexData->publics != null && $indexData->publics != '') {
                $publicids = '';
                foreach(array_keys($indexData->publics) AS $publickeys) {
                    if (!empty($publickeys) && strlen($publickeys) > 0) {
                        if (strlen($publicids) > 0) {
                            $publicids .= ',';
                        }
                        $publicids .= $publickeys;
                    }
                }
            }

            if($publicids != ""){
                $sql_public_inner = 'INNER JOIN (
                              SELECT p1.id, p1.nbm, count(*) AS nbp
                              FROM (
                                SELECT lci.id, count(*) AS nbm
                                FROM {'.CourseHub::TABLE_INDEX.'} lci
                                INNER JOIN {'.CourseHub::TABLE_INDEX_PUBLIC.'} lcip ON (lcip.indexationid = lci.id)
                                WHERE lcip.publicid IN (' . $publicids . ')
                                GROUP BY lci.id
                              ) p1
                              INNER JOIN {'.CourseHub::TABLE_INDEX_PUBLIC.'} lcip2 ON (lcip2.indexationid = p1.id)
                              GROUP BY p1.id
                            ) p2 ON (p2.id = lci.id)';
                $sql_public_order = 'p2.nbm DESC, p2.nbp ASC, ';
                $sql_public_field = 'p2.nbm as public_match, p2.nbp as nbpublic,';
            }
        }

        $sql = "SELECT lcc.id as fakeid,
                    lci.id,
                    lci.publishid,
                    lci.objectif,
                    lci.collectionid,
                    lci.tps_a_distance,
                    lci.tps_en_presence,
                    lci.accompagnement,
                    lci.origin,
                    lci.domainid,
                    lci.authors,
                    lci.validateby,
                    lci.updatedate,
                    lci.departementid,
                    lci.originespeid,
                    lci.academyid,
                    lci.contact,
                    lci.entree_metier,
                    lci.year,
                    lci.codeorigineid,
                    lci.title,
                    lci.version,
                    lci.thumbnailid,
                    lci.certificatid,
                    lci.videoid,
                    lci.rythme_formation,
                    lcc.courseid,
                    lcc.name AS fullname,
                    lcc.summary,
                    lcc.coursestartdate,
                    lcc.courseenddate,
                    lcc.deleted,
                    lcc.publish,
                    lcc.shortname as course_shortname,
                    lcc.courseurl as course_url,
                    lcc.courseurl as course_demourl,
                    lcc.timemodified as timepublished,
                    lcs.shortname,
                    IF(lcs.shortname='ac-caen','ac-normandie',lcs.shortname) as aca_uri,
                    IF(lcs.shortname='ac-caen','ac-normandie',lcs.shortname) as aca_name,
                    lcc.maxparticipant,
                    lcc.hasakey,
                    lcc.coursestartdate as startdate,
                    lcc.courseenddate as enddate,
                    lcik.id,
                    c_ind_origins.name as aca_origine,
                    c_ind_origins.shortname as origin_shortname,
                    c_ind_collections.name as col_name,
                    c_ind_collections.shortname as col_shortname,
                    c_ind_domains.name as domain_name,
                    c_ind_certificats.name as certificat_name,
					".$sql_public_field."
					publicjoin.nbpublic,
					publicjoin.publics,
					lcc.enrolstartdate as enrolstartdate,
                    GROUP_CONCAT(DISTINCT lcik.keyword SEPARATOR ', ') as keywords,
                    t_dep.libelle_long as departement
            FROM {".CourseHub::TABLE_INDEX."} as lci 
            INNER JOIN {".CourseHub::TABLE_COURSE."} lcc ON lcc.id = lci.publishid
            INNER JOIN {".CourseHub::TABLE_SLAVE."} lcs ON lcc.slaveid = lcs.id
			".$sql_public_inner."
            LEFT JOIN (SELECT lip.indexationid, GROUP_CONCAT(lips.name SEPARATOR ',') as publics, 
                          COUNT(lips.id) as nbpublic 
						FROM {".CourseHub::TABLE_INDEX_PUBLIC."} lip 
						INNER JOIN ".$CFG->centralized_dbname.".local_indexation_publics lips ON (lips.id = lip.publicid)
						GROUP BY lip.indexationid ) publicjoin ON (publicjoin.indexationid = lci.id)
            LEFT JOIN {".CourseHub::TABLE_INDEX_KEYWORDS."} lcik ON (lcik.indexationid = lci.id)
            LEFT JOIN {t_departement} t_dep ON (lci.departementid = t_dep.id)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_domains c_ind_domains ON (c_ind_domains.id = lci.domainid)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_codes c_ind_codes ON (c_ind_codes.id = lci.codeorigineid)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections c_ind_collections ON (c_ind_collections.id = lci.collectionid)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_origins c_ind_origins ON (c_ind_origins.shortname = lci.origin)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_certificats c_ind_certificats ON (c_ind_certificats.id = lci.certificatid)            
            ";

        $buttons_filter = "";
        $sql_filter = 'WHERE lcc.deleted = 0 '; // Supprimer les doublons venant de hub_course_directory

        // Parcours publié = Parcours, Parcours partagé = Formation
        if($tab == "formation"){
            $sql_filter .= 'AND lcc.publish = '.CourseHub::PUBLISH_PUBLISHED.' ';
            $sql_filter .= 'AND lcc.inscription_method like "%self%" ';
            if($this->userid > 0){
                $sql_filter .= 'AND (lcc.isasession = 1 
                                        OR (lcc.isalocalsession = 1 AND lcs.identifiant = "'.$this->mainaca.'")) ';
            } else {
                $sql_filter .= 'AND (lcc.isasession = 1 AND lcc.isalocalsession = 0) ';
            }

            $sql_filter .= 'AND lcc.enrolrole = "participant" ';

            $currenttime = time();
            $sql_filter .= "AND ((lcc.enrolenddate = 0 AND lcc.enrolstartdate = 0) 
                                OR (".$currenttime." <= lcc.enrolenddate AND lcc.enrolstartdate = 0) 
                                OR (lcc.enrolstartdate <= ".$currenttime." AND ".$currenttime." <= lcc.enrolenddate) 
                                OR (lcc.enrolenddate = 0 AND lcc.enrolstartdate <= ".$currenttime.")) ";
        } else {
            $sql_filter .= 'AND lcc.publish = '.CourseHub::PUBLISH_SHARED.' ';
        }

        if($notification == true){
            // Filtre sur les dates de publication (HUB) ou dates de modification d'indexation (Locaux)
            $sql_filter .= 'AND (lcc.timemodified >= '.strtotime('7 days ago midnight').') ';

        } else {
            // Filtre par nom de parcours, mots-clés et origines/départements. Attendu : chaîne de caractère, plusieurs mots possibles
            if (!empty($indexData->search_name)) {
                $words = array_filter(explode(' ', $indexData->search_name));
                // => #2779 - 08/10/2018
                $apo1 = "'";
                $apo2 = "’";
                $wordsBis = array();
                $length = count($words);
                foreach ($words as $key => $word) {
                    $pos1 = strpos($word, $apo1);
                    $pos2 = strpos($word, $apo2);
                    if ($pos1 !== false) {
                        $wordsBis[$key] = str_replace($apo1, $apo2, $word);
                    } elseif ($pos2 !== false) {
                        $wordsBis[$key] = str_replace($apo2, $apo1, $word);
                    }
                }
                $clauses = array();
                $wi = 0;

                foreach ($words as $i => $word) {
                    $word = '%' . trim($word) . '%';
                    if (strlen($word) >= 3) {
                        $wordindex = 'word' . ($wi++);
                        $sql_params[$wordindex] = strtolower($word);
                        $concat_str = 'LOWER(GROUP_CONCAT(DISTINCT lcik.keyword SEPARATOR \', \')), LOWER(departement), LOWER(lcc.name), LOWER(lci.origin), LOWER(lcs.shortname)';
                        if (isset($wordsBis[$i])) {
                            $wordbisindex = 'wordbi' . ($wi++);
                            $wordsBis[$i] = '%' . trim($wordsBis[$i]) . '%';
                            $sql_params[$wordbisindex] = strtolower($wordsBis[$i]);
                            $clauses[] = '( (SELECT (CONCAT_WS(\' \', '.$concat_str.')) LIKE :'.$wordindex.') OR (SELECT (CONCAT_WS(\' \', '.$concat_str.')) LIKE :'.$wordbisindex.'))';
                        } else {
                            $clauses[] = '( SELECT (CONCAT_WS(\' \', '.$concat_str.')) LIKE :'.$wordindex.')';
                        }
                    }
                }

                if(!empty($clauses)){
                    // on utilise having car on a besoin d'utiliser un group by sur les keywords
                    $sql_having = '(' . implode(' AND ', $clauses) . ')';
                }

            }

            // Filtre sur le domaine (NON CUMULABLE)
            if (isset($indexData->domains) && $indexData->domains != null && $indexData->domains != ''){
                $key = key($indexData->domains);
                $domain = $DBC->get_record('local_indexation_domains', array('id'=>$key));
                $sql_filter .= "AND domainid = " . $key . " ";
                $buttons_filter .= html_writer::tag('button', $domain->name, array('class'=>'button-typeahead domain', 'data-id'=>'id_domains_'.$key));
            }

            // Filtre sur les publics (CUMULABLE)
            if ((isset($indexData->publics) && $indexData->publics != null && $indexData->publics != '')
                || ($indexData == null && $this->user_publics != false)) {
                if ($indexData == null && $this->user_publics != false){
                    $publicids = '('.$this->user_publics.')';
                }
                if (isset($indexData->publics) && $indexData->publics != null && $indexData->publics != '') {
                    $publicids = '(';
                    foreach(array_keys($indexData->publics) AS $publickeys) {
                        if (!empty($publickeys) && strlen($publickeys) > 0) {
                            if (strlen($publicids) > 1) {
                                $publicids .= ',';
                            }
                            $publicids .= $publickeys;
                        }
                    }
                    $publicids .= ')';
                }
                if($publicids != "()"){
                    $buttons_filter .= $this->get_local_indexation_publics_buttons($publicids);
                }
            }

            // Filtre sur la nature (CUMULABLE)
            if (isset($indexData->natures) && $indexData->natures != null && $indexData->natures != ''){
                $i = 0;
                $len = count($indexData->natures);
                $clauses = array();
                $array = "(";
                $trad = "";
                foreach($indexData->natures as $key => $nature) {
                    if($key == 'autoformation'){
                        $clauses[] = 'c_ind_collections.shortname = "autoformation"';
                        $trad = $key;
                    }
                    if($key == 'accompanied_course'){
                        $clauses[] = 'c_ind_collections.shortname IN(
                                                                    SELECT shortname 
                                                                    FROM '.$CFG->centralized_dbname.'.local_indexation_collections 
                                                                    WHERE shortname != "autoformation" 
                                                                        AND shortname != "reseau"
                                                                    )';
                        $trad = $key;
                    }
                    if($key == 'professional_community'){
                        $clauses[] = 'c_ind_collections.shortname = "reseau"';
                        $trad = "professional_community";
                    }
                    $buttons_filter .= html_writer::tag('button',
                        get_string($trad, 'local_magistere_offers'),
                        array('class'=>'button-typeahead nature', 'data-id'=>'id_natures_'.$key));
                }
                foreach($clauses as $clause){
                    if ($i == $len - 1) {
                        $array .= $clause;
                    } else {
                        $array .= $clause. " AND ";
                    }
                    $i++;
                }
                $array .= ")";
                $sql_filter .= "AND " . $array . " ";
            }

            // Filtre sur les origines (CUMULABLE)
            if (isset($indexData->origins) && $indexData->origins != null && $indexData->origins != ''){
                $array = array();
                $array_aca = array();
                $origins = array();
                foreach($indexData->origins as $id => $origin) {
                    if(strpos($id, 'ac-') !== false){
                        if ($id == 'ac-normandie'){$array_aca[] = "'ac-caen'";}
                        $array_aca[] = "'".$id."'";
                    } else {
                        $array[] = "'".$id."'";
                    }
                    $origins[] = $id;
                }

                $sql_filter .= "AND(";
                if(!empty($array)){
                    $sql_filter .= "lci.origin IN (" . implode(",", $array) . ") ";
                }

                if(!empty($array_aca)){
                    if(!empty($array)){
                        $sql_filter .= "OR lcs.shortname IN (" . implode(",", $array_aca) . ")";
                    } else {
                        $sql_filter .= "lcs.shortname IN (" . implode(",", $array_aca) . ")";
                    }
                }
                $sql_filter .= ") ";

                $buttons_filter .= $this->get_local_indexation_origins_buttons($origins, $tab);
            }

            // Filtre sur la Durée (CUMULABLE). Attendu : [0-9]*-[0-9]* (premiere valeur = mini, derniere = maxi) plusieurs fois
            if (isset($indexData->durations) && $indexData->durations != null && $indexData->durations != ''){

                $durations = array();
                foreach($indexData->durations as $key => $duration){
                    if($key == 1){$durations[]='-3';$buttons_filter .= html_writer::tag('button', '- de 3h', array('class'=>'button-typeahead duration', 'data-id'=>'id_durations_'.$key));}
                    if($key == 2){$durations[]='3-6';$buttons_filter .= html_writer::tag('button', 'Entre 3 et 6h', array('class'=>'button-typeahead duration', 'data-id'=>'id_durations_'.$key));}
                    if($key == 3){$durations[]='6-9';$buttons_filter .= html_writer::tag('button', 'Entre 6 et 9h', array('class'=>'button-typeahead duration', 'data-id'=>'id_durations_'.$key));}
                    if($key == 4){$durations[]='9-';$buttons_filter .= html_writer::tag('button', 'Plus de 9h', array('class'=>'button-typeahead duration', 'data-id'=>'id_durations_'.$key));}
                }

                $clauses = array();
                foreach($durations as $duration) {
                    list($min, $max) = array_map('intval', explode('-', $duration));

                    $min *= 60;
                    $max *= 60;

                    if (0 === $max) {
                        $clauses[] = '(lci.tps_a_distance + lci.tps_en_presence) > '.$min;
                    } else {
                        $clauses[] = '(lci.tps_a_distance + lci.tps_en_presence) BETWEEN '.$min.' AND '.$max;
                    }
                }
                $sql_filter .= 'AND (' . implode(' OR ', $clauses) . ') ';
            }
        }

        $having = '';
        if (isset($sql_having)) {
            $having .= " HAVING " .$sql_having . ' ';
        }

        if($tab == "formation"){
            $order_by = "ORDER BY ".$sql_public_order."lcc.coursestartdate DESC, lcc.enrolstartdate DESC, lcc.timemodified DESC ";
        } else {
            $order_by = "ORDER BY ".$sql_public_order."lcc.timemodified DESC ";
        }
        $sql = $sql . $sql_filter. "GROUP BY lci.id " . $having . $order_by;


        $offers = databaseConnection::instance()->get($dbconn)->get_records_sql($sql, $sql_params);
        $this->buttons = $buttons_filter;

        return $offers;
    }

    /**
     * Fonction qui permet qui récupérer la liste des domaines sur la base de données centralisées.
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function get_indexation_domains(){
        $DBC = get_centralized_db_connection();
        return $DBC->get_records('local_indexation_domains');
    }

    /**
     * Fonction qui permet qui récupérer la liste des publics sur la base de données centralisées.
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function get_indexation_publics(){
        $DBC = get_centralized_db_connection();
        return $DBC->get_records('local_indexation_publics',null,'name ASC');
    }

    /**
     * Fonction qui permet qui récupérer la liste des origines pour l'offre de parcours.
     * @return array
     */
    static function get_course_indexation_origins(){
        global $CFG;

        asort($CFG->academylist);

        $array = array();

        foreach($CFG->academylist as $id => $academy){
            if($id != 'frontal' && $id != 'ac-caen'){
                if($id == 'ih2ef'){
                    $array['ih2ef'] = 'IH2EF';
                } else if($id == 'efe') {
                    $array['ife'] = 'Ifé';
                } else {
                    $array[$id] = $academy['name'];
                }
            }
        }
        $array['espe'] = 'ESPE';
        $array["dgrh"] = "DGRH";

        asort($array);
        return $array;
    }

    /**
     * Fonction qui permet qui récupérer la liste des origines pour l'offre de formation.
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function get_formation_indexation_origins(){
        global $CFG;
        //return self::get_course_indexation_origins();
        $DBC = get_centralized_db_connection();
        // ac list
        asort($CFG->academylist);
        $array_aca = array();
        foreach($CFG->academylist as $id => $academy){
            if($id != 'frontal' && $id != 'ac-caen'){
                if($id == 'ih2ef'){
                    $array_aca['ih2ef'] = 'IH2EF';
                }else if($id == 'efe') {
                    $array_aca['ife'] = 'Ifé';
                } else {
                    $array_aca[$id] = $academy['name'];
                }
            }
        }

        // institution et autre
        $array_lio = array();
        $records = $DBC->get_records('local_indexation_origins',null,'name');
        foreach ($records as $id => $record) {
            if ($record->shortname !== 'academie') { // on extrait academie car on a déjà la liste complète des académies
                $array_lio[$record->shortname] = $record->name;
            }
        }

        $array = array_merge($array_aca, $array_lio);
        asort($array);

        return $array;
    }

    /**
     * Fonction qui permet de formatter l'origine de l'offre pour l'afficher ensuite dans la tuile d'information
     * de cette même offre.
     * @param $academie
     * @return mixed|string
     * @throws coding_exception
     */
    static function string_format_origine_offers($academie){
        if($academie == ""){
            return "&nbsp;";
        }
        if(strpos($academie, 'ac-') === 0){
            $academiename = str_replace('ac-', '', $academie);
            $academiename = ucfirst($academiename);

            if( in_array($academiename[0], array('A', 'I', 'O', 'U', 'E'))){
                // si commence par une voyelle
                $academiename = 'd\''.$academiename;
            }else {
                $academiename = 'de '.$academiename;
            }
            $academiename = get_string('originacademielabel', 'block_mycourselist', $academiename);
        }else{
            if($academie == 'reseau-canope'){
                $academiename = 'Réseau Canopé';
            } else {
                $academiename = strtoupper($academie);
            }
        }
        return $academiename;
    }

    /**
     * Fonction qui permet de split le titre de l'offre dans le cas où il dépasse 100 caractères.
     * @param $title
     * @return string
     */
    static function string_format_offers_title($title){
        if(strlen($title) >= 100){
            $title = substr($title, 0, 95) .' ...';
        }
        return $title;
    }

    /**
     * Fonction qui permet de formatter un timestamp en heures et minutes pour l'afficher ensuite dans la tuile
     * d'information d'une offre.
     * @param $time
     * @return string
     */
    static function string_format_time($time){
        $h = intval($time / 60);
        $min = $time % 60;
        $ret = '';

        if($h > 0){
            if($h == 1){
                $ret .= $h.' heure ';
            } else {
                $ret .= $h.' heures ';
            }
        }

        if($min > 0){
            $ret .= $min . ' minutes';
        }

        if($time == 0){
            $ret .= '0 heure';
        }

        return $ret;
    }

    /**
     * Fonction qui permet de récupérer les statistiques d'une offre de parcours puis de les afficher dans la tuile
     * d'information.
     * @param $offer
     * @return stdClass
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function get_stats_index_course($offer)
    {
        $DBC = get_centralized_db_connection();

        if(isset($offer->codeorigineid) && $offer->codeorigineid != ""
            && isset($offer->year) && $offer->year != ""
            && isset($offer->title) && $offer->title != "")
        {
            $origine = $DBC->get_record('local_indexation_codes', array('id' => $offer->codeorigineid));
            if($origine){
                $course_identification = $offer->year."_".$origine->code."_".$offer->title;
                $stats_index = $DBC->get_record('cr_stats_indexation', array('course_identification' => $course_identification));

                $stats = new stdClass();
                $stats->course_number = (isset($stats_index->courses_number)?$stats_index->courses_number:0);
                $stats->active_sessions_number = (isset($stats_index->active_sessions_number)?$stats_index->active_sessions_number:0);
                $stats->archived_sessions_number = (isset($stats_index->archived_sessions_number)?$stats_index->archived_sessions_number + $stats->active_sessions_number:0);
                return $stats;
            } else {
                $stats = new stdClass();
                $stats->course_number = 0;
                $stats->active_sessions_number = 0;
                $stats->archived_sessions_number = 0;
                return $stats;
            }
        }
        $stats = new stdClass();
        $stats->course_number = 0;
        $stats->active_sessions_number = 0;
        $stats->archived_sessions_number = 0;
        return $stats;
    }

    /**
     * Fonction permettant de générer le ou les boutons publics présents dans les offres de parcours et de formation
     * une fois qu'ils ont été sélectionnés dans les filtres.
     * @param $publics
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function get_local_indexation_publics_buttons($publics){
        $DBC = get_centralized_db_connection();
        $publics = $DBC->get_records_sql('SELECT * FROM local_indexation_publics WHERE id IN '. $publics.' ORDER BY name ASC');
        $buttons = "";
        foreach($publics as $public){
            $buttons .= html_writer::tag('button', $public->name, array('class'=>'button-typeahead public', 'data-id'=>'id_publics_'.$public->id));
        }
        return $buttons;
    }

    /**
     * Fonction permettant de générer le ou les boutons origins présents dans les offres de parcours et de formation
     * une fois qu'ils ont été sélectionnés dans les filtres.
     * @param $origins
     * @param $tab
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function get_local_indexation_origins_buttons($origins, $tab){
        global $CFG;
        $DBC = get_centralized_db_connection();
        $buttons = "";

        if($tab == 'formation'){
            $i = 0;
            $len = count($origins);
            $array = "(";
            foreach($origins as $origin) {
                if ($i == $len - 1) {
                    $array .= "'".$origin."'";
                } else {
                    $array .= "'".$origin."', ";
                }
                $i++;
            }
            $array .= ")";
            $origins = $DBC->get_records_sql('SELECT * FROM local_indexation_origins WHERE shortname IN '. $array);
            foreach($origins as $origin){
                $buttons .= html_writer::tag('button', $origin->name, array('class'=>'button-typeahead origin', 'data-id'=>'id_origins_'.$origin->shortname));
            }
        } else {
            foreach($origins as $origin){
                if($origin == 'espe'){
                    $buttons .= html_writer::tag('button', 'ESPE', array('class'=>'button-typeahead origin', 'data-id'=>'id_origins_espe'));
                } else if($origin == 'ih2ef'){
                    $buttons .= html_writer::tag('button', 'IH2EF', array('class'=>'button-typeahead origin', 'data-id'=>'id_origins_ih2ef'));
                } else if($origin == 'ife'){
                    $buttons .= html_writer::tag('button', 'Ifé', array('class'=>'button-typeahead origin', 'data-id'=>'id_origins_ife'));
                } else if($origin == 'dgrh'){
                    $buttons .= html_writer::tag('button', 'DGRH', array('class'=>'button-typeahead origin', 'data-id'=>'id_origins_dgrh'));
                } else {
                    $buttons .= html_writer::tag('button', $CFG->academylist[$origin]['name'], array('class'=>'button-typeahead origin', 'data-id'=>'id_origins_'.$origin));
                }
            }
        }
        return $buttons;
    }

    /**
     * Fonction qui retourne l'ensemble des données venant du hub d'une offre nécessaires à l'affichage de la tuile
     * d'information de cette même offre.
     * @param unknown $id
     * @return void|unknown
     */
    static function get_hub_course_offer($id){
        $hub = CourseHub::instance();
        if ($hub->isRemoteSlave()){
            return self::get_remotehub_course_offer($id);
        }else{
            return self::get_localhub_course_offer($id);
        }
    }
    
    static private function get_remotehub_course_offer($id){
        $hub = CourseHub::instance();
        $course = $hub->getPublishedCourseById($id);
        $course->fakeid = $course->id;
        $course->fullname = $course->name;
        $course->course_demourl = $course->courseurl;
        $course->timepublished = $course->timemodified;
        return $course;
    }
    
    static private function get_localhub_course_offer($id){
        global $CFG;

        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        $courseHub = CourseHub::instance();
        if((databaseConnection::instance()->get($courseHub->getMasterIdentifiant())) === false){return;}

        $sql = "SELECT  lcc.id as fakeid,
                    lci.id,
                    lci.publishid,
                    lci.objectif,
                    lci.collectionid,
                    lci.tps_a_distance,
                    lci.tps_en_presence,
                    lci.accompagnement,
                    lci.origin,
                    lci.domainid,
                    lci.authors,
                    lci.validateby,
                    lci.updatedate,
                    lci.departementid,
                    lci.originespeid,
                    lci.academyid,
                    lci.contact,
                    lci.entree_metier,
                    lci.year,
                    lci.codeorigineid,
                    lci.title,
                    lci.version,
                    lci.thumbnailid,
                    lci.certificatid,
                    lci.videoid,
                    lci.rythme_formation,
                    lcc.id AS publishid,
                    lcc.deleted,
                    lcc.publish,
                    lcc.name AS fullname,
                    lcc.shortname AS course_shortname,
                    lcc.summary,
                    lcc.courseid as courseid,
                    lcc.courseurl as course_url,
                    lcc.courseurl as course_demourl,
                    lcc.timemodified as timepublished,
                    lcc.coursestartdate as startdate,
                    lcc.courseenddate as enddate,
                    lcs.shortname,
                    IF(lcs.shortname='ac-caen','ac-normandie',lcs.shortname) as aca_uri,
                    IF(lcs.shortname='ac-caen','ac-normandie',lcs.shortname) as aca_name,
                    IF(lcs.shortname='ac-caen','ac-normandie',lcs.shortname) as matricule,
                    c_ind_origins.name as aca_origine,
                    c_ind_origins.shortname as origin_shortname,
                    c_ind_collections.name as col_name,
                    c_ind_collections.shortname as col_shortname,
                    c_ind_certificats.name as certificat_name,
                    c_ind_domains.name as domain_name,
                    c_departement.libelle_long as departement,
                    publicjoin.publics,
					publicjoin.nbpublic,
                    GROUP_CONCAT(DISTINCT lcik.keyword SEPARATOR ', ') as keywords,
                    GROUP_CONCAT(DISTINCT CONCAT(lcin.id,'[[[]]]',lcin.version,'[[[]]]',lcin.timemodified,'[[[]]]',lcin.note) SEPARATOR '{{{}}}') as notes
            FROM {".CourseHub::TABLE_INDEX."} as lci
            INNER JOIN {".CourseHub::TABLE_COURSE."} lcc ON lcc.id = lci.publishid
            LEFT JOIN (SELECT lcip.indexationid, GROUP_CONCAT(lips.name SEPARATOR ',') as publics, COUNT(lips.id) as nbpublic 
						FROM {".CourseHub::TABLE_INDEX_PUBLIC."} lcip 
						INNER JOIN ".$CFG->centralized_dbname.".local_indexation_publics lips ON (lips.id = lcip.publicid)
						GROUP BY lcip.indexationid ) publicjoin ON (publicjoin.indexationid = lci.id)
            LEFT JOIN {".CourseHub::TABLE_INDEX_KEYWORDS."} lcik ON (lci.id = lcik.indexationid)
            LEFT JOIN {".CourseHub::TABLE_INDEX_NOTES."} lcin ON (lci.id = lcin.indexationid)
            LEFT JOIN {".CourseHub::TABLE_SLAVE."} lcs ON (lcc.slaveid = lcs.id)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_domains c_ind_domains ON (c_ind_domains.id = lci.domainid)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_codes c_ind_codes ON (c_ind_codes.id = lci.codeorigineid)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_collections c_ind_collections ON (c_ind_collections.id = lci.collectionid)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_origins c_ind_origins ON (c_ind_origins.shortname = lci.origin)
            LEFT JOIN ".$CFG->centralized_dbname.".local_indexation_certificats c_ind_certificats ON (c_ind_certificats.id = lci.certificatid)
            LEFT JOIN ".$CFG->centralized_dbname.".{t_departement} c_departement ON (c_departement.id = lci.departementid)
            WHERE lcc.deleted = 0 AND lcc.id = ".$id."
            GROUP BY lci.id
            ";

        return databaseConnection::instance()->get($courseHub->getMasterIdentifiant())->get_record_sql($sql);
    }
    
    static function user_is_formateur($userid, $roleshortname, $acaname)
    {
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        if ((databaseConnection::instance()->get($acaname)) === false){
            return false;
        }
        
        if(($role = databaseConnection::instance()->get($acaname)->get_record('role', array('shortname' => $roleshortname))) === false){
            return false;
        }
        
        $hasrole = databaseConnection::instance()->get($acaname)->get_records_sql('SELECT ra.id
FROM {role_assignments} ra
WHERE ra.roleid=? AND ra.userid=?', array($role->id, $userid));
        
        return (count($hasrole) > 0);
    }

    /**
     * Fonction qui vérifie si le user en question était bien connecté sur la plateforme specifié en paramètre
     * avant d'aller sur l'offre de parcours.
     * @param $aca_name
     * @return bool
     */
    static function user_has_specific_loggedin($aca_name){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        $userid = self::get_userid_by_specific_session_cookie($aca_name);
        if($userid == 0){ return false; }
        $roles = databaseConnection::instance()->get($aca_name)->get_records_sql("
                          SELECT * FROM {role_assignments} ra
                          LEFT JOIN {role} r ON (ra.roleid = r.id) 
                          WHERE ra.userid = ".$userid." 
                          AND ra.contextid = 1 
                          AND r.shortname IN ('gestionnaire','administrateurlocal','administrateurnational')");

        // Si l'utilisateur a au moins un des role, il peut voir la restauration
        if (count($roles) > 0)
        {
            return true;
        }
        else if (self::user_is_formateur($userid,'formateur',$aca_name)
            || self::user_is_formateur($userid,'gestionnaire',$aca_name))
        {
            return true;
        }
    }

    /**
     * Fonction qui recupère le userid en utilisant un cookie de session spécifique. Cette fonction est utilisé
     * principalement pour le cas des institutions EFE et DNE-FOAD.
     * @param $aca_name
     * @return int
     */
    static function get_userid_by_specific_session_cookie($aca_name){
        global $CFG;

        // If user not loggedin and specific session exist in cookie
        $coockieName = 'MoodleSession'.$CFG->sessioncookie_prefix.$aca_name;
        if (isset($_COOKIE[$coockieName]))
        {
            require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
            $sid = $_COOKIE[$coockieName];
            if (strlen($sid) > 25)
            {
                $session = databaseConnection::instance()->get($aca_name)->get_record('sessions',array('sid'=>$sid));

                // The user has a session?
                if ($session !== false)
                {
                    // Is the session still valid?
                    if ($session->timemodified + $CFG->sessiontimeout > time()
                        || $CFG->sessiontimeout == 0)
                    {
                        return $session->userid;
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Fonction qui génère la popin permettant de restaurer un parcours à partir d'une offre dans l'offre de parcours.
     * @param $courseid
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function dialog_restore_course(){
        global $USER;
        $random = rand(0, 999999999);

        if($USER->id == 0 && OfferCourse::user_has_specific_loggedin('efe')){
            $nexturl = new moodle_url("/efe/local/coursehub/restore.php");
        }else if($USER->id == 0 && OfferCourse::user_has_specific_loggedin('dne-foad')){
            $nexturl = new moodle_url("/dne-foad/local/coursehub/restore.php");
        } else {
            $nexturl = new moodle_url("/".$this->mainaca."/local/coursehub/restore.php");
        }

        return '
            <div id="dialog_restore_course" style="display:none;">
                <div style="font-size: 10px; color: #515151;">
                    Merci de renseigner les champs suivants afin de r&eacute;aliser la restauration du parcours<br/><br/>
                </div>
                <form method="POST" action="'.$nexturl.'" name="restore_form" id="restore_course_form">
                    <input type="hidden" id="course_id_'.$random.'" name="hubcourseid" value="" />
                    <table style="font-size: 12px; color: black;">
                        <tr align="left">
                            <td><label>Nom : *</label></td>
                            <td><input type="text" size="50%" name="fullname" id="new_course_name_'.$random.'" /></td>
                        </tr>
                        <tr >
                            <td><label>Nom abr&eacute;g&eacute; : *</label></td>
                            <td><input type="text" size="50%" name="shortname" id="new_course_shortname_'.$random.'" /></td>
                        </tr>
                        <tr id="tr_subcategory">
                            <td><label>Sous-cat&eacute;gorie : </label></td>
                            <td>
                                <select name="categoryid" style="width:100%" id="new_category_course_'.$random.'" >						
                                    '.$this->subcategory_select_content().'
                                </select>
                            </td>
                        </tr>
                    </table>
                </form>	
            </div>';
    }

    /**
     * Fonction qui retourne l'arborescence des sous catégories selon le contexte de l'action qui est par la suite intégrée
     * dans l'élément select des popin d'action.
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    private function subcategory_select_content(){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        $aca_name = $this->get_mainacademy_user();
        if((databaseConnection::instance()->get($aca_name)) === false){
            if($this->user_has_specific_loggedin('efe')){
                $aca_name = 'efe';
            } else if ($this->user_has_specific_loggedin('dne-foad')){
                $aca_name = 'dne-foad';
            } else {
                return;
            }
        }

        $actual_category = '';
        $main_category = databaseConnection::instance()->get($aca_name)->get_record('course_categories'
            , array('name' => CAT_PARCOURS_FORMATION));
        $subcategory_tree = $this->destination_subcategory_tree($main_category->id);

        $content = '<option value="'.$main_category->id.'">'
            .get_string('nosubcategory', 'block_course_management').'</option>';
        if($subcategory_tree){
            $content .= $this->select_content_build($subcategory_tree, $actual_category);
        }
        return $content;
    }

    /**
     * Fonction génère l'arborescence des sous catégories selon la catégorie dans laquelle se situe le parcours.
     * @param $main_category_id
     * @param bool $is_root
     * @return array|string
     * @throws dml_exception
     */
    private function destination_subcategory_tree($main_category_id, $is_root = false){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $aca_name = $this->get_mainacademy_user();
        if((databaseConnection::instance()->get($aca_name)) === false){
            if($this->user_has_specific_loggedin('efe')){
                $aca_name = 'efe';
            } else if ($this->user_has_specific_loggedin('dne-foad')){
                $aca_name = 'dne-foad';
            } else {
                return;
            }
        }

        $subcategory_tree = [];
        $offset = ($is_root)?1:0;
        $l_subcategories = databaseConnection::instance()->get($aca_name)->get_records_sql(
            'SELECT * FROM {course_categories} WHERE parent = ? ORDER BY name',[$main_category_id]);

        foreach($l_subcategories as $subcategory){
            $subcategory_tree[] = ['id' => $subcategory->id ,
                'name' => $subcategory->name,
                'depth' => ($subcategory->depth+$offset)];

            $children = $this->destination_subcategory_tree($subcategory->id, $is_root);
            if(!empty($children)){
                foreach($children as $child){
                    array_push($subcategory_tree,$child);
                }
            }
            $children =  null;
        }
        return $subcategory_tree;
    }

    /**
     * Fonction qui génère la liste des sous categorie sous forme HTML (balise option).
     * @param $subcategory_tree
     * @param string $selected_value
     * @return string
     */
    private function select_content_build($subcategory_tree, $selected_value = ''){
        $select_content = '';
        foreach($subcategory_tree as $subcategory){
            $select_content .= '<option value="'.$subcategory['id'].'" '.($selected_value==$subcategory['id']?' "':'').'>';

            for ($i = 2; $i < $subcategory['depth']; $i++) {
                $select_content .= '&nbsp&nbsp';
            }
            $select_content .= '► '.$subcategory['name'].'</option>';
        }
        return $select_content;
    }

    /**
     * Return the list of public depending of the tab
     * @return array|bool|string Return a string of public or return false if an error has occured
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_user_favorite_publics(){
        global $USER;

        if(isset($USER) && $USER->id > 0) {
            $publics = new PublicsByFunctionUser($USER->id, $this->tab);
            if($this->tab == 'formation'){
                return $publics->get_favorite_formation_publics();
            } else {
                return $publics->get_favorite_course_publics();
            }
        }
        return false;
    }

    /**
     * Fonction qui retourne le shortname du l'académie de rattachement du user.
     * @return mainacademy|string
     * @throws dml_exception
     */
    private function get_mainacademy_user(){
        global $DB;
        if($this->userid == 0){
            return '';
        }
        $user = $DB->get_record('user', ['id' => $this->userid]);
        if($user){
            $mainaca = user_get_mainacademy($this->userid);
            if ($mainaca || !empty($mainaca)) {
                return $mainaca;
            } else {
                return '';
            }
        }
        return '';
    }

}


/////////////////////////////
// CLASS COURSE OFFERS XML //
/////////////////////////////

/**
 * Class OfferCourseXML
 */
class OfferCourseXML{

    private $offers = array();
    private $xml_structure = "";
    private $attachfile = "";

    /**
     * Take the offers parameter, convert them to an XML structure and save them in a temporary XML file
     * @param Object[] $offers List of offers to export in XML
     */
    function __construct($offers){
        $this->offers = $offers;
        $this->construct_xml_contain();
        $this->create_xml_file();
    }

    /**
     * Build the XML structure
     */
    private function construct_xml_contain(){
        global $CFG;
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.'<!DOCTYPE root [<!ENTITY nbsp "&#160;">]>'.'<catalogue/>');

        foreach($this->offers as $offer){
            $resource_link = '';
            if (OfferCourse::isCentralizedRessourcesAvailable()) {
                if(isset($offer->thumbnailid) && $offer->thumbnailid != null){
                    $DBC = get_centralized_db_connection();
                    $cr_resource = $DBC->get_record('cr_resources', array('resourceid'=>$offer->thumbnailid));
                    $resource_link = "";
                    if($cr_resource){
                        $url_resource = '/'.$CFG->centralizedresources_media_types['indexthumb'].'/'.$cr_resource->cleanname;
                        $resource_link = get_resource_centralized_secure_url($url_resource, $cr_resource->hashname.$cr_resource->createdate, $CFG->secure_link_timestamp_image);
                    }
                }
            }
            $formation = $xml->addChild('formation');
            if (isset($resource_link)){
                $formation->addChild('image', htmlspecialchars($resource_link));
            }
            $formation->addChild('nom_parcours', htmlspecialchars($offer->fullname));
            if (isset($offer->domain_name)){
                $formation->addChild('domaine', htmlspecialchars($offer->domain_name));
            }
            if (isset($offer->publics)){
                $formation->addChild('public', htmlspecialchars($offer->publics));
            }
            if (isset($offer->col_name)){
                $formation->addChild('collection', htmlspecialchars($offer->col_name));
            }
            if (isset($offer->authors)){
                $formation->addChild('formateur', htmlspecialchars($offer->authors));
            }
            if (isset($offer->summary)){
                $description = strip_tags($offer->summary,'');
                $formation->addChild('description', htmlspecialchars($description));
            }
            if (isset($offer->objectif)){
                $objectif = strip_tags($offer->objectif,'');
                $formation->addChild('objectifs', htmlspecialchars($objectif));
            }
            if (isset($offer->tps_en_presence)){
                $temps_presence = OfferCourse::string_format_time($offer->tps_en_presence);
                if($temps_presence == null){
                    $temps_presence = get_string('no_lead_time_attendance', 'local_magistere_offers');
                }
                $formation->addChild('duree_presence', htmlspecialchars($temps_presence));
            }
            if (isset($offer->tps_a_distance)){
                $temps_distance = OfferCourse::string_format_time($offer->tps_a_distance);
                if($temps_distance == null){
                    $temps_distance = get_string('no_lead_time_remote', 'local_magistere_offers');
                }
                $formation->addChild('duree_distance', htmlspecialchars($temps_distance));
            }
            if (isset($offer->validateby)){
                $formation->addChild('validation', htmlspecialchars($offer->validateby));
            }
            if (isset($offer->aca_name)){
                $formation->addChild('origine', OfferCourse::string_format_origine_offers($offer->aca_name));
            }
            if (isset($offer->timepublished)){
                $updatedate = (isset($offer->timepublished)?date('YmdHis',$offer->timepublished):date('YmdHis',$offer->updatedate));
                $formation->addChild('date_publication', $updatedate);
            }
            $formation->addChild('url', $offer->courseurl);
        }

        $this->xml_structure = $xml->asXML();
    }

    /**
     * Save the XML structure to a temporary file
     */
    private function create_xml_file(){
        global $CFG;
        
        $tmpfileid = time().rand(1,99999);
        $tmpfile = 'export_xml_'.$tmpfileid.'.pdf';
        $this->attachfile = $CFG->tempdir.'/'.$tmpfile;
        
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($this->xml_structure);
        $dom->save($this->attachfile);
    }

    /**
     * Send the temporary XML file to the user 
     * @return boolean True if succeed, else false
     */
    public function download_xml_file(){
    
       if (file_exists($this->attachfile)) {
           $attachfilename = 'export_xml_'.date("Y-m-d").'.xml';
    
           header("Content-Description: File Transfer");
           header("Content-type: text/xml");
           header('Content-Disposition: attachment; filename="'.$attachfilename.'"');
    
           readfile($this->attachfile);
           unlink($this->attachfile);
           return true;
       }
       return false;
    }
}


////////////////////////////////////
// CLASS PUBLICS BY FONCTION USER //
////////////////////////////////////

/**
 * Class PublicsByFunctionUser. Class qui traite les publics favoris enregistrés par l'utilisateur afin de filtrer
 * automatiquementl'offre de parcours et de formation.
 */
class PublicsByFunctionUser{

    private $userid;
    private $username;
    private $mainaca;
    private $tab;
    private $fonction_in_identity_vector;
    private $type_in_identity_vector;
    private $all_publics;
    private $publics;

    const DEC = 'DEC';
    const ENS1D = 'ENS1D';
    const ENS2D_CLG = 'ENS2D-CLG';
    const ENS2D_LYC = 'ENS2D-LYC';
    const ENS2D = 'ENS2D';
    const INS2D = 'INS2D';
    const INS1D = 'INS1D';
    const PERDIR = 'PERDIR';
    const Type_LYC = 'LYC';
    const Type_CLG = 'CLG';

    /**
     * PublicsByFunctionUser constructor.
     * @param $userid
     * @param string $tab
     * @throws dml_exception
     * @throws moodle_exception
     */
    function __construct($userid, $tab='course'){
        $this->mainaca = $this->get_mainacademy_user($userid);
        $this->userid = $this->get_userid_in_mainacademy_by_username($userid);
        $this->tab = $tab;
        $this->pageid = null;
        $this->fonction_in_identity_vector = $this->get_vector_identity_fonction_user();
        $this->type_in_identity_vector = $this->get_vector_identity_fonction_type_user();
        $this->all_publics = $this->get_all_publics();
        $this->publics = $this->get_publics_user();
    }
    
    static function set_remote_user_preference($name,$value,$username = null){
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $userid = $USER->id;
        if ( $username == null){
            $username = $USER->username;
        }
        
        $user_is_manual = false;
        if($USER->id > 0) {
            if($USER->auth == 'manual'){
                $user_is_manual = true;
            }
            
        } else {
            if($username){
                $user = $DB->get_record('user', array("username" => $username));
                if($user){
                    $userid = $user->id;
                    if($user->auth == 'manual'){
                        $user_is_manual = true;
                    }
                }
            }
        }
        
        if($user_is_manual){
            $mainaca = $CFG->academie_name;
        } else {
            $mainaca = user_get_mainacademy($userid);
        }
        
        $academies = get_magistere_academy_config();
        
        $remoteuser = false;
        if ($mainaca !== false && array_key_exists($mainaca,$academies)) {
            $remoteuser = databaseConnection::instance()->get($mainaca)->get_record('user', array('username'=>$username));
        }
        
        if( !$mainaca || !array_key_exists($mainaca,$academies) || $remoteuser == false){
            return set_user_preference($name,$value);
        }
        
        if ($remoteuser !== false) {
            $exist_user_p = databaseConnection::instance()->get($mainaca)->record_exists("user_preferences",array("name" => $name,"userid" => $remoteuser->id));
            
            if($exist_user_p){
                databaseConnection::instance()->get($mainaca)->set_field("user_preferences","value",$value,array("name" => $name,"userid" => $remoteuser->id));
            }else{
                $up =  new stdClass();
                $up->userid = $remoteuser->id;
                $up->name = $name;
                $up->value = $value;
                databaseConnection::instance()->get($mainaca)->insert_record("user_preferences",$up);
            }
        }
    }
    
    static function get_remote_user_preference($name, $username = null){
        global $USER, $DB, $CFG;
        
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');
        
        $userid = $USER->id;
        if ( $username == null){
            $username = $USER->username;
        }
        
        $user_is_manual = false;
        if($USER->id > 0) {
            if($USER->auth == 'manual'){
                $user_is_manual = true;
            }
            
        } else {
            if($username){
                $user = $DB->get_record('user', array("username" => $username));
                if($user){
                    $userid = $user->id;
                    if($user->auth == 'manual'){
                        $user_is_manual = true;
                    }
                }
            }
        }
        
        if($user_is_manual){
            $mainaca = $CFG->academie_name;
        } else {
            $mainaca = user_get_mainacademy($userid);
        }
        
        $academies = get_magistere_academy_config();
        
        $remoteuser = false;
        if ($mainaca !== false && array_key_exists($mainaca,$academies)) {
            $remoteuser = databaseConnection::instance()->get($mainaca)->get_record('user', array('username'=>$username));
        }
        
        if(!$mainaca || !array_key_exists($mainaca,$academies) || $remoteuser === false){
            return get_user_preferences($name);
        }
        
        return databaseConnection::instance()->get($mainaca)->get_field("user_preferences","value",array("userid" => $remoteuser->id,"name" => $name));
    }

    /**
     * Fonction qui vérifie la class dans laquelle se situe l'utilisateur. Si l'utilisateur se situe sur
     * l'une des 2 offres, les données liées aux publics favoris seront enregistrés sur la plateforme de rattachement.
     * @param $pageid
     */
    public function set_page($pageid){
        if($pageid){
            if(strpos($pageid,'offercourse') !== false){
                $this->pageid = 'offercourse';
                $this->fonction_user_has_changing();
            }else if(strpos($pageid, 'offerformation') !== false){
                $this->pageid = 'offerformation';
                $this->fonction_user_has_changing();
            }
        }
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_formation_favorite_publics". Ce user preference est
     * utilisé pour filtrer les données sur l'offre de formation sur les publics choisi en pré-définis
     * selon la fonction rattaché au user.
     * @return array|bool|string
     */
    public function get_favorite_formation_publics(){
        if($this->userid > 0 ){
            return self::get_remote_user_preference("local_magistere_offers_formation_favorite_publics", $this->username);
        }
        return false;
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_formation_favorite_publics". sur
     * l'académie de rattachement. Les données enregistrées sont sous la forme d'un string contenant les id des publics
     * séparés par une virgule. Ce choix s'explique par l'utilisation de cette donnée en SQL sous la forme
     * d'une concaténation de chaine de caractère.
     * @param $publics
     * @return bool
     */
    public function set_favorite_formation_publics($publics){
        $publics_str = "";
        if($publics){
            $i = 0;
            $len = count($publics);
            foreach($publics as $key => $value) {
                if ($i == $len - 1) {
                    $publics_str .= $value;
                } else {
                    $publics_str .= $value .",";
                }
                $i++;
            }
        }
        self::set_remote_user_preference("local_magistere_offers_formation_favorite_publics", $publics_str, $this->username);
        return true;
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_course_favorite_publics". Ce user preference est
     * utilisé pour filtrer les données sur l'offre de parcours sur les publics choisi en pré-définis
     * selon la fonction rattaché au user.
     * @return array|bool|string
     */
    public function get_favorite_course_publics(){
        if($this->userid > 0 ){
            return self::get_remote_user_preference("local_magistere_offers_course_favorite_publics", $this->username);
        }
        return false;
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_course_favorite_publics". sur
     * l'académie de rattachement. Les données enregistrées sont sous la forme d'un string contenant les id des publics
     * séparés par une virgule. Ce choix s'explique par l'utilisation de cette donnée en SQL sous la forme
     * d'une concaténation de chaine de caractère.
     * @param $publics
     * @return bool
     */
    public function set_favorite_course_publics($publics){
        $publics_str = "";
        if($publics){
            $i = 0;
            $len = count($publics);
            foreach($publics as $key => $value) {
                if ($i == $len - 1) {
                    $publics_str .= $value;
                } else {
                    $publics_str .= $value .",";
                }
                $i++;
            }
        }
        self::set_remote_user_preference("local_magistere_offers_course_favorite_publics", $publics_str, $this->username);
        return true;
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_formation_notification". Ce user preference est
     * utilisé pour déterminer si le user en question souhaite recevoir des notifications hebdomadaires sur les nouvelles
     * formations ajoutées sur l'offre de formation dans le courant de la semaine passée.
     * Retourne un booléen enregistré par le même formulaire de choix de publics.
     * @return bool|mixed|null|string
     */
    public function get_formation_notification(){
        $notif = self::get_remote_user_preference("local_magistere_offers_formation_notification", $this->username);

        if($notif != null){
            $array = array();
            $array[1] = "1";
            return $notif;
        }
        return false;
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_formation_notification". sur l'académie de rattachement.
     * @param $notif
     * @return bool
     */
    public function set_formation_notification($notif){
        self::set_remote_user_preference("local_magistere_offers_formation_notification", $notif, $this->username);
        return true;
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_course_notification". Ce user preference est
     * utilisé pour déterminer si le user en question souhaite recevoir des notifications hebdomadaires sur les nouveaux
     * parcours ajoutés sur les 2 offres dans le courant de la semaine passée.
     * Retourne un booléen enregistré par le même formulaire de choix de publics.
     * @return bool|mixed|null|string
     */
    public function get_course_notification(){
        $notif = self::get_remote_user_preference("local_magistere_offers_course_notification", $this->username);

        if($notif != null){
            $array = array();
            $array[1] = "1";
            return $notif;
        }
        return false;
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_course_notification". sur l'académie de rattachement.
     * @param $notif
     * @return bool
     */
    public function set_course_notification($notif){
        self::set_remote_user_preference("local_magistere_offers_course_notification", $notif, $this->username);
        return true;
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_fonction_user". Ce user preference est utilisé
     * pour déterminer une liste de publics pré-défini selon la fonction du user servant par la suite de filtre sur
     * l'offre de formation et de parcours. Cette valeur est récupérée dans les données du vecteur d'identité du user
     * puis enregistrée sur la plaforme de rattachement du user.
     * @return mixed|null|string
     */
    public function get_fonction_user_pref(){
        if($this->userid > 0 ){
            return self::get_remote_user_preference("local_magistere_offers_fonction_user", $this->username);
        }else{
            return null;
        }
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_fonction_user". sur l'académie de rattachement.
     * @param $fonction
     * @return bool
     */
    public function set_fonction_user_pref($fonction){
        if($this->userid > 0 ){
            self::set_remote_user_preference("local_magistere_offers_fonction_user", $fonction, $this->username);
            return true;
        }
        return false;
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_type_user". Ce user preference est utilisé
     * dans le cas où la fonction possède un type bien précis et donc retourne des publics ciblés.
     * Cette valeur n'est pas obligatoire puisque toutes les fonctions possibles n'ont pas forcément de type pré-défini.
     * @return bool|mixed|null|string
     */
    public function get_type_user_pref(){
        if($this->userid > 0 ){
            return self::get_remote_user_preference("local_magistere_offers_type_user", $this->username);
        }
        return false;
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_type_user". sur l'académie de rattachement
     * du user.
     * @param $type
     * @return bool
     */
    public function set_type_user_pref($type){
        if($this->userid > 0 ) {
            self::set_remote_user_preference("local_magistere_offers_type_user", $type, $this->username);
            return true;
        }
        return false;
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_formation_first_connection_user".
     * Ce user preference est utilisé pour contrôler la validation des publics par la popin de choix des publics
     * sur l'offre de formation. Si la valeur ou l'existance de ce user preference est à false,
     * la popin continuera de s'afficher sur l'offre de formation.
     * @return bool|mixed|null|string
     */
    public function get_formation_first_connection(){
        if($this->userid > 0 ){
            return self::get_remote_user_preference("local_magistere_offers_formation_first_connection_user", $this->username);
        }
        return false;
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_formation_first_connection_user" sur l'académie
     * de rattachement du user.
     * @param $value
     * @return bool
     */
    public function set_formation_first_connection($value){
        if($this->userid > 0 ){
            return self::set_remote_user_preference("local_magistere_offers_formation_first_connection_user", $value, $this->username);
        }
        return false;
    }

    /**
     * Fonction qui retourne le user preference "local_magistere_offers_course_first_connection_user".
     * Ce user preference est utilisé pour contrôler la validation des publics par la popin de choix des publics
     * sur l'offre de parcours. Si la valeur ou l'existance de ce user preference est à false,
     * la popin continuera de s'afficher sur l'offre de formation.
     * @return bool|mixed|null|string
     */
    public function get_course_first_connection(){
        if($this->userid > 0 ){
            return self::get_remote_user_preference("local_magistere_offers_course_first_connection_user", $this->username);
        }
        return false;
    }

    /**
     * Fonction qui enregistre le user preference "local_magistere_offers_course_first_connection_user" sur l'académie
     * de rattachement du user.
     * @param $value
     * @return bool
     */
    public function set_course_first_connection($value){
        if($this->userid > 0 ){
            return self::set_remote_user_preference("local_magistere_offers_course_first_connection_user", $value, $this->username);
        }
        return false;
    }

    /**
     * Fonction qui retourne la liste des publics par défaut selon la fonction rattachée au user et selon l'offre dans
     * laquelle le user connecté se situe.
     * @return array|bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_publics_by_fonction_user(){
        if($this->tab == 'formation'){
            $array =  $this->get_publics_for_formations();
            if($this->add_publics_user_rules_for_formateur_and_tuteur()){
                $array[] = array_search("Formateurs", $this->all_publics);
            }
            return $array;
        }
        return $this->get_publics_for_courses();
    }

    /**
     * Fonction qui retourne soit les publics pré-definis par la fonction rattachée au user, soit les publics choisis
     * par le user.
     * @return array|bool|string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_publics_user(){
        if($this->tab == 'formation'){
            if($this->get_formation_first_connection() !== false){
                return $this->prepare_favorite_publics_in_array();
            } else {
                return $this->get_publics_by_fonction_user();
            }
        } else {
            if($this->get_course_first_connection() !== false){
                return $this->prepare_favorite_publics_in_array();
            } else {
                return $this->get_publics_by_fonction_user();
            }
        }
    }

    /**
     * Fonction qui prépare le user preference "local_magistere_offers_favorite_publics" sous la forme d'une liste
     * (la clé étant l'id du public et la valeur à 1) pour ensuite l'exploiter dans les formulaires de filtre
     * (offre de parcours et de formation) ou de choix de publics.
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function prepare_publics_for_checkboxes_form(){
        $array = array();
        if($this->get_publics_user()){
            foreach($this->get_publics_user() as $key => $public){
                $array[$public] = "1";
            }
        }
        return $array;
    }

    /**
     * Fonction qui prépare le user preference "local_magistere_offers_favorite_publics" sous la forme d'une liste.
     * @return array|bool|string
     */
    public function prepare_favorite_publics_in_array(){
        if($this->userid > 0) {
            if($this->tab == 'formation'){
                $publics = $this->get_favorite_formation_publics();
            } else {
                $publics = $this->get_favorite_course_publics();
            }

            if($publics !== false){
                if(is_string($publics) && $publics== ""){
                    return array();
                }
                if(empty($publics)){
                    return "";
                }
                return explode(",", $publics);
            }
            return false;
        }
        return false;
    }

    /**
     * Fonction de creation de la modal (Bootstrap) pour le choix des publics dans l'offre de formation. La popin contient
     * un moodle form possédant l'ensemble des choix de publics possibles. Par défaut, le formulaire est pré-rempli
     * par les publics selon la fonction rattachée au user.
     * Retourne le html sous forme d'une string ainsi que l'appel du module AMD contenant l'ajax retournant la liste
     * publics à pré-remplir.
     * @param $modal_name
     * @return string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_modal($modal_name){
        global $PAGE;
        if($this->tab == 'formation'){
            $PAGE->requires->js_call_amd("local_magistere_offers/modal_public", "init", array($this->get_formation_first_connection(), $this->tab, isloggedin()));
            $form_publics = new publics_form(null, array('v'=> $this->tab, 'publics_fav' => $this->prepare_publics_for_checkboxes_form(), 'get_notif' => $this->get_formation_notification()));

        } else {
            $PAGE->requires->js_call_amd("local_magistere_offers/modal_public", "init", array($this->get_course_first_connection(), $this->tab, isloggedin()));
            $form_publics = new publics_form(null, array('v'=> $this->tab, 'publics_fav' => $this->prepare_publics_for_checkboxes_form(), 'get_notif' => $this->get_course_notification()));
        }

        $html ='<div class="modal fade publics-modal" style="left : 15%;width: 70%;margin-left: 0;" id="'.$modal_name.'" tabindex="-1" role="dialog" aria-labelledby="'.$modal_name.'" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                    '.$form_publics->render().'
                    </div>
                </div>
            </div>';

        return $html;
    }

    /**
     * Fonction qui récupère l'id du user sur sa plateforme de rattachement. L'id et la plateforme de rattachement
     * seront ensuite utilisés pour inclure les données du public.
     * @param $userid
     * @return bool
     * @throws dml_exception
     */
    private function get_userid_in_mainacademy_by_username($userid){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        global $DB;
        if($userid > 0 ){
            if($this->mainaca){
                if((databaseConnection::instance()->get($this->mainaca)) === false){return false;}
                $user = $DB->get_record('user', array('id' => $userid));
                if($user->auth == 'manual'){
                    $this->username = $user->username;
                    return $user->id;
                } else {
                    if($user->username){
                        // Enlever la restriction sur l'auth shibboleth pour que ça marche sur la dev
                        $user_in_mainacademy = databaseConnection::instance()->get($this->mainaca)->get_record('user', array('username' => $user->username, 'auth' => "shibboleth"));
                        if($user_in_mainacademy){
                            $this->username = $user->username;
                            return $user_in_mainacademy->id;
                        }
                        return false;
                    }
                }
                return false;
            } else {
                $user = $DB->get_record('user', array('id' => $userid));
                if(isset($user)){
                    if($user->auth == 'manual'){
                        $this->username = $user->username;
                        return $user->id;
                    }
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Fonction qui retourne le shortname du l'académie de rattachement du user.
     * @param $userid
     * @return bool|mainacademy
     * @throws dml_exception
     */
    private function get_mainacademy_user($userid){
        global $CFG, $DB;
        $user = $DB->get_record('user', array('id' => $userid));
        if($user){
            if($user->auth == 'manual'){
                return $CFG->academie_name;
            } else {
                $mainaca = user_get_mainacademy($userid);
                if ($mainaca || !empty($mainaca)) {
                    return $mainaca;
                } else {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Fonction de vérification d'un changement de fonction du user. Cette vérification est établie entre
     * la donnée du vecteur d'identité et celle dans les user preference. Dans le cas d'une différence
     * entre ces 2 données, le user preference "local_magistere_offers_fonction_user" ainsi que
     * "local_magistere_offers_fonction_type_user" sont mis à jour, le user preference
     * "local_magistere_offers_favorite_publics" est, quant à lui, implémenté des publics par défaut selon la fonction
     * rattachée au user.
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function fonction_user_has_changing(){
        if($this->pageid && ($this->pageid == "offerformation" || $this->pageid == "offercourse")){
            if($this->userid > 0) {
                // On vérifie l'existence d'une fonction dans le vecteur d'identité
                if (isset($this->fonction_in_identity_vector)) {
                    $has_changing = false;
                    // Si on a une fonction dans les user preference, on continue
                    if ($this->get_fonction_user_pref()) {
                        // Si la fonction ou le type du vecteur d'identité est différente de celle des user preferences,
                        // on change les données publics et fonction et on initialise la première connexion
                        if ($this->fonction_in_identity_vector != $this->get_fonction_user_pref()
                            || $this->type_in_identity_vector != $this->get_type_user_pref()) {
                            $this->set_formation_first_connection(false);
                            $this->set_course_first_connection(false);
                        }
                    }
                    if ($this->tab == 'formation' && !$this->get_formation_first_connection()){
                        // On ajoute la fonction et le type du vecteur d'identité dans les user preferences avec les publics qui leur sont rattachés
                        $this->set_fonction_user_pref($this->fonction_in_identity_vector);
                        $this->fonction_in_identity_vector = $this->get_fonction_user_pref();
                        $this->set_type_user_pref($this->type_in_identity_vector);
                        $this->type_in_identity_vector = $this->get_type_user_pref();
                        $this->set_favorite_formation_publics($this->get_publics_by_fonction_user());
                        $this->publics = $this->get_publics_user();
                        $has_changing = true;
                    } else if ($this->tab == "course" && !$this->get_course_first_connection()){
                        // On ajoute la fonction et le type du vecteur d'identité dans les user preferences avec les publics qui leur sont rattachés
                        $this->set_fonction_user_pref($this->fonction_in_identity_vector);
                        $this->fonction_in_identity_vector = $this->get_fonction_user_pref();
                        $this->set_type_user_pref($this->type_in_identity_vector);
                        $this->type_in_identity_vector = $this->get_type_user_pref();
                        $this->set_favorite_course_publics($this->get_publics_by_fonction_user());
                        $this->publics = $this->get_publics_user();
                        $has_changing = true;
                    }
                    return $has_changing;
                }
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * Fonction de récupération de la fonction dans le vecteur d'identité du user.
     * Cette donnée est récupérée sur la plateforme de rattachement du user.
     * Retourne false si le user n'a pas de fonction dans son vecteur d'identité.
     * @return bool|void
     */
    private function get_vector_identity_fonction_user(){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        
        if($this->userid > 0) {
            if($this->mainaca){
                if((databaseConnection::instance()->get($this->mainaca)) === false){return;}
                $sql = "SELECT uid.id, uid.data, uid.userid
                    FROM {user_info_data} as uid
                    LEFT JOIN {user_info_field} uif ON (uif.id = uid.fieldid) 
                    WHERE uif.shortname = 'fonction' AND uid.userid = ".$this->userid."
                    ";

                $fonction = databaseConnection::instance()->get($this->mainaca)->get_record_sql($sql);
                if($fonction){
                    return $fonction->data;
                }
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * Fonction de récupération du type de la fonction rattachée au vecteur d'identité du user
     * @return bool
     */
    private function get_vector_identity_fonction_type_user(){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        
        if($this->userid > 0) {
            if($this->mainaca){
                if((databaseConnection::instance()->get($this->mainaca)) === false){return;}
                $sql = "SELECT uid.id, uid.data, uid.userid
                    FROM {user_info_data} as uid
                    LEFT JOIN {user_info_field} uif ON (uif.id = uid.fieldid) 
                    WHERE uif.shortname = 'type' AND uid.userid = ".$this->userid."
                    ";

                $type = databaseConnection::instance()->get($this->mainaca)->get_record_sql($sql);
                if($type){
                    return $type->data;
                }
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * Fonction de récupération de tous les publics de la table centralisée sous forme d'une liste
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_all_publics(){
        $DBC = get_centralized_db_connection();
        $li_publics = $DBC->get_records('local_indexation_publics');

        $array = array();
        foreach($li_publics as $public){
            $array[$public->id] = $public->name;
        }

        return $array;
    }

    /**
     * Fonction venant ajouter le public "Formateurs" dans la liste des publics pré-défini par la fonction rattachée
     * au user dans le cas où le user connecté est formateur ou tuteur sur une ou plusieurs formations de
     * l'offre de formation.
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function add_publics_user_rules_for_formateur_and_tuteur(){
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        if($this->userid > 0) {
            if($this->get_formation_first_connection() == false && $this->tab == 'formation'){
                $is_formateur = false;
                $is_tuteur = false;
                $user = databaseConnection::instance()->get($this->mainaca)->get_record('user', array('id' => $this->userid));
                if($user){
                    if(self::user_has_role_assignment($user, 'formateur', $this->mainaca)){
                        $is_formateur = true;
                    }
                    if(self::user_has_role_assignment($user, 'tuteur', $this->mainaca)){
                        $is_tuteur = true;
                    }
                    if($is_formateur || $is_tuteur){
                        return true;
                    }
                }
                return false;
            }
            return false;
        }
        return false;
    }
    
    
    
    static function user_has_role_assignment($user, $shortname, $aca)
    {
        require_once($GLOBALS['CFG']->dirroot.'/local/magisterelib/databaseConnection.php');
        
        if($user->auth != 'shibboleth'){
            return false;
        }
        
        if ((databaseConnection::instance()->get($aca)) === false){
            return false;
        }
        
        if(($useraca = databaseConnection::instance()->get($aca)->get_record('user', array('username' => $user->username))) === false){
            return false;
        }
        
        if(($role = databaseConnection::instance()->get($aca)->get_record('role', array('shortname' => $shortname))) === false){
            return false;
        }
        
        $hasrole = databaseConnection::instance()->get($aca)->get_records_sql('SELECT ra.id
FROM {role_assignments} ra
WHERE ra.roleid=? AND ra.userid=?', array($role->id, $useraca->id));
        
        return (count($hasrole) > 0);
    }

    /**
     * Fonction de création d'une liste de publics par défaut selon la fonction rattachée au user pour l'offre de formation.
     * Retourne false si le user n'a pas de fonction dans son vecteur d'identité.
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_publics_for_formations(){
        $array = array();
        if($this->fonction_in_identity_vector){
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::DEC){
                $array[] = array_search("Directeurs d'école", $this->all_publics);

                $array[] = array_search("Enseignants maternelle", $this->all_publics);

                $array[] = array_search("Enseignants élémentaire", $this->all_publics);

                return $array;
            }

            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS1D){
                $array[] = array_search("Enseignants maternelle", $this->all_publics);

                $array[] = array_search("Enseignants élémentaire", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS2D_CLG){
                $array[] = array_search("Enseignants collèges", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS2D_LYC){
                $array[] = array_search("Enseignants lycées", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS2D){
                $array[] = array_search("Enseignants collèges", $this->all_publics);

                $array[] = array_search("Enseignants lycées", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::INS2D){
                $array[] = array_search("Inspecteur 2nd degré", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::INS1D){
                $array[] = array_search("Inspecteur 1er degré", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::PERDIR){
                $array[] = array_search("Personnels de direction", $this->all_publics);

                return $array;
            }
        }
        return false;
    }

    /**
     * Fonction de création d'une liste de publics par défaut selon la fonction rattachée au user pour l'offre de parcours.
     * Retourne false si le user n'a pas de fonction dans son vecteur d'identité.
     * @return array|bool
     */
    private function get_publics_for_courses(){
        if($this->fonction_in_identity_vector){
            $array = array();
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::DEC){
                $array[] = array_search("Directeurs d'école", $this->all_publics);

                $array[] = array_search("Enseignants maternelle", $this->all_publics);

                $array[] = array_search("Enseignants élémentaire", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS1D){
                $array[] = array_search("Enseignants maternelle", $this->all_publics);

                $array[] = array_search("Enseignants élémentaire", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS2D_CLG){
                $array[] = array_search("Enseignants collèges", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS2D_LYC){
                $array[] = array_search("Enseignants lycées", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::ENS2D){
                $array[] = array_search("Enseignants collèges", $this->all_publics);

                $array[] = array_search("Enseignants lycées", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::INS2D){
                $array[] = array_search("Inspecteur 2nd degré", $this->all_publics);

                $array[] = array_search("Enseignants collèges", $this->all_publics);

                $array[] = array_search("Enseignants lycées", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::INS1D){
                $array[] = array_search("Inspecteur 1er degré", $this->all_publics);

                $array[] = array_search("Enseignants maternelle", $this->all_publics);

                $array[] = array_search("Enseignants élémentaire", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::PERDIR && $this->type_in_identity_vector == PublicsByFunctionUser::Type_LYC){
                $array[] = array_search("Personnels de direction", $this->all_publics);

                $array[] = array_search("Enseignants lycées", $this->all_publics);

                return $array;
            }
            if($this->fonction_in_identity_vector == PublicsByFunctionUser::PERDIR && $this->type_in_identity_vector == PublicsByFunctionUser::Type_CLG){
                $array[] = array_search("Personnels de direction", $this->all_publics);

                $array[] = array_search("Enseignants collèges", $this->all_publics);

                return $array;
            }
        }
        return false;
    }
}


///////////////////////////////////
///// CLASS CRON NOTIFICATION /////
///////////////////////////////////

/**
 * Class NotificationNewCourses. Class qui traite les envoi de notification aux utilisateurs afin de leur informer de
 * l'arrivée de nouvelles offres en rapport avec leurs publics favoris.
 */
class NotificationNewCourses{
    private $users;
    private $tab;

    /**
     * NotificationNewCourses constructor.
     * @param string $tab
     * @throws dml_exception
     */
    public function __construct($tab = "course"){
        $this->tab = $tab;
        $this->users = $this->get_all_users_with_notification_allowed();
    }

    /**
     * Fonction de configuration de la tâche planifiée du plugin local magistere offers. Cette tâche gère l'envoi de notification
     * en rapport aux arrivées de nouvelles offres sur l'offre de parcours et de formation. Si l'utilisateur a décidé
     * d'obtenir une notification en rapport avec son choix de publics favoris, ce même utilisateur l'a recevra,
     * si besoin selon les nouveautés, tous les mardis à 2h du matin.
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function notification_cron(){
        $site = get_site();

        // send notifications to a large number of users.
        $mailcount  = 0;
        $errorcount = 0;

        if($this->users && count($this->users) > 0){

            $subject = get_string('email_title_parcours','local_magistere_offers');
            if($this->tab == 'formation'){
                $subject = get_string('email_title_formation','local_magistere_offers');
            }

            foreach ($this->users as $userto){
                core_php_time_limit::raise(120); // terminate if processing of any account takes longer than 2 minutes
                mtrace('Processing user : ' . $userto->id);

                // set this so that the capabilities are cached, and environment matches receiving user
                cron_setup_user($userto);

                $publics = new PublicsByFunctionUser($userto->id, $this->tab);
                if($this->tab == 'formation'){
                    $publicids = $publics->get_favorite_formation_publics();
                } else {
                    $publicids = $publics->get_favorite_course_publics();
                }

                mtrace('Favorite publics : ' . $publicids);

                if($publicids){
                    $filters = new stdClass();
                    $filters->publics = $publics->prepare_publics_for_checkboxes_form();

                    $courses = $this->get_courses($filters);
                    if(isset($courses) && count($courses) > 0) {
                        $notificationmessagetext = $this->notification_make_mail_text($userto, $courses, $publicids);
                        $notificationmessagehtml = $this->notification_make_mail_html($userto, $courses, $publicids);

                        // Send the email now!
                        mtrace('Sending ', '');

                        $mailresult = email_to_user($userto, $site->shortname, $subject, $notificationmessagetext, $notificationmessagehtml);
                        if (!$mailresult) {
                            mtrace("Error: local/magistere_offers/lib.php notification_cron(): Could not send out mail to user $userto->id" .
                                " ($userto->email) .. not trying again.");
                            $errorcount++;
                        } else {
                            $mailcount++;
                        }

                        mtrace('notification magistere offers : ' . $subject . ' for user : id = ' . $userto->id . ' email = ' . $userto->email);
                    }
                }
            }
        }

        mtrace('mail count : '.$mailcount);
        mtrace('error count : '.$errorcount);
        // release some memory
        unset($mailcount);
        unset($errorcount);

        return true;
    }

    /**
     * Fonction qui récupère l'ensemble des offres de parcours ou de formation filtrées par les publics choisis
     * par l'utilisateur.
     * @param null $filters
     * @return array
     */
    private function get_courses($filters = null){
        $courses = new OfferCourse($filters, $this->tab, 0,true);

        return $courses->get_all_course_offers();
    }

    /**
     * Fonction qui récupère l'ensemble des utilisateurs souhaitant obtenir une notification.
     * @return array
     * @throws dml_exception
     */
    private function get_all_users_with_notification_allowed(){
        global $DB;

        if($this->tab == 'formation'){
            $name_user_pref = "local_magistere_offers_formation_notification";
        } else {
            $name_user_pref = "local_magistere_offers_course_notification";
        }
        return $DB->get_records_sql("
        SELECT u.* FROM {user} as u 
        LEFT JOIN {user_preferences} up ON (up.userid = u.id)
        WHERE up.name LIKE '".$name_user_pref."' AND up.value = '1'");
    }

    /**
     * Fonction permettant de récupérer les publics sur la base de données centralisée.
     * @param $publicids
     * @return array|bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function get_publics_by_stringids($publicids){
        $DBC = get_centralized_db_connection();
        if($publicids){
            return $DBC->get_records_sql('SELECT * FROM {local_indexation_publics} WHERE id IN('.$publicids.')');
        } else {
            return false;
        }
    }

    /**
     * Builds and returns the body of the email notification in plain text.
     * @param $userto
     * @param $courses
     * @param $publicids
     * @return string The email body in plain text format.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function notification_make_mail_text($userto, $courses, $publicids){
        global $CFG;

        $courses_list = '';
        foreach($courses as $course){
            if($this->tab == "course"){
                if($course->course_demourl){
                    $courses_list .= '- '.$course->fullname.' ('.$course->course_demourl.') (Parcours en démonstration) '."\n".'';
                } else {
                    $courses_list .= '- '.$course->fullname.' ('.$course->course_url.') '."\n".'';
                }
            } else {
                if($course->source == 'local'){
                    $courses_list .= '- '.$course->fullname.' ('.$course->course_url.'?id='.$course->courseid.') '."\n".'';
                } else {
                    $courses_list .= '- '.$course->fullname.' ('.$course->course_url.') '."\n".'';
                }
            }
        }
        $courses_list .= "\n";

        $data = new stdClass();
        $data->username = $userto->firstname ." ". $userto->lastname;
        $data->courses_list = $courses_list;

        $message_intro = get_string('email_message_intro_parcours','local_magistere_offers', $data);
        if($this->tab == 'formation'){
            $message_intro = get_string('email_message_intro_formation','local_magistere_offers', $data);
        }
        $message_intro .= "\n";

        $publics_list = '';
        foreach($this->get_publics_by_stringids($publicids) as $public){
            $publics_list .= '- '.$public->name.''."\n".'';
        }
        $publics_list .= "\n";
        $publics_message = get_string('email_message_publics','local_magistere_offers', $publics_list)."\n";

        $link_preference = get_string('email_message_preference_link', 'local_magistere_offers').'('.$CFG->wwwroot.'/local/magistere_offers/index.php?v=parcours&action=changepref)'."\n";
        if($this->tab == 'formation'){
            $link_preference = get_string('email_message_preference_link', 'local_magistere_offers').'('.$CFG->wwwroot.'/local/magistere_offers/index.php?v=formation&action=changepref)'."\n";
        }

        $message_outro = get_string('email_message_outro', 'local_magistere_offers');

        $posttext = "\n---------------------------------------------------------------------\n";
        $posttext .= $message_intro;
        $posttext .= "---------------------------------------------------------------------\n";
        $posttext .= $publics_message;
        $posttext .= "---------------------------------------------------------------------\n";
        $posttext .= $link_preference;
        $posttext .= "---------------------------------------------------------------------\n";
        $posttext .= $message_outro;
        $posttext .= "\n\n";

        return $posttext;
    }

    /**
     * Builds and returns the body of the email notification in html format.
     * @param $userto
     * @param $courses
     * @param $publicids
     * @return string The email text in HTML format
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function notification_make_mail_html($userto, $courses, $publicids) {
        global $CFG, $OUTPUT;
        $posthtml = '<head>';
        $posthtml .= '</head>';

        $courses_list = "<ul>";
        foreach($courses as $course){
            $courses_list .= '<li>';
            if($this->tab == "course"){
                if(isset($course->course_demourl) && $course->course_demourl != null){
                    $courses_list .= '<a target="_blank" href="'.$course->course_demourl.'">'.$course->fullname.' (Parcours en démonstration)</a>';
                } else{
                    $courses_list .= '<a target="_blank" href="'.$course->course_url.'">'.$course->fullname.'</a>';
                }
            } else {
                if($course->source == 'local'){
                    $local_url = $course->course_url.'?id='.$course->courseid;
                    $courses_list .= '<a target="_blank" href="'.$local_url.'">'.$course->fullname.'</a>';
                } else {
                    $courses_list .= '<a target="_blank" href="'.$course->course_url.'">'.$course->fullname.'</a>';
                }
            }
            $courses_list .= '</li>';
        }
        $courses_list .= '</ul>';

        $data = new stdClass();
        $data->username = $userto->firstname ." ". $userto->lastname;
        $data->courses_list = $courses_list;
        $message_intro = get_string('email_message_intro_parcours','local_magistere_offers', $data);
        if($this->tab == 'formation'){
            $message_intro = get_string('email_message_intro_formation','local_magistere_offers', $data);
        }

        $publics_list = '<ul>';
        foreach($this->get_publics_by_stringids($publicids) as $public){
            $publics_list .= '<li>'.$public->name.'</li>';
        }
        $publics_list .= '</ul>';
        $publics_message = get_string('email_message_publics','local_magistere_offers', $publics_list);

        $link_preference = '<a target="_blank" href="'.$CFG->wwwroot.'/local/magistere_offers/index.php?v=parcours&action=changepref">'.get_string('email_message_preference_link', 'local_magistere_offers').'</a>';
        if($this->tab == 'formation'){
            $link_preference = '<a target="_blank" href="'.$CFG->wwwroot.'/local/magistere_offers/index.php?v=formation&action=changepref">'.get_string('email_message_preference_link', 'local_magistere_offers').'</a>';
        }

        $message_outro = get_string('email_message_outro', 'local_magistere_offers');

        $posthtml .= '<body id="notif_offers_email">';
        $posthtml .= '<p>'.$message_intro.'</p>';
        $posthtml .= '<p>'.$publics_message.'</p>';
        $posthtml .= '<p>'.$link_preference.'</p>';
        $posthtml .= '</br>';
        $posthtml .= '<p>'.$message_outro.'</p>';
        $posthtml .= '</body>';

        $posthtml .= '<footer style="text-align:center;">';
        $posthtml .= '<div style="margin:auto;display:inline-block;">
            <img src="data:image/png;base64,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">
            <img style="margin-left: 10px;" src="data:image/jpeg;base64,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+t1/8/s/wiSmlT1S6zK6fQ+mpzMh7xuLH1lnp0NvaamXN93ue6r1G+z0/wDttVMjrDaYGXh44OQwuxryP0Zyd1rfsd8jfXZkto3Y13+Ht/Vv5/0ftGy/o+A8M3McXVOD6n+pYHscGGj9FaH+rX+ic+vax/56k/pXTrKXUW0Mspeyut1T/c3bUd9HsfLW+k872u/fSU5DOr3g519WE227FsvrDKanmx7an1Mda2yG13P2W+q/EY/17dnp0erYo0dcxnXYRbVjDH6h+jZmBrhVa51j634brNm/DzW1srf9izWfp8qz7DXZ62PYtf8AY/T4eBWQLLHXOAe8D1XOFrrmgP8AZbvb9Nn0EzOidLY4ObTqHNefc8hz2Pdk123NL9t1rMix13qXb3+r70lNfoHUsjqFDX2VsqYGT6bGvaGkPtp2tfY1tVrP0H+D/m/8xW+rf8lZv/he3/qHJ8TpuHhBoxmFgY0sYC5zgA53qP2tsc7bvf8ASTdW/wCSs3/wvb/1DklP/9KhX/NV/wBRv5ApLzdJJT6QkvN0klPpCS83SSU+kJLzdJJT6JkfzD/7P/VsRXcn4rzZJJT6QkvN0klPpCS83SSU+kJLzdJJT6QkvN0klPpCZ/8ANv8A6jvyFecJJKf/2f/tFORQaG90b3Nob3AgMy4wADhCSU0EBAAAAAAAKxwBWgADGyVHHAIAAAIAAhwCBQAXMjAxNF9NRU5sb2dvX2hvcml6b250YWwAOEJJTQQlAAAAAAAQWDW9qyYQReGD1gdPdZ0kUThCSU0D7QAAAAAAEABIAAAAAQACAEgAAAABAAI4QklNBCYAAAAAAA4AAAAAAAAAAAAAP4AAADhCSU0EDQAAAAAABAAAAB44QklNBBkAAAAAAAQAAAAeOEJJTQPzAAAAAAAJAAAAAAAAAAABADhCSU0nEAAAAAAACgABAAAAAAAAAAI4QklNA/UAAAAAAEgAL2ZmAAEAbGZmAAYAAAAAAAEAL2ZmAAEAoZmaAAYAAAAAAAEAMgAAAAEAWgAAAAYAAAAAAAEANQAAAAEALQAAAAYAAAAAAAE4QklNA/gAAAAAAHAAAP////////////////////////////8D6AAAAAD/////////////////////////////A+gAAAAA/////////////////////////////wPoAAAAAP//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bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIiB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0idXVpZDo1RDIwODkyNDkzQkZEQjExOTE0QTg1OTBEMzE1MDhDOCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpDNTlGNkZGQ0QxMzkxMUU4OTFDNUJBQzk5MjMyMUFBRiIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDowREJDQzI4NDY5MUJFQTExQjE3RjgzRDc2RjJEQTJCMSIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBJbGx1c3RyYXRvciBDQyAyMi4xIChNYWNpbnRvc2gpIiB4bXA6Q3JlYXRlRGF0ZT0iMjAxOS0xMi0wM1QwMToxMDo1NyswMTowMCIgeG1wOk1vZGlmeURhdGU9IjIwMTktMTItMTBUMTc6MjQ6MjArMDE6MDAiIHhtcDpNZXRhZGF0YURhdGU9IjIwMTktMTItMTBUMTc6MjQ6MjArMDE6MDAiIGRjOmZvcm1hdD0iaW1hZ2UvanBlZyIgcGhvdG9zaG9wOkNvbG9yTW9kZT0iMyIgdGlmZjpPcmllbnRhdGlvbj0iMSIgdGlmZjpYUmVzb2x1dGlvbj0iNzIwMDAwLzEwMDAwIiB0aWZmOllSZXNvbHV0aW9uPSI3MjAwMDAvMTAwMDAiIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiIHRpZmY6TmF0aXZlRGlnZXN0PSIyNTYsMjU3LDI1OCwyNTksMjYyLDI3NCwyNzcsMjg0LDUzMCw1MzEsMjgyLDI4MywyOTYsMzAxLDMxOCwzMTksNTI5LDUzMiwzMDYsMjcwLDI3MSwyNzIsMzA1LDMxNSwzMzQzMjtDNENFOTFCMUJEMzYxQkUyRUY3MkZGQjlGRjU2NTFGNyIgZXhpZjpQaXhlbFhEaW1lbnNpb249IjIwMCIgZXhpZjpQaXhlbFlEaW1lbnNpb249IjYxIiBleGlmOkNvbG9yU3BhY2U9IjY1NTM1IiBleGlmOk5hdGl2ZURpZ2VzdD0iMzY4NjQsNDA5NjAsNDA5NjEsMzcxMjEsMzcxMjIsNDA5NjIsNDA5NjMsMzc1MTAsNDA5NjQsMzY4NjcsMzY4NjgsMzM0MzQsMzM0MzcsMzQ4NTAsMzQ4NTIsMzQ4NTUsMzQ4NTYsMzczNzcsMzczNzgsMzczNzksMzczODAsMzczODEsMzczODIsMzczODMsMzczODQsMzczODUsMzczODYsMzczOTYsNDE0ODMsNDE0ODQsNDE0ODYsNDE0ODcsNDE0ODgsNDE0OTIsNDE0OTMsNDE0OTUsNDE3MjgsNDE3MjksNDE3MzAsNDE5ODUsNDE5ODYsNDE5ODcsNDE5ODgsNDE5ODksNDE5OTAsNDE5OTEsNDE5OTIsNDE5OTMsNDE5OTQsNDE5OTUsNDE5OTYsNDIwMTYsMCwyLDQsNSw2LDcsOCw5LDEwLDExLDEyLDEzLDE0LDE1LDE2LDE3LDE4LDIwLDIyLDIzLDI0LDI1LDI2LDI3LDI4LDMwOzgzQkQ0MUM2QkZFQ0IyRjIxQTVGNEE0MDE1NjlGMTk0Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6ODZhOTIyMTAtODExNi00MjMxLWJjZjItYjk0MDIzZmU4OTkxIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjg2YTkyMjEwLTgxMTYtNDIzMS1iY2YyLWI5NDAyM2ZlODk5MSIvPiA8eG1wTU06SGlzdG9yeT4gPHJkZjpTZXE+IDxyZGY6bGkgc3RFdnQ6YWN0aW9uPSJzYXZlZCIgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDowQ0JDQzI4NDY5MUJFQTExQjE3RjgzRDc2RjJEQTJCMSIgc3RFdnQ6d2hlbj0iMjAxOS0xMi0xMFQxNzoyNDoyMCswMTowMCIgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIENTNCBXaW5kb3dzIiBzdEV2dDpjaGFuZ2VkPSIvIi8+IDxyZGY6bGkgc3RFdnQ6YWN0aW9uPSJzYXZlZCIgc3RFdnQ6aW5zdGFuY2VJRD0ieG1wLmlpZDowREJDQzI4NDY5MUJFQTExQjE3RjgzRDc2RjJEQTJCMSIgc3RFdnQ6d2hlbj0iMjAxOS0xMi0xMFQxNzoyNDoyMCswMTowMCIgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIENTNCBXaW5kb3dzIiBzdEV2dDpjaGFuZ2VkPSIvIi8+IDwvcmRmOlNlcT4gPC94bXBNTTpIaXN0b3J5PiA8ZGM6dGl0bGU+IDxyZGY6QWx0PiA8cmRmOmxpIHhtbDpsYW5nPSJ4LWRlZmF1bHQiPjIwMTRfTUVObG9nb19ob3Jpem9udGFsPC9yZGY6bGk+IDwvcmRmOkFsdD4gPC9kYzp0aXRsZT4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPD94cGFja2V0IGVuZD0idyI/Pv/uACFBZG9iZQBkQAAAAAEDABADAgMGAAAAAAAAAAAAAAAA/9sAhAABAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAgICAgICAgICAgIDAwMDAwMDAwMDAQEBAQEBAQEBAQECAgECAgMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwP/wgARCAA9AMgDAREAAhEBAxEB/8QA5AAAAgICAgMBAAAAAAAAAAAAAAgGBwUJAwQBCgsCAQEBAAEFAQEBAAAAAAAAAAAAAQcCBQYICQQDChAAAAQEBAUBBwUBAQAAAAAAAAYHCAECBAURIRIDMRYYOAkQMEBBIhc3GjITNhkKIDkRAAEEAgEBBAgEBAUFAQAAAAQCAwUGAQcIABESE5gUFdY3tzh42BAhFrYxIpcJMiM0NRdBUYE2dhgSAAIBAwMBAwQOCAcAAAAAAAECAwARBCExBRIQQQZRE5PTMEBhcSIyQjNzsxSUtAcg8IE0dNQVNaHBYpU2djf/2gAMAwEBAhEDEQAAAPbAupjpAAAAAAFdVokAAAAxxkQACjS/bJRyHZtNedMShz6de+jrnmqoOPZV1c8B7oBsS5p1Qa/keD1MVs0DW01JHbN5P1UuO+U3K0CbT2kF/MDSiZixn82X1V87A3I4KzD9GLy87/K7s+SfnB9Hv6/eW/n7jvZXwC3+Zj8ulMWcFNEnI+U6vRLsTkKhOwtpoxiQ9cBYo+YcafNi9VvOvcPgvNO3LCmV/bj6W9moFs3O/m3dIv664Z83Ifcd7K+AW/zMfl0pi9kSxWbS1RG2qVpKkvmk9lkBYtkni2TA2cm7bfp6ybwS3Pj+ma7Hu7S8M5Ku3BO1FL/hvvg2I8iwU5W741UxWzTSI1SYypQ9sWlty6b3iqKypII24NK/mArIS6v7csd8soXonp2h8ZGtRTFzQqa3alrn6E2W2rKrlmaVKttjstNaGBplYxZ5MGZg5THncMid4UxZGLAt4JnCDEDWZp0zJFcLHK2JTTWyrDTHwAAAAAAratIgAAAAAABRh//aAAgBAgABBQCPH3iPEpli5HMw9KSojpSVETtVU7akq9iWlqb9faEuW36yFIfWQpAtGi2muj/4+HxEfYR4t/jgsAygHIb11p0lBgt8lzscscZQiv8AHvbx4oB94Aq0DfPSnKvMtvKVTS1NHvVV4tdHNV1M9bVhFf497ePGxX27Fq7dQCwipXJWavclUw7wuFXVVVfVVqfE+41f00JA+mhHFmsNpL9P7ePH3iPH3iPH3j//2gAIAQMAAQUA1TDVMNURqmGqYaphqmGqYaphqmGqYRmmx1TDVMNUw1TDVMNUw1TDVMNUw1TDVMNUwhGOIMF9oi1Z+oVPx1Cp+NtwBE3tyn3Y7+wj6Rm5cD5/Vo5wf1aOcC8t+PrdDSJuPpCGIwhhhlkOAjwmhDCbCEfSHGPFYvtsIQjEITt23eUUI6baoiqru6f3B5YPvwJuMIYjTEQxxhjEYRxhCIz9MxhEQhj6Q4x4rD9tgm3LEm6V6KyVph2d/ZqNuwpwfDJslEubBOKg8sH35E3GEctQhHOGUdXzQjgIRwGQxzhNhDHKMcRDiLrarffLf9HE2FOlCeU0k5IK89FT02xSbBSei50iFnr2d0OvZ3QVBYFIWi9iPEQwiIQEeE2QjgIccPljwh+nH5RDjHjDjhEZj4cBnj6R9Jv1QhiNIhnGGcfjpGAwiMMsI4whj6x4+yjxhHCGoQjhERjjHViMRCOAxGOWrH0hxjhjkMhkMhkMhkMhkMhHDHIZDIZDIZDIZDIZDIZDIf/aAAgBAQABBQBnTKmbmBo3QoyEdCjIh0KMhHQoyEdCjIR0JshHQoyEdCjIR0JshHQoyEMjZYzgws96E2QjoUZCOhNkI6FGQjoUZCOhRkI6FGQiVlbCp7v0JshHQoyEdCjIR0JshDn2TMzszaWR9mLmnEp+01C/yW/HKPyW/HKKT/Sh48K+rLN53zEXnLuQTdqCQ/kDMMH5AzCw0F5SQPaT4ML7KPR86yqO21RDA5FxJvMh2cOvKImKvU91VZu0hsMSsW1MJlJubwWHr+4ZT1U8Y52V9UUP9HY9rDI+zHzgSxm8VwhCMY+A60pte/J4HkWwvGhvOxq/ZH+djtCDDOyhX3A2NHzfa39I6ZK9aVQSMqpGtszbiDe6Vx6eE1Bj4sLJrBvqcprZElvm9UNysm7fFbZ0TSOTnAs7RS7qa/VIUWsxWVosnQ5Oy7WGR9mHm8/8sB42dxrdGZWokxv57c/YTDYDVazet6QkapUc71amKGP87HaEGF9lC8o+oR9WDb8digbx7WdvSnqI0g9E1YVOR++MLUzcbgs7G1QXE3ri0tWlvvCipysaxW6wMQWQpJmuDET8rS2mprykH1b2uNBlawozse1hkfZi8WxpQaG57rFPC7RmMjMH8TVPbD6ljE08Mxedwg5AKy9Mq8fhpuO6zzxK2SSLRvEPHdZMRW2kFOQwzsoDuDwsDfDQ6xyCgpQbSaTiXtOYZMeIq7RImqryrcXlmW5W4tgJLlUkMD4kYPU9+dm5My3PdfvMQyvdPIuHY9rDI+zF3J+pkubmU3HFA0Wu+OURixUBuVNvsxhTE6poqh7JjrU6ulotrikJvt1vjgUKtFxaOfCqfSUGGdlC8L6YEhPUnkBJcppWFdLSlLW1pNJGQFHLLfEnU1BiQ/fbOd3o3H0tQVN106PSGMiuFLpwWyV/KKHRBzK7OYsGtODVXngiux7WGR9mNZRUdwptgrlmmq4lgtRmvKZJyYrNQlIrWyHLRc03cikq/F7cKhW3YUNrtlsiGGdlC3IXe1VUHZ8dexTnFXmw3xWGxmhBDmpt/Stqh3Qdu1gYJtEq+ytIN1DfVvYoV18s5VaNeigpJaYgbC4pZB8almTmrTgr3MlEp2Pawzkxun2Wi80O7HNDuxzQ7sc0O7HNDuxzQ7sc0O7HNDuxzQ7sc0O7DITG6fZZ3zQ7sc0O7HNDuxzQ7sc0O7HNDuxzQ7sc0O7HNDuxzQ7sc0O7HNDuw6AyOr3W0f/aAAgBAgIGPwA+2TXG+GeHMQ5LLZlTzjFUuqM56mAYj4KnuOtq+d4v7w3qa+d4v7w3qaeWXI4lYlBJY5LAAAXJJMVgANSe4VPjpkxzKjleuPqKPY26kLKrFT3EqCR3VLynIFvs6EAhenqNyB8EMy9R12Bva5tYGvms30S+sr5nN9EvrKnzeMWUQxydB84oU9QUNoAW0sw9h0q/6RrwT9NL+Gm7Lk6Vz8nFzmNTJAs1tzA8gR1B7gzMgbyp1KdCezlsKRQevHktcBrMFJUgEHUEAjS99taB9zs5P+NP1cftA14J+nl/Dzdiz8JiTZvILKPs+BErdDRKV87lZUysvTIoDDGHWqxsysqSTANFntzniXmOP5DkcVgOHyJcbkvOoRYTHIaMHDxhr50TL5xukqr9VrNj5eO8U4AJV1KsAwDAkEAi6kEaagg7Gp45+QgXIjQuUMiBwoF7lSbgEbG1ZWZIqh5ZGchQFUFiToo0A12HZyf8afq4/aBrD5zg8w4/K47ExyAKxUlSpNnDKbqxGoO9f82m9FjeprEln8bZRMMnWoVYkXq7utUjVZANwsgZQdbX1puXl5szcmZfOedmihndX7ihmjk6On5ATpCfJAtWRm5uQ8uZM5d3clmdmN2ZmNySSbknWsjOzuFWTLlcs7F5blj36OAPIALADQaV/YU9JN6yv7CnpJvWVJi8PhiGB36yAWa7WAv8JmOwHfb9AeyH2Db2b3O0+2T7Z//aAAgBAwIGPwDc1ua3NbmtzW5rc1ua3NbmtzR1O9fGNbmtzW5rc1ua3NfGNfGNbmtzXxjW/Zm85yIf7FjgFugAt8JlQWBKjdhuRpW2d6JPW1tneiT1tJFDFntKxsAIkJJOwA87qTUMzQvGzqD0vbqW4vZgCwBHeATr31xP5deCI4Tz+YsjIZjIsCCKNpGaWSOKXzS2UgO6hOsqpYFhX714Y/3B/wCVr958Mf7g/wDK1xfg/wDMKTAbl8vAXMj+yTGePzTyywjqYxx2frhe62OljfWwo9pHfQ07jVxvQv5KYW2oVt+tqI/QNeKPo4/r4uywGtcUmfGHk83KYb7CVULBrbEhQxXyN0kajs/LvxTizOhxOaw2fpleEPEciNZo3kR42WOSMsknwwpQsGutwZOk3XqNj5R3HTTX3NOzwN/1OD8dn9ho9lga3ret/wBR2AXo69mnYKNeKfoo/r4uxoeRnjx8QxXnzJCvUJCG6IIIyGJj1Hnz0kuoYM8cRIlxP6XwfGZmDhTqf6lBFPghHB1jEKv05ExsOgxN0DqVinSSaWbHlV4jezKQwNiQbEXGhBB8hBFcTm8P4P5Sfic3LXGiyUxMh8dpmcR9AmSMxsysQGUMWHeK8M+EsXImlx+MwIMVXmkaaRhBEsYLyuS8h+D8ZiTa3Z4G/wCpwfjs/sNEUfeq9A1f36A7ta9zsv7lAe//AI1ah5aHZk8VymOJcCYAOhLLcAhhqpVhYgHQjav+Lxeln9bWRHF4Xx+mVeluoyMQP9LO5MZ7iyFWtpe1f05OLEeB0dBjieSFWXvDCJ06+r5Ra5b5RN6hxcWFY8aNQqqoAVVAsAANAANABtXCeDfCH5r5WB4Y46AQ4+PFjYPRFGtyFHViszEklmZ2Z3YszMWJNf8AtOb924/+Ur/2nN+68f8AydYXiP8AM7xTLy3N42KuNHLJHDGVgV3kEYEEcSkB5Ha5Ut8K17AAUeweWrW1NCjbymiO69G/fW2t6Hvf51e3fX7ewdvd2bCjpRGld1d3affomt9K3q1a0de+hrQN9DW9WvrR19mNEURbuq9aVe1ftr3ezard1G/YKOtb1vW9b1vW9b1vW9b0da3ret63ret63ret63ret63r/9oACAEBAQY/AOLU9PcS+M03OTfHPSUvMzMvofVklLS8tJ60rJslKSkiZVXjD5GQMfW6++6tbrrq1KUrKs5z18m/FXy9aj9kOvk44q+XrUfsh18m/FXy9aj9kOvk34q+XrUfsh18m/FXy9aj9kOvk34q+XrUfsh18m/FXy9aj9kOvk34q+XrUfsh18m/FXy86j9kOvk34q+XrUfsh1xhnZ/iZxmnJuX0VrGRlpiX0PqySlJOQMqUW+WdISBlVeLNMKfWpbjri1LWvOc5znOevk34q+XnUfsh18m/FXy9aj9kOvk34q+XrUfsh18m/FXy9aj9kOvk34q+XrUfsh18m/FXy9aj9kOvk34q+XrUfsh07X0cTOIip4eOHmH4ROh9NKl2YgskoISVdjcVXJjccUYE8y2/lGGlusrThWVIVjHyb8VfLzqP2Q6+Tfir5etR+yHXyb8VfL1qP2Q6+Tfir5edR+yHXIeXh+I/GOJlorRm2pKLlI3QmqgZGNkQaDYCQjwDRao0SGaGS0lxp1tSVtrThSc4zjGeuI30xaF+FdU62DyG2m1YXqDrQCMkrG1VYxmZsCx5afia2LiNjHzY5kpzEjNM5XhT7eEt95Xbns7M/wC2cj/6UxHtz1/tnI/+lMR7c9CAR8DyYPkDyhgQAAtRRhJpxxjyBgwgxmbut4kssl1LbTaMZUtasJxjOc9Qs8VATlWfmIwOScrlmajmLDCKMZQ/6tm2ImSmI4eTFwvuvNslPpbcxlPfznGerFuzaz0k3Tqy9FsGsQbcYXPmPS8kNFisQsVJysRmZMw+Vhahx3FkeCla0oVhCuz/AEG+/wCmQHtd1/oN9/0yA9rup/ZemGbgzXK3by6RIpukCzXpHM0HEQ809kYRmSk0uhehzjHY5lac5X3sd38u3PXFH6ftU/syI/HQG/Ii7Gx+haBi3E8tKMW0C/XidIzFh1vRitwYdXHEy8VKaFnb4NYjXRX2mn64PJJeQ5ltjKOZ1hVtu4VCAheQf9sULSNbrnqCNxRdMcidm6/rFkii+9FySpKa2hTLA9JSDxa3yI5+RQwIodQqVZ2vQ9q7Gs5ukdw8jXNd8Yd6xuRg7hpnZwWwarC5407Klm4sgGVgtogOFPUieLZy8stZUGa7ktUQ8Rv96g2C+7nA1t/cB21U53S9X2HU6LvOd490/SNHnRavx9nrKLHV+XndfXe8NTT0TJmBFWAJHoa5ZCcIYI/t4XbXnLbkTK0rlFbZCi2eTyJVtcTR0RTuLO8rWS/IU8mirep+xcbN1wwTOI7uUNyA74rbaA/8nPIUSanNyW6P0zyH1DqSG2k1bNXwsTTNZWDjVpHcNwqVwjyIsay2Cu3nZcgrxmRx1qGJnGchkCpGxhuKo1u3DeS4EnUnMmeuiNoP1BJ+xiqdy4umntP2bjYbW4liVGI0vVqgRG3vBzjCWXpaCeyC66Zg1XHfa23bPt2UmNh8Xde2mWl75b9dS1P2hdbawBLzVnq1YrDKZqryNeFjMOKa7oIihJ3CMi5cYwtv8OS30/7k+HVj64jfTHoX4V1Trl7jHZ/6jSlfn/2TtnX6s/8Ansx+CU4xlSlZ7qUpxnKlK7M57Epx25znsx/060gFsaHFmnGoDZEtrts7DbgIGzIGpGTUDLvDu9rT5UbEgSLgfbjOWT8MvI7HG0Kx1sjXs/EmS7my6tY6TXGgYCMspIdtkq9KkV6WHiZVolhwiENDwchxLLrjORvERjCkpz01hxOUOYbRhxGcLwpC8JxhaFYc/wAzCkqxnGcK/mxn+P5/htn6irB8Pdc/hxR+n7VP7MiOtPUU2lX+6WbeVitFVooFHDqr6HJqo0ef2LLjy5lpt1UDi2v0nVzyGnVLU24sfwu9hxbaV60g6fWNpWy2bL2ls/SDdMCrULBWmjbd07TpS/X+g7HjbvaKoirzEXUoZ45h3LjwMgKph4Qh5koVx+P3/tXTNnnAJRNe1CbWT6nUJy7Axe877W9XZqUzFyNkxXi6zY7ZNRrEm2yeWC+OpshXijt+Im4v7B455s7lm1jL7/vlmjNd0+djpxri/iuH12PtB5UsKQbfKePJMk1XBSMijZCWkUth9DLblp2ht7QFjXrbYV+1ZtCuxtf1pQXArIDyC2xV67o6TmBH9pz4s3tBu2HQJ02cZmLJFNJbIbHw0G4+jYMdduPM3C7Jq279SYv9FVrqtxOyA79y4sgOmNebqDno6yiwVjhtly6cQJlphJw5xCAyAzVowK+y3A1e56qQIXoY3UQ+kB4ulVg9P663iVaNb62pmiosCSzKBXSa9Ryca/3RY0YON8dwoluPy87jee1b/wAeitYzms8Qm6Nn2C20GsvyVokYiBeloC6RM5TJe1xWw7TBA05oUZY5RMiESIMKjCFpZRjjTaqrxnPt2ttmzNEk+P1r19rXXAcBEXblw9MjgAQj1itVRkatY9gYJIdsbo7SBkYOxmTfSt/OM3CKjNFzOi53Seu9FxSITOs6vByoYnJ60GVjUOktdwVVm5Ut6z3K70j1f6mAYaBbJEYccdwP4L2dmyW2KvtCkyWm4zXNu2XVCa9BT9mrurtn2YmnV/cAYtPtVjAuGu4+ygFiSrsCTKSUU4G7kgNCcJUs2pVpo2XDZ13Rtoxl5jia/JUOzVLYpdjEq5lZmYycNLlMm/pUt3v+ioHyPhtxDq0ut5VyX+n/AHL8OrH1xG+mLQvwrqnXL7/46m/Fag/g/Ebhs9W1LRZKtFM7X5RXeWhv1HEXCSDkf0NpbSlDlYuZVLVaUMIHeuL2QHyZcQN4d4oCJVliRpjGnOPvFjeVF0VsuHKO5+aqpO4+IKKVPBnKdOobGqoazFtb83KahA+IRddIxFCYJSQ+N6P3kuMTdYm4ixQpLpjA0vBSQcvGEPxxpEbIMsHgPECPOAyIjrDyUrzlt5paFdik5xiwxdm2XQYqxVavFWmYqh9yrYNnDhRgXpDB70EZJsyTApAzKlNurbS2vH54V2dXvYx4kYAbfbhY7gUFCxQkHFDPWGWKlFtgw4KGw45rtJ7ctNpwnC85z2fn+G2vqKsHw81z+HFH6ftU/syI64pbOo66Y6Jx+2Dsi62CHtc3NwZFgYumkNhamj4+GNiKxZ2xXhDb0k15x9ru5aFy2lOVOd9Gv9l3CV1Zseck+W+8eWW9qvIkWiqVI2U2fxsc410+ia4dEgbLJ4jKLVIuGcfkJLCH5QoIh/wR/SksjQGjKujWFat0BcNFS8THrl7dnX8DUtIbxoWy4WqBzKoKQtMq+mnUAaIwa8G1l4txRSmkIx4Gd/UixM63r09sGk3Sia6YhZyxTkTEgWWjvQTEpb7CbU4KRfKVYZF951gONy2yI00lK3HFLziM0fS4bRFHlmYnhmqz2QOzbBkk3m0cW9s6yvZMial2miNV+NPrlBLBDaZGLfcKlUvEOpQJhBG5tz2K6UOD2rPN8VITS9Zi2bAfRKpT+J3Ikfk3ERd8sZAYE5ZJnad/WQNJGiRYrcLG4GQKwU62+4VGbeVO6+pG4tb7k0Dt3UdbSbYbZr1a9GBbMizK7f7EqArU6SjYcHuyzAqKBi05hM5AKbaNcHcaeqFetJ9XolVTsOs2m6A0G3zhdlahaQM7ZIGPh7PKUcESbfktnBxZJjT0dHMJiQHB8qIUUvw6RpSGudEkdcaZ5+1Lkzp1c9LWZ6xxmioy+EbSltXz77NYyM5ao24zkqxDus5yAxDvCDr7uRO87vDbgFxp8AROzvBPZelFEpmpFcXs3hTsLYewGQdixSI8Vgil7C/XSox1YBijAWUrJShx3uN4s/Imwu0EC4EU/Q2p6dSQp2fka7E661vvmL3tsOTsVkJqIZU9abpKgNggDNxQwQIwTeXHHVEveHux2m29ReiLqHSMaZ1MSIrC9DshT2zLRfKHV5PvZw9q420Xv1hX4zOMYr6SCQB+6A2GyzyW+n/cnw6sfXEb6Y9C/CuqdbBq27qMDsrWNocptcslHkp+Qq0fYFzl9q8ZCMG2GLfGOiBBLCSIS6+hxOENs5yrPc73VggjuFJjrEerKq5K18rfdoFuAosJLT0qXHYh7a82CmPBicqSghxCyUvN5Zwvtz3d1tU3guSeJXKqzEXxtupbTuNzcbkLBORS65QxSZ6eswtgLHg8lPO194c1UcUzhxeErU2mb45WPTAuv9KVmqUuhoi9d3PZdORbZ66iESyamRVNYWGvlTcUDT4502bPk1lOHIeTh5bnYtK2IWs1CVrWsalV4xigDVKqvJjHa7F1ZiRZioirxYDKoUeLab9CHHQleXlIxltPh/njcm7dscQpa4bLbo8Zua2TFgI20CJcbDa0TIcXS4i2InsV1+3sSVebCIjAm8qiGSQsqYbZfYTnIF04jOVq1ABPHWGvNFbmlExIsfZW6nMyY0iuyxvreDi5hsvtJaYSp5gB9xtpWPCw6GOniJPsvyQZB8d6yi93xYxQgRUWGa56UVasNjqHJk8tdxzCVLdFIQnGctK7LTF8W9Xnau14TeHjJCPNTZW8zFtXWa0maPaRaJOWL7YxKWot/LLyhvSwHsIyrGMrV1xR+n7VH7MiPw5l7rpdxvewuPL1Qdou/dTwE/O2G4cdLFLaXBlaNyb02FHHv2WBihLBNoHu0FG5Y8ON8GxAt4fjjmzddblpk5P/AP584S2HW8Ry0XHTVfMq8vX9uwY1c2Zi9KkrUxZDZjj5SrTXLoJ6MIWt9wshDyu8ju55qV8HZ+z5GB0voDjlfaWITyJ2tMAwp93p/IhdrsJw5V+KANHtgEZHFELIbWxlTA5DWG1ttLTrSA5ObStA+tY3+2Xwr2vqOal9t2qAEv1qvdUuRvKHdUvsRiyR8pZNnUy3AQob75Eg8/WxyGiW8Mrk1uL1HydmJ6+7JnqhxT17O8pOL5LhSbNujRs7tLdsFC8gKHr5TjLlS5OQur6bB2P1aEwC3amCz4dxGDsxjg+hJvi/M3y4TfHvSGnebO1GIizxJU5sGkMAx9jjNRbLXsG0A3TEfvHXY1xNyrwn5NmRiY/vI7HcozY9hDbzhkaas/8Abz0LuSpettm+ha/cCt+190OGX0CuHzyK0LKyNSh4pok9A+CfRmGm1L7v5Z5WvS92jpSn1PnXQK5AEzfJHZELY6fBz3FPRVoiKbQNOBKVXLDD2bblpw0RFukNtrXLn5UGpbOMdXykquEWxVQ+CNKuh9ctfJS+6MrVakyt73yBnNhxLdTdWL+rRaWIrCSXFRyspDHQopCM99uoa6B2luIiqWXh1b9/NRTW/dqs4PvTPIvXjkLcmYTFvajkjDw0m/HtCJDTHYi3FCKYyz2o/Dkt9P8AuT4dWPriN9MWhfhXVOtobALashA1XiI6QdYqFqkqVZX0qsEQLhqGskPFzUqCY5kjswgcR94rGcsIRlTuOog8Q7l5LyMyzpQEWpUzkdI2KxP2nc0TKT4kMG5IF14N1EBFgoaMbJSEeGel9ktgbDWVYpMk/cuZhg12g7PMj5A36p9yHdqdNrFvlYebwqwtthSrCbaKE41lWcsl95C+zPcwtqNNtvKi7GVeSqslFmR+82ptxSrIHo1waQo6XZvL9gnomQ3xFNEgBdpqEAlOoQvOBEE63okDsrlawXszXDmyYiUf5CSBcaCAPV6dZDIaTXFSBy2ZyOduTQRDeO1DZIz+MKV3U4VrQ29SHMOpSm1NJ2belYi4PkNYbMwZW4GLkSo2DjzJcanPzdpuctFrj4MUQd3Ms88w4N32vS1Bgw8LsfmO45PtUAunzEtu6ThKnZ4vYsZrAqNl2bSVLuRMWLWpraAsPPsmKZPiZVttlQ7mDo9ZVviBtl8xJU+iylij7DgPd8ooT0CsVeo2SUt8G+3LPv2ikNu2KQCTIRrJWXya3KYZadwy141yMqEzsadjYLZl0qT0hsy7KvMyQdUZcirGkRcmogrDVcNKhFvhoQtSFodyv8lqcQjrij9P2qf2ZEdce6BXNcMX2X5C3+0a7hiSrozUI+tytY1fddsOmzDjldsBBEaZX6CeyjI7a3UlqZTlHhrW43V6nD6PGruxLzyN2Vxb29DbDt1ToONe7V1npeU3kh232aOirVGXCnXTV4rB0FJiOPqKZlBEOMMrUS2M/v03StUtTFondZj2CiQdqq5sRYnNobIq2t4CxC3JmALgrdGKLtAEi2U6O2p6N7Vpxh5KWVbK28brBmyv1ukEOPUeh1yLkLXsQ9sVmPrmtK4woMLE3J2mZWLFRw7+EMqeeawpKEYz3ahtii0+lXmiSdBj9tayjSoyHGhiBZevYtEIRHLdipEavmGMFpwp9sfLjDi1d5PbjOOqhJxWiKVX7RsTiNpjlOCVbtzVWqy8hUNzTNhhqlrViZMpmW5S6iyNcShbCiERnjFjoQTnKs5TzIt7GhoZsrjZsEvUk7HqssGh7ajMFr2gXx8jB7dXWmNih6xscMdgY1L/AGktPtfyNYbdc5HaxnuNlWiN2cbWgJyNocwzVm4PburyH6wNnZWobs7U8Ny0BW5mztxk0M4AOZCS2GmSm0NGBEEbGpo2gtWRll1Vv9zT01L52BT87EmJZOsqtts690ipuUoKfsQIdUuSHXmklMSSsiGZbbcw0nxZ/llYtJ0ib1GJXaaezsZdkqNrDrEtZ9kwmsiqNvg5+rpP0jZtTFT7Mjd2TkHjVeLYLcU6+sR1vGhnJPRlFDsm87Rsqj1bYUhtqosUvELQNcm7OjrdCbKEqciTPa/vlXjyPVjqBhn8P4Sh8ZrClKRV7fJQo1fMsUSPKqiwbBG2yOaZK7yxCYuzRCW46eiZETuEiFNIbw8O8hWUIVnKcclvp/3J8OrH1xG+mPQvwrqnTocgIMcI9hOHhTGGiRncIWlxGHWHkLacwhxGFY7cZ7FYxn+OOlHjV2CHOW4l5RrERHtFqeQ88Ql1RLY6XsuJIIccwrvduFrUr+Ks5ylea9B5Wl6RISvMSBlSSJhOUS76Vej9uHpROewlX+J/H+PKuk16codQlYNsgcxmKNrsS+CMaJ4HoZwg6hO4IeHkZrLL7WEOtZbRlCsZSnsj/V1bggsxLJI8YoWJAZcj2Te/k1oNxDGHB0G5cVl7CM48XKs5V25znoRP6fhO7H4ESAn1UB3QkgOuPg4Ex4HYNgJ95a2u53fDWvOU9mc56l6lNVKuSdXnwjY2brxkNHvQ8qBJIabkAz49Y+RShzUMIw6lac4XhCe3t7uOxKXa1AOJQygdCXIeOXhI7bi3m2E4UNnCWW3nVLSnH8uFKznGO3Oei8xscDH5PKcOOyCIOJk057CUumF5Ybb9IKdwjGFOL7VqxjHbn8vw4o/T9qn9mRHXHLYcFdoypn8eNjWvYwUfK1Em0hWoy0aj2BqLMWaoS2VYiLEBjNhlGYcbU6twgdpOcJR3+9Rdkv7Rj7Rf43k5s7lZtKTvOsw5+A2hfNhaIleOsVXh60Ja4dFNpGu9akgCw4uCJInGYplb77zzpLz2OPK9hQFXLRYtezQdng9aMj1qDi9Z7YrWzqtU69r4W2hJj4COEqQUK0lcq88kJGXFLW7ntxVidt7KirJTqxdv1uxSajULJr5tchFVd2Gp/g2SP2bJz7JEBPSRsu84pxxBZGREJaH9Dwt63cftQ7ZgYeI/5BuM5qB6xa4NsETq3V112A/czdNvRmb+JJ2qKg4uXkoiJk1SAZIQr4+ctOeiYQ7WpOp3mmyIFM4h6V4l1SP2fqEfYL0UFo+Xsk7Wtj5KRd62M/YypSwIedFSM0OlwJlSVYzjPW9o2L2xHuar5K3uubH2nX5qjKLuothBoWuddXUWlWuNs8RCRUTsSC1oG46gmHLXFFklLGypDjDQx6bZc5CCu0VvP/mnV2yKVFKg7TQGDBqtD2nXpjz0xINW2l7FqlddiLGG96OHJhl4X6O0SKI+zuLYsbe6M5Jbb5A43m3MF6eZKvtEZXq+jakNqFRuZF4eZH9ZVOlKackXY5akYlTEoHxhTeW7bu6N2tRoDcF511rbXGyLDUdMNwdU3wzS7rXLRYbnv6hZvZUfeb3ba/EnVtqQYJBIi4eaNQhx5Kh2htWk1S00GFg9c753nvljUjWpnTtKRJu8NYu6zPoGuKQ9exv+PaHB+mFziRGHiGn5w8p5toVh70dMDVpaViZgqEGeCaIgKyPTa+HGILIVCwcBVxj5VELBVuHUxHhsqKJd9HGRlx1bmVKzyW+n/cvw6sfXFlmD03x/koRrjlpFqHkZbkrsWElD4pvWlZTHmyUMHxPnxIiQKEwhx4Vo81sdxWW0kPJThxXuO44eajZ32cde47jh5qNnfZx17juOHmo2d9nHXuO44eajZ32cde47jh5qNnfZx17juOHmo2d9nHXuO44eanZ32cde47jh5qNnfZx17juOHmo2d9nHXuO44eajZ32cdcYGoPTegJGGb0TrFEWfLcldiwsmYAmoxeBST4gTifPixpbzOMKcYbOLQ0rOUpdcxjvZ9x3HDzUbO+zjr3HccPNRs77OOvcdxw81Gzvs469x3HDzUbO+zjr3HccPNRs77OOvcdxw81Gzvs469x3HDzUbO+zjr3HccPNTs77OOvcdxw81Gzvs469x3HDzUbO+zjr3HccPNRs77OOvcdxw81Gzvs465ENS+mePgMU5ovbaJM2O5M7HlZAOPXQLAkwoGLJ4lQw0kYOPlS2mHDBUPLxhCnm8ZytP/9k=" >
            </div>';
        $posthtml .= '</footer>';

        return $posthtml;
    }

}
