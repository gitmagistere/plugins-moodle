<?php

/**
 * workflow local plugin
 *
 * Fichier csvForm. Fichier qui contient la structure HTML du formulaire d'ajout d'utilisateur dans le parcours
 * en utilisant un fichier CSV.
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
 * Class workflow_csv_form
 */
class WFEnrolCSVForm extends moodleform {
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
     * @param $options
     * @throws coding_exception
     */
    public function construct_form(&$mform, $data, $options){

        $mform->addElement('html','<div id="'.$data->role.'-workflow-csv-enrol-warning"></div>');

        $mform->addElement('static', $data->role.'_name', "", get_string('description', 'local_workflow', $data->coursename));

        $mform->addElement('filepicker', $data->role.'_userfile', get_string('uploadcsv','local_workflow'), null, array('accepted_types' => '*.csv'));

        $mform->addElement('filemanager', $data->role.'_files_filemanager', get_string('resultfiles','local_workflow'), null, $options);

        $mform->addElement('html','<div class="panel panel-complex '.$data->role.'" style="display: none;">');
        $groups = groups_get_all_groups($data->courseid);
        $groups_for_select = array();
        if($groups){
            foreach($groups as $group){
                $groups_for_select[$group->id] = $group->name;
            }
        }

        $select = $mform->addElement('select', $data->role.'_groups', get_string('group'), $groups_for_select);
        $select->setMultiple(true);

        $mform->addElement('html','</div>');
    }
}