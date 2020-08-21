<?php

class mod_completionmarker_renderer extends plugin_renderer_base{

    public function custom_content($mod){
        global $DB, $CFG, $COURSE, $USER, $PAGE;

        $completion = new completion_info($COURSE);

        if(!$completion->is_enabled()){
            return '<button class="btn btn-warning">'.get_string('errorcompletionnotenable', 'completionmarker').'</button>';
        }

        $completion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $mod->id, 'userid'=>$USER->id));

        $label_completed = '<i class=\'fa fa-check-square completionmarker-icon\' aria-hidden=\'true\'></i> Étape terminée';
        $label_uncompleted = '<i class=\'fa fa-square completionmarker-icon\' aria-hidden=\'true\'></i> Marquer cette étape comme terminée';

        if($completion && $completion->completionstate == 1){
            $label = $label_completed;
            //$label = get_string('unmark', 'completionmarker');
        }else{
            $label = $label_uncompleted;
            //$label = get_string('mark', 'completionmarker');
        }


        $button = '<a href="#" class="mark">'.$label.'</a>';

        $PAGE->requires->js_call_amd('mod_completionmarker/completionmarker', 'init', array($label_completed, $label_uncompleted));

        return $button;
    }
}