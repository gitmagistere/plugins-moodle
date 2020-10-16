<?php
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/blocks/course_badges/overview/Filters.php');

class map_form extends moodleform {

    const BADGE_SELECT_FIELD = 'badgeselect';

    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $ajaxformdata = null)
    {
        $attributes['class'] = 'mapform';
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    public function definition() {
        global $COURSE, $PAGE, $OUTPUT;

        $mform =& $this->_form;

        $mform->disable_form_change_checker();

        $listBadge = Filters::get_list_badges($COURSE->id);
        $options = [
            -1 => ['text' => get_string('nobadgeselected', 'block_course_badges')]
        ];

        $coursectx = context_course::instance($COURSE->id)->id;
        foreach($listBadge as $b){
            $imgurl = get_img_url_badge($b->id, $coursectx);
            $options[$b->id] = ['text' => $b->name, 'src' => $imgurl];
        }

        $mform->addElement('html', html_writer::start_div('map_controls'));
        $mform->addElement('html', $OUTPUT->heading(get_string('participantsmap','block_course_badges'), 3));
        $mform->addElement('html', $this->custom_select(self::BADGE_SELECT_FIELD, $options));
        $mform->addElement('html', html_writer::end_div());

        $PAGE->requires->js_call_amd('block_course_badges/course_badges', 'init_custom_select', [
            'select-img'
        ]);

        $mform->addElement('hidden', 'id', $COURSE->id);
        $mform->setType('id', PARAM_INT);

       // $this->add_action_buttons(false, get_string('refreshmap', 'local_interactive_map'));
    }

    public function custom_select($name, $options)
    {
        $badgeid = $this->_customdata['badgeid'];

        if(!isset($options[$badgeid])){
            $badgeid = array_key_first($options);
        }

        $customSelect = html_writer::start_div('select-img');

        $selectedoptioncontent = '';

        $s = $options[$badgeid];

        $img = '<i class="fas fa-shield-alt"></i>';
        if(isset($s['src'])){
            $img = html_writer::img($s['src'], '');
        }

        $selectedoptioncontent = $img.' '.$s['text'];


        $customSelect .= html_writer::div($selectedoptioncontent, 'option-selected');

        $customSelect .= html_writer::div('', 'option-selected-arrow');

        $customSelect .= html_writer::start_div('options');
        $i = 0;
        foreach($options as $val => $option){
            $id = $val.$i;

            $htmlattr = [
                'type' => 'radio',
                'name' => $name,
                'value' => $val,
                'id' => $id
            ];

            if($val == $badgeid){
                $htmlattr['checked'] = 'checked';
            }

            $htmloption = html_writer::tag('input', '', $htmlattr);



            $img = '<i class="fas fa-shield-alt"></i>';
            if(isset($option['src'])){
                $img = html_writer::img($option['src'], '');
            }

            $htmloption .= html_writer::tag('label', $img.' '.$option['text'], ['for' => $id]);

            $customSelect .= html_writer::div($htmloption, 'option');
            $i++;
        }

        $customSelect .= html_writer::end_div();
        $customSelect .= html_writer::end_div();

        return $customSelect;
    }
}
