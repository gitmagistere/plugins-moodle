<?php

/**
 * workflow local plugin
 *
 * Fichier librairie de fonctions pour le plugin workflow.
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2018
 *
 */

/**
 * Definition des constantes (en vue d'une modification des intitules)
 */

define("WKF_CAT_GAB",     "Gabarit");
define("WKF_CAT_PDF",     "Parcours de formation");
define("WKF_CAT_PED",     "Parcours en démonstration");
define("WKF_CAT_SDF",     "Session de formation");
define("WKF_CAT_SAF",     "Session en auto-inscription");
define("WKF_CAT_SLAF",    "Session locale en auto-inscription");
define("WKF_CAT_ARC", 	  "Archive");
define("WKF_CAT_TRASH",   "Corbeille");

define("WKF_IND_COLL_AUTOFORMATION",        "autoformation");
define("WKF_IND_COLL_ESPACE_COLLABORATIF",  "espacecollab");
define("WKF_IND_COLL_RESEAU",               "reseau");
define("WKF_IND_COLL_VOLET_DISTANT",        "volet_distant");

define("WKF_IS_SESSION_PREPARATION",    1);
define("WKF_IS_SESSION_COURS",          2);
define("WKF_IS_SESSION_ARCHIVE",        3);

function get_wf_role($shortname){
    if (!in_array($shortname, array('participant','tuteur','formateur','concepteur'))){
        return false;
    }
    $roleid = get_config('local_workflow','role_'.$shortname);
    if ($roleid !== false){
        $role = $GLOBALS['DB']->get_record('role', array('id'=>$roleid),'shortname');
        if ($role === false){
            return $shortname;
        }else{
            return $role->shortname;
        }
    }
}

define("WKF_ROLE_PARTICIPANT",   get_wf_role('participant'));
define("WKF_ROLE_FORMATEUR",     get_wf_role('tuteur'));
define("WKF_ROLE_TUTEUR",        get_wf_role('formateur'));
define("WKF_ROLE_CONCEPTEUR",    get_wf_role('concepteur'));

function get_wf_role_name($name){
    if (!in_array($name, array('participant','tuteur','formateur','concepteur'))){
        return false;
    }
    $roleid = get_config('local_workflow','role_'.$name);
    if ($roleid !== false){
        $role = $GLOBALS['DB']->get_record('role', array('id'=>$roleid),'id,name,shortname,archetype');
        if ($role === false){
            return $name;
        }else{
            return role_get_name($role);
        }
    }
}

define("WKF_ROLE_NAME_PARTICIPANT",   get_wf_role_name('participant'));
define("WKF_ROLE_NAME_FORMATEUR",     get_wf_role_name('tuteur'));
define("WKF_ROLE_NAME_TUTEUR",        get_wf_role_name('formateur'));
define("WKF_ROLE_NAME_CONCEPTEUR",    get_wf_role_name('concepteur'));


function issetConfig(){
    return !(get_config('local_workflow','role_participant') == false
        || get_config('local_workflow','role_tuteur') == false
        || get_config('local_workflow','role_formateur') == false
        || get_config('local_workflow','role_concepteur') == false);
}

function issetCourseCat(){
    return 
           $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_GAB,  'depth'=>1)) !== false
        && $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_PDF,  'depth'=>1)) !== false
        && $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_PED,  'depth'=>2)) !== false
        && $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_SDF,  'depth'=>1)) !== false
        && $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_SAF,  'depth'=>2)) !== false
        && $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_SLAF, 'depth'=>2)) !== false
        && $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_ARC,  'depth'=>1)) !== false
        && $GLOBALS['DB']->get_record('course_categories', array('name'=>WKF_CAT_TRASH,'depth'=>1)) !== false
    ;
}

function isIndexationAvailable(){
    return (file_exists($GLOBALS['CFG']->dirroot.'/local/indexation/lib.php') && get_config('local_workflow','enable_indexation') == true);
}

function isCourseHubAvailable(){
    return (file_exists($GLOBALS['CFG']->dirroot.'/local/coursehub/CourseHub.php') && get_config('local_workflow','enable_coursehub') == true);
}

function isOptimizerAvailable(){
    return (file_exists($GLOBALS['CFG']->dirroot.'/local/magisterelib/CourseFilesOptimizer.php') && get_config('local_workflow','enable_optimizer') == true);
}

function isVIAAvailable(){
    return (file_exists($GLOBALS['CFG']->dirroot.'/mod/via/version.php'));
}


function local_workflow_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;
    
    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }
    
    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('moodle/backup:backupcourse', context_course::instance($PAGE->course->id))) {
        return;
    }
    
    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $name = get_string('local_workflow_course_settings_label', 'local_workflow');
        $url = new moodle_url('/local/workflow/index.php', array('id' => $PAGE->course->id));
        $workflownode = navigation_node::create(
            $name,
            $url,
            navigation_node::NODETYPE_LEAF,
            'workflow',
            'workflow',
            new pix_icon('i/settings', $name)
            );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $workflownode->make_active();
        }
        $settingnode->add_node($workflownode,'turneditingonoff');
    }
}


/**
 * Fonction qui permet de demarrer le workflow pour un parcours. Cette fonction est utilisee pricipalement
 * pour la page index du workflow.
 * @param $course_id
 * @param bool $is_title
 * @return mixed|WFStepGabarit|WFStepParcours
 */
function start_workflow($course_id, $is_title=false){
    global $CFG;
    require_once($CFG->dirroot.'/local/workflow/lib/steps/WFStep.php');

    $workflow = new WFStepGabarit($course_id, $is_title);
    $i = 0;

    while(!$workflow->isValidateState() && $i < 6){
        $workflow = $workflow->getNextStep();
        $i++;
    }

    return $workflow;
}

/**
 * Fonction qui determine la categorie principale dans lequel de se trouve le parcours.
 * @return mixed
 */
function wf_get_main_category(){
    global $PAGE;
    foreach ($PAGE->categories as $cat){
        if($cat->name==WKF_CAT_GAB
            ||$cat->name==WKF_CAT_PDF
            ||$cat->name==WKF_CAT_SDF
            ||$cat->name==WKF_CAT_SLAF
            ||$cat->name==WKF_CAT_ARC
            ||$cat->name==WKF_CAT_TRASH){
            return $cat->name;
            break;
        }
    }
}

/**
 * Fonction qui indique si l'indexation a ete saisie completement pour un parcours selon les regles etablies par le processus de workflow.
 * @param $course_id
 * @return bool
 * @throws dml_exception
 * @throws moodle_exception
 */
function indexation_achievement($course_id){
    global $DB;
    if(!isIndexationAvailable() && !function_exists('get_centralized_db_connection')){
        return true;
    }
    $DBC = get_centralized_db_connection();
    $collab_autoformation_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_AUTOFORMATION));
    $collab_espace_collab_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_ESPACE_COLLABORATIF));
    $collab_reseau_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_RESEAU));
    $volet_distant_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_VOLET_DISTANT));

    $course = $DB->get_record('course', array('id' => $course_id));
    $indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));

    if(!$indexation){
        return false;
    }

    foreach((array)$indexation as $key => $value){
        if($value == null){
            // Cas du champs enddate non obligatoire : si la collection choisie est Espace Collaboratif, Reseau, Autoformation ou Volet Distant
            if($key == 'enddate' && $indexation->collectionid != $collab_autoformation_id
                && $indexation->collectionid != $collab_espace_collab_id
                && $indexation->collectionid != $collab_reseau_id
                && $indexation->collectionid != $volet_distant_id
                && !$course->enddate){
                return false;
            }

            if(($key == 'departementid' || $key == 'academyid') && $indexation->origin != 'academy'){
                continue;
            }

            if($key == 'originespeid' && $indexation->origin != 'espe'){
                continue;
            }

            // Champs de l'indexation non obligatoire
            if( $key == 'indexationtitle' ||
                $key == 'description' ||
                $key == 'objectif' ||
                $key == 'accompagnement' ||
                $key == 'validateby' ||
                $key == 'updatedate' ||
                $key == 'contact' ||
                $key == 'thumbnailid' ||
                $key == 'certificatid' ||
                $key == 'videoid' ||
                $key == 'rythme_formation' ||
                $key == 'entree_metier'){
                continue;
            }

            // Cas où la collection choisie est Espace Collaboratif, Reseau ou Volet Distant : Champs tps_a_distance non obligatoire
            if($key == 'tps_a_distance'
                && ($indexation->collectionid == $collab_espace_collab_id
                    || $indexation->collectionid == $collab_reseau_id
                    || $indexation->collectionid == $volet_distant_id)){
                continue;
            }

            // Cas où la collection choisie est Espace Collaboratif, Reseau ou Volet Distant : Champs tps_en_presence non obligatoire
            if($key == 'tps_en_presence'
                && ($indexation->collectionid == $collab_espace_collab_id
                    || $indexation->collectionid == $collab_reseau_id
                    || $indexation->collectionid == $volet_distant_id)){
                continue;
            }

            // enddate is optional here
            if($key == 'enddate'){
                continue;
            }

            // startdate is required
            if($key == 'startdate' && $course->startdate){
                continue;
            }

            return false;
        }
    }
    return true;
}

/**
 * Fonction qui indique si l'indexation a ete saisie completement pour un parcours selon les regles
 * etablies par le processus de workflow. Cette fonction est legerement differente de celle presentee ci-dessus
 * mais du fait l'impact qu'elle represente sur le systeme de validation de l'indexation, cette nouvelle fonction
 * a ete developpe afin de verifier la saisie des donnees d'indexation avant publication sur l'offre de formation.
 * @param $course_id
 * @return bool
 * @throws dml_exception
 * @throws moodle_exception
 */
function indexation_achievement_for_sharing($course_id){
    global $DB;
    if(!isIndexationAvailable() && !function_exists('get_centralized_db_connection')){
        return true;
    }
    $DBC = get_centralized_db_connection();
    $collab_espace_collab_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_ESPACE_COLLABORATIF));
    $collab_reseau_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_RESEAU));
    $volet_distant_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_VOLET_DISTANT));

    $course = $DB->get_record('course', array('id' => $course_id));
    $indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));

    if(!$indexation){
        return false;
    }

    foreach((array)$indexation as $key => $value){
        if($value == null){
            if(($key == 'departementid' || $key == 'academyid') && $indexation->origin != 'academy'){
                continue;
            }

            if($key == 'originespeid' && $indexation->origin != 'espe'){
                continue;
            }

            // Champs de l'indexation non obligatoire
            if( $key == 'indexationtitle' ||
                $key == 'description' ||
                $key == 'objectif' ||
                $key == 'accompagnement' ||
                $key == 'validateby' ||
                $key == 'updatedate' ||
                $key == 'contact' ||
                $key == 'thumbnailid' ||
                $key == 'certificatid' ||
                $key == 'videoid' ||
                $key == 'rythme_formation' ||
                $key == 'entree_metier'){
                continue;
            }

            // Cas où la collection choisie est Espace Collaboratif, Reseau ou Volet Distant : Champs tps_a_distance non obligatoire
            if($key == 'tps_a_distance'
                && ($indexation->collectionid == $collab_espace_collab_id
                    || $indexation->collectionid == $collab_reseau_id
                    || $indexation->collectionid == $volet_distant_id)){
                continue;
            }

            // Cas où la collection choisie est Espace Collaboratif, Reseau ou Volet Distant : Champs tps_en_presence non obligatoire
            if($key == 'tps_en_presence'
                && ($indexation->collectionid == $collab_espace_collab_id
                    || $indexation->collectionid == $collab_reseau_id
                    || $indexation->collectionid == $volet_distant_id)){
                continue;
            }

            // enddate is optional here
            if($key == 'enddate'){
                continue;
            }

            // startdate is required
            if($key == 'startdate' && $course->startdate){
                continue;
            }

            return false;
        }
    }
    return true;
}

/**
 * Fonction qui retourne le pourcentage d'accomplissement de saisie de l'indexation d'un parcours.
 * @param $course_id
 * @return float|int
 * @throws dml_exception
 */
function percent_indexation_achievement($course_id){
    global $DB;
    $indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));
    $percent = 0;
    if($indexation){
        $max_columns_number = count($DB->get_columns('local_indexation'));
        $nb_null = 0;
        $nb_contenu = 0;
        foreach((array)$indexation as $key => $value){
            if($value == null){
                $nb_null ++;
            } else {
                $nb_contenu ++;
            }
        }
        $percent = round($nb_contenu / $max_columns_number * 100, 2);
    }
    return $percent;
}

/**
 * Fonction qui controle si le parcours en question est bien une session dans l'etat "Session en cours".
 * @param $course_id
 * @return bool
 * @throws dml_exception
 */
function is_session_en_cours($course_id){
    global $DB;

    $workflow = $DB->get_record('local_workflow', array('courseid' => $course_id));

    if($workflow && $workflow->issessioncours == true){
        return true;
    }

    return false;
}

/**
 * Fonction qui controle si le parcours en question est bien une session en autoformation dans l'etat "Session en cours".
 * @param $course_id
 * @return bool
 * @throws dml_exception
 */
function is_session_auto_formation_en_cours($course_id){
    global $DB;

    $workflow = $DB->get_record('local_workflow', array('courseid' => $course_id));

    if($workflow && $workflow->issessionautoformationcours == true){
        return true;
    }

    return false;
}

/**
 * Fonction qui determine si le parcours en question est bien dans l'etat "Session en cours" de maniere generale.
 * @param $course_id
 * @return bool
 * @throws dml_exception
 */
function is_en_cours($course_id){
    global $DB;

    $workflow = $DB->get_record('local_workflow', array('courseid' => $course_id));

    if($workflow){
        return true;
    }

    return false;
}

/**
 * Fonction qui verifie l'integrite des donnees liees à l'indexation ainsi qu'aux regles pre-etablies afin
 * qu'une session soit apte à être ouverte.
 * @return bool
 * @throws dml_exception
 * @throws moodle_exception
 */
function is_ready_for_session_en_cours($course_id){
    global $DB;
    if(!isIndexationAvailable() && !function_exists('get_centralized_db_connection')){
        return true;
    }
    $DBC = get_centralized_db_connection();
    $collab_autoformation_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_AUTOFORMATION));
    $ind_collection = $DB->get_field('local_indexation','collectionid',array('courseid'=>$course_id));
    $indexation_achievement = indexation_achievement($course_id);
    $participants = get_participants_on_context($course_id);
    $formateurs = get_formateurs_on_context($course_id);

    if($ind_collection == $collab_autoformation_id){
        $formateurs = true;
    }

    if($participants && $formateurs && $indexation_achievement == true){
        return true;
    }

    return false;
}

/**
 * Fonction qui verifie l'integrite des donnees liees à l'indexation ainsi qu'aux regles pre-etablies afin
 * qu'une session soit apte à être ouverte en tant que session en autoformation.
 * @param $course_id
 * @return bool
 * @throws dml_exception
 * @throws moodle_exception
 */
function is_ready_for_session_auto_formation_en_cours($course_id){
    global $DB;
    if(!isIndexationAvailable() && !function_exists('get_centralized_db_connection')){
        return true;
    }
    $DBC = get_centralized_db_connection();
    $collab_autoformation_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_AUTOFORMATION));
    $ind_collection = $DB->get_field('local_indexation','collectionid',array('courseid'=>$course_id));
    $indexation_achievement = indexation_achievement($course_id);
    $formateurs = get_formateurs_on_context($course_id);

    if($ind_collection == $collab_autoformation_id){
        $formateurs = true;
    }

    if($formateurs && $indexation_achievement == true){
        return true;
    }

    return false;
}

/**
 * Fonction qui verifie l'existance d'un bloc de type "completion_progress" et/ou "progress" afin de rendre possible
 * l'action d'attestion de participants au travers du workflow.
 * @param $course_id
 * @return bool|stdClass
 * @throws dml_exception
 */
function is_ready_for_attest_participant($course_id){
    global $DB;

    $course_is_visible = $DB->get_field('course', 'visible', array('id' => $course_id));
    $context = context_course::instance($course_id);
    $block_completion_progress_instance = $DB->get_record('block_instances', array('blockname' => 'completion_progress', 'parentcontextid' => $context->id));
    $block_progress_instance = $DB->get_record('block_instances', array('blockname' => 'progress', 'parentcontextid' => $context->id));
    $data = new stdClass();

    if(($course_is_visible && $block_completion_progress_instance && $block_progress_instance) ||
        ($course_is_visible && $block_completion_progress_instance)){
        $data->block = 'completion_progress';
        $data->id = $block_completion_progress_instance->id;
        return $data;
    }
    if($course_is_visible && $block_progress_instance){
        $data->block = 'progress';
        $data->id = $block_progress_instance->id;
        return $data;
    }
    return false;
}

/**
 * Fonction qui verifie l'integrite des donnees liees à l'indexation ainsi qu'aux regles pre-etablies afin
 * qu'une session soit apte à être publie sur l'offre de parcours.
 * @param $courseid
 * @return bool
 * @throws dml_exception
 * @throws moodle_exception
 */
function is_ready_to_share($courseid){
    if (!isCourseHubAvailable()){
        return false;
    }
    global $CFG, $DB;
    require_once($CFG->dirroot . '/local/coursehub/CourseHub.php');

    $hub = CourseHub::instance();

    if($hub->canShare()){
        
        if (!indexation_achievement_for_sharing($courseid)) {
            return false;
        }
        
        $course = get_course($courseid);

        $course_category = $DB->get_records_sql("
SELECT * 
FROM {local_indexation} im
INNER JOIN {context} c ON (c.instanceid = im.courseid) 
WHERE im.courseid = ? AND c.contextlevel = 50 
AND c.path LIKE(
    SELECT CONCAT('%/',id,'/%') 
    FROM {context} 
    WHERE contextlevel = 40 
    AND instanceid = (
        SELECT id 
        FROM {course_categories} 
        WHERE name = ? 
        AND depth = 2
    )
)",
            array($course->id, WKF_CAT_PED));
        
        if($course_category){
            $inscriptionmethod = $DB->get_records_sql('
SELECT * 
FROM {enrol} 
WHERE enrol = "self"
AND courseid = ?
AND (password IS NULL OR password = "")
AND roleid = (SELECT id FROM {role} WHERE shortname = "participant")
AND status = 0
AND customint6 = 1',
                array($courseid, time(), time(), time()));

            if(count($inscriptionmethod) > 0){
                return true;
            }
        }
    }
    return false;
}

/**
 * Fonction qui verifie l'integrite des donnees liees à l'indexation ainsi qu'aux regles pre-etablies afin
 * qu'une session soit apte à être publie sur l'offre de parcours.
 * @param $courseid
 * @param int $isalocalsession
 * @return bool
 * @throws dml_exception
 * @throws moodle_exception
 */
function is_ready_to_publish($courseid, $isalocalsession = 0){
    if (!isCourseHubAvailable()){
        return false;
    }
    global $CFG, $DB;
    require_once($CFG->dirroot . '/local/coursehub/CourseHub.php');

    $hub = CourseHub::instance();

    if($hub->canPublish()){
        $course = get_course($courseid);
        
        if (!indexation_achievement($courseid)) {
            return false;
        }

        if($isalocalsession){
            $cat_name = WKF_CAT_SLAF;
        } else {
            $cat_name = WKF_CAT_SAF;
        }

        $course_category = $DB->get_record_sql("SELECT * 
FROM {local_indexation} im
INNER JOIN {context} cx ON (cx.instanceid = im.courseid) 
WHERE im.courseid = ? AND cx.contextlevel = 50
AND cx.path LIKE(SELECT CONCAT('%/',id,'/%') FROM {context} WHERE contextlevel = 40 AND instanceid = (SELECT id FROM {course_categories} WHERE name = ? AND depth = 2))",
                                                array($course->id, $cat_name));
        if(!is_en_cours($courseid)){
            return false;
        }

        if($course_category !== false){
            $inscriptionmethod = $DB->get_records_sql('SELECT * 
FROM {enrol} 
WHERE enrol = "self"
AND courseid = ?
AND (enrolstartdate = 0 
    OR (enrolstartdate < ? AND enrolenddate = 0) 
    OR (enrolstartdate < ? AND enrolenddate > ?)
)
AND roleid = (SELECT id FROM {role} WHERE shortname = "participant")
AND status = 0
AND customint6 = 1',
                                                        array($courseid, time(), time(), time()));
            if(count($inscriptionmethod) > 0){
                return true;
            }
        }
    }
    return false;
}

/**
 * Fonction qui determine si un parcours est publie sur l'offre de parcours ou de formation.
 * @param $courseid
 * @return bool|int
 */
function course_is_published($courseid){
    if (!isCourseHubAvailable()){
        return false;
    }
    global $CFG;
    require_once($CFG->dirroot . '/local/coursehub/CourseHub.php');

    $hub = CourseHub::instance();
    $publish = $hub->getPublishedCourse($CFG->academie_name, $courseid, CourseHub::PUBLISH_PUBLISHED);
    
    if($publish !== false){
        return CourseHub::PUBLISH_PUBLISHED;
    }else{
        if ($hub->getPublishedCourse($CFG->academie_name, $courseid, CourseHub::PUBLISH_SHARED) !== false ){
            return CourseHub::PUBLISH_SHARED;
        }
    }
    return false;
}

/**
 * Fonction qui recupere l'ensemble des utilisateurs d'un parcours selon un rôle specifique.
 * @param $course_id
 * @param $rolename
 * @return array
 * @throws dml_exception
 */
function get_users_with_role_on_context($course_id, $rolename){
    global $DB;

    $context = context_course::instance($course_id);
    $role = $DB->get_record('role', array('shortname' => $rolename));

    return get_users_from_role_on_context($role, $context);
}

/**
 * Fonction qui recupere l'ensemble des participants d'un parcours.
 * @param $course_id
 * @return array
 * @throws dml_exception
 */
function get_participants_on_context($course_id){
    return get_users_with_role_on_context($course_id, WKF_ROLE_PARTICIPANT);
}

/**
 * Fonction qui recupere l'ensemble des formateurs d'un parcours.
 * @param $course_id
 * @return array
 * @throws dml_exception
 */
function get_formateurs_on_context($course_id){
    return get_users_with_role_on_context($course_id, WKF_ROLE_FORMATEUR);
}

/**
 * Fonction qui genere les notifications badges sur le formulaire de workflow et d'indexation.
 * @param $nb_notification
 * @return bool|string
 */
function generate_notification_HTML($nb_notification){
    if($nb_notification){
        return html_writer::span(
            html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x')).
            html_writer::span($nb_notification,'fa fa-stack-1x number'),'fa fa-stack fa-1x notification-badge');
    }
    return false;
}

/**
 * Fonction qui génère un help_icon custom pour l'activité Workflow.
 * @param $identifier
 * @param $component
 * @param string $argument
 * @return string
 * @throws coding_exception
 * @throws moodle_exception
 */
function generate_help_icon_HTML($identifier, $component, $argument = ""){
    global $CFG;

    $helpicon = new help_icon($identifier, $component);
    $title = get_string($helpicon->identifier, $helpicon->component, $argument);

    if (empty($helpicon->linktext)) {
        $alt = get_string('helpprefix2', '', trim($title, ". \t"));
    } else {
        $alt = get_string('helpwiththis');
    }

    $output = html_writer::tag('i','', array('alt'=>$alt, 'class'=>'iconhelp fa fa-2x fa-exclamation-circle'));

    // now create the link around it - we need https on loginhttps pages
    $url = new moodle_url($CFG->httpswwwroot.'/help.php', array('component' => $helpicon->component, 'identifier' => $helpicon->identifier, 'lang'=>current_language()));

    // note: this title is displayed only if JS is disabled, otherwise the link will have the new ajax tooltip
    $title = get_string('helpprefix2', '', trim($title, ". \t"));

    $attributes = array('href' => $url, 'title' => $title, 'aria-haspopup' => 'true', 'target'=>'_blank');
    $output = html_writer::tag('a', $output, $attributes);

    // and finally span
    return html_writer::tag('span', $output, array('class' => 'helptooltip'));
}


////////////////////
// CRON FUNCTIONS //
////////////////////

/**
 * Fonction de configuration de la tache planifiee du plugin local workflow. Cette tache gere l'envoi de notification
 * en rapport aux ouvertures et fermetures de session. Elle traite egalement l'envoi de plusieurs rappels
 * selon les dates de debuts et de fin de session.
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function workflow_cron(){
    global $CFG, $DB;
    require_once($CFG->dirroot . '/lib/coursecatlib.php');
    require_once($CFG->dirroot . '/lib/moodlelib.php');

    $site = get_site();

    $mailcount  = 0;
    $errorcount = 0;


    // Posts older than 2 days will not be mailed.  This is to avoid the problem where
    // cron has not been running for a long time, and then suddenly people are flooded
    // with mail from the past few weeks or months
    $startdatenow = mktime(0, 0, 0);
    $enddatenow = mktime(23, 59, 59);
    $first_rappel = $enddatenow - (86400 * 7);
    $second_rappel = $first_rappel - (86400 * 7);
    $third_rappel = $second_rappel - (86400 * 7);

    $cat = $DB->get_record('course_categories', array('name' => WKF_CAT_SDF));
    $cat_auto = $DB->get_record('course_categories', array('name' => WKF_CAT_SAF));
    $cat_local_auto = $DB->get_record('course_categories', array('name' => WKF_CAT_SLAF));
    $session_cat = coursecat::get($cat->id);
    $session_cat_auto = coursecat::get($cat_auto->id);
    $session_cat_local_auto = coursecat::get($cat_local_auto->id);
    $sessions = $session_cat->get_courses();
    $sessions_auto = $session_cat_auto->get_courses();
    $sessions_local_auto = $session_cat_local_auto->get_courses();
    $courses = array_merge($sessions, $sessions_auto, $sessions_local_auto);

    if($courses){
        foreach($courses as $key => $course){
            if($course->format == "flexpage"){
                continue;
            }
            if(!indexation_achievement($course->id)){
                continue;
            }
            if($course->startdate != $startdatenow
                && ($course->enddate != $enddatenow
                    && $course->enddate != $first_rappel
                    && $course->enddate != $second_rappel
                    && $course->enddate != $third_rappel)
                ){
                continue;
            }
            $formateurs = get_formateurs_on_context($course->id);
            if(count($formateurs) == 0){
                continue;
            }
            mtrace('Processing course ' . $course->id);

            $newsforum = $DB->get_record('forum', array('course' => $course->id, 'type' => 'news'));
            $module_forum_id = $DB->get_field('modules', 'id', array('name' => 'forum'));
            $cm_news_id = $DB->get_field('course_modules', 'id', array('course' => $course->id, 'instance' => $newsforum->id, 'module' => $module_forum_id));
            $linkannonce = $CFG->wwwroot . '/mod/forum/view.php?id=' . $cm_news_id;

            $is_enable_attest = is_ready_for_attest_participant($course->id);
            $instance = 'instanceid=' . $is_enable_attest->id;
            if ($is_enable_attest->block == 'progress'){
                $instance = 'id=' . $is_enable_attest->id;
            }
            $linkattest = $CFG->wwwroot . '/blocks/'.$is_enable_attest->block.'/overview.php?' . $instance .'&courseid=' . $course->id;

            $mail = new stdClass();
            $mail->linksession = $CFG->wwwroot . "/course/view.php?id=" . $course->id;
            $mail->linkworkflow = $CFG->wwwroot . "/local/workflow/index.php?id=" . $course->id;
            $mail->linkannonce = $linkannonce;
            $mail->linkattest = $linkattest;
            $mail->sessionname = $course->fullname;

            foreach($formateurs as $formateur){
                core_php_time_limit::raise(120); // terminate if processing of any account takes longer than 2 minutes
                $user = $DB->get_record('user', array('id' => $formateur->userid));
                mtrace('Processing user ' . $user->id);

                // set this so that the capabilities are cached, and environment matches receiving user
                cron_setup_user($formateur);

                $mail->userto = $user->firstname." ".$user->lastname;

                if($course->startdate == $startdatenow){
                    $subject = html_to_text(get_string('notification_send_email_open_session_subject',
                        'local_workflow', $mail->sessionname));
                    $mail->participant = WKF_ROLE_NAME_PARTICIPANT;
                    $mail->formateur = WKF_ROLE_NAME_FORMATEUR;
                    $message = html_to_text(get_string('notification_send_email_open_session_message',
                        'local_workflow', $mail));
                    $message_html = workflow_message_to_html(get_string('notification_send_email_open_session_message',
                        'local_workflow', $mail));
                }

                if($course->enddate == $enddatenow
                    || $course->enddate == $first_rappel
                    || $course->enddate == $second_rappel
                    || $course->enddate == $third_rappel){
                    $subject = html_to_text(get_string('notification_send_email_close_session_subject',
                        'local_workflow', $mail->sessionname));
                    $mail->participant = WKF_ROLE_NAME_PARTICIPANT;
                    $message = html_to_text(get_string('notification_send_email_close_session_message',
                        'local_workflow', $mail));
                    $message_html = workflow_message_to_html(get_string('notification_send_email_close_session_message',
                        'local_workflow', $mail));
                }

                // Send the post now!

                mtrace('Sending ', '');

                $user->mailformat = 1;  // Always send HTML version as well.

                $attachment = $attachname='';
                $mailresult = email_to_user($user, $site->shortname, $subject, $message, $message_html, $attachment, $attachname);
                if (!$mailresult){
                    mtrace("Error: local/workflow/lib.php workflow_cron(): Could not send out mail for id $course->id to user $user->id".
                        " ($user->email) .. not trying again.");
                    $errorcount++;
                } else {
                    $mailcount++;
                }
            }
            mtrace('course '.$course->id. ': done');
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
 * Fonction qui traite l'envoi de notification dans le cas des sessions parvenant à la fin du workflow c'est à dire
 * lorsque qu'une session devient une session archivee.
 * @param $course_id
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function send_notification_for_archive_session($course_id){
    global $DB, $CFG;
    $site = get_site();
    $course = $DB->get_record('course', array('id' => $course_id));
    if($course){
        $participants = get_participants_on_context($course_id);
        if($participants){
            $mail = new stdClass();
            $mail->linksession = $CFG->wwwroot . "/course/view.php?id=" . $course_id;
            $mail->sessionname = $course->fullname;
            foreach ($participants as $participant) {
                $user = $DB->get_record('user', array('id' => $participant->userid));
                $mail->userto = $user->firstname." ".$user->lastname;
                $user->mailformat = 1;  // Always send HTML version as well.
                $attachment = $attachname='';

                $subject = html_to_text(get_string('notification_send_email_archive_session_subject',
                    'local_workflow', $mail->sessionname));
                $message = html_to_text(get_string('notification_send_email_archive_session_message',
                    'local_workflow', $mail));
                $message_html = workflow_message_to_html(get_string('notification_send_email_archive_session_message',
                    'local_workflow', $mail));
                email_to_user($user, $site->shortname, $subject, $message, $message_html, $attachment, $attachname);
            }
        }
        return false;
    }
    return false;
}

/**
 * Fonction qui determine la structure principale d'une notification en HTML.
 * @param $message
 * @return string
 */
function workflow_message_to_html($message){
    $posthtml = '<head>';
    $posthtml .= '</head>';
    $posthtml .= '<body>';
    $posthtml .= $message;
    $posthtml .= '</body>';
    return $posthtml;
}
