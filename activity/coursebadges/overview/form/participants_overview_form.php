<?php
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/ParticipantsOverviewData.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/ModFilters.php');

class participants_overview_form extends moodleform {

    const BADGEID_FIELD = 'badgeid';
    const MODID_FIELD = 'modid';
    const STATUS_FIELD = 'status';
    const GROUPID_FIELD = 'groupid';
    const USERNAME_FIELD = 'username';

    public function definition() {
        global $COURSE;

        $mform =& $this->_form;

        $mform->disable_form_change_checker();

        $cmid = $this->_customdata['cmid'];

        $mform->addElement('header', 'filter', get_string('participantoverviewtitle', 'coursebadges'));

        $mform->addElement('text', self::USERNAME_FIELD, get_string('usernamefield', 'coursebadges'));
        $mform->setType(self::USERNAME_FIELD, PARAM_TEXT);

        $groups = ModFilters::get_groups_list($COURSE->id, $cmid);
        $this->add_select(self::GROUPID_FIELD, $groups, 'groupfieldsearch');

        $badges = ModFilters::get_list_badges($cmid);
        $this->add_select(self::BADGEID_FIELD, $badges, 'badgefieldsearch');

        $options = [
            -1 => get_string('all'),
            ParticipantsOverviewData::EARNED_BADGES => get_string('earnedbadgelabel', 'coursebadges'),
            ParticipantsOverviewData::SELECTED_BADGES => get_string('selectedbadgelabel', 'coursebadges'),
        ];
        $mform->addElement('select', self::STATUS_FIELD, get_string('statuslabel', 'coursebadges'), $options);

        Utils::html_input_data($mform, 'jtablecolumns', ParticipantsOverviewData::getJTableColumns());

        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('html', '<div id="results"></div>');
    }

    private function add_select($name, $list, $label)
    {
        $options = [-1 => get_string('all')];
        foreach($list as $l){
            $options[$l->id] = $l->name;
        }
        $this->_form->addElement('select', $name, get_string($label, 'coursebadges'), $options);
    }
}
