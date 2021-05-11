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
 * Defines the form that allow student to submit as many words as required
 *
 * @package   mod_wordcloud
 * @copyright 2021 TCS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');


/**
 * A form that allow student to submit as many words as required
 *
 * @copyright  2021 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wordcloud_wordsubmit_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
        
        //$mform->addElement('hidden', 'g', $this->_customdata['group']);
        //$mform->setType('g', PARAM_INT);
        
        $wordsallowed = $this->_customdata['wordsallowed'];
        $wordsrequired = $this->_customdata['wordsrequired'];
        $wordmaxlenght = get_config('wordcloud', 'wordmaxlenght');
        
        for($i=1;$i<=$wordsallowed;$i++){
            $mform->addElement('text', 'word_'.$i, get_string('word_nb', 'wordcloud').' '.$i, array('maxlength'=>$wordmaxlenght,'size'=>$wordmaxlenght+1));
            $mform->setType('word_'.$i, PARAM_NOTAGS);
            //$mform->addRule('word_'.$i, get_string('missingword', 'wordcloud'), 'required', null, 'client');
            if ($wordsrequired >= $i){
                $mform->addRule('word_'.$i, get_string('missingword', 'wordcloud'), 'required', null, 'server');
            }
        }

        $this->add_action_buttons(false, get_string('send', 'wordcloud'));
        $mform->setDisableShortforms();
        $mform->disable_form_change_checker();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $words = array();
        for($i=1;$i<=$this->_customdata['wordsallowed'];$i++){
            $word = str_replace('"','',trim($data['word_'.$i]));
            if ($word == ''){
                continue;
            }else if (in_array($word,$words)) {
                $errors['word_'.$i] = get_string('word_already_used', 'wordcloud');
            }else{
                $words[] = $word;
            }
        }

        return $errors;
    }
}
