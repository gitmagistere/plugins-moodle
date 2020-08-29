<?php
/**
 * workflow local plugin
 *
 * Fichier index qui gere l'affichage du formulaire de workflow selon l'etat dans lequel se situe le parcours.
 *
 * @package    local
 * @subpackage workflow
 * @author     TCS
 * @date       Aout 2018
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/workflow/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_course_login($course, true);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/local/workflow/index.php', array('id' => $id));
$PAGE->set_course($course);

$PAGE->set_title($course->shortname.' : '. get_string('pluginname', 'local_workflow'));
$PAGE->set_heading($course->fullname);

$canseeworkflow = has_capability('local/workflow:globalaccess', context_course::instance($course->id));
if ($PAGE->user_allowed_editing() || $canseeworkflow) {
    
    if (!issetConfig()){
        print_error('plugin_config_not_set: Vous devez definir tous les roles','error',(new moodle_url('/admin/settings.php',array('section'=>'local_workflow_settings_config')))->out());
    }
    
    if (!issetCourseCat()){
        print_error('plugin_course_category_not_found: Vous devez créer l\'ensemble des catégories nécessaire au bon fonctionnement de ce plugin avant de pouvoir y accéder.','error',(new moodle_url('/course/management.php'))->out());
    }

    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->css("/local/workflow/styles/jquery.loadingModal.min.css");
    $PAGE->requires->js_call_amd("local_workflow/dialogs", "init");
    $PAGE->requires->js_call_amd("local_workflow/datepicker", "init", array('datepicker_session'));

    $mail_check_url = new moodle_url('/local/workflow/ajax/WFMailPreviewApi.php');
    $PAGE->requires->js_call_amd("local_workflow/WFEnrolMailPreview", "init", array($mail_check_url->out()));
    
    if (file_exists($CFG->dirroot.'/local/workflow/lib/Gaia.php') && get_config('local_workflow','enable_gaia') == true){
        $PAGE->requires->js_call_amd("local_workflow/gaia", "init");
    }
    
    if (isOptimizerAvailable()){
        $apiurl = new moodle_url('/local/workflow/api.php');
        $PAGE->requires->js_call_amd("local_magisterelib/optimizer", "init",array($apiurl->out(),$id));
    }
    $buttons = $OUTPUT->edit_button($PAGE->url, true);
    $PAGE->set_button($buttons);


    $workflow = start_workflow($course->id);

    if($workflow->isSubmittedForm()){
        $workflow->processForm();
    }

    echo $OUTPUT->header();

    echo html_writer::start_div('workflow');
    echo html_writer::tag('h2', get_string('pluginname', 'local_workflow'));

    echo $workflow->showWorkflow();

    echo html_writer::end_div();

    echo $OUTPUT->footer();
} else {
    $notification = get_string('error_access_denied', 'local_workflow');
    redirect(new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id)),
        $notification,
        null,
        \core\output\notification::NOTIFY_ERROR);
}
