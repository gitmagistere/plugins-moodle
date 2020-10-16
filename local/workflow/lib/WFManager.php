<?php

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/local/workflow/lib.php');

/**
 * Class self. Class contenant l'ensemble des actions possibles sur le workflow.
 */
class WFManager
{
    /**
     * Fonction permettant de vérifier l'unicité du shortname du parcours saisi dans le formulaire.
     * @param $shortname
     * @param $courseid
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function shortname_is_unique($shortname, $courseid)
    {
        global $DB;
        $result = $DB->get_records_menu('course', array('shortname' => $shortname));
        if (count($result) != 0) {
            $_SESSION['short_name_not_unique'] = 'Ce nom de parcours est déjà utilisé, la duplication a été annulée.';
            $redirect_url = new moodle_url("/course/view.php?", array('id' => $courseid));
            redirect($redirect_url);
        } else return true;
    }

    /**
     * Fonction qui permet la création d'une duplication d'un parcours.
     * @param $courseid
     * @param $action_type
     * @param $name
     * @param $shortname
     * @param $category
     * @param null $date
     * @return moodle_url
     * @throws coding_exception
     * @throws dml_exception
     * @throws dml_transaction_exception
     * @throws moodle_exception
     */
    static function course_duplication($courseid, $action_type, $name, $shortname, $category, $date = null)
    {
        global $USER, $DB, $CFG;

        $backupid = self::course_backup($courseid, $USER->id, $action_type);
        //$transaction = $DB->start_delegated_transaction();
        $formateur = $DB->get_record('role', array('shortname' => WKF_ROLE_FORMATEUR));

        if ($action_type == 'createsessionfromparcours'
            || $action_type == 'recreatesessionfromparcours'
            || $action_type == 'createparcoursfromgabarit') {
            $categorie_name = '';
            switch ($action_type) {
                case 'createsessionfromparcours':
                    $categorie_name = 'Session de formation';
                    break;
                case 'recreatesessionfromparcours':
                    $categorie_name = 'Session de formation';
                    break;
                case 'createparcoursfromgabarit':
                    $categorie_name = 'Parcours de formation';
                    break;
                default:
                    $categorie_name = 'Gabarit';
            }

            $course_categorie = $DB->get_record('course_categories', array('name' => $categorie_name));
            $categorie_context = $DB->get_record('context', array('contextlevel' => 40, 'instanceid' => $course_categorie->id));
            role_assign($formateur->id, $USER->id, $categorie_context->id);
        }

        try {
            $new_course = self::course_create($name, $shortname, $category);
            self::course_restore($backupid, $new_course, $USER->id, $action_type);
        } catch (Exception $e) {
            echo 'Exception reçue : ', $e->getMessage(), "\n";
        }

        if ($action_type == 'createsessionfromparcours'
            || $action_type == 'recreatesessionfromparcours'
            || $action_type == 'createparcoursfromgabarit') {
            try {

                if ($DB->record_exists('enrol', array('courseid' => $new_course, 'enrol' => 'manual')) === false) {
                    //gestion des méthode d'inscription
                    $record_enrol = new stdClass();
                    $record_enrol->enrol = 'manual';
                    $record_enrol->courseid = $new_course;
                    $DB->insert_record('enrol', $record_enrol, false);
                }
                //inscription au parcours
                if ($action_type == 'createsessionfromparcours'
                || $action_type == 'recreatesessionfromparcours') {
                    self::enrol_course_creator($new_course, $USER->id, $formateur->id, strtotime("today", time()));
                } else {
                    self::enrol_course_creator($new_course, $USER->id, $formateur->id);
                }
            } catch (Exception $e) {
                error_log('Exception reçue : ' . $e->getMessage());
                //die();
            }
            //retrait du rôle formateur sur les sessions
            role_unassign($formateur->id, $USER->id, $categorie_context->id);
        }

        // Commit
        //$transaction->allow_commit();
        $record = new stdClass();
        $record->id = $new_course;
        $record->fullname = $name;
        $record->shortname = $shortname;
        $record->visible = 0; // création d'une session ou parcours => visible à 0 par défaut

        //Gestion des dates de début de parcours
        if ($action_type == 'createsessionfromparcours' || $action_type == 'recreatesessionfromparcours') {
            if($date != null) {
                $date = str_replace('/', '-', $date);
                $date = strtotime($date);
                $record->startdate = $date;
            } else {
                $record->startdate = time();
            }
            $record->enddate = 0;
        } elseif ($action_type != 'duplicate' || $action_type == 'createparcoursfromgabarit') {
            $record->startdate = time();
            $record->enddate = 0;
        }

        $record->category = $category;
        $DB->update_record('course', $record);

        if (isVIAAvailable()){
            //2948 : JB - 14/03/2019 Traitement des activités Via
            if ($action_type == 'duplicate' || $action_type == 'createsessionfromparcours') {
                self::get_via_activities_in_course($new_course);
            }
        }

        if (isIndexationAvailable()){
            require_once($CFG->dirroot . '/local/magisterelib/indexationServices.php');
            //Traitement de l'indexation
            IndexationServices::copy_indexation($courseid, $new_course, $name);
    
            //Vidange de l'indexation excepté l'origine et la collection dans le cas exclusif d'une création de parcours à partir d'un gabarit.
            if ($action_type == 'createparcoursfromgabarit') {
                $indexation = $DB->get_record('local_indexation', array('courseid' => $new_course));
                if ($indexation) {
                    $record = new stdClass();
                    $record->id = $indexation->id;
                    $record->objectif = null;
                    $record->tps_a_distance = null;
                    $record->tps_en_presence = null;
                    $record->accompagnement = null;
                    $record->domainid = null;
                    $record->authors = null;
                    $record->validateby = null;
                    $record->updatedate = null;
                    $record->departementid = null;
                    $record->originespeid = null;
                    $record->academyid = null;
                    $record->contact = null;
                    $record->entree_metier = null;
                    $record->year = null;
                    $record->codeorigineid = null;
                    $record->title = null;
                    $record->version = null;
                    $record->thumbnailid = null;
                    $record->certificatid = null;
                    $record->videoid = null;
                    $record->rythme_formation = null;
    
                    $DB->update_record('local_indexation', $record);
    
                    //Suppression des données rattachées à l'indexation
                    $DB->delete_records('local_indexation_keywords', array('indexationid' => $indexation->id));
                    $DB->delete_records('local_indexation_public', array('indexationid' => $indexation->id));
                }
            }
            
        }
        return new moodle_url("/course/view.php?", array('id' => $new_course));
    }

    /**
     * Fonction qui traite le cas où le parcours bascule en tant que parcours archivé.
     * @param $course_id
     * @param $course_category
     * @param $archive_type
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function archive_course($course_id, $course_category, $archive_type)
    {
        global $DB, $CFG;
        $data_archive = new stdClass();
        $data_archive->id = $course_id;

        $a_courseid[] = $course_id;
        if (!$course_category) {
            $course_category = $DB->get_field('course_categories', 'id', array('name' => WKF_CAT_ARC));
        }
        move_courses($a_courseid, $course_category);

        // edit visibility of course
        if ($archive_type == 'hidden') {
            $data_archive->visible = 0;
        } else {    //visible or data_error
            $data_archive->visible = 1;
        }

        //updating
        $DB->update_record('course', $data_archive);

        send_notification_for_archive_session($course_id);
        //redirect
        $msg = get_string('course_management_session_archived', 'local_workflow');
        redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_INFO);
    }

    /**
     * Fonction permettant de réouvrir une session archivée.
     * @param $course_id
     * @param $course_category
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function unarchive_course($course_id, $course_category)
    {
        global $DB, $CFG;
        $data_archive = new stdClass();
        $data_archive->id = $course_id;
        $categoryid = $course_category;

        $a_courseid[] = $course_id;
        move_courses($a_courseid, $categoryid);
        // visibility
        $data_archive->visible = 0;
        //updating
        $DB->update_record('course', $data_archive);

        $DB->delete_records('local_workflow', array('courseid' => $course_id));
        //redirect
        $msg = get_string('course_management_session_unarchived', 'local_workflow');
        redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_INFO);
    }

    /**
     * Fonction permettant d'ouvrir une session de formation et permettre également d'afficher le status
     * "Session en cours" dans le workflow.
     * @param $course_id
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function open_session($course_id)
    {
        global $DB, $CFG;
        $data_course = new stdClass();
        $data_course->id = $course_id;

        // visibility ok
        $data_course->visible = 1;

        //updating
        $DB->update_record('course', $data_course);

        if (isIndexationAvailable()){
            require_once($CFG->dirroot . '/local/magisterelib/indexationServices.php');
            $indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));
            $indexation_achievement = indexation_achievement($course_id);

            if (!($indexation && $indexation_achievement == true)) {
                //redirect
                $msg = get_string('course_management_open_session_failed', 'local_workflow');
                redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_ERROR);
            }
        }
    
        $course = new stdClass();
        $course->id = $course_id;
        $course->startdate = mktime(0, 0, 0);
        
        $DB->update_record('course', $course);
        
        // Ajout donnees pour passage etat session en cours
        $data_workflow = new stdClass();
        $data_workflow->courseid = $course_id;
        $data_workflow->issessioncours = 1;
        $data_workflow->issessionautoformationcours = 0;
        $data_workflow->timecreated = time();
        
        $course_workflow = $DB->get_record('local_workflow', array('courseid' => $course_id));
        if ($course_workflow) {
            $data_workflow->id = $course_workflow->id;
            $DB->update_record('local_workflow', $data_workflow);
        } else {
            $DB->insert_record('local_workflow', $data_workflow);
        }
        //redirect
        $msg = get_string('course_management_open_session', 'local_workflow');
        redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_INFO);
    }

    /**
     * Fonction permettant d'ouvrir une session mais sous la forme d'une session en auto-inscription.
     * @param $course_id
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function open_auto_inscription($course_id)
    {
        global $DB, $CFG;

        if (isIndexationAvailable()){
            require_once($CFG->dirroot . '/local/magisterelib/indexationServices.php');
            // Verification indexation ok et remplie a 100%
            $indexation = $DB->get_record('local_indexation', array('courseid' => $course_id));
            $indexation_achievement = indexation_achievement($course_id);
        }else{
            $indexation = true;
            $indexation_achievement = true;
        }
        
        if ($indexation && $indexation_achievement == true) {
            // Activation la session en l'a rendant visible
            $data_course = new stdClass();
            $data_course->id = $course_id;
            $data_course->visible = 1;
            $course = $DB->get_record('course', array('id' => $course_id));
            
            $participant_id = $DB->get_field('role', 'id', array('shortname' => WKF_ROLE_PARTICIPANT));

            if (isIndexationAvailable()){
                // On change la collection de l'indexation
                $collab_autoformation_id = get_centralized_db_connection()->get_field('local_indexation_collections', 'id', array('shortname' => WKF_IND_COLL_AUTOFORMATION));
    
                $data_indexation = new stdClass();
                $data_indexation->id = $indexation->id;
                $data_indexation->collectionid = $collab_autoformation_id;
                $DB->update_record('local_indexation', $data_indexation);
            }

            // Changement de la catégorie en session en auto-inscription
            $session_cat_id = $DB->get_field('course_categories', 'id', array('name' => WKF_CAT_SDF));
            if ($session_cat_id) {
                $session_autoformation_id = $DB->get_field('course_categories', 'id', array('name' => WKF_CAT_SAF, 'parent' => $session_cat_id));
                if ($session_autoformation_id) {
                    move_courses(array($course_id), $session_autoformation_id);
                }
            }
            
            //Updating course
            if (!$DB->update_record('course', $data_course)) {
                $msg = get_string('course_management_open_auto_formation_failed', 'local_workflow');
                redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_ERROR);
            }

            // Ajout données pour passage état session en cours
            $data_workflow = new stdClass();
            $data_workflow->courseid = $course_id;
            $data_workflow->issessioncours = 0;
            $data_workflow->issessionautoformationcours = 1;
            $data_workflow->timecreated = time();

            $course_workflow = $DB->get_record('local_workflow', array('courseid' => $course_id));
            if ($course_workflow) {
                $data_workflow->id = $course_workflow->id;
                $DB->update_record('local_workflow', $data_workflow);
            } else {
                $DB->insert_record('local_workflow', $data_workflow);
            }

            // Création la méthode d'inscription
            $count_enrol = $DB->count_records('enrol', array('courseid' => $course->id));
            $enrol_self = new stdClass();
            $enrol_self->enrol = "self";
            $enrol_self->status = 0;   // status
            $enrol_self->courseid = $course->id;
            $enrol_self->name = "";
            $enrol_self->customint6 = 1; // new enrols
            $enrol_self->password = null;
            $enrol_self->customint1 = 0; // group key
            $enrol_self->roleid = $participant_id;
            $enrol_self->enrolperiod = 0;
            $enrol_self->expirynotify = 0;
            $enrol_self->expirythreshold = 0;
            $enrol_self->enrolstartdate = time();
            $enrol_self->enrolenddate = 0;
            $enrol_self->customint2 = 31536000; // long time no see = 365 jours
            $enrol_self->customint3 = 0; // max enrolled
            $enrol_self->customint4 = 1; // send course welcome message
            $enrol_self->customint5 = 0;
            $enrol_self->notifyall = 0;
            $enrol_self->customtext1 = get_string('field_enrol_self_custom_welcome_message', 'local_workflow', $course->shortname); // custom welcome message
            $enrol_self->sortorder = $count_enrol;
            $enrol_self->timecreated = time();
            $enrol_self->timemodified = time();

            if (!$DB->insert_record('enrol', $enrol_self)) {
                $msg = get_string('course_management_open_auto_formation_failed', 'local_workflow');
                redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_ERROR);
            } else {
                $msg = get_string('course_management_open_auto_formation', 'local_workflow');
                redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_INFO);
            }
        } else {
            //redirect
            $msg = get_string('course_management_open_auto_formation_failed', 'local_workflow');
            redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $course_id, $msg, null, \core\output\notification::NOTIFY_ERROR);
        }
    }

    /**
     * Fonction permettant d'actionner le processus de mise à la corbeille de la session avec notamment la dépublication
     * de cette même session.
     * @param $course_id
     * @param $course_category
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function discard_course($course_id, $course_category)
    {
        global $DB, $CFG;

        $course = $DB->get_record('course', array('id' => $course_id));
        $category_save = new stdClass();
        $category_save->course_id = $course->id;
        $category_save->category_id = $course->category;

        if (!$DB->insert_record('course_trash_category', $category_save)) {
            echo 'L\'insertion dans la table corbeille a echouee, deplacement du cours interrompu!';
            return false;
        }

        require_once($CFG->dirroot . '/' . $CFG->admin . '/registration/lib.php');
        require_once($CFG->dirroot . '/course/publish/lib.php');


        /// UNPUBLISH
        $publicationmanager = new course_publish_manager();
        $registrationmanager = new registration_manager();

        $published = $DB->get_records('course_published', array('courseid' => $course->id));

        if (count($published) > 0) {

            $courseids = array();
            $publicationids = array();
            $huburl = '';
            foreach ($published as $publish) {
                if ($huburl == '') {
                    $huburl = $publish->huburl;
                }
                $publicationids[] = $publish->id;
                $courseids[] = $publish->hubcourseid;
            }

            //unpublish the publication by web service
            //$huburl = $published[0]->huburl;
            $registeredhub = $registrationmanager->get_registeredhub($huburl);
            $function = 'hub_unregister_courses';
            $params = array('courseids' => $courseids);
            $serverurl = $huburl . "/local/hub/webservice/webservices.php";
            require_once($CFG->dirroot . "/webservice/xmlrpc/lib.php");
            $xmlrpcclient = new webservice_xmlrpc_client($serverurl, $registeredhub->token);
            $result = $xmlrpcclient->call($function, $params);

            //delete the publication from the database
            foreach ($publicationids as $id) {
                $publicationmanager->delete_publication($id);
            }
        }

        $data_archive = new stdClass();
        $data_archive->id = $course_id;

        $a_courseid[] = $course_id;
        move_courses($a_courseid, $course_category);

        // edit visibility of course
        $data_archive->visible = 0;

        //updating
        $DB->update_record('course', $data_archive);
        //redirect
        $nexturl = new moodle_url("/course/view.php?", array('id' => $course_id));
        redirect($nexturl);
    }

    /**
     * Fonction qui permet de restaurer une session mise à la corbeille.
     * @param $course_id
     * @param $course_category
     * @throws dml_exception
     * @throws moodle_exception
     */
    static function restorefromtrash_course($course_id, $course_category)
    {
        global $DB;
        $data_archive = new stdClass();
        $data_archive->id = $course_id;
        $data_archive->startdate = time();
        $categoryid = $course_category;

        $a_courseid[] = $course_id;
        move_courses($a_courseid, $categoryid);
        // visibility ok
        $data_archive->visible = 1;
        //updating
        $DB->update_record('course', $data_archive);

        $DB->delete_records('course_trash_category', array('course_id' => $course_id));
        //redirect
        $nexturl = new moodle_url("/course/view.php?", array('id' => $course_id));
        redirect($nexturl);
    }

    /**
     * Fonction qui permet de faire une sauvegarde du parcours avant de procéder à la duplication.
     * @param $courseid
     * @param $userid
     * @param $action_type
     * @return mixed
     */
    static function course_backup($courseid, $userid, $action_type)
    {
        if ($action_type == 'createsessionfromparcours' || $action_type == 'recreatesessionfromparcours') {
            $backupsettings = array(
                'users' => 0,               // Include enrolled users (default = 1)
                'anonymize' => 0,           // Anonymize user information (default = 0)
                'role_assignments' => 0,    // Include user role assignments (default = 1)
                'activities' => 1,          // Include activities (default = 1)
                'blocks' => 1,              // Include blocks (default = 1)
                'filters' => 1,             // Include filters (default = 1)
                'comments' => 0,            // Include comments (default = 1)
                'userscompletion' => 0,     // Include user completion details (default = 1)
                'logs' => 0,                // Include course logs (default = 0)
                'grade_histories' => 0,     // Include grade history (default = 0)
                'badges' => 1
            );
        } else {
            $backupsettings = array(
                'users' => 1,
                'anonymize' => 0,
                'role_assignments' => 1,
                'activities' => 1,
                'blocks' => 1,
                'filters' => 1,
                'comments' => 1,
                'userscompletion' => 1,
                'logs' => 1,
                'grade_histories' => 1,
                'badges' => 1
            );
        }
        
        $admin = get_admin();
        
        $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $admin->id);

        foreach ($bc->get_plan()->get_tasks() as $taskindex => $task) {
            $settings = $task->get_settings();
            foreach ($settings as $settingindex => $setting) {

                $setting->set_status(backup_setting::NOT_LOCKED);

                // Modify the values of the intial backup settings
                if ($taskindex == 0) {
                    foreach ($backupsettings as $key => $value) {
                        if ($setting->get_name() == $key) {

                            $setting->set_value($value);
                        }
                    }
                }
            }
        }
        $backupid = $bc->get_backupid();

        $bc->execute_plan();
        $bc->destroy();
        return $backupid;
    }

    /**
     * Fonction qui permet de créer un nouveau parcours avec les informations envoyées par les popin d'action.
     * @param $coursename
     * @param $shortname
     * @param $coursecategory
     * @return int
     */
    static function course_create($coursename, $shortname, $coursecategory)
    {
        global $DB;
        $category = $DB->get_record('course_categories', array('id'=>$coursecategory), '*', MUST_EXIST);

        $course = new stdClass;
        $course->fullname = $coursename;
        $course->shortname = $shortname;
        $course->category = $category->id;
        $course->sortorder = 0;
        $course->startdate  = time();
        $course->timecreated  = time();
        $course->timemodified = $course->timecreated;
        $course->visible = 0;

        $courseid = $DB->insert_record('course', $course);

        $category->coursecount++;
        $DB->update_record('course_categories', $category);

        return $courseid;
    }

    /**
     * Fonction qui permet de restaurer un parcours. Cette fonction est la dernière étape pour la duplication
     * d'un parcours. L'idée étant d'utiliser le backup créé par la fonction ci-dessus et de l'inclure dans
     * le nouveau parcours créé au préalable par la fonction ci-dessus.
     * @param $backupid
     * @param $ci
     * @param $ui
     * @param $action_type
     */
    static function course_restore($backupid, $ci, $ui, $action_type)
    {
        global $DB;

        $transaction = null;
        if ($action_type == 'createsessionfromparcours'
            || $action_type == 'recreatesessionfromparcours'
            || $action_type == 'createparcoursfromgabarit') {
            $backupsettings = array(
                'users' => 0,               // Include enrolled users (default = 1)
                'anonymize' => 0,           // Anonymize user information (default = 0)
                'role_assignments' => 0,    // Include user role assignments (default = 1)
                'activities' => 1,          // Include activities (default = 1)
                'blocks' => 1,              // Include blocks (default = 1)
                'filters' => 1,             // Include filters (default = 1)
                'comments' => 0,            // Include comments (default = 1)
                'userscompletion' => 0,     // Include user completion details (default = 1)
                'logs' => 0,                // Include course logs (default = 0)
                'grade_histories' => 0,      // Include grade history (default = 0)
                'badges' => 1
            );
        } else {
            $backupsettings = array(
                'users' => 1,
                'anonymize' => 0,
                'role_assignments' => 1,
                'activities' => 1,
                'blocks' => 1,
                'filters' => 1,
                'comments' => 1,
                'userscompletion' => 1,
                'logs' => 1,
                'grade_histories' => 1,
                'badges' => 1
            );
        }

        try {
            $admin = get_admin();
            
            $transaction = $DB->start_delegated_transaction();
            $controller = new restore_controller($backupid, $ci,
                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $admin->id,
                backup::TARGET_NEW_COURSE);
/*
            foreach ($controller->get_plan()->get_tasks() as $taskindex => $task) {
                $settings = $task->get_settings();
                foreach ($settings as $setting) {
                    $setting->set_status(backup_setting::NOT_LOCKED);

                    // Modify the values of the intial backup settings
                    if ($taskindex == 0) {
                        foreach ($backupsettings as $key => $value) {
                            if ($setting->get_name() == $key) {
                                $setting->set_value($value);
                            }
                        }
                    }
                }
            }
*/
            $controller->execute_precheck();
            $controller->execute_plan();
            $transaction->allow_commit();

            $ctx = context_course::instance($ci);
            $ctx->reset_paths(true);

        } catch (Exception $e) {
            $transaction->dispose();
        }
    }

    /**
     * Fonction permettant de publier un parcours ou une session sur l'offre de parcours ou de formation.
     * @param $courseid
     * @param string $method
     * @throws coding_exception
     */
    static function course_publish($courseid, $method="share", $isalocalsession = 0){
        global $CFG;
        if (!isCourseHubAvailable()){
            return false;
        }
        require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

        $hub = CourseHub::instance();
        $msg = get_string('msg_share_success', 'local_workflow');

        if($method == "publish"){
            if($isalocalsession){
                $msg = get_string('msg_local_publish_success', 'local_workflow');
            } else {
                $msg = get_string('msg_publish_success', 'local_workflow');
            }

            if($hub->canPublish()){
                if (file_exists($CFG->dirroot.'/local/magisterelib/magistereLib.php')){
                    require_once($CFG->dirroot.'/local/magisterelib/magistereLib.php');
                    MagistereLib::update_course_modified(false,$courseid);
                }
                $hub->publishCourse($courseid, $isalocalsession);
            }
        } else {
            if($hub->canShare()){
                if (file_exists($CFG->dirroot.'/local/magisterelib/magistereLib.php')){
                    require_once($CFG->dirroot.'/local/magisterelib/magistereLib.php');
                    MagistereLib::update_course_modified(false,$courseid);
                }
                $hub->shareCourse($courseid);
            }
        }
        echo '{"error":"false"}';
    }

    /**
     * Fonction permettant de dépublier un parcours ou une session de l'offre de parcours ou de formation.
     * Une redirection sur le workflow du parcours est alors fait avec l'affichage d'une notification de type success.
     * @param $courseid
     * @param string $method
     * @throws coding_exception
     * @throws moodle_exception
     */
    static function course_unpublish($courseid, $method="share"){
        global $CFG;
        if (!isCourseHubAvailable()){
            return false;
        }
        require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');

        $hub = CourseHub::instance();
        $msg = get_string('msg_share_discard_success', 'local_workflow');

        if($method == "publish"){
            $msg = get_string('msg_publish_discard_success', 'local_workflow');
            if($hub->canDelete()){
                $hub->unpublishCourse($courseid, CourseHub::PUBLISH_PUBLISHED);
            }
        } else {
            if($hub->canDelete()){
                $hub->unpublishCourse($courseid, CourseHub::PUBLISH_SHARED);
            }
        }
        redirect($CFG->wwwroot . '/local/workflow/index.php?id=' . $courseid, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    /**
     * Fonction permettant de supprimer les fichiers temporaires d'un parcours.
     * @param $dossier
     */
    static function clear_folder($dossier)
    {
        if (($dir = opendir($dossier)) === false) {
            return;
        } else {
            while ($name = readdir($dir)) {
                if ($name === '.' or $name === '..')
                    continue;
                $full_name = $dossier . '/' . $name;

                if (is_dir($full_name))
                    self::clear_folder($full_name);
                // else unlink($full_name);
            }
            closedir($dir);
            @rmdir($dossier);
        }
    }

    /**
     * Fonction permettant d'ajouter un utilisateur dans un parcours avec un rôle spécifique.
     * @param $course_id
     * @param $userid
     * @param $role_id
     * @throws coding_exception
     * @throws dml_exception
     */
    static function enrol_course_creator($course_id, $userid, $role_id, $timestart = 0)
    {
        global $DB;
        $instances = $DB->get_records('enrol', array('courseid' => $course_id, 'enrol' => 'manual'), '', '*');
        foreach ($instances as $instance) {
            $user_enrolement = enrol_get_plugin('manual');
            $user_enrolement->enrol_user($instance, $userid, $role_id, $timestart, 0, NULL);
        }
    }

    /**
     * Fonction de modification de la date de début de session des classes virtuelles pour les parcours duppliqués
     * @param $courseid
     * @return bool
     * @throws dml_exception
     */
    static function get_via_activities_in_course($courseid)
    {
        global $DB;
        $via_activities = $DB->get_records('via', array('course' => $courseid));
        if (!$via_activities) {
            return false;
        }

        foreach ($via_activities as $via_activity) {
            $update_via = new stdClass();
            $update_via->id = $via_activity->id;
            $update_via->datebegin = 0;
            $update_via->viaactivityid = null;
            $update_via->activitytype = 3;

            try {
                $DB->update_record('via', $update_via);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        return true;
    }
}

