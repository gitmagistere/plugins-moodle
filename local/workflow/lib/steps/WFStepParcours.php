<?php

/**
 * Class WFStepParcours. Etat 2 du workflow.
 */
class WFStepParcours extends WFStep {
    private $form;
    private $notification;

    /**
     * WFStepParcours constructor.
     * @param $courseid
     * @param $is_title
     */
    public function __construct($courseid, $is_title) {
        $this->courseid = $courseid;
        $this->is_title = $is_title;
        $this->notification = '';
    }

    /**
     * Fonction qui détermine les conditions nécessaires pour passer à l'état "Session en préparation".
     * @return bool|mixed
     * @throws dml_exception
     */
    public function isValidateState() {
        $main_category = wf_get_main_category();
        if($main_category == WKF_CAT_PDF){
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
     * Fonction qui créé une instance de l'état suivant à savoir "Session en préparation".
     * @return mixed|WFStepSessionPreparation
     */
    public function getNextStep() {
        return new WFStepSessionPreparation($this->courseid, $this->is_title);
    }

    /**
     * Fonction qui retourne le nom de l'état "Parcours en construction" sous forme de texte. Si le parcours est
     * publié sur l'offre de parcours, le nom est transformé en "Parcours publié".
     * @return mixed|string
     * @throws coding_exception
     */
    public function getStepName() {
        if (isCourseHubAvailable()){
            if(course_is_published($this->courseid) !== false &&
                course_is_published($this->courseid) == CourseHub::PUBLISH_SHARED){
                return get_string('status_share', 'local_workflow');
            }
        }
        return get_string('banner_parcours_title', 'local_workflow');
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher sur le workflow
     * pour l'état "Parcours en construction".
     * @return array|mixed
     * @throws dml_exception
     */
    public function getNotificationBadges(){
        return $this->getIndexationNotificationBadges();
    }

    /**
     * Fonction génère les notification badges au format HTML.
     * @return bool|mixed|string
     * @throws dml_exception
     */
    public function getNotificationBadgesHTML(){
        return generate_notification_HTML(count($this->getNotificationBadges()));
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher dans le formulaire d'indexation du parcours
     * pour l'état "Parcours en construction".
     * @return array|mixed
     * @throws dml_exception
     */
    public function getIndexationNotificationBadges(){
        global $DB;
        
        if(!isIndexationAvailable()){
            return array();
        }
        
        $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
        if($indexation){
            $list = array();
            $required_fields = array(
                'year' => 'version',
                'authors' => 'organisme',
                'origin' => 'organisme',
                'title' => 'general',
                'version' => 'version'
            );

            foreach($required_fields as $field => $tab){
                if(!$indexation->$field){
                    $list[$field] = $tab;
                }
            }

            return $list;
        }
        return array();
    }

    /**
     * Fonction génère les notification badges au format HTML.
     * @return bool|mixed|string
     * @throws dml_exception
     */
    public function getIndexationNotificationBadgesHTML(){
        return generate_notification_HTML(count($this->getIndexationNotificationBadges()));
    }

    /**
     * Fonction génère la bannière du workflow pour l'état "Parcours en construction" au format HTML.
     * @return mixed|string
     * @throws coding_exception
     * @throws dml_exception
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
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x actual')).
                html_writer::span('2','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_parcours_title', 'local_workflow'), 'title actual'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x soon')).
                html_writer::span('3','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_preparation_title', 'local_workflow'), 'title soon'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x soon')).
                html_writer::span('4','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_en_cours_title', 'local_workflow'), 'title soon'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x soon')).
                html_writer::span('5','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_archive_title', 'local_workflow'), 'title soon'),'banner-title');

        $header .= html_writer::end_div();
        $header .= html_writer::start_div('content');
        $header .= get_string('banner_parcours_description', 'local_workflow', WKF_ROLE_NAME_CONCEPTEUR);
        $header .= html_writer::end_div();
        $header .= html_writer::end_div();
        $header .= $this->getActionButton();
        return $header;
    }

    /**
     * Fonction qui détermine quelles sont les actions possibles du workflow pour l'état "Parcours en construction".
     * @return bool|string
     * @throws dml_exception
     */
    protected function getActionButton() {
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/lib/dialogs.php');

        // get the main category
        $coursecat = wf_get_main_category();

        //définition des types de lien à afficher
        if(count($this->getIndexationNotificationBadges()) != 0){
            $l_links_type = wf_get_main_action_links($coursecat, $this->courseid, true);
        } else {
            $l_links_type = wf_get_main_action_links($coursecat, $this->courseid);
        }
        if(count($l_links_type) != 0){
            return wf_main_action_content($l_links_type, $this->courseid);
        }
        return false;
    }

    /**
     * Fonction qui charge le type de formulaire à afficher pour l'état "Parcours en construction".
     * @return WFParcoursForm
     * @throws dml_exception
     */
    private function loadForm() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/workflow/form/WFParcoursForm.php');
        
        $formData = array(
            'id' => $this->courseid,
            'contextid' => context_course::instance($this->courseid)->id,
        );
        
        if(isIndexationAvailable()){
            $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
            $formData['indexation'] = $indexation;
            $formData['notification_indexation_badges'] = $this->getIndexationNotificationBadges();
        }

        return new WFParcoursForm(null, $formData);
    }

    /**
     * Fonction génère le formulaire de workflow au format HTML.
     * @return mixed|string
     */
    protected function getForm() {
        global $CFG;
        
        $source = html_writer::start_div('form parcours');
        $source .= $this->form->render();
        $source .= html_writer::end_div();
        $source .= dialog_preview_mails();
        
        if (isCourseHubAvailable()){
            require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');
            $source .= dialog_publish($this->courseid, "share");
    
            if(course_is_published($this->courseid) !== false){
                $method = "share";
                if(course_is_published($this->courseid) == CourseHub::PUBLISH_PUBLISHED){
                    $method = "publish";
                }
                $source .= dialog_unpublish($this->courseid, $method);
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
            
            $csvform = new WFEnrolCSV($this->courseid, WKF_ROLE_CONCEPTEUR);
            $csvform->processForm($data);

            $enroluserform = new WFEnrolManual($this->courseid, WKF_ROLE_CONCEPTEUR);
            $enroluserform->processForm($data);
            
            if(isIndexationAvailable()){
                $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
                
                $dbdata = new stdClass();
                $dbdata->courseid = $data->id;
                $dbdata->tps_a_distance = $data->tps_a_distance;
                $dbdata->tps_en_presence = $data->tps_en_presence;
                $dbdata->collectionid = $data->collection;
                $dbdata->updatedate = time();
                
                if($indexation){
                    $dbdata->id = $indexation->id;
                    $DB->update_record('local_indexation', $dbdata);
                    $this->notification = get_string('notification_indexation_updated', 'local_workflow');
                }else{
                    $dbdata->id = $DB->insert_record('local_indexation', $dbdata);
                    $this->notification =  get_string('notification_indexation_created', 'local_workflow');
                }
            }
            redirect(new moodle_url($CFG->wwwroot . '/local/workflow/index.php', array('id' => $this->courseid)),
                $this->notification, null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    /**
     * Fonction qui détermine quel type de message à afficher à la suite du traitement du formulaire de workflow
     * pour l'état "Parcours en construction".
     * @return mixed|string
     */
    public function getNotification(){
        return $this->notification ;
    }
}