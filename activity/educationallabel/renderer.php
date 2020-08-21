<?php
defined('MOODLE_INTERNAL') || die();

class mod_educationallabel_renderer extends plugin_renderer_base {
    public function display_label($educlabel, $cm){
        global $USER, $DB;
        $output = '';
        if ($educlabel) {
            if (empty($educlabel->name)) {
                // label name missing, fix it
                $educlabel->name = "educationallabel{$educlabel->id}";
                $DB->set_field('educationallabel', 'name', $educlabel->name, array('id'=>$educlabel->id));
            }

            $title = $educlabel->name;

            if($educlabel->type == LBL_IMPORTANT){
                $title = '<i class="fa fa-exclamation-triangle" aria-hidden="true" style="margin-right: 10px;"></i>'.$title;
            }

            $output .= html_writer::tag('h5', $title, array('class' => 'type-title'));
            $output .= '<br/>' . format_module_intro('educationallabel', $educlabel, $cm->id, false);
        }
        return $output;
    }
}