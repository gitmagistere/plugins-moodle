<?php

/**
 * workflow local plugin
 *
 * Fichier enrolForm. Fichier qui contient la structure HTML du formulaire d'ajout d'utilisateur dans le parcours
 * en utilisant un champ de saisie.
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2018
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Class WFManualEnrolForm
 */
class WFEnrolManualForm extends moodleform {

    /**
     * Fonction obligatoire à l'utilisation d'une class étendue de moodleform. Vu que ce formulaire est intégrée
     * au formulaire de workflow, rien n'est à déclarer dans cette fonction.
     */
    function definition() {
    }

    /**
     * Fonction qui définit la composition du formulaire.
     * @param $mform
     * @param $data
     * @throws coding_exception
     */
    public function construct_form(&$mform, $data){

        $lng = new stdClass();
        $lng->concepteur = WKF_ROLE_NAME_CONCEPTEUR;
        $lng->participant = WKF_ROLE_NAME_PARTICIPANT;
        $lng->formateur = WKF_ROLE_NAME_FORMATEUR;
        $lng->tuteur = WKF_ROLE_NAME_TUTEUR;
        
        $main_cat = wf_get_main_category();
        if($main_cat == WKF_CAT_PDF){
            $label = get_string('field_email_concepteur_enrol', 'local_workflow', $lng);
            $labelhelp = get_string('field_email_concepteur_enrol_help', 'local_workflow', $lng);
            
        }else{
            $label = get_string('field_email_enrol', 'local_workflow', $data->role);
            $labelhelp = get_string('field_email_enrol_help', 'local_workflow', $data->role);
        }

        $mform->addElement('html', '<div class="fitem"><div class="fitemtitle">&nbsp;</div><div class="felement"><p>'.$labelhelp.'</p></div></div>');
        $mform->addElement('textarea', $data->role.'_email_user_enrol', $label);

        $groups = groups_get_all_groups($data->courseid);
        $groups_for_select = array();
        if($groups){
            foreach($groups as $group){
                $groups_for_select[$group->id] = $group->name;
            }
        }

        $select = $mform->addElement('select', $data->role.'_groups_user_enrol', get_string('group'), $groups_for_select);
        $select->setMultiple(true);
    }
}