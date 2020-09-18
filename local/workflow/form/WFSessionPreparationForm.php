<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Session preparation workflow form
 *
 * @package local_workflow
 * @copyright  2018 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/local/workflow/lib/WFEnrolCSV.php');
require_once($CFG->dirroot.'/local/workflow/lib/WFEnrolManual.php');
require_once($CFG->dirroot.'/local/workflow/lib/dialogs.php');

/**
 * Class WFSessionPreparationForm. Formulaire utilise pour les etats "Session en preparation" et "Session en cours".
 */
class WFSessionPreparationForm extends moodleform {

    /**
     * WFSessionPreparationForm constructor.
     * @param null $action
     * @param null $customdata
     * @param string $method
     * @param string $target
     * @param null $attributes
     * @param bool $editable
     * @param array|null $ajaxformdata
     * @throws moodle_exception
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, array $ajaxformdata = null)
    {
        $action = new moodle_url('/local/workflow/index.php', array('id' => $customdata['id']));
        $action = $action->out(false);
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Fonction qui definit la composition du formulaire pour les etats "Session en preparation" et "Session en cours".
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function definition() {
        global $CFG;
        require_once($CFG->dirroot.'/local/workflow/lib/dialogs.php');

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);
        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);
        $mform->setDefault('contextid', $this->_customdata['contextid']);

        $required_fields = array();
        if (isset($this->_customdata['notification_indexation_badges'])){
            $required_fields = $this->_customdata['notification_indexation_badges'];
        }
        $context = context::instance_by_id($this->_customdata['contextid']);

        // Collapse actions
        $mform->addElement('header', 'actionsessionheader',
            get_string('label_actions_session', 'local_workflow'));
        $link = new moodle_url('/enrol/users.php', array('id' => $this->_customdata['id']));
        $mform->addElement('html',
            wf_secondary_generate_action_links($this->_customdata['main_category'],$this->_customdata['id'],
                $this->_customdata['status']));

        // Collapse formateur
        if(has_capability('local/workflow:addformateur', $context)){
            $formateur_notification = '';
            if(array_key_exists('no_formateurs', $required_fields)){
                $formateur_notification = generate_help_icon_HTML('label_inscription_formateur',
                    'local_workflow', 'formateur');
            }
            $mform->addElement('header', 'inscriptionformateurheader',
                get_string('label_inscription_formateur', 'local_workflow', WKF_ROLE_NAME_FORMATEUR)
                .$formateur_notification);
            if(has_capability('local/workflow:addformateurmanual', $context)){
                $link = new moodle_url('/user/index.php', array('id' => $this->_customdata['id']));
                $mform->addElement('html', '<a class="enrol-link" href="'.$link.'">'
                    .get_string('inscription_manuelle', 'local_workflow')
                    .' <i class="fa fa-external-link" aria-hidden="true"></i></a>');
                $mform->addElement('html','<hr>&nbsp;</hr>');
            }
            if(has_capability('local/workflow:addformateurcsv', $context)){
                $csvform = new WFEnrolCSV($this->_customdata['id'], WKF_ROLE_FORMATEUR);
                $datacsv = $csvform->getFrameElement($mform);
                $this->set_data($datacsv); // Mapping de l'id filemanager pour l'historique
                $mform->addElement('html','<hr>&nbsp;</hr>');
            }

            $enroluserform = new WFEnrolManual($this->_customdata['id'], WKF_ROLE_FORMATEUR);
            $enroluserform->getFrameElement($mform);
        }

        // Collapse tuteur
        if(has_capability('local/workflow:addtuteur', $context)){
            $mform->addElement('header', 'inscriptiontuteurheader',
                get_string('label_inscription_tuteur', 'local_workflow', WKF_ROLE_NAME_TUTEUR));
            if(has_capability('local/workflow:addformateurmanual', $context)){
                $link = new moodle_url('/user/index.php', array('id' => $this->_customdata['id']));
                $mform->addElement('html', '<a class="enrol-link" href="'.$link.'">'
                    .get_string('inscription_manuelle', 'local_workflow')
                    .' <i class="fa fa-external-link" aria-hidden="true"></i></a>');
                $mform->addElement('html','<hr>&nbsp;</hr>');
            }

            if(has_capability('local/workflow:addformateurcsv', $context)){
                $csvform = new WFEnrolCSV($this->_customdata['id'], WKF_ROLE_TUTEUR);
                $datacsv = $csvform->getFrameElement($mform);
                $this->set_data($datacsv); // Mapping de l'id filemanager pour l'historique
                $mform->addElement('html','<hr>&nbsp;</hr>');
            }

            $enroluserform = new WFEnrolManual($this->_customdata['id'], WKF_ROLE_TUTEUR);
            $enroluserform->getFrameElement($mform);
        }

        // Collapse participant
        if(has_capability('local/workflow:addparticipant', $context)){
            $participant_notification = '';
            if(array_key_exists('no_participants', $required_fields)){
                $participant_notification = generate_help_icon_HTML('label_inscription_participant',
                    'local_workflow', 'participant');
            }
            $mform->addElement('header', 'inscriptionparticipantheader',
                get_string('label_inscription_participant', 'local_workflow', WKF_ROLE_NAME_PARTICIPANT)
                .$participant_notification);
            if(has_capability('local/workflow:addparticipantmanual', $context)){
                $link = new moodle_url('/user/index.php', array('id' => $this->_customdata['id']));
                $mform->addElement('html', '<a class="enrol-link" href="'.$link.'">'
                    .get_string('inscription_manuelle', 'local_workflow')
                    .' <i class="fa fa-external-link" aria-hidden="true"></i></a>');
                $mform->addElement('html','<hr>&nbsp;</hr>');
            }

            if(has_capability('local/workflow:addparticipantcsv', $context)){
                $csvform = new WFEnrolCSV($this->_customdata['id'], WKF_ROLE_PARTICIPANT);
                $datacsv = $csvform->getFrameElement($mform);
                $this->set_data($datacsv); // Mapping de l'id filemanager pour l'historique
                $mform->addElement('html','<hr>&nbsp;</hr>');
            }

            $enroluserform = new WFEnrolManual($this->_customdata['id'], WKF_ROLE_PARTICIPANT);
            $enroluserform->getFrameElement($mform);
        }

        // Collapse gaia
        require_once($CFG->dirroot.'/local/workflow/lib/Gaia.php');
        if (Gaia::isAvailable()){
            if(has_capability('local/workflow:setgaiasession', $context)){
                $mform->addElement('header', 'gaiaheader', get_string('label_gaia', 'local_workflow'));
                $mform->addElement('html', Gaia::getContent($this->_customdata['id']));
            }
        }

        // Collapse dates
        if(has_capability('local/workflow:setcoursedates', $context)){
            $start_date_notification = '';
            $count_notification_date = 0;
            if(array_key_exists('startdate', $required_fields)){
                $start_date_notification = generate_help_icon_HTML('field_date_debut', 'local_workflow', 1);
                $count_notification_date += 1 ;
            }
            $end_date_notification = '';
            if(array_key_exists('enddate', $required_fields)){
                $end_date_notification = generate_help_icon_HTML('field_date_fin', 'local_workflow', 1);
                $count_notification_date += 1 ;
            }
            $header_notification = '';
            if($count_notification_date != 0 ){
                $header_notification = generate_notification_HTML($count_notification_date);
            }
            $mform->addElement('header', 'dateheader', get_string('label_date', 'local_workflow')
                .$header_notification);
            $mform->addElement('date_selector', 'startdate', get_string('field_date_debut', 'local_workflow')
                .$start_date_notification, ['optional' => true]);
            $mform->setType('startdate', PARAM_TEXT);

            $mform->addElement('date_selector', 'enddate', get_string('field_date_fin', 'local_workflow')
                .$end_date_notification, ['optional' => true]);
            $mform->setType('enddate', PARAM_TEXT);
        }

        // Collapse duree
        if(isIndexationAvailable() && has_capability('local/workflow:setcourseduration', $context)){
            $count_notification_duree = 0;
            $tps_en_presence_notification = '';
            if(array_key_exists('tps_en_presence', $required_fields)){
                $tps_en_presence_notification = generate_help_icon_HTML('field_temps_en_presence', 'local_workflow', 1);
                $count_notification_duree += 1 ;
            }
            $tps_a_distance_notification = '';
            if(array_key_exists('tps_a_distance', $required_fields)){
                $tps_a_distance_notification = generate_help_icon_HTML('field_temps_a_distance', 'local_workflow', 1);
                $count_notification_duree += 1 ;
            }
            $header_notification = '';
            if($count_notification_duree != 0 ){
                $header_notification = generate_notification_HTML($count_notification_duree);
            }
            $mform->addElement('header', 'dureeheader',
                get_string('label_duree', 'local_workflow')
                .$header_notification);
            $tempspresence = array();
            $tempspresence[] =& $mform->createElement('text', 'tempspresence_h');
            $tempspresence[] =& $mform->createElement('text', 'tempspresence_min');
            $mform->addGroup($tempspresence, 'tempspresence_group',
                get_string('field_temps_en_presence', 'local_workflow')
                .$tps_en_presence_notification, array(' h '), false);
            $mform->setType('tempspresence_h', PARAM_ALPHANUM);
            $mform->setType('tempspresence_min', PARAM_ALPHANUM);

            $tempsdist = array();
            $tempsdist[] = $mform->createElement('text', 'tempsdistance_h');
            $tempsdist[] = $mform->createElement('text', 'tempsdistance_min');
            $mform->addGroup($tempsdist, 'tempsdistance_group',
                get_string('field_temps_a_distance', 'local_workflow')
                .$tps_a_distance_notification, array(' h '), false);
            $mform->setType('tempsdistance_h', PARAM_ALPHANUM);
            $mform->setType('tempsdistance_min', PARAM_ALPHANUM);
        }

        // Collapse collection
        
        if(isIndexationAvailable() && has_capability('local/workflow:setcoursecollection', $context)){
            $notification = '';
            if(array_key_exists('collectionid', $required_fields)){
                $notification = generate_notification_HTML(1);
            }
            $mform->addElement('header', 'collectionheader',
                get_string('label_collection', 'local_workflow')
                .$notification);
            $collections = get_centralized_db_connection()->get_records('local_indexation_collections');
            $collectionsOptions = array(get_string('label_collection', 'local_workflow'));
            foreach($collections as $id => $data){
                $collectionsOptions[$id] = $data->name;
            }

            $mform->addElement('select', 'collection',
                get_string('field_collection', 'local_workflow'),
                $collectionsOptions);
        }

        // Collapse indexation
        if(isIndexationAvailable() && has_capability('local/workflow:index', $context)){
            $notification_html = '';
            if(isset($this->_customdata['notification_indexation_badges_html'])){
                $notification_html = $this->_customdata['notification_indexation_badges_html'];
            }
            $mform->addElement('header', 'indexationheader',
                get_string('label_indexation', 'local_workflow')
                .$notification_html);
            $link = new moodle_url('/local/indexation/', array('id' => $this->_customdata['id']));
            $mform->addElement('html', '<a class="indexation-link" href="'.$link.'">'
                . get_string('link_indexation', 'local_workflow')
                . ' <i class="fa fa-external-link" aria-hidden="true"></i></a>');
        }

        // Collapse publication
        if(isCourseHubAvailable() && (has_capability('local/coursehub:publish', $context)
            || has_capability('local/coursehub:share', $context))){
            $header_publication= '';
            $mform->addElement('header', 'publicationheader',
                get_string('label_publication', 'local_workflow')
                .$header_publication);

            $status = get_string('status_course_not_published', 'local_workflow');
            $btn_share_title = get_string('link_share', 'local_workflow');
            $btn_publish_title = get_string('link_publish', 'local_workflow');
            $btn_local_publish_title = get_string('link_local_publish', 'local_workflow');

            if(course_is_published($this->_customdata['id']) !== false
                && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_SHARED){
                $status = get_string('status_share_course', 'local_workflow');
                $btn_share_title = get_string('link_share_update', 'local_workflow');
            }

            if(course_is_published($this->_customdata['id']) !== false
                && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_PUBLISHED){
                if(wf_get_main_category() == WKF_CAT_SLAF){
                    $status = get_string('status_local_publish', 'local_workflow');
                } else {
                    $status = get_string('status_publish_course', 'local_workflow');
                }
                $btn_publish_title = $btn_local_publish_title = get_string('link_publish_update', 'local_workflow');
            }

            $mform->addElement('html', '<div class="fitem">
                                                    <div class="fitemtitle"></div>
                                                    <div class="felement">
                                                        <span>'.$status.'</span>
                                                    </div>');

            require_once($CFG->dirroot.'/local/coursehub/CourseHub.php');
            require_once($CFG->dirroot.'/local/magisterelib/magistereLib.php');
            //$courseupdated = MagistereLib::hasCourseBeenUpdated($this->_customdata['id']);
            $indexupdated = MagistereLib::hasIndexBeenUpdated($this->_customdata['id']);
            $updatedhtml = '';
            if ($indexupdated){
                $updatedhtml .= '<br/>
                    <i  alt="L\'indexation liée à votre session est différente de celle publiée. Vous pouvez la mettre à jour en cliquant ci-dessous." 
                        class="infoicon fa fa-2x fa-info-circle"></i>
                    <span class="infolabel"> L\'indexation liée à votre session est différente de celle publiée. Vous pouvez la mettre à jour en cliquant ci-dessous.</span>';
            }

            if ($indexupdated) {
                $mform->addElement('html',
                    '<div class="fitem">
                        <div class="fitemtitle"></div>
                        <div class="felement">
                            '.$updatedhtml.'
                        </div>
                    </div>');
            }

            // Gestion des boutons dans le cas d'un parcours
            if(has_capability('local/coursehub:share', $context)) {
                if (is_ready_to_share($this->_customdata['id'])
                    || (course_is_published($this->_customdata['id']) !== false
                        && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_SHARED)) {
                    $html_share_content = '';
                    if (is_ready_to_share($this->_customdata['id'])) {
                        $html_share_content .= '<a class="share-link btn" id="wf_link_publish" href="#">'
                            . $btn_share_title . '</a>';
                    } else {
                        $html_share_content .= '<span class="secondary-link disable">'
                            . get_string('link_share_disable', 'local_workflow')
                            . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                            . generate_help_icon_HTML('link_share_disable','local_workflow');
                    }
                    if (course_is_published($this->_customdata['id']) !== false
                        && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_SHARED
                        && has_capability('local/coursehub:unpublish', $context)) {
                        $html_share_content .= '<a class="share-link cancel btn" id="wf_link_unpublish" href="#">'
                            . get_string('link_share_cancel', 'local_workflow') . '</a>';
                    }
                } else {
                    $html_share_content = '<span class="secondary-link disable">'
                        . get_string('link_share_disable', 'local_workflow')
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                        . generate_help_icon_HTML('link_share_disable','local_workflow');
                }
                $mform->addElement('html', '    
                    <div class="fitemtitle"></div>
                    <div class="felement">
                        ' . $html_share_content . '
                    </div>');
            }

            // Gestion des boutons dans le cas d'une session de formation
            if(has_capability('local/coursehub:publish', $context)) {
                if (is_ready_to_publish($this->_customdata['id'])
                    || (course_is_published($this->_customdata['id']) !== false
                        && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_PUBLISHED)) {
                    $html_publish_content = '';

                    if (is_ready_to_publish($this->_customdata['id'])) {
                        if (course_is_published($this->_customdata['id']) !== false
                            && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_SHARED) {
                            $html_share_content .= '';
                        } else {
                            $html_publish_content .= '<a class="publish-link btn" id="wf_link_publish" href="#">'
                                . $btn_publish_title . '</a>';
                        }
                    } else {
                        $html_publish_content .= '<span class="secondary-link disable">'
                            . get_string('link_publish_disable', 'local_workflow')
                            . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                            . generate_help_icon_HTML('link_publish_disable','local_workflow');
                    }
                    if (course_is_published($this->_customdata['id']) !== false
                        && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_PUBLISHED
                        && wf_get_main_category() == WKF_CAT_SDF
                        && has_capability('local/coursehub:unpublish', $context)) {
                        $html_publish_content .= '<a class="publish-link cancel btn" id="wf_link_unpublish" href="#">'
                            . get_string('link_publish_cancel', 'local_workflow') . '</a>';
                    }
                } else {
                    $html_publish_content = '<span class="secondary-link disable">'
                        . get_string('link_publish_disable', 'local_workflow')
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                        . generate_help_icon_HTML('link_publish_disable','local_workflow');
                }
            }
            $mform->addElement('html', '    
                <div class="fitemtitle"></div>
                <div class="felement">
                    '.$html_publish_content.'
                </div>');

            // Gestion des boutons dans le cas d'une session locale
            if(has_capability('local/coursehub:publish', $context)) {
                if (is_ready_to_publish($this->_customdata['id'], 1)
                    || (course_is_published($this->_customdata['id']) !== false
                        && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_PUBLISHED)) {
                    $html_publish_content = '';

                    if (is_ready_to_publish($this->_customdata['id'], 1)) {
                        if (course_is_published($this->_customdata['id']) !== false
                            && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_SHARED) {
                            $html_share_content .= '';
                        } else {
                            $html_publish_content .= '<a class="publish-link btn" id="wf_link_publish" href="#">'
                                . $btn_local_publish_title . '</a>';
                        }
                    } else {
                        $html_publish_content .= '<span class="secondary-link disable">'
                            . get_string('link_local_publish_disable', 'local_workflow')
                            . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                            . generate_help_icon_HTML('link_local_publish_disable','local_workflow');
                    }
                    if (course_is_published($this->_customdata['id']) !== false
                        && course_is_published($this->_customdata['id']) == CourseHub::PUBLISH_PUBLISHED
                        && wf_get_main_category() == WKF_CAT_SLAF
                        && has_capability('local/coursehub:unpublish', $context)) {
                        $html_publish_content .= '<a class="publish-link cancel btn" id="wf_link_unpublish" href="#">'
                            . get_string('link_publish_cancel', 'local_workflow') . '</a>';
                    }
                } else {
                    $html_publish_content = '<span class="secondary-link disable">'
                        . get_string('link_local_publish_disable', 'local_workflow')
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                        . generate_help_icon_HTML('link_local_publish_disable','local_workflow');
                }
            }
            $mform->addElement('html', '    
                <div class="fitemtitle"></div>
                <div class="felement">
                    '.$html_publish_content.'
                </div>
                </div>');
        }

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('cancel', 'cancelgeneral');
        $buttonarray[] = $mform->createElement('button', 'submitgeneral', get_string('savechanges'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
        $mform->closeHeaderBefore('buttonar');
        $mform->disable_form_change_checker();
    }

    /**
     * Fonction qui modifie la valeur de certains champs avant chargement du formulaire.
     */
    public function definition_after_data()
    {
        $mform =& $this->_form;

        $course = $this->_customdata['course'];

        if($course->startdate){
            $mform->setDefault('startdate', $course->startdate);
        }

        if($course->enddate){
            $mform->setDefault('enddate', $course->enddate);
        }
        
        if (isIndexationAvailable() && isset($this->_customdata['indexation']) && $this->_customdata['indexation'] !== false){
            $indexation = $this->_customdata['indexation'];
            
            if($indexation->tps_a_distance || $indexation->tps_a_distance == "0"){
                $h = intval($indexation->tps_a_distance / 60);
                $min = $indexation->tps_a_distance % 60;
                $mform->setDefault('tempsdistance_h', $h);
                $mform->setDefault('tempsdistance_min', $min);
            } else {
                $mform->setDefault('tempsdistance_h', "");
                $mform->setDefault('tempsdistance_min', "");
            }
            
            if($indexation->tps_en_presence || $indexation->tps_en_presence == "0"){
                $h = intval($indexation->tps_en_presence / 60);
                $min = $indexation->tps_en_presence % 60;
                $mform->setDefault('tempspresence_h', $h);
                $mform->setDefault('tempspresence_min', $min);
            } else {
                $mform->setDefault('tempspresence_h', "");
                $mform->setDefault('tempspresence_min', "");
            }
            
            $mform->setDefault('collection', $indexation->collectionid);
        }
    }

    /**
     * Fonction qui modifie la valeur de certaines proprietes de l'objet data apres validation du formulaire.
     * @return object
     */
    public function get_data()
    {
        $data = parent::get_data();
        

        if(!$data){
            return $data;
        }

        if (isIndexationAvailable()){
            $tempdistance = 0;
            $data->tps_a_distance = null;
            if($data->tempsdistance_h != null){
                $tempdistance += $data->tempsdistance_h*60;
                $data->tps_a_distance = $tempdistance;
            }
            if($data->tempsdistance_min != null){
                $tempdistance += $data->tempsdistance_min;
                $data->tps_a_distance = $tempdistance;
            }
    
            $tempspresence = 0;
            $data->tps_en_presence = null;
            if($data->tempspresence_h != null){
                $tempspresence += $data->tempspresence_h*60;
                $data->tps_en_presence = $tempspresence;
            }
            if($data->tempspresence_min != null){
                $tempspresence += $data->tempspresence_min;
                $data->tps_en_presence = $tempspresence;
            }
            
            $data->collection = ($data->collection == 0 ? null : $data->collection);
        }

        $data->startdate = (empty($data->startdate) ? 0 : $data->startdate);
        $data->enddate = (empty($data->enddate) ? 0 : $data->enddate);
/*
        if($data->startdate){
            $arg = explode('/', $data->startdate);
            $data->startdate = mktime(0, 0, 0, $arg[1], $arg[0], $arg[2]);
        }

        if($data->enddate){
            $arg = explode('/', $data->enddate);
            $data->enddate = mktime(23, 59, 59, $arg[1], $arg[0], $arg[2]);
        }
*/
        return $data;
    }
}
