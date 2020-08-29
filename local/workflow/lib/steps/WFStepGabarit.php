<?php

/**
 * Class WFStepGabarit. Etat 1 du workflow.
 */
class WFStepGabarit extends WFStep {

    /**
     * WFStepGabarit constructor.
     * @param $courseid
     * @param $is_title
     */
    public function __construct($courseid, $is_title) {
        $this->courseid = $courseid;
        $this->is_title = $is_title;
    }

    /**
     * Fonction qui détermine les conditions nécessaires pour passer à l'état "Parcours en construction"
     * @return bool|mixed
     */
    public function isValidateState() {
        $main_category = wf_get_main_category();
        if($main_category == WKF_CAT_GAB){
            return true;
        }
        return false;
    }

    /**
     * Fonction qui permet de déterminer si le formulaire du workflow a été validé. Dans le cas de cet état, la fonction toujours false.
     * @return bool|mixed
     */
    public function isSubmittedForm() {
        return false;
    }

    /**
     * Fonction qui créé une instance de l'état suivant à savoir "Parcours en construction".
     * @return mixed|WFStepParcours
     */
    public function getNextStep() {
        return new WFStepParcours($this->courseid, $this->is_title);
    }

    /**
     * Fonction qui retourne le nom de l'état "Gabarit" sous forme de texte.
     * @return mixed|string
     * @throws coding_exception
     */
    public function getStepName() {
        return get_string('banner_gabarit_title', 'local_workflow');
    }

    /**
     * Fonction qui détermine quelles sont les notifications badges à afficher sur le workflow pour l'état "Gabarit".
     * Dans cet état, cette fonction retourne un tableau vide car aucune notification badge n'est à afficher.
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
     * Fonction qui détermine quelles sont les notifications badges à afficher dans le formulaire d'indexation
     * du parcours. Pour cet état, aucune notification badge n'est à afficher. Cette fonction retourne
     * donc un tableau vide.
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
     * Fonction génère la bannière du workflow pour l'état "Gabarit" au format HTML.
     * @return mixed|string
     * @throws coding_exception
     */
    protected function getHeader() {
        $header = "";
        $header .= html_writer::start_div('header');
        $header .= html_writer::start_div('timeline');
        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x actual')).
                html_writer::span('1','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_gabarit_title', 'local_workflow'), 'title actual'),'banner-title');

        $header .= html_writer::div('&nbsp;','separator');

        $header .= html_writer::div(
            html_writer::span(
                html_writer::tag('i','',array('class' => 'fa fa-circle fa-stack-2x soon')).
                html_writer::span('2','fa fa-stack-1x number'),'fa fa-stack fa-1x').
            html_writer::span(get_string('banner_parcours_title', 'local_workflow'), 'title soon'),'banner-title');

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
        $header .= get_string('banner_gabarit_description', 'local_workflow');
        $header .= html_writer::end_div();
        $header .= html_writer::end_div();
        $header .= $this->getActionButton();
        return $header;
    }

    /**
     * Fonction qui détermine quelles sont les actions possibles du workflow pour l'état "Gabarit".
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
     * Fonction génère le formulaire de workflow au format HTML. Pour cet état, aucun formulaire n'est à généré.
     * Cette fonction retourne donc une chaine de caractère vide.
     * @return mixed|string
     */
    protected function getForm() {
        return '';
    }

    /**
     * Fonction qui traite les données validées et transitées en post du formulaire de workflow. Pour cet état,
     * aucun formulaire n'est à généré. Cette fonction retourne donc une chaine de caractère vide.
     * @return mixed|string
     */
    public function processForm() {
        return '';
    }

    /**
     * Fonction qui détermine quel type de message à afficher à la suite du traitement du formulaire de workflow.
     * Pour cet état, aucun formulaire n'est à généré. Cette fonction retourne donc une chaine de caractère vide.
     * @return mixed|string
     */
    public function getNotification() {
        return '';
    }
}