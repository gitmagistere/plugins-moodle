<?php

/**
 * Class WFEnrolManual. Ajout d'utilisateurs en utilisant un champ texte dans le formulaire de workflow.
 */
class WFEnrolManual {

    private $form;
    private $data;
    private $role;

    /**
     * WFEnrolManual constructor.
     * @param $courseid
     * @param $role
     */
    public function __construct($courseid, $role){
        $this->courseid = $courseid;
        $this->role = $role;
        $this->form = $this->loadForm();
        $this->data = $this->getData();
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
        return $data;
    }

    /**
     * Fonction qui charge le type de formulaire à afficher, ici le formulaire d'ajout d'utilisateur par champ text
     * en l'occurence.
     * @return mixed|WFEnrolManualForm
     */
    protected function loadForm() {
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/form/WFEnrolManualForm.php');

        $formData = array(
            'data' => $this->data
        );

        return new WFEnrolManualForm(null, $formData);
    }

    /**
     * Fonction qui génère la partie du formulaire nécessaire à l'utilisation du formulaire d'ajout d'utilisateurs.
     * @param null $mform
     * @return mixed|void
     */
    public function getFrameElement(&$mform = null) {
        return $this->form->construct_form($mform, $this->data);
    }

    /**
     * Fonction appelée à la suite de la validation du formulaire qui traite la liste d'emails saisie dans le champ text.
     * @param null $data
     * @return bool|mixed|void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function processForm($data = null) {
        if($data != null){
            $groups = null;
            $groups_field = $this->role.'_groups_user_enrol';
            if(isset($data->$groups_field)){
                $groups = $data->$groups_field;
            }
            $data_field = $this->role."_email_user_enrol";
            if(isset($data->$data_field)){
                return $this->process_email_user_enrol($data->$data_field, $this->courseid, $this->role, $groups);
            }
            return false;
        }
    }

    /**
     * Fonction permettant de créer une liste à partir d'une string et de plusieurs caractères de sépararion.
     * Dans cette class, les emails peuvent être séparés par ",", ";" ou encore "|".
     * @param $delimiters
     * @param $string
     * @return array
     */
    public static function multiexplode($delimiters, $string) {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }

    /**
     * Fonction qui procède à l'ajout des utilisateurs dans le parcours. Si l'email saisie ne corresponds à
     * aucun utilisateur, il est alors créer. Si un groupe à été spécifié dans le formulaire, l'utilisateur sera alors
     * ajouté dans ce même groupe.
     * @param $list_email
     * @param $courseid
     * @param $role
     * @param $groups
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function process_email_user_enrol($list_email, $courseid, $role, $groups){
        global $DB, $CFG;

        require_once($CFG->libdir.'/enrollib.php');

        $v = "/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9\-\.]+\.[a-zA-Z]+$/";
        $vIgnored = "/^[a-zA-Z0-9_\-\.]+@(?i)ac-normandie\.fr$/";
        $manual = enrol_get_plugin('manual');
        $context = context_course::instance($courseid);
        $emails = self::multiexplode(array(",",";","|"),$list_email);
        $role = $DB->get_record('role', array('shortname' => $role), '*', MUST_EXIST);

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

        foreach ($emails as $email) {
            if(trim($email)=="" || !(preg_match($v, trim($email)))) continue;
            if((preg_match($vIgnored, trim($email)))) continue; // Cas ou l'email est ignore (email en @ac-normandie.fr)

            $user = $DB->get_record('user', array('email' => strtolower(trim($email))));
            if($user) {
                if (!$user->deleted && !$user->suspended)
                {
                    if(is_enrolled($context,$user)) { // Si l'utilisateur est déjà inscrit
                        if($role){
                            role_assign($role->id, $user->id, $context->id);
                            if(isset($groups)){
                                $this->add_member_to_groups($groups,$user->id,$courseid);
                            }
                        }
                    } else {
                        // On inscrit l'utilisateur
                        // Ajout du rôle et du groupe pour un fichier simple
                        if($role){
                            $manual->enrol_user($enrolment,$user->id,$role->id,time());
                            if(isset($groups)){
                                $this->add_member_to_groups($groups,$user->id,$courseid);
                            }
                        }
                    }
                }
            } else {
                $email = clean_param($email, PARAM_USERNAME);
                $array = $this->generate_firstname_lastname_using_email(strtolower(trim($email))); // Génération des noms et prénoms à partir de l'email.

                $record = new stdClass();
                $record->email = $email;
                $record->password = generate_password(8);
                $record->country = 'FR';
                $record->lang = $CFG->lang;
                $record->confirmed = 1;
                $record->mnethostid = 1;
                $record->username = $email;
                $record->firstname = ucfirst($array['firstname']);
                $record->lastname = ucfirst($array['lastname']);
                $userid = user_create_user($record);//$DB->insert_record('user', $record, true);

                // On inscrit le nouvel utilisateur
                if($role){
                    $manual->enrol_user($enrolment,$userid,$role->id,time());
                    if(isset($groups)){
                        $this->add_member_to_groups($groups,$userid,$courseid);
                    }
                }
            }
        }
    }

    /**
     * Fonction qui permet d'ajouter un utilisateur dans un ou plusieurs groupes existant.
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
     * Fonction qui génère le nom et prénom d'un nouvel utilisateur en utilisant l'email saisie dans le formulaire.
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
                // Cas ou il n'y a pas de prenom dans l'email.
                $firstName = "";
                $lastName = $rawFirstLastName;
            }

            return array('firstname' => ucfirst($firstName), 'lastname' => ucfirst($lastName));
        }
        return [];
    }
}