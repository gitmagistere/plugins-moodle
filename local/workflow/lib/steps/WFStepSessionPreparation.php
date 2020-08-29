<?php

/**
 * Class WFStepSessionPreparation. Etat 3 du workflow.
 */
class WFStepSessionPreparation extends WFStep {
    private $form;
    private $notification;
    private $status;
    private $main_category;

    /**
     * WFStepSessionPreparation constructor.
     * @param $courseid
     * @param $is_title
     */
    public function __construct($courseid, $is_title) {
        $this->courseid = $courseid;
        $this->is_title = $is_title;
        $this->context = context_course::instance($courseid);
        $this->status = WKF_IS_SESSION_PREPARATION;
        $this->main_category = $this->mainCategory();
        $this->notification = '';
    }

    /**
     * Fonction qui détermine les conditions nécessaires pour passer à l'état "Session en cours".
     * @return bool|mixed
     */
    public function isValidateState() {
        if(($this->main_category == WKF_CAT_SDF || $this->main_category == WKF_CAT_SLAF)
            && is_en_cours($this->courseid) == false){
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
     * Fonction qui créé une instance de l'état suivant à savoir "Session en cours".
     * @return mixed|WFStepSessionCours
     */
    public function getNextStep() {
        return new WFStepSessionCours($this->courseid, $this->is_title);
    }

    /**
     * Fonction qui retourne le nom de l'état "Session en préparation" sous forme de texte. Si le parcours est
     * publié sur l'offre de parcours, le nom est transformé en "Session publiée".
     * @return mixed|string
     * @throws coding_exception
     */
    public function getStepName() {
        if (isCourseHubAvailable()){
            if(course_is_published($this->courseid) !== false &&
                course_is_published($this->courseid) == CourseHub::PUBLISH_PUBLISHED){
                return get_string('status_publish', 'local_workflow');
            }
        }
        return get_string('banner_session_preparation_title', 'local_workflow');
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher sur le workflow
     * pour l'état "Session en préparation".
     * @return array|mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function getNotificationBadges(){
        global $DB;
        if (!isIndexationAvailable()){
            return array();
        }
        $collab_autoformation_id = get_centralized_db_connection()->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_AUTOFORMATION));

        $formateurs = get_formateurs_on_context($this->courseid);
        $participants = get_participants_on_context($this->courseid);
        $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));

        $list = $this->getIndexationNotificationBadges();
        if($indexation){
            if(count($formateurs) == 0 && $indexation->collectionid != $collab_autoformation_id){
                $list['no_formateurs'] = 'no_formateurs';
            }
            if(count($participants) == 0 && $indexation->collectionid != $collab_autoformation_id){
                $list['no_participants'] = 'no_participants';
            }
        }
        return $list;
    }

    /**
     * Fonction génère les notification badges au format HTML.
     * @return bool|mixed|string
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function getNotificationBadgesHTML(){
        return generate_notification_HTML(count($this->getNotificationBadges()));
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher dans le formulaire d'indexation du parcours
     * pour l'état "Session en préparation".
     * @return array|mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function getIndexationNotificationBadges(){
        global $DB;
        
        if (!isIndexationAvailable()){
            return array();
        }
        
        $DBC = get_centralized_db_connection();
        $collab_autoformation_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_AUTOFORMATION));
        $collab_espace_collab_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_ESPACE_COLLABORATIF));
        $collab_reseau_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_RESEAU));
        $volet_distant_id = $DBC->get_field('local_indexation_collections','id', array('shortname' => WKF_IND_COLL_VOLET_DISTANT));

        $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
        $course = $DB->get_record('course', array('id' => $this->courseid));

        if($indexation){
            if ($indexation->collectionid == null){
                $required_fields = array();
                $required_fields['collectionid'] = 'general';
                return $required_fields;
            }
            $list = array();
            $required_fields = array();
            $required_fields['collectionid'] = 'general';
            $required_fields['domainid'] = 'general';
            $required_fields['authors'] = 'organisme';
            $required_fields['year'] = 'version';
            $required_fields['codeorigineid'] = 'organisme';
            $required_fields['title'] = 'general';
            $required_fields['version'] = 'version';
            $required_fields['startdate'] = 'detail';
            $required_fields['public_cibles'] = 'detail';
            if($indexation->collectionid != $collab_autoformation_id &&
                $indexation->collectionid != $collab_espace_collab_id &&
                $indexation->collectionid != $collab_reseau_id &&
                $indexation->collectionid != $volet_distant_id){
                $required_fields['enddate'] = 'detail';
            }
            if($indexation->collectionid != $collab_espace_collab_id &&
                $indexation->collectionid != $collab_reseau_id &&
                $indexation->collectionid != $volet_distant_id){
                $required_fields['tps_a_distance'] = 'detail';
                $required_fields['tps_en_presence'] = 'detail';
            }

            foreach($required_fields as $key => $value){
                if($key == 'public_cibles'){
                    $publics = $DB->get_records('local_indexation_public', array('indexationid' => $indexation->id));
                    if(!$publics){
                        $list[$key] = $value;
                    }
                } else if($key =='startdate' || $key == 'enddate'){
                    if(!$course->{$key}){
                        $list[$key] = $value;
                    }
                } elseif (!$indexation->$key){
                    if(($key == 'tps_en_presence' || $key == 'tps_a_distance') && $indexation->$key == "0") { continue; }
                    $list[$key] = $value;
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
     * @throws moodle_exception
     */
    public function getIndexationNotificationBadgesHTML(){
        return generate_notification_HTML(count($this->getIndexationNotificationBadges()));
    }

    /**
     * Fonction génère la bannière du workflow pour l'état "Session en préparation" au format HTML.
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
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x actual')).
                html_writer::span('3','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_preparation_title', 'local_workflow'), 'title actual'),'banner-title');

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
        $lng = new stdClass();
        $lng->participant = WKF_ROLE_NAME_PARTICIPANT;
        $lng->formateur = WKF_ROLE_NAME_FORMATEUR;
        $header .= get_string('banner_session_preparation_description', 'local_workflow', $lng);
        $header .= html_writer::end_div();
        $header .= html_writer::end_div();
        $header .= $this->getActionButton();
        return $header;
    }

    /**
     * Fonction qui détermine quelles sont les actions possibles du workflow pour l'état "Session en préparation".
     * @return bool|string
     */
    protected function getActionButton() {
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/lib/dialogs.php');

        //définition des types de lien à afficher
        $l_links_type = wf_get_main_action_links($this->main_category, $this->courseid);

        if(count($l_links_type) != 0){
            return wf_main_action_content($l_links_type, $this->courseid);
        }
        return false;
    }

    /**
     * Fonction qui charge le type de formulaire à afficher pour l'état "Session en préparation".
     * @return WFSessionPreparationForm
     * @throws dml_exception
     * @throws moodle_exception
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
            'main_category' => $this->main_category,
        );
        
        if (isIndexationAvailable()){
            $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));

            $formData['indexation'] = $indexation;
            $formData['notification_indexation_badges'] = $this->getNotificationBadges();
            $formData['notification_indexation_badges_html'] = $this->getIndexationNotificationBadgesHTML();
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

        $source = html_writer::start_div('form session-preparation');
        $source .= $this->form->render();
        $source .= html_writer::end_div();
        $source .= dialog_preview_mails();
        
        require_once($CFG->dirroot.'/local/workflow/lib/Gaia.php');
        if (Gaia::isAvailable()){
            $source .= Gaia::unlink_dialog();
        }
        
        $source .= wf_secondary_generate_action_dialogs($this->main_category, $this->courseid, $this->status);

        if (isCourseHubAvailable()){
            if(course_is_published($this->courseid) !== false){
                $method = "publish";
                if(course_is_published($this->courseid) == CourseHub::PUBLISH_SHARED){
                    $method = "share";
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
                $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
                
                $dbdata = new stdClass();
                $dbdata->courseid = $data->id;
                $dbdata->tps_a_distance = $data->tps_a_distance;
                $dbdata->tps_en_presence = $data->tps_en_presence;
                $dbdata->collectionid = $data->collection;
                $dbdata->updatedate = time();
                
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
     * pour l'état "Session en préparation".
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