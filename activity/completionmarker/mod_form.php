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
 * Achievement configuration form
 *
 * @package    mod_achievement
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_completionmarker_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $mform->addElement('hidden', 'name','completionmarker_mod');
        $mform->setType('name', PARAM_TEXT);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    function definition_after_data()
    {
        parent::definition_after_data();

        $this->_form->setDefault('completion', 1);
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

}
