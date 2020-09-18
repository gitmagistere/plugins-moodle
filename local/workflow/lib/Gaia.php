<?php

class Gaia {

    private static $available = null;
    
    static function isAvailable(){
        if (self::$available != null){
            return self::$available;
        }
        //set_config('enable_gaia',true,'local_workflow');
        self::$available = (file_exists($GLOBALS['CFG']->dirroot.'/local/gaia/lib/GaiaUtils.php') && get_config('local_workflow','enable_gaia') == true);
        return self::$available;
    }
    
    static function getContent($courseid)
    {
        global $CFG;
        require_once($CFG->dirroot . '/local/gaia/lib/GaiaUtils.php');
        
        $gaia_session = GaiaUtils::get_sessions($courseid);
    
        $session_node = new stdClass();
        $session_node->content = "";
        $session_node->children = array();
        $session_node->expanded = true;
        
        $content = '';
        $content .= html_writer::tag('h5', html_writer::span('Session Gaia associées à ce parcours'), array('class' => 'sessions-course-title'));
        foreach($gaia_session as $session){
    
            $dispositif = html_writer::span($session->dispositif_id." : ".$session->dispositif_name, 'dispositif');
            $module = html_writer::span($session->module_id." : ".$session->module_name, 'module');
            $from_date = date('d/m/Y', $session->startdate);
            $from_hour = date('G\hi', $session->startdate);
            $from = $from_date." à ".$from_hour;
            $to_date = date('d/m/Y', $session->enddate);
            $to_hour = date('G\hi', $session->enddate);
            $to = $to_date." à ".$to_hour;
            $date = html_writer::span("Du ".$from." au ".$to);
            $formation_place = html_writer::span($session->formation_place);
            $session_detail = html_writer::div($dispositif.$module.$date.$formation_place, 'session-detail');
    
            $formateurs = GaiaUtils::get_intervenant_gaia($session->session_id, $session->dispositif_id, $session->module_id);
            $participants = GaiaUtils::get_stagiaire_gaia($session->session_id, $session->dispositif_id, $session->module_id);
    
            $info_users = "";
            if(count($formateurs) > 0){
                $info_users = count($formateurs). ' formateurs';
            }
    
            if(count($participants) > 0){
                if(count($formateurs) > 0){
                    $info_users .= ' / '.count($participants). ' participants';
                } else {
                    $info_users .= count($participants). ' participants';
                }
            }
    
            $info_users = html_writer::div($info_users, 'info-users');
    
            $unlinkurl = new moodle_url('/local/gaia/unlink.php', array(
                'courseid' => $courseid, 'dispositifid' => $session->dispositif_id,
                'moduleid' => $session->module_id, 'sessionid' => $session->session_id, 'returnurl'=> (new moodle_url('/local/workflow/index.php',array('id'=>$courseid)))->out()));
    
            $edit_url = new moodle_url('/local/gaia/linkcourse.php', array('id'=>$courseid, 'dispositifid' => $session->dispositif_id,
                    'moduleid' => $session->module_id, 'sessionid' => $session->session_id));
    
            $edit_action = html_writer::link($edit_url, html_writer::tag('i','',array('class' => 'fas fa-cog')));
            $unlink_action = html_writer::link($unlinkurl, html_writer::tag('i','',array('class' => 'fas fa-times')), array('class'=>'unlink_link gaia-unlink'));
            $actions = html_writer::div($edit_action.$unlink_action, 'actions');
    
            $info_actions = html_writer::div($info_users.$actions, 'info-actions');
            $content .= html_writer::div($session_detail.$info_actions, 'session-course-content');
        }
    
        $form_url = new moodle_url('/local/gaia/linkcourse.php', array('id'=>$courseid));
        $add_link = html_writer::link($form_url, html_writer::tag('i','',array('class' => 'fas fa-plus')), array('class' => 'btn'));
        $content .= html_writer::div($add_link, 'add-gaia-association');
    
        $via_blocks = GaiaUtils::get_via_block($courseid);
    
        if(count($via_blocks)){
            $content .= html_writer::tag('h5', html_writer::span('Classes virtuelles de ce parcours'), array('class' => 'sessions-via-title'));
    
            foreach ($via_blocks as $via_block){
    
                $via_name = html_writer::span($via_block->name, 'via-name');
                $datebegin_date = date('d/m/Y', $via_block->datebegin);
                $datebegin_hour = date('G\hi', $via_block->datebegin);
                $datebegin = html_writer::span($datebegin_date .' à '. $datebegin_hour, 'via-datebegin');
                $duration = html_writer::span($via_block->duration ." min", 'via-duration');
                $via_detail = html_writer::div($via_name.$datebegin.$duration, 'via-detail');
    
                $via = GaiaUtils::get_gaia_via_info($via_block->id);
                
                $via_gaia = "";
                if($via){
                    $arrow = html_writer::tag('i','',array('class' => 'fas fa-arrow-right'));
                    $arrow = html_writer::div($arrow, 'separator');
    
                    $dispositif = html_writer::span($via->dispositif_id." : ".$via->dispositif_name, 'dispositif');
                    $module = html_writer::span($via->module_id." : ".$via->module_name, 'module');
                    $from_date = date('d/m/Y', $via->startdate);
                    $from_hour = date('G\hi', $via->startdate);
                    $from = $from_date." à ".$from_hour;
                    $to_date = date('d/m/Y', $via->enddate);
                    $to_hour = date('G\hi', $via->enddate);
                    $to = $to_date." à ".$to_hour;
                    $date = html_writer::span("Du ".$from." au ".$to);
                    $formation_place = html_writer::span($via->formation_place);
    
                    $formateurs = GaiaUtils::get_intervenant_gaia($via->session_id, $via->dispositif_id, $via->module_id);
                    $participants = GaiaUtils::get_stagiaire_gaia($via->session_id, $via->dispositif_id, $via->module_id);
    
                    $info_users = "";
                    if(count($formateurs) > 0){
                        $info_users = count($formateurs). ' formateurs';
                    }
    
                    if(count($participants) > 0){
                        if(count($formateurs) > 0){
                            $info_users .= ' / '.count($participants). ' participants';
                        } else {
                            $info_users .= count($participants). ' participants';
                        }
                    }
    
                    $info_users = html_writer::div($info_users, 'info-users');
    
                    $via_gaia_detail = html_writer::div($dispositif.$module.$date.$formation_place.$info_users, 'via-gaia-detail');
    
                    $unlinkurl = new moodle_url('/local/gaia/unlink.php', array(
                        'courseid' => $courseid, 'dispositifid' => $via->dispositif_id,
                        'moduleid' => $via->module_id, 'sessionid' => $via->session_id, 'returnurl'=> (new moodle_url('/local/workflow/index.php',array('id'=>$courseid)))->out()));
    
                    $edit_url = new moodle_url('/local/gaia/linkvia.php', array('id'=>$courseid, 'vid' => $via_block->id, 'dispositifid' => $via->dispositif_id,
                        'moduleid' => $via->module_id, 'sessionid' => $via->session_id));
    
                    $edit_action = html_writer::link($edit_url, html_writer::tag('i','',array('class' => 'fas fa-cog')));
                    $unlink_action = html_writer::link($unlinkurl, html_writer::tag('i','',array('class' => 'fas fa-times')), array('class'=>'unlink_link gaia-unlink'));
                    $actions = html_writer::div($edit_action.$unlink_action, 'actions');
    
                    $info_actions = html_writer::div($actions, 'info-actions');
                    $via_gaia = $arrow.$via_gaia_detail.$info_actions;
    
                } else {
                    $form_url = new moodle_url('/local/gaia/linkvia.php', array('id'=>$courseid,'vid'=>$via_block->id));
                    $via_gaia = html_writer::link($form_url, html_writer::tag('i','',array('class' => 'fas fa-cog')). html_writer::span("Lier l'activité"), array('class' => 'bind-via'));
                }
    
                $via_gaia = html_writer::div($via_gaia, 'via-gaia-content');
    
                $content .= html_writer::div($via_detail.$via_gaia, 'session-via-content');
            }
        }
    
    
        return $content;
    }
    
    static function unlink_dialog(){
        global $CFG;
        $html = '<div id="dialog_unlink" style="display:none;">
                <form method="POST" action="'.$CFG->wwwroot.'/local/workflow/lib/gaia/ajax_gaia.php" name="unlink_form" id="unlink_form">
                    <p>
                    Attention : <span style="color: red">Les utilisateurs inscrits lors de l’association seront désinscrits du parcours.</span>
                    </p>
                    <input type="hidden" name="action" value="unlink"/>
                </form>
            </div>';
        return $html;
    }
    
}