<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Wordcloud module renderer
 *
 * @package   mod_wordcloud
 * @copyright 2021 TCS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


require_once(__DIR__.'/wordsubmit_form.php');

class mod_wordcloud_renderer extends plugin_renderer_base {

    public function display_wordcloud_cloud($wordcloud){
        return '<div class="wordcloudcontainer"><div class="wc_empty" style="display:none">'.get_string('empty_wordcloud','mod_wordcloud').'</div></div>';
    }
    
    public function display_wordcloud_editor($wordcloud,$hide=false) {
        $endstart_date = '';
        if ($wordcloud->has_ended() && $wordcloud->activity->timeend > 0) {
            $endstart_date = get_string('student_cant_submit_since','mod_wordcloud',strftime(get_string('strftimedaydatetime','core_langconfig'),$wordcloud->activity->timeend));
        }else if ($wordcloud->is_started() && $wordcloud->activity->timestart > 0) {
            $endstart_date = get_string('student_can_submit_upto','mod_wordcloud',strftime(get_string('strftimedaydatetime','core_langconfig'),$wordcloud->activity->timestart));
        }else if ($wordcloud->activity->timestart > 0){
            $endstart_date = get_string('student_can_submit_from','mod_wordcloud',strftime(get_string('strftimedaydatetime','core_langconfig'),$wordcloud->activity->timestart));
        }
        
        return '
<div class="wc_tools"'.($hide?' style="display:none"':'').'>
    <div class="wc_editor_view">
        <div id="wc_exports"><div><button class="wc_exportpng">'.get_string('exporttoimage','mod_wordcloud').'</button></div><div><button class="wc_exportdata">'.get_string('exportdata','mod_wordcloud').'</button></div></div>
        <div class="wc_participation"><span class="wc_participation_count">'.get_string('nosubmition','mod_wordcloud').'</span><br/><span class="wc_endstart_date">'.$endstart_date.'</span></div>
        <div>
            <div class="wc_addword_header">'.get_string('addword','mod_wordcloud').'</div>
            <div class="row wc_formrow">
                <div class="col-md-3">'.get_string('word','mod_wordcloud').'</div>
                <div class="col-md-3"><input maxlength="'.get_config('wordcloud', 'wordmaxlenght').'" type="text" class="form-control" name="wcaddword"/></div>
                <div class="col-md-6"></div>
            </div>
            <div class="row wc_formrow">
                <div class="col-md-3"></div>
                <div class="col-md-9"><button class="wc_addword">'.get_string('add','mod_wordcloud').'</button></div>
            </div> 
        </div>
        
    </div>
</div>
<div class="wc_editor_word">
    <div class="wc_editword_header">'.get_string('updateaword','mod_wordcloud').'</div>
    <div class="row wc_formrow">
        <div class="col-md-3">'.get_string('word','mod_wordcloud').'</div>
        <div class="col-md-3">
            <input type="text" maxlength="'.get_config('wordcloud', 'wordmaxlenght').'" name="wceditword" class="form-control"/>
        </div>
        <div class="col-md-6"></div>
    </div>
    <div class="row wc_formrow wc_weight">
        <div class="col-md-3">'.get_string('wordweight','mod_wordcloud').'</div>
        <div class="col-md-9">
            <span>0</span>
        </div>
    </div>
    <div class="row wc_formrow">
        <div class="col-md-3"></div>
        <div class="row col-md-9">
            <div><button class="wc_updateword">'.get_string('updateword','mod_wordcloud').'</button></div>
            <div><button class="wc_removeword">'.get_string('removeword','mod_wordcloud').'</button></div>
            <div><button class="wc_closeedit">'.get_string('canceledit','mod_wordcloud').'</button></div>
        </div>    
    </div>
    <div class="row wc_formrow wc_users">
        <div class="col-md-3">'.get_string('wordusers','mod_wordcloud').'</div>
        <div class="col-md-9">
            <ul></ul>
        </div>
    </div>
</div>';
    }
    
    
    public function display_wordcloud_submit_form($wordcloud,$g=0) {
        $url = new moodle_url('/mod/wordcloud/view.php',array('id'=>$wordcloud->cm->id,'g'=>$g));
        $form = new mod_wordcloud_wordsubmit_form($url,
            array('wordsallowed' => $wordcloud->activity->wordsallowed, 'wordsrequired' => $wordcloud->activity->wordsrequired, 'group'=>$g), 'post');
        
        return $form->render();
    }

    public function display_wordcloud_submit($wordcloud,$g=0,$hide,$content='') {
        return '<div id="wcform"'.($hide?' style="display:none"':'').'>'.$content.'</div>';
    }

    public function display_wordcloud_groupselector($wordcloud,$g) {
        $user_groups = $wordcloud->get_user_groups();
        
        if (count($user_groups) == 0 ){return '';}
        
        $notdisplayed = count($user_groups) == 1 ? 'hide' : '';
        $output = '<div class="wc_groups '.$notdisplayed.'">'.get_string('group','mod_wordcloud').' <select class="wc_groupselector">';
        
        
        foreach($user_groups AS $group) {
            $output .= '<option value="'.$group->id.'"'.($group->id==$g?' selected="selected"':'').'>'.$group->name.($group->member?' (membre)':'').'</option>';
        }
        
        $output .= '</select></div>';
        
        return $output;
    }
    

}

