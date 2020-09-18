<?php

/**
 * Class WFEnrolCSV. Ajout d'utilisateurs par fichier CSV sur le formulaire de workflow.
 */
class WFEnrolCSV {
    private $context;
    private $course;
    private $options;
    private $data;
    private $form;
    private $role;

    /**
     * WFEnrolCSV constructor.
     * @param $courseid
     * @param $role
     * @throws dml_exception
     */
    public function __construct($courseid, $role){
        $this->courseid = $courseid;
        $this->role = $role;
        $this->context = context_course::instance($courseid);
        $this->course = $this->getCourse();
        $this->options = $this->getOptions();
        $this->data = $this->getData();
        $this->form = $this->loadForm();
    }

    /**
     * Fonction qui retourne les options démandées pour la création du formulaire d'ajout d'utilisateurs pas CSV.
     * @return array
     */
    protected function getOptions(){
        global $CFG;
        require_once($CFG->dirroot.'/repository/lib.php');

        return array('subdirs' => 1, 'maxbytes' => $CFG->userquota, 'maxfiles' => - 1, 'accepted_types' => array('*.csv','*.txt'), 'return_types' => FILE_INTERNAL);
    }

    /**
     * Fonction qui retourne le parcours concerné par le workflow.
     * @return mixed
     * @throws dml_exception
     */
    protected function getCourse(){
        global $DB;
        return $DB->get_record('course', array('id'=>$this->courseid));
    }

    /**
     * Fonction qui récupère des données nécessaire au fonctionnement du formulaire puis créer un objet pour préparer
     * ce même formulaire.
     * @return stdClass
     */
    protected function getData(){
        $data = new stdClass();
        $data->courseid = $this->courseid;
        $data->role = $this->role;
        $data->coursename = $this->course->fullname;
        $data->contextid = $this->context->id;
        return $data;
    }

    /**
     * Fonction qui charge le type de formulaire à afficher, ici le formulaire d'ajout d'utilisateur par fichier CSV
     * en l'occurence.
     * @return mixed|WFEnrolCSVForm
     */
    protected function loadForm(){
        global $CFG, $PAGE;
        require_once($CFG->dirroot.'/local/workflow/form/WFEnrolCSVForm.php');
        $formData = array(
            'options' => $this->options,
            'data' => $this->data
        );

        $ajax_log_url = $CFG->wwwroot.'/local/workflow/ajax/WFEnrolApi.php';
        $PAGE->requires->js_call_amd("local_workflow/WFEnrolCSVFilepicker", "init", array($this->data->courseid, $ajax_log_url, $this->data->role));
        
        return new WFEnrolCSVForm(null, $formData);
    }

    /**
     * Fonction qui génère la partie du formulaire nécessaire à l'utilisation du filepicker.
     * @param $mform
     * @return stdClass Permet le mapping de l'id du champs filemanager pour l'historique des fichiers uploadés dans le formulaire.
     */
    public function getFrameElement(&$mform = null){
        file_prepare_standard_filemanager($this->data, $this->role.'_files', $this->options, $this->context, 'user', 'workflowcsvenrol_'.$this->role, 0);
        $this->form->construct_form($mform, $this->data, $this->options);
        return $this->data;
    }

    /**
     * Fonction appelée à la suite de la validation du formulaire. Préparation et backup du fichier csv simple ou complexe.
     * @param null $data
     * @return mixed|void
     * @throws coding_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public function processForm($data = null){
        global $USER;

        if($data != null) {
            //upload file, store, and process csv
            $draftid = file_get_submitted_draft_itemid($this->role.'_userfile');

            $fpinfo = file_get_draft_area_info($draftid);

            if($fpinfo['filecount'] != 0){
                $content = $this->file_get_contents_utf8($draftid);//save uploaded file

                $fs = get_file_storage();

                //Cleanup old files:
                //First, create target directory:
                if (!$fs->file_exists($data->contextid, 'user', 'workflowcsvenrol_'.$this->role, 0, '/', 'History'))
                    $fs->create_directory($data->contextid, 'user', 'workflowcsvenrol_'.$this->role, 0, '/History/', $USER->id);

                //Second, move all files to created dir
                $areafiles = $fs->get_area_files($data->contextid, 'user', 'workflowcsvenrol_'.$this->role, false, "filename", false);
                $filechanges = array("filepath" => '/History/');
                foreach ($areafiles as $key => $areafile) {
                    if ($areafile->get_filepath() == "/") {
                        $fs->create_file_from_storedfile($filechanges, $areafile); //copy file to new location
                        $areafile->delete(); //remove old copy
                    }
                }

                $filename = "upload_" . date("Ymd_His") . ".csv";

                // Prepare file record object
                $fileinfo = array(
                    'contextid' => $data->contextid, // ID of context
                    'component' => 'user',     // usually = table name
                    'filearea' => 'workflowcsvenrol_'.$this->role,     // usually = table name
                    'itemid' => 0,               // usually = ID of row in table
                    'filepath' => '/',           // any path beginning and ending in /
                    'filename' => $filename,// any filename
                    'userid' => $USER->id);

                // Create file containing uploaded file content
                $newfile = $fs->create_file_from_string($fileinfo, $content);

                // Read CSV and get results
                $log = $this->process_csv_enrol_users($data->id, $content, $data);

                //save log file, reuse fileinfo from csv file
                $fileinfo['filename'] = "upload_" . date("Ymd_His") . "_log.txt";
                $newfile = $fs->create_file_from_string($fileinfo, $log);
            }
        }
    }

    /**
     * Fonction qui permet de définir le processus de création des utilisateurs par fichier csv simple ou complexe.
     * @param $courseid
     * @param $csvcontent
     * @param $formdata
     * @return string permet l'alimentation du fichier de log visible dans l'historique (filemanager) du formulaire.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function process_csv_enrol_users($courseid, $csvcontent, $formdata)
    {
        global $DB, $CFG;
        require_once($CFG->libdir.'/enrollib.php');
        require_once($CFG->libdir.'/csvlib.class.php');
        require_once($CFG->libdir.'/accesslib.php');

        //get enrolment instance (manual and student)
        $instances = enrol_get_instances($courseid, false);
        $enrolment = "";
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                $enrolment = $instance;
                break;
            }
        }
        
        if ($enrolment == false){
            print_error('no_manual_enrol_method_found: Aucun methode d\'inscription manuelle trouvée sur ce parcours!');
        }

        //get enrolment plugin
        $manual = enrol_get_plugin('manual');
        $context = context_course::instance($courseid);
        $returnurl = new moodle_url($CFG->wwwroot.'/course/view.php', array('id' => $courseid));

        $stats = new StdClass();
        $stats->success = $stats->failed = 0; //init counters
        $log = get_string('enrolling','local_workflow')."\r\n";

        // Test du fichier (simple ou complexe)
        $cir = $this->load_content_csv_enrol($csvcontent, $returnurl);
        $columns = $cir->get_columns();

        $v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/";
        $vIgnored = "/^[a-zA-Z0-9_\-\.]+@(?i)ac-normandie\.fr$/";
        
        $role = $DB->get_record('role', array('shortname' => $this->role), '*', MUST_EXIST);

        // Si le fichier a plus d'une colonne, c'est un fichier complexe
        if(count($columns) > 1) {
            $lines = explode("\n",$csvcontent);
            foreach ($lines as $key=>$line) {
                if(trim($line)=="") continue;
                $rawuser = explode(";",$line); // email = $rawuser[0], role = $rawuser[1], $rawuser[n] = nom de groupe(s)
                if(trim($rawuser[0])!="" && trim($rawuser[1])!=""){ // Si l'email et / ou le role n'est pas vide on poursuit le traitement
                    if($key == 0 && !(preg_match($v, trim($rawuser[0])))) continue; // Cas où le fichier contient une entete
                    if(!(preg_match($v, trim($rawuser[0])))) continue; // Cas où l'email est mal écrit
                    if((preg_match($vIgnored, trim($rawuser[0])))) continue; // Cas où l'email est ignoré (email en @ac-normandie.fr)

                    $user = $DB->get_record('user', array('email' => strtolower(trim($rawuser[0]))));
                    if($user) {
                        if (!$user->deleted && !$user->suspended){
                            if(is_enrolled($context,$user)) {
                                $log .= get_string('alreadyenrolled','local_workflow',fullname($user).' ('.$user->username.')')."\r\n";

                                if($role){
                                    role_assign($role->id, $user->id, $context->id);
                                    $this->group_and_user_management_for_complex_file_csv_enrol($rawuser, $user->id, $courseid);
                                    $stats->success++;
                                } else {
                                    $log .= get_string('rolenotfound','local_workflow', $this->role)."\r\n";
                                    $stats->failed++;
                                }

                            } else { // Cas où le user n'est pas inscrit dans le parcours
                                $log .= get_string('enrollinguser','local_workflow',fullname($user).' ('.$user->username.')')."\r\n";

                                // Ajout du rôle et du groupe pour un fichier simple
                                if($role){
                                    $manual->enrol_user($enrolment,$user->id,$role->id,time());
                                    $this->group_and_user_management_for_complex_file_csv_enrol($rawuser, $user->id, $courseid);
                                    $stats->success++;
                                } else {
                                    $log .= get_string('rolenotfound','local_workflow', $this->role)."\r\n";
                                    $stats->failed++;
                                }
                            }
                        }
                        $stats->success++;
                    } else { // Cas où le user n'existe pas sur la plateforme
                        $clean_username = clean_param($rawuser[0], PARAM_USERNAME);
                        $array = $this->generate_firstname_lastname_using_email(strtolower(trim($rawuser[0]))); // Génération des noms et prénoms à partir de l'email.

                        $record = new stdClass();
                        $record->email = $clean_username;
                        $record->password = generate_password(8);
                        $record->country = 'FR';
                        $record->lang = $CFG->lang;
                        $record->confirmed = 1;
                        $record->mnethostid = 1;
                        $record->username = $clean_username;
                        $record->firstname = ucfirst($array['firstname']);
                        $record->lastname = ucfirst($array['lastname']);
                        $userid = user_create_user($record);
                        $log .= get_string('enrollinguser','local_workflow',$record->firstname.' '.$record->lastname .' ('.$record->username.')')."\r\n";

                        // Ajout du rôle et du groupe pour un fichier complexe
                        $manual->enrol_user($enrolment,$userid,$role->id,time());
                        $this->group_and_user_management_for_complex_file_csv_enrol($rawuser, $userid, $courseid);

                        $stats->success++;
                    }
                }
            }
        }
        else
        {
            $lines = explode("\n",$csvcontent);

            foreach ($lines as $key=>$line) {
                if(trim($line)=="") continue;
                if($key == 0 && !(preg_match($v, trim($line)))) continue; // Cas où le fichier contient une entete
                if(!(preg_match($v, trim($line)))) continue; // Cas où l'email est mal écrit
                if((preg_match($vIgnored, trim($line)))) continue; // Cas où l'email est ignoré (email en @ac-normandie.fr)
                $user = $DB->get_record('user', array('email' => strtolower(trim($line))));

                if($user) {
                    if (!$user->deleted && !$user->suspended)
                    {
                        if(is_enrolled($context,$user)) {
                            $log .= get_string('alreadyenrolled','local_workflow',fullname($user).' ('.$user->username.')')."\r\n";

                            if($role){
                                role_assign($role->id, $user->id, $context->id);
                                if(isset($formdata->groups)){
                                    $this->add_member_to_groups($formdata->groups,$user->id,$courseid);
                                }
                            } else {
                                $log .= get_string('rolenotfound','local_workflow',trim($line))."\r\n";
                                $stats->failed++;
                            }
                        } else {
                            $log .= get_string('enrollinguser','local_workflow',fullname($user).' ('.$user->username.')')."\r\n";

                            // Ajout du rôle et du groupe pour un fichier simple
                            if($role){
                                $manual->enrol_user($enrolment,$user->id,$role->id,time());
                                if(isset($formdata->groups)){
                                    $this->add_member_to_groups($formdata->groups,$user->id,$courseid);
                                }
                            } else {
                                $log .= get_string('rolenotfound','local_workflow',trim($line))."\r\n";
                                $stats->failed++;
                            }
                        }
                        $stats->success++;
                    }
                } else {
                    $line = clean_param($line, PARAM_USERNAME);
                    $array = $this->generate_firstname_lastname_using_email(strtolower(trim($line))); // Génération des noms et prénoms à partir de l'email.

                    $record = new stdClass();
                    $record->email = $line;
                    $record->password = generate_password(8);
                    $record->country = 'FR';
                    $record->lang = $CFG->lang;
                    $record->confirmed = 1;
                    $record->mnethostid = 1;
                    $record->username = $line;
                    $record->firstname = ucfirst($array['firstname']);
                    $record->lastname = ucfirst($array['lastname']);
                    $userid = user_create_user($record);//$DB->insert_record('user', $record, true);

                    $log .= get_string('enrollinguser','local_workflow',$record->firstname.' '.$record->lastname .' ('.$record->username.')')."\r\n";

                    if($role){
                        $manual->enrol_user($enrolment,$userid,$role->id,time());
                        if(isset($formdata->groups)){
                            $this->add_member_to_groups($formdata->groups,$userid,$courseid);
                        }
                        $stats->success++;
                    } else {
                        $log .= get_string('rolenotfound','local_workflow',trim($line))."\r\n";
                        $stats->failed++;
                    }
                }
            }
        }
        $log .= get_string('done','local_workflow')."\r\n";
        $log = get_string('status','local_workflow',$stats).' '.get_string('enrolmentlog','local_workflow')."\r\n\r\n".$log;
        return $log;
    }

    /**
     * Fonction permettant d'ajouter un utilisateur dans un ou plusieurs groupes existant.
     * @param $groupids
     * @param $userid
     * @param $courseid
     * @throws coding_exception
     * @throws dml_exception
     */
    private function add_member_to_groups($groupids, $userid, $courseid) {
        global $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        if(is_string($groupids)){ // Cas spécifique où un seul groupe est sélectionné pour un fichier simple.
            $groupids = explode(',', $groupids);
        }
        if(count($groupids) > 0){
            foreach($groupids as $groupid){
                $group_obj = groups_get_group($groupid);
                if($group_obj){
                    groups_add_member($group_obj->id, $userid);
                }
            }
        }
    }

    /**
     * Fonction qui permemt de créer un groupe en connaissant le nom et l'id du parcours.
     * @param $groupname
     * @param $courseid
     * @return mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function create_group_csv_enrol($groupname, $courseid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        if($groupname && $courseid){
            $newgroup = new stdClass();
            $newgroup->name = $groupname;
            $newgroup->courseid = $courseid;
            $newgroup->timecreated = time();
            $newid = groups_create_group($newgroup);
            return $DB->get_record('groups', array('id' => $newid), '*', MUST_EXIST);
        }
    }

    /**
     * Fonction qui gère la création ou non de groupe + ajout d'un user dans ce même groupe.
     * @param $rawuser
     * @param $userid
     * @param $courseid
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function group_and_user_management_for_complex_file_csv_enrol($rawuser, $userid, $courseid) {
        global $DB;

        if($rawuser && $userid && $courseid){
            $groupids = array(); // on créé une liste d'id de groupe

            for($i = 2; $i <= count($rawuser)-1; ++$i) { // on démarre à 2 car les 2 premiers sont l'email et le role.
                if(trim($rawuser[$i])=="") continue;
                $group = $DB->get_record('groups', array('name' => trim($rawuser[$i]), 'courseid' => $courseid));
                if(!($group)){ // on crée le groupe
                    $group = $this->create_group_csv_enrol(trim($rawuser[$i]), $courseid);
                }
                $groupids[] = $group->id;
            }
            // on affecte le user dedans
            $this->add_member_to_groups($groupids, $userid, $courseid);
        }
    }

    /**
     * Fonction qui permet l'utilisation des fonctions de chargement d'un fichier csv et vérification des erreurs.
     * @param $csvcontent
     * @param $returnurl
     * @return csv_import_reader
     * @throws moodle_exception
     */
    private function load_content_csv_enrol($csvcontent, $returnurl) {
        global $CFG;
        require_once($CFG->libdir.'/csvlib.class.php');

        $iid = csv_import_reader::get_new_iid('uploaduser');
        $cir = new csv_import_reader($iid, 'uploaduser');

        $cir->load_csv_content($csvcontent, 'UTF-8', 'semicolon');

        unset($csvcontent);

        $columns = $cir->get_columns();
        if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            print_error('cannotreadtmpfile', 'error', $returnurl);
        }

        return $cir;
    }

    /**
     * Fonction qui génère une adresse mail en fonction du nom et prénom du ou des utilisateurs présents
     * dans le fichier CSV.
     * @param $email
     * @return array
     */
    private function generate_firstname_lastname_using_email($email) {
        $posRawFirstLastName = strrpos($email, "@", -1);
        if($posRawFirstLastName != false) {
            $rawFirstLastName = substr($email, 0, $posRawFirstLastName);
            $rawFirstLastName = preg_replace("/[^A-Z-.a-z]/", "", $rawFirstLastName); // Si la combinaison nom prénom contient aussi des chiffres (ex: gerard.dupont45@magistere.fr)

            $posFirstLastName = strrpos($rawFirstLastName, ".");
            if ($posFirstLastName != false) {
                $firstName = substr($rawFirstLastName, 0, $posFirstLastName);
                $lastName = substr($rawFirstLastName, $posFirstLastName + 1);
            } else {
                // Cas où il n'y a pas de prénom dans l'email.
                $firstName = "";
                $lastName = $rawFirstLastName;
            }

            return array('firstname' => ucfirst($firstName), 'lastname' => ucfirst($lastName));
        }
    }

    /**
     * Fonction qui permet d'utiliser et traduire le fichier CSV avec un encodage UTF-8.
     * @param $draftid
     * @return string
     */
    private function file_get_contents_utf8($draftid) {
        global $USER;
        $fs = get_file_storage();
        $context = context_user::instance($USER->id);
        $draftareaFiles = file_get_drafarea_files($draftid, false);
        $fileinfo = $draftareaFiles->list[0];
        file_save_draft_area_files($draftid, $context->id, 'user', 'workflowcsvenrol_'.$this->role, 0);
        $file = $fs->get_file($context->id, 'user', 'workflowcsvenrol_'.$this->role,
            0, $fileinfo->filepath, $fileinfo->filename);

        return $file->get_content();
    }
}