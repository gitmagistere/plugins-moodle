<?php

/**
 * Moodle Magistere_offer local plugin
 * This class publics_form is a form used by the user to select his favorite publics
 *
 * @package    local_magistere_offers
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir."/formslib.php");

class publics_form extends moodleform
{
    private $labels;
    
    /**
     * The constructor function calls the abstract function definition() and it will then
     * process and clean and attempt to validate incoming data.
     *
     * It will call your custom validate method to validate data and will also check any rules
     * you have specified in definition using addRule
     *
     * The name of the form (id attribute of the form) is automatically generated depending on
     * the name you gave the class extending moodleform. You should call your class something
     * like
     *
     * @param mixed $action the action attribute for the form. If empty defaults to auto detect the
     *              current url. If a moodle_url object then outputs params as hidden variables.
     * @param mixed $customdata if your form defintion method needs access to data such as $course
     *              $cm, etc. to construct the form definition then pass it in this array. You can
     *              use globals for somethings.
     * @param string $method if you set this to anything other than 'post' then _GET and _POST will
     *               be merged and used as incoming data to the form.
     * @param string $target target frame for form submission. You will rarely use this. Don't use
     *               it if you don't need to as the target attribute is deprecated in xhtml strict.
     * @param mixed $attributes you can pass a string of html attributes here or an array.
     * @param bool $editable
     * @param array $ajaxformdata Forms submitted via ajax, must pass their data here, instead of relying on _GET and _POST.
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, array $ajaxformdata = null)
    {
        global $USER;
        $email = "";
        if(isset($USER) && $USER->id > 0){
            $email = $USER->email;
        }

        $this->labels = array(
            'filtre' => get_string('form_publics_filter', 'local_magistere_offers'),
            'notif' => get_string('form_publics_notif', 'local_magistere_offers', $email),
            'note' => get_string('form_publics_note', 'local_magistere_offers')
        );

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /***
     * 
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition()
    {
        $mform =& $this->_form;
        
        $mform->disable_form_change_checker();

        $mform->_attributes['class'] = 'mform publics-form';
        $mform->setType('v', PARAM_ALPHA);
        $mform->setDefault('v', $this->_customdata['v']);
        $mform->setType('publics_fav', PARAM_RAW);
        $mform->setDefault('publics_fav', $this->_customdata['publics_fav']);
        $mform->addElement('hidden', 'v', $this->_customdata['v']);

        $mform->setType('get_notif', PARAM_RAW);
        $mform->setDefault('get_notif', $this->_customdata['get_notif']);

        $publics = OfferCourse::get_indexation_publics();

        $checkbox_left = array();
        $checkbox_right = array();
        $cpt =1;
        foreach($publics as $id => $public) {
            if($cpt > count($publics) / 2){
                $checkbox_right[] =& $mform->createElement('checkbox', 'publics_fav['.$public->id.']', '', $public->name);
            } else {
                $checkbox_left[] =& $mform->createElement('checkbox', 'publics_fav['.$public->id.']', '', $public->name);
            }
            $cpt++;
        }

        $mform->addElement('html', html_writer::start_div('modal-header'));
        $mform->addElement('html', html_writer::tag('h5','Option de filtrage de l\'offre',array('class'=>'modal-title text-center')));
        $mform->addElement('html', html_writer::tag('button',html_writer::span('&times;','',array('aria-hidden'=>'true')),array('class'=>'close', 'type'=>'button', 'data-dismiss'=>'modal', 'aria-label'=>'Close')));
        $mform->addElement('html', html_writer::end_div()); //div modal-header

        $mform->addElement('html', html_writer::start_div('modal-body'));
        $mform->addElement('html', html_writer::tag('p',$this->labels['filtre']));

        $mform->addElement('html', html_writer::start_div('publics'));

        $mform->addElement('html', html_writer::start_div('left-column'));
        $mform->addGroup($checkbox_left, 'public_fav_group', null, array("<br/>"), false);
        $mform->addElement('html', html_writer::end_div()); //div left-column
        $mform->addElement('html', html_writer::end_div()); //div publics

        $mform->addElement('html', html_writer::start_div('right-column'));
        $mform->addGroup($checkbox_right, 'public_fav_group', null, array("<br/>"), false);
        $mform->addElement('html', html_writer::end_div()); //div right-column

//        $mform->addElement('html', html_writer::end_div()); // Obligé de fermer la div publics plus haut à cause d'un bug de génération HTML caussé par le framework.

        $mform->addElement('html', html_writer::div('','',array('style'=> 'clear:both')));
        $mform->addElement('html', html_writer::start_div('notif'));
        $mform->addElement('checkbox', 'get_notif', '', $this->labels['notif']);
        $mform->addElement('html', html_writer::end_div()); //div notif
        $mform->addElement('html', html_writer::end_div()); //div modal-body

        $mform->addElement('html', html_writer::start_div('modal-footer'));
        $mform->addElement('submit', 'publicsformsubmitbutton', "Valider");
        $mform->addElement('html', html_writer::tag('p',$this->labels['note'],array('class'=>'note')));
        $mform->addElement('html', html_writer::end_div());

    }

    /***
     * 
     * {@inheritDoc}
     * @see moodleform::get_data()
     */
    public function get_data()
    {
        return parent::get_data(); // TODO: Change the autogenerated stub
    }
}