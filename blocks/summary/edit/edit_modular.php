<?php

require_once($CFG->dirroot.'/course/format/modular/format_modular_helper.php');

$context = context_course::instance($courseid);

// Set up page parameters
$PAGE->set_course($course);
$PAGE->set_url('/blocks/summary/edit.php', array('id' => $courseid));
$PAGE->set_context($context);
$title = get_string('edit', 'block_summary');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/blocks/summary/custom_nestable.css'));
$PAGE->requires->css(new moodle_url('/blocks/summary/modular.css'));

$PAGE->requires->js_call_amd('block_summary/modular', 'init');
$PAGE->requires->jquery_plugin('ui-css');

// Check user is logged in and capable of grading
require_login($course, false);
require_capability('block/summary:managepages', $context);

$submit = optional_param('isSubmited', false, PARAM_BOOL);
$treedata = optional_param('treedata', false, PARAM_RAW);



if ($submit != false && $treedata != false && ($data = json_decode($treedata)) != false)
{
    process_section_modular($courseid, $data);

    rebuild_course_cache($courseid);
}

// Start page output
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_summary');

$delbutton = "<button class='del fa fa-times'></button>";
$hidebutton = "<button class='hide fa fa-eye'></button>";
$dragbutton = "<button class='move fa fa-arrows'></button>";
$editbutton = "<button class='edit fa fa-pencil'></button>";
$buttonspacer = "<span class='dd-buttonsblockspacer'></span>";
$buttons = $buttonspacer.$editbutton.$buttonspacer.$hidebutton.$buttonspacer.$delbutton;

$modular = new format_modular_helper($courseid);

$tree = $modular->get_all_sections_tree();

$binexpiry = get_config('tool_recyclebin', 'coursebinexpiry') / 86400;

echo build_html_tree_modular($tree);

echo '
<form id="form" action="" method="POST">
<button id="addsection"><i class="fa fa-plus" aria-hidden="true"></i> Ajouter une section</button>
<input type="hidden" id="treedata" name="treedata" />
<input type="hidden" id="isSubmited" name="isSubmited" />
<input type="submit" id="save" name="save" value="Enregistrer les modifications"/>
</form>



<div id="dialog-confirm" title="Confirmation de la suppression de la section" style="display:none">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Attention : La section ainsi que son contenu seront supprimés lors de la validation du formulaire !<br/>Cette modification est irréversible !</p>
</div>
<div id="dialog-confirm-save" title="Confirmation de la sauvegarde de modifications" style="display:none">
  <p>
    <span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>
    Attention : Les sections suivantes vont être supprimées :
    <ol id="dialog-confirm-save-list">
    
    </ol>
    <br/>
    Il n\'existe pas de corbeille de sections, ces dernières seront donc supprimées définitivement. Les activités contenues dans ces sections seront stockées '.$binexpiry.' jours dans la corbeille du parcours.
  </p>
</div>';
echo '<div id="main_form_div">' . $OUTPUT->container_end() . '</div>';

echo $OUTPUT->footer();

echo '<script>
var dd_buttons = "'.$buttons.'";
var dd_drag_button = "'.$dragbutton.'";
</script>';

function process_section_modular($courseid, $data)
{
    global $DB;

    $formatmodular = course_get_format($courseid);
    $mapids = array();
    $numsec = 1;

    // first step : delete all old sections
    $sectionstodelete = $DB->get_records_sql('SELECT cs.id, cs.section
FROM {course_sections} cs
WHERE cs.course=? AND cs.section > 0 
AND cs.id <> '.implode(' AND cs.id <> ', $data->ids).'
ORDER BY cs.section DESC', array($courseid));

    foreach($sectionstodelete as $s){
        course_delete_section($courseid, $s->section);
    }

    // second step : update sections or create new sections
    foreach($data->nodes as $section){
        $section->section = $numsec++;
        $section->course = $courseid;

        if($section->parentid != null){
            $section->parentid = $mapids[$section->parentid];
        }

        $mapids[$section->id] = $section->section;

        if($section->id > 0){
            $formatmodular->update_section($section);
            continue;
        }

        $formatmodular->create_section($section);
    }
}
