<?php
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/ParticipantsOverviewData.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/Filters.php');

class participants_overview_form extends moodleform {

    const BADGEID_FIELD = 'badgeid';
    const MODID_FIELD = 'modid';
    const STATUS_FIELD = 'status';
    const GROUPID_FIELD = 'groupid';
    const USERNAME_FIELD = 'username';
    const ROLEID_FIELD = 'roleid';

    public function definition() {
        global $COURSE;
        

        $mform =& $this->_form;

        $mform->disable_form_change_checker();

        $mform->addElement('header', 'filter', get_string('participantoverviewtitle', 'block_course_badges'));

        $mform->addElement('text', self::USERNAME_FIELD, get_string('usernamefield', 'block_course_badges'));
        $mform->setType(self::USERNAME_FIELD, PARAM_TEXT);

        $groups = Filters::get_groups_list($COURSE->id);
        $this->add_select(self::GROUPID_FIELD, $groups, 'groupfieldsearch');

        $badges = Filters::get_list_badges($COURSE->id);
        $this->add_select(self::BADGEID_FIELD, $badges, 'badgefieldsearch');

        $options = [
            -1 => get_string('all'),
            ParticipantsOverviewData::EARNED_BADGES => get_string('earnedbadgelabel', 'block_course_badges'),
            ParticipantsOverviewData::SELECTED_BADGES => get_string('selectedbadgelabel', 'block_course_badges'),
        ];
        $mform->addElement('select', self::STATUS_FIELD, get_string('statuslabel', 'block_course_badges'), $options);

        $mods = Filters::get_list_mod_badges($COURSE->id);
        $this->add_select(self::MODID_FIELD, $mods, 'modnamefieldsearch');
        
        $coursebadgeAvailable = false;
        foreach($mods as $mod) {
            $cminfo = get_fast_modinfo($COURSE->id)->cms[$mod->cmid];
            if ($cminfo->visible && $cminfo->available) {
                $coursebadgeAvailable = true;
            }
        }
            
        $jtableCols = ParticipantsOverviewData::getJTableColumns();
        
        if (!$coursebadgeAvailable) {
            unset($jtableCols['selectedbadges']);
            unset($jtableCols['percent']);
            unset($jtableCols['modname']);
        }
        
        $context = context_course::instance($COURSE->id);
        $roles  =  role_fix_names(get_default_enrol_roles($context), $context, ROLENAME_ALIAS, true);

        $roleOptions = array();
        foreach($roles as $id => $name) {
            $roleOptions[$id] =  $name;
        }
        $roleSelect = $this->_form->addElement('select', self::ROLEID_FIELD, get_string('roles', 'role'), $roleOptions);
        $roleSelect->setMultiple(true);
        $roleSelect->setSelected(array_keys($roleOptions));
        
        html_input_data($mform, 'jtablecolumns', $jtableCols);

        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('html', '<div id="results"></div>');
    }

    private function add_select($name, $list, $label)
    {
        $options = [-1 => get_string('all')];
        foreach($list as $l){
            $options[$l->id] = $l->name;
        }
        $this->_form->addElement('select', $name, get_string($label, 'block_course_badges'), $options);
    }
}
