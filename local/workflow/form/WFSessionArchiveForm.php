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
 * Archived session workflow form
 *
 * @package local_workflow
 * @copyright  2018 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * Class WFSessionArchiveForm. Formulaire utilisé pour l'état "Session archivée".
 */
class WFSessionArchiveForm extends moodleform {

    /**
     * WFSessionArchiveForm constructor.
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
     * Fonction qui définit la composition du formulaire pour cet état du workflow.
     * @throws coding_exception
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

        $mform->addElement('header', 'actionsessionheader', get_string('label_actions_session', 'local_workflow'));
        $mform->addElement('html',
            wf_secondary_generate_action_links($this->_customdata['main_category'],$this->_customdata['id'],
                $this->_customdata['status']));

    }
}
