<?php

/**
 * Class WFStepSessionArchive. Etat 5 du workflow.
 */
class WFStepSessionArchive extends WFStep {
    private $form;
    private $notification;

    /**
     * WFStepSessionArchive constructor.
     * @param $courseid
     * @param $is_title
     */
    public function __construct($courseid, $is_title) {
        $this->courseid = $courseid;
        $this->is_title = $is_title;

        $this->context = context_course::instance($courseid);
        $this->status = WKF_IS_SESSION_ARCHIVE;
        $this->main_category = $this->mainCategory();
        $this->notification = '';
    }

    /**
     * Fonction qui détermine les conditions nécessaires pour passer à l'état suivant du workflow. Vu que cet état
     * est le dernier, cette fonction retourne donc un booléen à vrai.
     * @return bool|mixed
     */
    public function isValidateState() {
        if(!$this->is_title){
            $this->form = $this->loadForm();
        }
        return true;
    }

    /**
     * Fonction qui permet de déterminer si le formulaire du workflow a été validé.
     * @return bool|mixed
     */
    public function isSubmittedForm() {
        return false;
    }

    /**
     * Fonction qui créé une instance de l'état suivant. Vu que cet état est le dernier, cette fonction
     * retourne donc un string vide.
     * @return mixed|string
     */
    public function getNextStep() {
        return '';
    }

    /**
     * Fonction qui retourne le nom de l'état "Session archivée" sous forme de texte.
     * @return mixed|string
     * @throws coding_exception
     */
    public function getStepName() {
        return get_string('banner_session_archive_title', 'local_workflow');
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher sur le workflow
     * pour l'état "Session archivée". Dans cet état, cette fonction retourne un tableau vide
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
     * pour l'état "Session archivée". Dans cet état, cette fonction retourne un tableau vide
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
     * Fonction génère la bannière du workflow pour l'état "Session archivée" au format HTML.
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
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x')).
                html_writer::span('4','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_en_cours_title', 'local_workflow'), 'title'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x actual')).
                html_writer::span('5','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_session_archive_title', 'local_workflow'), 'title actual'),'banner-title');

        $header .= html_writer::end_div();
        $header .= html_writer::start_div('content');
        $header .= get_string('banner_session_archive_description', 'local_workflow', WKF_ROLE_NAME_PARTICIPANT);
        $header .= html_writer::end_div();
        $header .= html_writer::end_div();
        $header .= $this->getActionButton();
        return $header;
    }

    /**
     * Fonction qui détermine quelles sont les actions possibles du workflow pour l'état "Session archivée".
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
     * Fonction qui charge le type de formulaire à afficher pour l'état "Session archivée".
     * @return WFSessionArchiveForm
     * @throws dml_exception
     */
    private function loadForm() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/workflow/form/WFSessionArchiveForm.php');

        $formData = array(
            'id' => $this->courseid,
            'contextid' => context_course::instance($this->courseid)->id,
            'status' => $this->status,
            'main_category' => $this->main_category
        );
        
        if(isIndexationAvailable()){
            $indexation = $DB->get_record('local_indexation', array('courseid' => $this->courseid));
            $formData['indexation'] = $indexation;
            $formData['notification_indexation_badges'] = $this->getIndexationNotificationBadges();
        }

        return new WFSessionArchiveForm(null, $formData);
    }

    /**
     * Fonction génère le formulaire de workflow au format HTML.
     * @return mixed|string
     */
    protected function getForm() {
        global $CFG;

        $source = html_writer::start_div('form session-archive');
        $source .= $this->form->render();
        $source .= html_writer::end_div();
        $source .= wf_secondary_generate_action_dialogs($this->main_category, $this->courseid, $this->status);
        return $source;
    }

    /**
     * Fonction qui traite les données validées et transitées en post du formulaire de workflow.
     * @return mixed|void
     * @throws moodle_exception
     */
    public function processForm() {
        global $CFG;

        redirect(new moodle_url($CFG->wwwroot . '/local/workflow/index.php', array('id' => $this->courseid)), $this->notification, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    /**
     * Fonction qui détermine quel type de message à afficher à la suite du traitement du formulaire de workflow
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