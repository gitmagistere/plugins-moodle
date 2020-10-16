<?php
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/BadgesOverviewData.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/Filters.php');

class badges_overview_form extends moodleform {

    const MODNAME_SEARCH_FIELD = 'modnamesearch';

    public function definition() {
        global $COURSE;

        $mform =& $this->_form;

        $mform->disable_form_change_checker();
        
        $mform->addElement('header', 'filter', get_string('filtertitle', 'block_course_badges'));

        $listMod = Filters::get_list_mod_badges($COURSE->id);
        $options = [-1 => get_string('all')];
        foreach($listMod as $b){
            $options[$b->id] = $b->name;
        }
        
        $countoptions = count($options);
        if  ($countoptions > 2) {
            $mform->addElement('select', self::MODNAME_SEARCH_FIELD, get_string('modnamefieldsearch', 'block_course_badges'), $options);
        } elseif ($countoptions > 1) {
            $mform->addElement('hidden', self::MODNAME_SEARCH_FIELD, array_keys($options)[1]);
            $mform->setType(self::MODNAME_SEARCH_FIELD, PARAM_TEXT);
        } else {
            $mform->addElement('hidden', self::MODNAME_SEARCH_FIELD,  array_keys($options)[0]);
            $mform->setType(self::MODNAME_SEARCH_FIELD, PARAM_TEXT);
        }
        
        $jtableCols = BadgesOverviewData::getJTableColumns();
        if ($countoptions <= 2) {
            // pas de nom choix de badge si une seule activité
            unset($jtableCols['modname']);
        }
        html_input_data($mform, 'jtablecolumns', $jtableCols);

        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('html', '<div id="results"></div>');
    }
}
