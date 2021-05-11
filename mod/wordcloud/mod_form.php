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
 * Add wordcloud form
 *
 * @package mod_wordcloud
 * @copyright  2021 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_wordcloud_mod_form extends moodleform_mod {

    function definition() {
        global $PAGE;

        $PAGE->force_settings_menu();

        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        
        $mform->addElement('text', 'name', get_string('name', 'wordcloud'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        
        $this->standard_intro_elements(get_string('description'));
        
        $mform->addElement('editor', 'instructionseditor', get_string('instructions', 'wordcloud'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true, 'context' => $this->context, 'subdirs' => true));
        
        
        
        $maxword_options = array();
        for ($i = 1; $i <= get_config('wordcloud', 'maxwordsallowed'); $i++) {
            $maxword_options[$i] = $i;
        }
        
        $mform->addElement('select', 'wordsallowed', get_string('maxwordsallowed', 'wordcloud'), $maxword_options);
        $mform->setType('wordsallowed', PARAM_INT);
        $mform->addRule('wordsallowed', null, 'required', null, 'client');
        $mform->setDefault('wordsallowed', 5);
        
        $mform->addElement('static', 'submitions_wont_be_altered', '', '<span>'.get_string('submitions_wont_be_altered', 'wordcloud').'</span>');
        
        array_unshift($maxword_options,0);
        
        $mform->addElement('select', 'wordsrequired', get_string('wordrequired', 'wordcloud'), $maxword_options);
        $mform->setType('wordsrequired', PARAM_INT);
        $mform->addRule('wordsrequired', null, 'required', null, 'client');
        $mform->setDefault('wordsrequired', 0);
        
        $mform->addElement('header', 'availabilityhdr', get_string('availability'));
        $mform->addElement('date_time_selector', 'timestart', get_string('allowsubmitionfrom', 'wordcloud'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeend', get_string('allowsubmitionupto', 'wordcloud'), ['optional' => true]);
        
        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
        
    }
    
    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('instructions');
            $default_values['instructionseditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_wordcloud', 'instructions', 0,
                                array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean'=>true, 'context'=>$this->context),
                                $default_values['instructions']);
            $default_values['instructionseditor']['format'] = 1;
            $default_values['instructionseditor']['itemid'] = $draftitemid;
        } else {
            $draftitemid = file_get_submitted_draft_itemid('instructions');
            file_prepare_draft_area($draftitemid, null, 'mod_wordcloud', 'instructions', 0);    // no context yet, itemid not used
            $data['instructionseditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);
        }

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionwordssenabled']= (!empty($default_values['completionwords']) && $default_values['completionwords']!==0)? 1 : 0;
    }
    
    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if (!isset($data['wordsallowed'])) {
            $errors['wordsallowed'] = get_string('maxwordsallowed_undefined', 'wordcloud');
        }else if ($data['wordsallowed'] > get_config('wordcloud', 'maxwordsallowed')) {
            $errors['wordsallowed'] = get_string('maxwordsallowed_tomany', 'wordcloud');
        }
        
        if (!isset($data['wordsrequired'])) {
            $errors['wordsrequired'] = get_string('maxwordrequired_undefined', 'wordcloud');
        }else if ($data['wordsrequired'] > get_config('wordcloud', 'maxwordsallowed')) {
            $errors['wordsrequired'] = get_string('maxwordrequired_tomany', 'wordcloud');
        }
        
        if (isset($data['wordsrequired']) && isset($data['wordsallowed'])) {
            if ($data['wordsrequired'] > $data['wordsallowed']){
                $errors['wordsrequired'] = get_string('maxwordrequired_bigger_than_allowed', 'wordcloud');
            }
        }
        
        if (!empty($data['timestart']) && !empty($data['timeend'])) {
            if ($data['timeend'] < $data['timestart'] ) {
                $errors['timeend'] = get_string('timeend_before_start', 'assign');
            }
        }
        
        return $errors;
    }
    
    /**
     * Add elements for setting the custom completion rules.
     *
     * @category completion
     * @return array List of added element names, or names of wrapping group elements.
     */
    public function add_completion_rules() {
        
        $mform = $this->_form;
        
        $group = array(
            $mform->createElement('checkbox', 'completionwordssenabled', '', get_string('completionwords', 'wordcloud')),
        );
        
        $mform->addGroup($group, 'completionwordsgroup', get_string('completionwordsgroup','wordcloud'), array(' '), false);
        
        $mform->disabledIf('completionwordssenabled', 'wordsrequired', 'eq', 0);
        $mform->disabledIf('completionwordssenabled', 'completionunlocked', 'eq', 0);
        
        return array('completionwordsgroup');
    }

    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        } 
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked
            $autocompletion = !empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionwordssenabled) || !$autocompletion) {
               $data->completionwords = 0;
            }
        }
        return $data;
    }
    
    function completion_rule_enabled($data) {
        return !empty($data['completionwordssenabled']);
    }

}
