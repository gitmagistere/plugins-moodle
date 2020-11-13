<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/mod/coursebadges/DualList.php');
require_once($CFG->dirroot.'/mod/coursebadges/CourseBadges.php');
require_once($CFG->dirroot.'/mod/coursebadges/CourseBadgesNotification.php');
require_once($CFG->dirroot.'/mod/coursebadges/choicebadges_form.php');
require_once($CFG->dirroot.'/mod/coursebadges/lib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID

if (!$cm = get_coursemodule_from_id('coursebadges', $id)) {
    print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
}
if (!$course = $DB->get_record('course', ['id'=> $cm->course])) {
    print_error('course is misconfigured');  // NOTE As above
}
if (!$course_badge = $DB->get_record('coursebadges', ['id'=> $cm->instance])) {
    print_error('course module is incorrect'); // NOTE As above
}

$params = [];
if ($id) {
    $params['id'] = $id;
}

$PAGE->set_url('/mod/coursebadges/view.php', $params);
require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$PAGE->set_title($course_badge->name);
$PAGE->set_heading($course->fullname);

$formData = ['id' => $id,
    'contextid' => $context->id,
    'coursebadgesid' => $course_badge->id
];

$form = new choicebadges_form(null, $formData);
if($form->get_data()){
    $cb = new CourseBadges($course_badge->id, $form->get_data());
    $cb_data = $cb->get_course_badges_instance();

    $minbadgerequired = $cb_data->badgesminrequired;
    $maxbadgerequired = $cb_data->badgesmaxrequired;

    $cbselected = explode(",", $form->get_data()->rightlistids);
    if((count($cbselected) < $minbadgerequired && $minbadgerequired > 0)
        || (count($cbselected) > $maxbadgerequired) && $maxbadgerequired > 0){
        $message = "";
        $a = new stdClass();
        $a->ruleminallowbadge = "";
        $a->rulemaxallowbadge = "";
        if(count($cbselected) < $minbadgerequired){
            $a->ruleminallowbadge = get_string('error_rule_min_allow_badge', 'coursebadges', $minbadgerequired);
        }
        if(count($cbselected) > $maxbadgerequired){
            $a->rulemaxallowbadge = get_string('error_rule_max_allow_badge', 'coursebadges', $maxbadgerequired);
        }
        $message = get_string('error_configuration_activity_notif', 'coursebadges', $a);

        \core\notification::add($message, \core\notification::ERROR);
    } else {
        if($cb->create_selected_badges_instances()){
            $notif_badges = new CoursesBadgesNotification($course_badge->id);
            $notif_badges->send_notification();
            $action = new moodle_url('/mod/coursebadges/view.php', array('id' => $cm->id));
            $action = $action->out(false);
            \core\notification::fetch();
            redirect($action, get_string('notification_add_choice', 'coursebadges'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course_badge->name), 2);

if(!empty($course_badge->intro)) {
    echo $OUTPUT->box(format_module_intro('coursebadges', $course_badge, $cm->id), 'generalbox', 'intro');
}

echo $form->render();

$canviewbadgesoverview = has_capability('mod/coursebadges:viewbadgesoverview', $context);

if($canviewbadgesoverview){
    $url = new moodle_url('/mod/coursebadges/overview/badges.php', ['id' => $cm->id]);
    echo html_writer::link($url, get_string('badgesoverviewlink', 'mod_coursebadges'));
    echo html_writer::empty_tag('br');
}

if(has_capability('mod/coursebadges:viewparticipantsoverview', $context)){
    $canviewparticipantsoverview = true;
} else {
    if($course_badge->showawardedresults == CourseBadges::ALWAYS_SHOW_RESULTS) {
        $canviewparticipantsoverview = true;
    } else if($course_badge->showawardedresults == CourseBadges::SHOW_RESULTS_AFTER_RESPONSE) {
        if(CourseBadges::has_selected_badges($course_badge->id)) {
            $canviewparticipantsoverview = true;
        } else {
            $canviewparticipantsoverview = false;
        }
    } else {
        $canviewparticipantsoverview = false;
    }
}

if($canviewparticipantsoverview) {
    $url = new moodle_url('/mod/coursebadges/overview/participants.php', ['id' => $cm->id]);
    echo html_writer::link($url, get_string('participantsoverviewlink', 'mod_coursebadges'));
    echo html_writer::empty_tag('br');
}

echo $OUTPUT->footer($course);
