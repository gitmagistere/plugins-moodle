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
 * Add educational label form
 *
 * @package mod_educationallabel
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_educationallabel_mod_form extends moodleform_mod {

    function definition() {
        global $PAGE;
        
        $mform = $this->_form;
        $data = $this->current;

        $types = array(
            LBL_TRAINING_FORMATION => get_string('displaytype'. LBL_TRAINING_FORMATION, 'educationallabel'),
            LBL_HOW_SUCCEED_TRAINING => get_string('displaytype'. LBL_HOW_SUCCEED_TRAINING, 'educationallabel'),
            LBL_REALISE_ACTIVITY => get_string('displaytype'. LBL_REALISE_ACTIVITY, 'educationallabel'),
            LBL_FORMER_NOTE => get_string('displaytype'. LBL_FORMER_NOTE, 'educationallabel'),
            LBL_IMPORTANT => get_string('displaytype'. LBL_IMPORTANT, 'educationallabel')
        );

        $mform->addElement('header', 'typehdr', get_string('displaytype', 'educationallabel'));
        $select = $mform->addElement('select', 'config_selecttype', 'Type de bloc:', $types);

        $cb = $mform->addElement('advcheckbox', 'customize_title_cb', get_string('customize_title', 'educationallabel'), null, 'class="fitem_fcheckbox"');
        $titleField = $mform->addElement('text', 'custom_title', get_string('custom_title', 'educationallabel').':', 'maxlength="80" size="80" disabled');
        $mform->setType('custom_title', PARAM_TEXT);

        // validation for title
        function title_valid($fields) {
            if (isset($fields['customize_title_cb']) && $fields['customize_title_cb'] && (!isset($fields['custom_title']) || trim(strlen($fields['custom_title'])) < 3)) {
                return array('custom_title' => get_string('custom_title_validation', 'educationallabel'));
            }
            return true;
        }
        $mform->addFormRule('title_valid');


        if(isset($data->type)){
            $select->setSelected($data->type);
        }
        if (isset($data->name) && !in_array($data->name, $types)) {
            $cb->setValue(true);
            $titleField->setValue($data->name);
        }

        $mform->addElement('header', 'generalhr', get_string('general'));
        $mform->setExpanded('generalhr');

        $this->standard_intro_elements(get_string('labeltext', 'educationallabel'));
        $this->standard_coursemodule_elements();
        
        $this->add_action_buttons(true, false, null);
        
        $PAGE->requires->js_call_amd('mod_educationallabel/educationallabel', 'init');

    }

}
