<?php
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/BadgesOverviewData.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/ModFilters.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/utils.php');

class badges_overview_form extends moodleform {

    const MODNAME_SEARCH_FIELD = 'modnamesearch';

    public function definition() {
        global $COURSE;

        $mform =& $this->_form;

        $cmid = $this->_customdata['cmid'];

        $mform->disable_form_change_checker();
        
        $mform->addElement('header', 'filter', get_string('badgeoverviewtitle', 'mod_coursebadges'));

        Utils::html_input_data($mform, 'jtablecolumns', BadgesOverviewData::getJTableColumns());

        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('html', '<div id="results"></div>');
    }

}
