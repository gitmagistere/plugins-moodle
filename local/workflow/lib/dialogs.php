<?php


require_once($CFG->dirroot . '/user/lib.php');

/**
 * Fonction qui retourne une liste des action autorisées par l'utilisateur sur le workflow.
 * @param $course_main_category
 * @param $courseid
 * @param bool $notification_badge
 * @return array|null
 * @throws coding_exception
 */
function wf_get_main_action_links($course_main_category, $courseid, $notification_badge = false){
	$l_action_links = null;
	if($courseid){
        $context = context_course::instance($courseid);
		if($course_main_category==WKF_CAT_GAB){
            if (has_capability('local/workflow:createparcours', $context)){
                $l_action_links[] = 'createparcoursfromgabarit';
            }
            if (isOptimizerAvailable() && has_capability('local/workflow:optimize', $context)){
                $l_action_links[] = 'optimize';
            }
            if (has_capability('local/workflow:duplicate', $context)){
                $l_action_links[] = 'duplicate';
            }
            if (has_capability('local/workflow:trash', $context)){
                $l_action_links[] = 'discard';
            }
		}elseif($course_main_category==WKF_CAT_PDF){
            if (has_capability('local/workflow:createsession', $context)){
                if($notification_badge){
                    $l_action_links[] = 'createsessionfromparcours_disable';
                } else {
                    $l_action_links[] = 'createsessionfromparcours';
                }
            }
            if (isOptimizerAvailable() && has_capability('local/workflow:optimize', $context)){
                $l_action_links[] = 'optimize';
            }
            if (has_capability('local/workflow:duplicate', $context)){
                $l_action_links[] = 'duplicate';
            }
            if (has_capability('local/workflow:trash', $context)){
                $l_action_links[] = 'discard';
            }
		}elseif($course_main_category==WKF_CAT_SDF || $course_main_category == WKF_CAT_SLAF){
            if (has_capability('local/workflow:recreatesession', $context)){
                $l_action_links[] = 'recreatesessionfromparcours';
            }
            if (isOptimizerAvailable() && has_capability('local/workflow:optimize', $context)){
                $l_action_links[] = 'optimize';
            }
            if (has_capability('local/workflow:duplicate', $context)){
                $l_action_links[] = 'duplicate';
            }
            if (has_capability('local/workflow:trash', $context)){
                $l_action_links[] = 'discard';
            }
		}elseif($course_main_category==WKF_CAT_ARC){
            if (has_capability('local/workflow:createsession', $context)){
                $l_action_links[] = 'createsessionfromparcours';
            }
            if (has_capability('local/workflow:recreatesession', $context)){
                $l_action_links[] = 'unarchive';
            }
            if (has_capability('local/workflow:trash', $context)){
                $l_action_links[] = 'discard';
            }
		}elseif($course_main_category==WKF_CAT_TRASH){
            if (has_capability('local/workflow:reopencourse', $context)){
                $l_action_links[] = 'restorefromtrash';
            }
		}
	}
	return $l_action_links;
}


/**
 * Fonction genere le contenu des differentes popin d'action en fonction de l'utilisateur et de la categorie
 * retournee par function block_display
 * @param $l_links_type
 * @param $courseid
 * @return string
 * @throws coding_exception
 */
function wf_main_action_content($l_links_type, $courseid){
    global $OUTPUT;
    $context = context_course::instance($courseid);
    
    $popin = null;
    $buttons = html_writer::start_div('buttons-action');
    foreach($l_links_type as $link_type){
        if($link_type == 'createparcoursfromgabarit'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_createparcoursfromgabarit">'.get_string('course_management_link_create_parcours', 'local_workflow').'</a></div>';
            $popin .= dialog_create_parcours_from_gabarit($courseid);

        }elseif($link_type == 'createsessionfromparcours'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_createsessionfromparcours">'.get_string('course_management_link_create_session', 'local_workflow').'</a></div>';
            $popin .= dialog_create_session_from_parcours($courseid);

        }elseif($link_type == 'createsessionfromparcours_disable'){
            $buttons .= '<div><span class="button disable" id="wf_link_createsessionfromparcours">'.get_string('course_management_link_create_session', 'local_workflow').'</span>'.
                $OUTPUT->help_icon('label_indexation', 'local_workflow').'</div>';

        }elseif($link_type == 'recreatesessionfromparcours'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_recreatesessionfromparcours">'.get_string('course_management_link_recreate_session', 'local_workflow').'</a></div>';
            $popin .= dialog_recreate_session_from_parcours($courseid);

        }elseif($link_type == 'archive'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_archive">'.get_string('course_management_link_close_session', 'local_workflow').'</a></div>';
            $popin .= dialog_archive($courseid);

        }elseif($link_type == 'duplicate'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_duplicate">'.get_string('course_management_link_duplicate_parcours', 'local_workflow').'</a></div>';
            $popin .= dialog_duplicate($courseid);
            
        }elseif(isOptimizerAvailable() && $link_type == 'optimize'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_optimize">'.get_string('course_management_link_optimize_course', 'local_workflow').'</a></div>';
            $popin .= dialog_optimize($courseid);
            
        }elseif($link_type == 'unarchive'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_unarchive">'.get_string('course_management_link_reopen_session', 'local_workflow').'</a></div>';
            $popin .= dialog_unarchive($courseid);

        }elseif($link_type == 'discard'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_discard"><i class="fa fa-trash" aria-hidden="true"></i> '.get_string('course_management_link_discard', 'local_workflow').'</a></div>';
            $popin .= dialog_discard($courseid);

        }elseif($link_type == 'restorefromtrash'){
            $buttons .= '<div><a href="#" class="button" id="wf_link_restorefromtrash">'.get_string('course_management_link_restore_parcours', 'local_workflow').'</a></div>';
            $popin .= dialog_restore_from_trash($courseid);
        }
    }
    $buttons .= html_writer::end_div();
    //$buttons .= html_writer::div('', 'clear');

    return $buttons.$popin;
}

/**
 * Fonction qui retourne l'arborescence des sous categories selon le contexte de l'action qui est par la suite integree
 * dans l'element select des popin d'action.
 * @param $link_type
 * @param string $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function wf_subcategory_select_content($link_type, $courseid = ''){
    global $DB, $PAGE;
    $trash_category = '';
    $subcategory_tree = '';
    $actual_category = '';
    if($link_type == 'createparcoursfromgabarit'){
        $main_category = $DB->get_record('course_categories' , array('name' => WKF_CAT_PDF));
        $subcategory_tree = wf_destination_subcategory_tree($main_category->id);
    }elseif($link_type == 'createsessionfromparcours'){
        $main_category = $DB->get_record('course_categories' , array('name' => WKF_CAT_SDF));
        $subcategory_tree = wf_destination_subcategory_tree($main_category->id);
    }elseif($link_type == 'archive'){
        $main_category = $DB->get_record('course_categories' , array('name' => WKF_CAT_ARC));
        $subcategory_tree = wf_destination_subcategory_tree($main_category->id);
    }elseif($link_type == 'duplicate'){
        $categoryid = $DB->get_field('course','category', array('id'=> $courseid));
        $main_category = $DB->get_record('course_categories', array('id'=> $categoryid));
        $actual_category = $main_category->name;
        $subcategory_tree = wf_destination_subcategory_tree($main_category->id);
    }elseif($link_type == 'unarchive'){
        $main_category = $DB->get_record('course_categories' , array('name' => WKF_CAT_SDF));
        $subcategory_tree = wf_destination_subcategory_tree($main_category->id);
    }elseif($link_type == 'restorefromtrash'){
        $trash_category = $DB->get_record('course_trash_category', array('course_id'=>$PAGE->course->id));
        $actual_category = $trash_category->category_id;
        $subcategory_tree = wf_destination_subcategory_tree(0,true);
    }

    $content = '';
    if ($link_type != 'restorefromtrash')
    {
        $content .= '<option value="'.$main_category->id.'">'.get_string('nosubcategory', 'local_workflow').'</option>';
    }
    if($subcategory_tree){
        $content .= wf_select_content_build($subcategory_tree, $actual_category);
    }
    return $content;
}

/**
 * Fonction genere l'arborescence des sous categories selon la categorie dans laquelle se situe le parcours.
 * @param $main_category_id
 * @param bool $is_root
 * @return array|string
 * @throws dml_exception
 */
function wf_destination_subcategory_tree($main_category_id, $is_root = false){
    global $DB;
    $subcategory_tree = [];

    $offset = ($is_root)?1:0;

    $l_subcategories = $DB->get_records_sql('SELECT * FROM {course_categories} WHERE parent = ? ORDER BY name',array($main_category_id));
    foreach($l_subcategories as $subcategory){
        if ($subcategory->name != WKF_CAT_ARC && $subcategory->name != WKF_CAT_TRASH)
        {
            $subcategory_tree[] = array('id' => $subcategory->id , 'name' => $subcategory->name, 'depth' => ($subcategory->depth+$offset));

            $children = wf_destination_subcategory_tree($subcategory->id, $is_root);
            if(!empty($children)){
                foreach($children as $child){
                    array_push($subcategory_tree,$child);
                }
            }
            $children=null;
        }
    }
    return $subcategory_tree;
}

/**
 * Fonction qui genere la liste des sous categorie sous forme HTML (balise option).
 * @param $subcategory_tree
 * @param string $selected_value
 * @return string
 */
function wf_select_content_build($subcategory_tree, $selected_value = ''){
    $select_content = '';
    foreach($subcategory_tree as $subcategory){
        $select_content .= '<option value="'.$subcategory['id'].'" '.($selected_value==$subcategory['id']?' "':'').'>';

        for ($i = 2; $i < $subcategory['depth']; $i++) {
            $select_content .= '&nbsp&nbsp';
        }
        $select_content .= '► '.$subcategory['name'].'</option>';
    }
    return $select_content;
}

/**
 * Fonction qui genere le contenu HTML correspondant aux liens d'action presents dans le formulaire de workflow.
 * @param $course_main_category
 * @param $courseid
 * @param $status
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function wf_secondary_generate_action_links($course_main_category, $courseid, $status){
    global $DB, $CFG, $USER, $OUTPUT;
    $l_action_links = null;
    if($courseid){
        $context = context_course::instance($courseid);
        if(($course_main_category==WKF_CAT_SDF && $status == WKF_IS_SESSION_PREPARATION)
        ||($course_main_category==WKF_CAT_SLAF && $status == WKF_IS_SESSION_PREPARATION)){
            if (has_capability('local/workflow:opensession', $context)){
                $l_action_links[] = 'open_session';
            }
            if (has_capability('local/workflow:openselfenrolement', $context)){
                $l_action_links[] = 'open_auto_inscription';
            }
        }elseif(($course_main_category==WKF_CAT_SDF && $status == WKF_IS_SESSION_COURS)
            || ($course_main_category==WKF_CAT_SLAF && $status == WKF_IS_SESSION_COURS)){
            if (has_capability('local/workflow:closecourse', $context)){
                $l_action_links[] = 'archive';
            }
            if (has_capability('local/workflow:courseopening', $context)){
                $l_action_links[] = 'annonce';
            }
            if (has_capability('local/workflow:confirmparticipation', $context)){
                $l_action_links[] = 'attester';
            }
        }elseif($course_main_category==WKF_CAT_ARC){
            if (has_capability('local/workflow:confirmparticipation', $context)){
                $l_action_links[] = 'attester';
            }
        }
    }
    $links = html_writer::start_div('secondary-action-links');
    if($l_action_links) {
        $lng = new stdClass();
        $lng->participant = WKF_ROLE_NAME_PARTICIPANT;
        $lng->formateur = WKF_ROLE_NAME_FORMATEUR;
        foreach ($l_action_links as $link_type) {
            if ($link_type == 'archive') {
                $links .= '<a href="#" class="secondary-link" id="wf_link_archive">'
                    . get_string('course_management_link_close_session', 'local_workflow', $lng)
                    . ' <i class="fa fa-external-link" aria-hidden="true"></i></a><hr>';

            } elseif ($link_type == 'open_session') {
                $ready_session = is_ready_for_session_en_cours($courseid);
                if ($ready_session == true) {
                    $links .= '<a href="#" class="secondary-link" id="wf_link_open_session">'
                        . get_string('course_management_link_open_session', 'local_workflow', $lng)
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></a><hr>';
                } else {
                    $links .= '<span class="secondary-link disable" id="wf_link_open_session">'
                        . get_string('course_management_link_open_session', 'local_workflow', $lng)
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                        . generate_help_icon_HTML('course_management_link_open_session', 'local_workflow')
                        . '<hr>';
                }
            } elseif ($link_type == 'open_auto_inscription') {
                $ready_session_auto_formation = is_ready_for_session_auto_formation_en_cours($courseid);
                if ($ready_session_auto_formation == true) {
                    $links .= '<a href="#" class="secondary-link" id="wf_link_open_auto_inscription">'
                        . get_string('course_management_link_open_session_auto_formation', 'local_workflow', $lng)
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></a><hr>';
                } else {
                    $links .= '<span class="secondary-link disable" id="wf_link_open_auto_inscription">'
                        . get_string('course_management_link_open_session_auto_formation', 'local_workflow', $lng)
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                        . generate_help_icon_HTML('course_management_link_open_session_auto_formation', 'local_workflow')
                        . '<hr>';
                }
            } elseif ($link_type == 'annonce') {
                $newsforum = $DB->get_record('forum', array('course' => $courseid, 'type' => 'news'));
                if ($newsforum) {
                    $module_forum_id = $DB->get_field('modules', 'id', array('name' => 'forum'));
                    $cm_news_id = $DB->get_field('course_modules', 'id'
                        , array('course' => $courseid, 'instance' => $newsforum->id, 'module' => $module_forum_id));
                    $url = $CFG->wwwroot . '/mod/forum/view.php?id=' . $cm_news_id;
                    $links .= '<a href="' . $url . '" class="secondary-link" id="wf_link_annonce">'
                        . get_string('course_management_link_announce_open_session', 'local_workflow', $lng)
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></a><hr>';
                } else {
                    $links .= '<span class="secondary-link disable" id="wf_link_annonce">'
                        . get_string('course_management_link_announce_open_session', 'local_workflow', $lng)
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                        . generate_help_icon_HTML('course_management_link_announce_open_session', 'local_workflow')
                        . '<hr>';
                }
            } elseif ($link_type == 'attester') {
                $is_enable_attest = is_ready_for_attest_participant($courseid);
                if ($is_enable_attest) {
                    $instance = 'instanceid=' . $is_enable_attest->id;
                    if ($is_enable_attest->block == 'progress'){
                        $instance = 'id=' . $is_enable_attest->id;
                    }
                    $url = $CFG->wwwroot . '/blocks/'.$is_enable_attest->block.'/overview.php?' . $instance
                        . '&courseid=' . $courseid . '&sesskey=' . $USER->sesskey;
                    $links .= '<a href="' . $url . '" class="secondary-link" id="wf_link_attester">'
                        . get_string('course_management_link_attest_participation', 'local_workflow')
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></a><hr>';
                } else {
                    $links .= '<span class="secondary-link disable" id="wf_link_attester">'
                        . get_string('course_management_link_attest_participation', 'local_workflow')
                        . ' <i class="fa fa-external-link" aria-hidden="true"></i></span>'
                        . generate_help_icon_HTML('course_management_link_attest_participation', 'local_workflow')
                        . '<hr>';
                }
            }
        }
    }
    $links .= html_writer::end_div();

    return $links;
}

/**
 * Fonction qui genere les popins correspondant aux liens d'action presents dans le formulaire de workflow.
 * @param $course_main_category
 * @param $courseid
 * @param $status
 * @return string
 * @throws dml_exception
 * @throws moodle_exception
 */
function wf_secondary_generate_action_dialogs($course_main_category, $courseid, $status){
    $l_action_links = null;
    if($courseid){
        if(($course_main_category==WKF_CAT_SDF && $status == WKF_IS_SESSION_PREPARATION)
        || ($course_main_category==WKF_CAT_SLAF && $status == WKF_IS_SESSION_PREPARATION)){
            $l_action_links[] = 'open_session';
            $l_action_links[] = 'open_auto_inscription';

        }elseif(($course_main_category==WKF_CAT_SDF && $status == WKF_IS_SESSION_COURS)
        || ($course_main_category==WKF_CAT_SLAF && $status == WKF_IS_SESSION_COURS)){
            $l_action_links[] = 'archive';
            $l_action_links[] = 'annonce';
            $l_action_links[] = 'attester';
        }elseif($course_main_category==WKF_CAT_ARC){
            $l_action_links[] = 'attester';
        }
    }

    $popin = html_writer::start_div('secondary-action-dialogs');

    foreach($l_action_links as $link_type){
        if($link_type == 'createsessionfromparcours'){
            $popin .= dialog_create_session_from_parcours($courseid);

        }elseif($link_type == 'archive'){
            $popin .= dialog_archive($courseid);

        }elseif($link_type == 'open_session'){
            $ready_session = is_ready_for_session_en_cours($courseid);
            if($ready_session == true){
                $popin .= dialog_open_session($courseid);
            }
        }elseif($link_type == 'open_auto_inscription'){
            $ready_session_auto_formation = is_ready_for_session_auto_formation_en_cours($courseid);
            if($ready_session_auto_formation == true){
                $popin .= dialog_open_auto_inscription($courseid);
            }
        }
    }
    $popin .= html_writer::end_div();

    return $popin;
}






/**
 * Fonction qui genere la popin permettant de creer un parcours en construction a partir d'un parcours gabarit.
 * @param $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_create_parcours_from_gabarit($courseid){
    $random = rand(0, 999999999);
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_createparcoursfromgabarit" style="display:none;">
    <div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
    <form  method="POST" action="'.$nexturl.'" name="restore_form" id="wf_createparcoursfromgabarit_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="createparcoursfromgabarit" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr align="left">
                <td><label>Nom : *</label></td>
                <td><input type="text" size="50%" name="new_course_name" id="new_course_name_'.$random.'" /></td>
            </tr>
            <tr >
                <td><label>Nom abr&eacute;g&eacute; : *</label></td>
                <td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname_'.$random.'" /></td>
            </tr>
            <tr id="tr_subcategory">
                <td><label>Sous-cat&eacute;gorie : </label></td>
                <td>
                    <select name="new_category_course" style="width:100%" id="new_category_course_'.$random.'" >
                        '.wf_subcategory_select_content("createparcoursfromgabarit").'
                    </select>
                </td>
            </tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant de creer une session en preparation a partir d'un parcours.
 * @param $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_create_session_from_parcours($courseid){
    global $COURSE;
    $random = rand(0, 999999999);
    $fullname = ($COURSE->fullname !== '' ? $COURSE->fullname : '');
    $shortname = ($COURSE->shortname !== '' ? $COURSE->shortname : '');
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_createsessionfromparcours" style="display:none;">
    <div style="font-size: 10px; color: #515151;">'.get_string("createsessiondesc", "local_workflow").'<br/><br/></div>
    <form  method="POST" action="'.$nexturl.'" name="restore_form" id="wf_createsessionfromWFParcoursForm">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="createsessionfromparcours" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
        <tr align="left">
                <td><label>'.get_string("createsession", "local_workflow").'</label></td>
                <td><label for="move_type_copy"><input type="radio" name="move_type" value="duplication" id="move_type_copy_'.$random.'" checked/>En copiant le parcours</label><br/>
                <label for="move_type_move"><input type="radio" name="move_type" value="move" id="move_type_move_'.$random.'"/>En d&eacute;pla&ccedil;ant le parcours</label></td>
            </tr>
            <tr align="left">
                <td><label>'.get_string("coursename", "local_workflow").'</label></td>
                <td><input type="text" size="50%" name="new_course_name" id="new_course_name_'.$random.'" value="'.$fullname.'"/></td>
            </tr>
            <tr >
                <td><label>'.get_string("courseshortname", "local_workflow").'</label></td>
                <td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname_'.$random.'" value="'.$shortname.'"/><br/>
                <em>'.get_string("courseshortnamehelp", "local_workflow").'</em></td>
            </tr>
            <tr id=\'tr_cat_popin_local_workflow\'>
                <td><label>'.get_string("subcategory", "local_workflow").'</label></td>
                <td>
                    <select name="new_category_course" style="width:100%" id="new_category_course_'.$random.'" >
                        '.wf_subcategory_select_content('createsessionfromparcours').'
                    </select>
                </td>
            </tr>
            <tr id="tr_datepicker_session">
                <td><label>'.get_string("startdatecourse", "local_workflow").'</label></td>
                <td><input size="50%" name="datepicker_session" id="datepicker_session_'.$random.'" /></td>
            </tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant de recreer une nouvelle session a partir d'une autre session.
 * @param $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_recreate_session_from_parcours($courseid){
    global $COURSE;
    $random = rand(0, 999999999);
    $fullname = ($COURSE->fullname !== '' ? $COURSE->fullname : '');
    $shortname = ($COURSE->shortname !== '' ? $COURSE->shortname : '');
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_recreatesessionfromparcours" style="display:none;">
    <div style="font-size: 10px; color: #515151;">'.get_string("createsessiondesc", "local_workflow").'<br/><br/></div>
    <form  method="POST" action="'.$nexturl.'" name="restore_form" id="wf_recreatesessionfromWFParcoursForm">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="recreatesessionfromparcours" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
        <tr align="left">
                <td><label>'.get_string("createsession", "local_workflow").'</label></td>
                <td><label for="move_type_copy"><input type="radio" name="move_type" value="duplication" id="move_type_copy_'.$random.'" checked/>En copiant le parcours</label><br/>
                <label for="move_type_move"><input type="radio" name="move_type" value="move" id="move_type_move_'.$random.'"/>En d&eacute;pla&ccedil;ant le parcours</label></td>
            </tr>
            <tr align="left">
                <td><label>'.get_string("coursename", "local_workflow").'</label></td>
                <td><input type="text" size="50%" name="new_course_name" id="new_course_name_'.$random.'" value="'.$fullname.'"/></td>
            </tr>
            <tr >
                <td><label>'.get_string("courseshortname", "local_workflow").'</label></td>
                <td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname_'.$random.'" value="'.$shortname.'"/><br/>
                <em>'.get_string("courseshortnamehelp", "local_workflow").'</em></td>
            </tr>
            <tr id=\'tr_cat_popin_local_workflow\'>
                <td><label>'.get_string("subcategory", "local_workflow").'</label></td>
                <td>
                    <select name="new_category_course" style="width:100%" id="new_category_course_'.$random.'" >
                        '.wf_subcategory_select_content('createsessionfromparcours').'
                    </select>
                </td>
            </tr>
            <tr id="tr_datepicker_session">
                <td><label>'.get_string("startdatecourse", "local_workflow").'</label></td>
                <td><input size="50%" name="datepicker_session" id="datepicker_session_'.$random.'" /></td>
            </tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant d'archiver une session.
 * @param $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_archive($courseid){
    global $COURSE,$DB,$OUTPUT;
    
    $nbParticipants = $DB->count_records_sql("SELECT COUNT(*) FROM {enrol} e INNER JOIN {user_enrolments} ue ON e.id = ue.enrolid AND e.courseid = :courseid LEFT JOIN {user} u ON ue.userid = u.id AND u.deleted = 0",array("courseid"=>$COURSE->id));
    if ($DB->get_manager()->table_exists('progress_complete'))
    {
        $nbComplete = $DB->count_records('progress_complete',array("courseid"=>$COURSE->id,"is_complete"=>1));
    }else{
        $nbComplete = 0;
    }
    
    $alert = "";
    if($nbParticipants > 0 && $nbComplete == 0){
        
        $alert = "<div class ='alert-close'><p><i class=\"fas fa-exclamation-triangle\"></i>    ".get_string('alert_close', 'local_workflow')."</p></br></div>";
    }
    
    $random = rand(0, 999999999);
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_archive" style="display:none;">
    '.$alert.'
    <form  method="POST" action="'.$nexturl.'" name="restore_form" id="wf_archive_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="archive" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr id=\'tr_cat_popin_local_workflow\'>
                <td><label>Sous-cat&eacute;gorie : </label></td>
                <td>
                    <select name="new_category_course" style="width:100%" id="new_category_course_'.$random.'" >
                        '.wf_subcategory_select_content('archive').'
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="radio" name="access" value="visible" checked>
                    Avec accès des participants
                </td>
            </tr>
            <tr>
                <td>
                    <input type="radio" name="access" value="hidden">
                    Sans accès des participants
                </td>
            </tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant de dupliquer une session.
 * @param $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_duplicate($courseid){
    $random = rand(0, 999999999);
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_duplicate" style="display:none;">
    <div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
    <form  method="POST" action="'.$nexturl.'" name="restore_form" id="wf_duplicate_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="duplicate" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr align="left">
                <td><label>Nom : *</label></td>
                <td><input type="text" size="50%" name="new_course_name" id="new_course_name_'.$random.'" /></td>
            </tr>
            <tr>
                <td><label>Nom abr&eacute;g&eacute; : *</label></td>
                <td><input type="text" size="50%" name="new_course_shortname" id="new_course_shortname_'.$random.'" /></td>
            </tr>
            <tr id=\'tr_cat_popin_local_workflow\'>
                <td><label>Sous-cat&eacute;gorie : </label></td>
                <td>
                    <select name="new_category_course" style="width:100%" id="new_category_course_'.$random.'" >
                        '.wf_subcategory_select_content('duplicate', $courseid).'
                    </select>
                </td>
            </tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant d'optimiser une session.
 * @param $courseid
 * @return string
 */
function dialog_optimize($courseid){
    if (!isOptimizerAvailable()){
        return '';
    }
    global $CFG;
    require_once($CFG->dirroot.'/local/magisterelib/CourseFilesOptimizer.php');
    
    return CourseFilesOptimizer::getDialogHtml();
}

/**
 * Fonction qui genere la popin permettant de reouvirir une session archivee.
 * @param $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_unarchive($courseid){
    $random = rand(0, 999999999);
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_unarchive" style="display:none;">
    <div style="font-size: 10px; color: #515151;">Merci de renseigner la sous-catégorie de session de formation afin de rouvrir ce parcours.<br/><br/></div>
    <form  method="POST" action="'.$nexturl.'" name="restore_form" id="wf_unarchive_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="unarchive" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr id="tr_cat_popin_local_workflow">
                <td><label>Sous-cat&eacute;gorie : </label></td>
                <td>
                    <select name="new_category_course" style="width:100%" id="new_category_course_'.$random.'" >
                        '.wf_subcategory_select_content('unarchive').'
                    </select>
                </td>
            </tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant de mettre a la corbeille une session.
 * @param $courseid
 * @return string
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_discard($courseid){
    global $DB;
    $random = rand(0, 999999999);
    $published = $DB->record_exists('course_published', array('courseid'=>$courseid));
    $text = "";
    if ($published) {
        $text = '<tr><td>Ce parcours est partagé sur l’offre de parcours / publier pour inscription, la mise à la corbeille induira la suppression de cette publication</td></tr>';
    }
    
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_discard" style="display:none;">
    <form  method="POST" action="'.$nexturl.'" name="discard_form" id="wf_discard_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="discard" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr><td>Le parcours va être déplacé dans la corbeille, celui-ci sera supprimé définitivement dans 6 mois.</td></tr>
            '.$text.'
            <tr><td>Souhaitez-vous continuer ?</td></tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant d'ouvrir une session.
 * @param $courseid
 * @return string
 * @throws moodle_exception
 */
function dialog_open_session($courseid){
    $random = rand(0, 999999999);
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_open_session" style="display:none;">
    <form  method="POST" action="'.$nexturl.'" name="open_session_form" id="wf_open_session_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="open_session" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr><td>Cette session est sur le point d\'être ouverte aux participants.</td></tr>
            <tr><td>Souhaitez-vous continuer ?</td></tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant d'ouvrir une session en tant que session en autoformation.
 * @param $courseid
 * @return string
 * @throws moodle_exception
 */
function dialog_open_auto_inscription($courseid){
    $random = rand(0, 999999999);
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_open_auto_inscription" style="display:none;">
    <form  method="POST" action="'.$nexturl.'" name="open_auto_inscription_form" id="wf_open_auto_inscription_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="open_auto_inscription" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr><td>Cette session est sur le point d\'être ouverte en auto-inscription.</td></tr>
            <tr><td>Souhaitez-vous continuer ?</td></tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant de restaurer une session mise a la corbeille.
 * @param $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function dialog_restore_from_trash($courseid){
    $random = rand(0, 999999999);
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_restorefromtrash" style="display:none;">
    <div style="font-size: 10px; color: #515151;">Merci de renseigner les champs suivants afin de r&eacute;aliser la cr&eacute;ation du parcours<br/><br/></div>
    <form  method="POST" action="'.$nexturl.'" name="restorefromtrash_form" id="wf_restorefromtrash_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="restorefromtrash" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <table style="font-size: 12px; color: black;">
            <tr id=\'tr_cat_popin_local_workflow\'>
                <td><label>Sous-cat&eacute;gorie : </label></td>
                <td>
                    <select name="new_category_course" style="width:100%" id="new_category_course_'.$random.'" >
                        '.wf_subcategory_select_content('restorefromtrash').'
                    </select>
                </td>
            </tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant de publier un parcours ou une session sur l'offre de parcours ou de formation.
 * @param $courseid
 * @param string $method
 * @return string
 * @throws moodle_exception
 */
function dialog_publish($courseid, $method="share", $isalocalsession = 0){
    $random = rand(0, 999999999);
    $text = "Vous êtes sur le point de publier / mettre à jour ce parcours sur l'offre de parcours.";
    if($method == "publish"){
        if($isalocalsession){
            $text = "Vous êtes sur le point de publier / mettre à jour cette session sur l'offre de formation locale.";
        } else {
            $text = "Vous êtes sur le point de publier / mettre à jour cette session sur l'offre de formation.";
        }
    }
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_publish" style="display:none;">
    <form  method="POST" action="'.$nexturl.'" name="publish_form" id="wf_publish_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="publish" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <input type="hidden"  id="method_'.$random.'" name="method" value="'.$method.'" />
        <input type="hidden"  id="is_local_session_'.$random.'" name="is_local_session" value="'.$isalocalsession.'" />
        <table style="font-size: 12px; color: black;">
            <tr><td>'.$text.'</td></tr>
            <tr><td>Souhaitez-vous continuer ?</td></tr>
        </table>
    </form>
</div>';
}

/**
 * Fonction qui genere la popin permettant de depublier un parcours ou un session de l'offre de parcours ou de formation.
 * @param $courseid
 * @param string $method
 * @return string
 * @throws moodle_exception
 */
function dialog_unpublish($courseid, $method="share"){
    $random = rand(0, 999999999);
    $text = "Vous êtes sur le point de retirer ce parcours de l'offre de parcours.";
    if($method == "publish"){
        $text = "Vous êtes sur le point de retirer ce parcours de l'offre de formation.";
    }
    $nexturl = new moodle_url("/local/workflow/ajax/WFApi.php");
    
    return '
<div id="wf_dialog_unpublish" style="display:none;">
    <form  method="POST" action="'.$nexturl.'" name="unpublish_form" id="wf_unpublish_form">
        <input type="hidden"  id="link_type_'.$random.'" name="link_type" value="unpublish" />
        <input type="hidden"  id="course_id_'.$random.'" name="course_id" value="'.$courseid.'" />
        <input type="hidden"  id="method_'.$random.'" name="method" value="'.$method.'" />
        <table style="font-size: 12px; color: black;">
            <tr><td>'.$text.'</td></tr>
            <tr><td>Souhaitez-vous continuer ?</td></tr>
        </table>
    </form>
</div>';
}


/**
 * Fonction qui genere la popin de preview des mails d'inscription des utilisateurs.
 * @return string
 */
function dialog_preview_mails() {
    return '
    <div id="wf_dialog_preview_mails" style="display:none">
    </div>
';
}

