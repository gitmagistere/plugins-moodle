<?php

/**
 * Class WFStepSessionCours. Etat 4 du workflow.
 */
class WFStepSessionCours extends WFStep {
    private $form;
    private $notification;

    /**
     * WFStepSessionCours constructor.
     * @param $courseid
     * @param $is_title
     */
    public function __construct($courseid, $is_title) {
        $this->courseid = $courseid;
        $this->is_title = $is_title;
        $this->context = context_course::instance($courseid);
        $this->status = WKF_IS_SESSION_COURS;
        $this->main_category = $this->mainCategory();
        $this->notification = '';
    }

    /**
     * Fonction qui détermine les conditions nécessaires pour passer à l'état "Session archivée".
     * @return bool|mixed
     */
    public function isValidateState() {
        $main_category = wf_get_main_category();
        if($main_category != WKF_CAT_ARC){
            if(!$this->is_title){
                $this->form = $this->loadForm();
            }
            return true;
        }
        return false;
    }

    /**
     * Fonction qui permet de déterminer si le formulaire du workflow a été validé.
     * @return bool|mixed
     */
    public function isSubmittedForm() {
        if($this->form != null){
            if($this->form->get_data() != null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fonction qui créé une instance de l'état suivant à savoir "Session archivée.
     * @return mixed|WFStepSessionArchive
     */
    public function getNextStep() {
        return new WFStepSessionArchive($this->courseid, $this->is_title);
    }

    /**
     * Fonction qui retourne le nom de l'état "Session en cours" sous forme de texte. Si le parcours est
     * publié sur l'offre de parcours, le nom est transformé en "Session publiée".
     * @return mixed|string
     * @throws coding_exception
     */
    public function getStepName() {
        if (isCourseHubAvailable()){
            require_once($GLOBALS['CFG']->dirroot.'/local/coursehub/CourseHub.php');
            if(courseIsPublished($this->courseid, CourseHub::PUBLISH_PUBLISHED)){
                if(wf_get_main_category() == WKF_CAT_SLAF){
                    return get_string('status_local_publish', 'local_workflow');
                }
                return get_string('status_publish', 'local_workflow');
            }
        }
        return get_string('banner_session_en_cours_title', 'local_workflow');
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher sur le workflow
     * pour l'état "Session en cours". Dans cet état, cette fonction retourne un tableau vide
     * car aucune notification badge n'est à afficher.
     * @return array|mixed
     */
    public function getNotificationBadges(){
        return array();
    }

    /**
     * Fonction génère les notification badges au format HTML. Vu qu'aucune notification badge n'est générée,
     * cette fonction retourne une chaine de caractère vide.
     * @return mixed|string
     */
    public function getNotificationBadgesHTML(){
        return '';
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher dans le formulaire d'indexation du parcours
     * pour l'état "Session en cours". Dans cet état, cette fonction retourne un tableau vide
     * car aucune notification badge n'est à afficher.
     * @return array|mixed
     */
    public function getIndexationNotificationBadges(){
        return array();
    }

    /**
     * Fonction génère les notification badges au format HTML. Vu qu'aucune notification badge n'est générée,
     * cette fonction retourne une chaine de caractère vide.
     * @return mixed|string
     */
    public function getIndexationNotificationBadgesHTML(){
        return '';
    }

    /**
     * Fonction génère la bannière du workflow pour l'état "Session en cours" au format HTML.
     * @return mixed|string
     * @throws coding_exception
     */
    protected function getHeader() {
        $header = "";
        $header .= html_writer::start_div('header');
        $header .= html_writer::start_div('timeline');
        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x')).
                html_writer::span('1','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_gabarit_title', 'local_workflow'), 'title'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x')).
                html_writer::span('2','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_parcours_title', 'local_workflow'), 'title'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x')).
                html_writer::span('3','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_preparation_title', 'local_workflow'), 'title'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x actual')).
                html_writer::span('4','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_en_cours_title', 'local_workflow'), 'title actual'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x soon')).
                html_writer::span('5','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_archive_title', 'local_workflow'), 'title soon'),'banner-title');

        $header .= html_writer::end_div();
        $header .= html_writer::start_div('content');
        $lng = new stdClass();
        $lng->participant = WKF_ROLE_NAME_PARTICIPANT;
        $lng->formateur = WKF_ROLE_NAME_FORMATEUR;
        $header .= get_string('banner_session_en_cours_description', 'local_workflow', $lng);
        $header .= html_writer::end_div();
        $header .= html_writer::end_div();
        $header .= $this->getActionButton();
        return $header;
    }

    /**
     * Fonction qui détermine quelles sont les actions possibles du workflow pour l'état "Session en cours".
     * @return bool|string
     */
    protected function getActionButton() {
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/lib/dialogs.php');

        // get the main category
        $coursecat = wf_get_main_category();

        //définition des types de lien à afficher
        $l_links_type = wf_get_main_action_links($coursecat, $this->courseid);

        if(count($l_links_type) != 0){
            return wf_main_action_content($l_links_type, $this->courseid);
        }
        return false;
    }

    /**
     * Fonction qui charge le type de formulaire à afficher pour l'état "Session en cours".
     * @return WFSessionPreparationForm
     * @throws dml_exception
     */
    private function loadForm() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/workflow/form/WFSessionPreparationForm.php');

        $course = $DB->get_record('course', array('id' => $this->courseid));
        
        $formData = array(
            'id' => $this->courseid,
            'course' => $course,
            'contextid' => context_course::instance($this->courseid)->id,
            'status' => $this->status,
            'main_category' => $this->main_category
        );
        
        if (isIndexationAvailable()){
            $formData['indexation'] = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
            $formData['notification_indexation_badges'] = $this->getIndexationNotificationBadges();
        }
        
        return new WFSessionPreparationForm(null, $formData);
    }

    /**
     * Fonction génère le formulaire de workflow au format HTML.
     * @return mixed|string
     */
    protected function getForm() {
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/lib/dialogs.php');
        
        $isalocalsession = 0;
        $main_category = wf_get_main_category();
        if($main_category == WKF_CAT_SLAF){
            $isalocalsession = 1;
        }

        $source = html_writer::start_div('form session-cours');
        $source .= $this->form->render();
        $source .= html_writer::end_div();
        $source .= dialog_preview_mails();
        
        require_once($CFG->dirroot.'/local/workflow/lib/Gaia.php');
        if (Gaia::isAvailable()){
            $source .= Gaia::unlink_dialog();
        }
        
        $source .= wf_secondary_generate_action_dialogs($this->main_category, $this->courseid, $this->status);
        
        if (isCourseHubAvailable()){
            require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');
            $source .= dialog_publish($this->courseid, "publish", $isalocalsession);
    
            if(courseIsPublished($this->courseid,CourseHub::PUBLISH_PUBLISHED)){
                $source .= dialog_unpublish($this->courseid, 'publish');
            }
            if(courseIsPublished($this->courseid,CourseHub::PUBLISH_SHARED)){
                $source .= dialog_unpublish($this->courseid, 'share');
            }
        }
        return $source;
    }

    /**
     * Fonction qui traite les données validées et transitées en post du formulaire de workflow.
     * @return mixed|void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function processForm() {
        global $DB, $CFG;

        if($this->form != null){
            $data = $this->form->get_data();

            $csvform = new WFEnrolCSV($this->courseid, WKF_ROLE_FORMATEUR);
            $csvform->processForm($data);

            $enroluserform = new WFEnrolManual($this->courseid, WKF_ROLE_FORMATEUR);
            $enroluserform->processForm($data);

            $csvform = new WFEnrolCSV($this->courseid, WKF_ROLE_TUTEUR);
            $csvform->processForm($data);

            $enroluserform = new WFEnrolManual($this->courseid, WKF_ROLE_TUTEUR);
            $enroluserform->processForm($data);

            $csvform = new WFEnrolCSV($this->courseid, WKF_ROLE_PARTICIPANT);
            $csvform->processForm($data);

            $enroluserform = new WFEnrolManual($this->courseid, WKF_ROLE_PARTICIPANT);
            $enroluserform->processForm($data);

            // update course dates
            $c = new stdClass();
            $c->id = $data->id;
            $c->startdate = $data->startdate;
            $c->enddate = $data->enddate;
            $DB->update_record('course', $c);
            
            if (isIndexationAvailable()){
                $dbdata = new stdClass();
                $dbdata->courseid = $data->id;
                $dbdata->tps_a_distance = $data->tps_a_distance;
                $dbdata->tps_en_presence = $data->tps_en_presence;
                $dbdata->collectionid = $data->collection;
                $dbdata->updatedate = time();
                
                $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
    
                if($indexation !== false){
                    $dbdata->id = $indexation->id;
                    $DB->update_record('local_indexation', $dbdata);
                    $this->notification = get_string('notification_indexation_updated', 'local_workflow');
                }else{
                    $dbdata->id = $DB->insert_record('local_indexation', $dbdata);
                    $this->notification =  get_string('notification_indexation_created', 'local_workflow');
                }
            }
            redirect(new moodle_url($CFG->wwwroot . '/local/workflow/index.php', array('id' => $this->courseid)), $this->notification, null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    /**
     * Fonction qui détermine quel type de message à afficher à la suite du traitement du formulaire de workflow
     * pour l'état "Session en cours".
     * @return mixed|string
     */
    public function getNotification(){
        return $this->notification ;
    }

    /**
     * Fonction qui détermine la catégorie principale dans lequel de se trouve le parcours.
     * @return mixed
     */
    private function mainCategory(){
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/lib/dialogs.php');

        return wf_get_main_category();
    }
}