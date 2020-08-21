<?php


$context = context_course::instance($courseid);


$summary = $DB->get_records('block_summary',array('courseid'=>$courseid));


// Set up page parameters
$PAGE->set_course($course);
//$PAGE->requires->css('/blocks/summary/edit.php');
$PAGE->set_url('/blocks/summary/edit.php', array('id' => $courseid));
$PAGE->set_context($context);
$title = get_string('edit', 'block_summary');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/blocks/summary/custom_nestable.css'));

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
//$PAGE->requires->jquery_plugin('nestable');
//$PAGE->requires->jquery_plugin('tooltipster');
//$PAGE->requires->jquery_plugin('tooltipster-css');

// Check user is logged in and capable of grading
require_login($course, false);
require_capability('block/summary:managepages', $context);




$newsectionstartid = 1000000000;

$submit = optional_param('isSubmited', null, PARAM_ALPHA);
$treedata = optional_param('treedata', null, PARAM_RAW);

$binexpiry = get_config('tool_recyclebin', 'coursebinexpiry') / 86400;

if ($submit != false && $treedata != false && strlen($treedata) > 5)
{
    $data = json_decode($treedata);

    $sectionids = array();

    foreach($data as $section)
    {
        $sectionids[] = $section->id;

        if (isset($section->children) && count($section->children) > 0)
        {
            foreach($section->children AS $child)
            {
                $sectionids[] = $child->id;
            }
        }
    }

    // Delete all removed section
    $sections_delete = $DB->get_records('course_sections', array('course'=>$courseid), 'section DESC');

    foreach($sections_delete AS $section)
    {
        if (!in_array($section->id,$sectionids) && $section->section> 0)
        {
            course_delete_section($courseid,$section->section);
        }
    }

    // Purge old block_summary records
    $block_summary_delete = $DB->get_records('block_summary', array('courseid'=>$courseid));

    foreach($block_summary_delete AS $summary)
    {
        if (!in_array($summary->sectionid,$sectionids))
        {
            $DB->delete_records('block_summary',array('id'=>$summary->id));
        }
    }


    $weight = 1;
    $parent = null;
    foreach($data as $section)
    {
        $sec = $DB->get_record('block_summary', array('sectionid'=>$section->id));

        // N'existe pas dans summary
        if ($sec === false)
        {

            // Nouvelle section?
            if ($section->id >= $newsectionstartid)
            {
                // Avoid duplicate key conflict
                $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                if ($oldsec !== false)
                {
                    $oldsec->section = $oldsec->section+10000;
                    $DB->update_record('course_sections', $oldsec);
                }

                $course_section = new stdClass();
                $course_section->course = $courseid;
                if (isset($section->name) && strlen(trim($section->name)) > 1)
                {
                    $course_section->name = trim($section->name);
                }
                else
                {
                    $course_section->name = 'Nouvelle page '.$weight;
                }

                $course_section->summaryformat = 1;
                $course_section->section = $weight;
                $course_section->visible = ($section->hidden?0:1);

                $section->id = $DB->insert_record('course_sections', $course_section, true);

            }
            else{
                $course_section = $DB->get_record('course_sections', array('id'=>$section->id));

                // La section n'existe pas?
                if ($course_section === false)
                {
                    continue;
                }

                $course_section_updated = false;

                if ($course_section->visible == $section->hidden)
                {
                    $course_section->visible = ($section->hidden?0:1);
                    $course_section_updated= true;
                }

                if ($course_section->section != $weight)
                {
                    // Avoid duplicate key conflict
                    $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                    if ($oldsec !== false)
                    {
                        $oldsec->section = $oldsec->section+10000;
                        $DB->update_record('course_sections', $oldsec);
                    }
                    $course_section->section = $weight;

                    $course_section_updated= true;
                }

                if (isset($section->name) && strlen(trim($section->name)) > 1)
                {
                    $course_section->name = trim($section->name);
                    $course_section_updated = true;
                }
                else if (strlen($course_section->name) < 1)
                {
                    $course_section->name = 'Nouvelle page '.$weight;
                    $course_section_updated= true;
                }

                if ($course_section_updated)
                {
                    $DB->update_record('course_sections', $course_section);
                }

            }


            $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
            if ($blocksummary_colision!== false)
            {
                $blocksummary_colision->weight= $blocksummary_colision->weight+10000;
                $DB->update_record('block_summary', $blocksummary_colision);
            }

            $blocksummary = new stdClass();
            $blocksummary->courseid = $courseid;
            $blocksummary->sectionid = $section->id;
            $blocksummary->parentid = null;
            $blocksummary->weight = $weight;

            $DB->insert_record('block_summary', $blocksummary);

        }else{

            $course_section = $DB->get_record('course_sections', array('id'=>$section->id));

            if ($course_section === false)
            {
                continue;
            }

            $course_section_updated = false;

            if ($course_section->visible == $section->hidden)
            {
                $course_section->visible = ($section->hidden?0:1);
                $course_section_updated= true;
            }

            if ($course_section->section != $weight)
            {
                // Avoid duplicate key conflict
                $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                if ($oldsec !== false)
                {
                    $oldsec->section = $oldsec->section+10000;
                    $DB->update_record('course_sections', $oldsec);
                }
                $course_section->section = $weight;
                $course_section_updated= true;
            }

            if (isset($section->name) && strlen(trim($section->name)) > 1)
            {
                $course_section->name = trim($section->name);
                $course_section_updated = true;
            }
            else if (strlen($course_section->name) < 1)
            {
                $course_section->name = 'Nouvelle page '.$weight;
                $course_section_updated = true;
            }

            if ($course_section_updated)
            {
                $DB->update_record('course_sections', $course_section);
            }

            $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
            if ($blocksummary_colision !== false)
            {
                $blocksummary_colision->weight= $blocksummary_colision->weight+10000;
                $DB->update_record('block_summary', $blocksummary_colision);
            }

            $sec->parentid = null;
            $sec->weight = $weight;

            $DB->update_record('block_summary', $sec);

        }




        // children
        if (isset($section->children) && count($section->children) > 0)
        {

            foreach($section->children AS $child)
            {

                $weight = $weight + 1;


                $sec2 = $DB->get_record('block_summary', array('sectionid'=>$child->id));

                // N'existe pas dans summary
                if ($sec2 === false)
                {

                    // Nouvelle section?
                    if ($child->id >= $newsectionstartid)
                    {
                        // Avoid duplicate key conflict
                        $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                        if ($oldsec !== false)
                        {
                            $oldsec->section = $oldsec->section+10000;
                            $DB->update_record('course_sections', $oldsec);
                        }

                        $course_section = new stdClass();
                        $course_section->course = $courseid;
                        if (isset($child->name) && strlen(trim($child->name)) > 1)
                        {
                            $course_section->name = trim($child->name);
                        }
                        else
                        {
                            $course_section->name = 'Nouvelle page '.$weight;
                        }
                        $course_section->section = $weight;
                        $course_section->visible = ($child->hidden?0:1);

                        $child->id = $DB->insert_record('course_sections', $course_section, true);
                    }
                    else{
                        $course_section = $DB->get_record('course_sections', array('id'=>$child->id));

                        // La section n'existe pas?
                        if ($course_section === false)
                        {
                            continue;
                        }

                        $course_section_updated = false;

                        if ($course_section->visible == $child->hidden)
                        {
                            $course_section->visible = ($child->hidden?0:1);
                            $course_section_updated= true;
                        }

                        if ($course_section->section != $weight)
                        {
                            // Avoid duplicate key conflict
                            $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                            if ($oldsec !== false)
                            {
                                $oldsec->section = $oldsec->section+10000;
                                $DB->update_record('course_sections', $oldsec);
                            }
                            $course_section->section = $weight;
                            $course_section_updated = true;
                        }

                        if (isset($child->name) && strlen(trim($child->name)) > 1)
                        {
                            $course_section->name = trim($child->name);
                            $course_section_updated = true;
                        }
                        else if (strlen($course_section->name) < 1)
                        {
                            $course_section->name = 'Nouvelle page '.$weight;
                            $course_section_updated = true;
                        }

                        if ($course_section_updated)
                        {
                            $DB->update_record('course_sections', $course_section);
                        }

                    }

                    if ($course_section->visible == $child->hidden)
                    {
                        $course_section->visible = ($child->hidden?0:1);
                        $DB->update_record('course_sections', $course_section);
                    }

                    $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
                    if ($blocksummary_colision !== false)
                    {
                        $blocksummary_colision->weight= $blocksummary_colision->weight+10000;
                        $DB->update_record('block_summary', $blocksummary_colision);
                    }

                    $blocksummary = new stdClass();
                    $blocksummary->courseid = $courseid;
                    $blocksummary->sectionid = $child->id;
                    $blocksummary->parentid = $section->id;
                    $blocksummary->weight = $weight;

                    $DB->insert_record('block_summary', $blocksummary);

                }else{

                    $course_section = $DB->get_record('course_sections', array('id'=>$child->id));

                    if ($course_section === false)
                    {
                        continue;
                    }

                    $course_section_updated = false;

                    if ($course_section->visible == $child->hidden)
                    {
                        $course_section->visible = ($child->hidden?0:1);
                        $course_section_updated= true;
                    }

                    if ($course_section->section != $weight)
                    {
                        // Avoid duplicate key conflict
                        $oldsec = $DB->get_record('course_sections', array('course'=>$courseid,'section'=>$weight));
                        if ($oldsec !== false)
                        {
                            $oldsec->section = $oldsec->section+10000;
                            $DB->update_record('course_sections', $oldsec);
                        }
                        $course_section->section = $weight;
                        $course_section_updated= true;
                    }

                    if (isset($child->name) && strlen(trim($child->name)) > 1)
                    {
                        $course_section->name = trim($child->name);
                        $course_section_updated = true;
                    }
                    else if (strlen($course_section->name) < 1)
                    {
                        $course_section->name = 'Nouvelle page '.$weight;
                        $course_section_updated= true;
                    }

                    if ($course_section_updated)
                    {
                        $DB->update_record('course_sections', $course_section);
                    }

                    $blocksummary_colision = $DB->get_record('block_summary', array('courseid'=>$courseid,'weight'=>$weight));
                    if ($blocksummary_colision !== false)
                    {
                        $blocksummary_colision->weight = -$blocksummary_colision->weight;
                        $DB->update_record('block_summary', $blocksummary_colision);
                    }

                    $sec2->parentid = $section->id;
                    $sec2->weight = $weight;

                    $DB->update_record('block_summary', $sec2);

                }

            }

        }

        $weight = $weight + 1;
    }

    // Delete all remaining sections to avoid any 10.000 section course
    $sections_delete = $DB->get_records_sql('SELECT * FROM {course_sections} WHERE course='.$courseid.' AND section > 10000 ORDER BY section DESC');
    foreach($sections_delete AS $section)
    {
        course_delete_section($courseid,$section->section);
    }



    // Update course format option with the new number of session
    update_course((object)array('id' => $courseid,'numsections' => $weight-1));

    rebuild_course_cache($courseid);
}






// Get specific block config
//$block = $DB->get_record('block_instances', array('id' => $id));
//$config = unserialize(base64_decode($block->configdata));

// Start page output
echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2);
echo $OUTPUT->container_start('block_summary');

$delbutton = '<button class="del fa fa-times"></button>';
$hidebutton = '<button class="hide fa fa-eye"></button>';
$dragbutton = '<button class="move fa fa-arrows"></button>';
$editbutton = '<button class="edit fa fa-pencil"></button>';
$buttonspacer = '<span class="dd-buttonsblockspacer"></span>';
$buttons = $buttonspacer.$editbutton.$buttonspacer.$hidebutton.$buttonspacer.$delbutton;
$coursenextweight = get_course_next_weight($courseid)+1;

$PAGE->requires->js_call_amd('block_summary/thematique', 'init', array($newsectionstartid, $coursenextweight, $dragbutton, $buttonspacer, $buttons));

echo get_course_tree_html($courseid);
/*
echo '
<div id="dd" class="dd">
    <ol class="dd-list">
        <li class="dd-item" data-id="1">
            <div class="dd-handle">Page 1</div>'.$buttons.'
        </li>
        <li class="dd-item" data-id="2">
            <div class="dd-handle">Page 2</div>'.$buttons.'
        </li>
        <li class="dd-item" data-id="3">
            <div class="dd-handle">Page 3</div>'.$buttons.'
            <ol class="dd-list">
                <li class="dd-item" data-id="4">
                    <div class="dd-handle">Page 4</div>'.$buttons.'
                </li>
                <li class="dd-item" data-id="5">
                    <div class="dd-handle">Page 5</div>'.$buttons.'
                </li>

            </ol>
        </li>
		<li class="dd-item" data-id="6">
            <div class="dd-handle">Page 6</div>'.$buttons.'
        </li>
    </ol>
</div>';
*/
echo '
<form id="form" action="" method="POST">
<button id="addsection"><i class="fa fa-plus" aria-hidden="true"></i> Ajouter une section</button>
<input type="hidden" id="treedata" name="treedata" />
<input type="hidden" id="isSubmited" name="isSubmited" />
<input type="submit" id="save" name="save" value="Enregistrer les modifications" />
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
</div>
<script type="text/javascript">

</script>';

echo '<div id="main_form_div">' . $OUTPUT->container_end() . '</div>';


echo $OUTPUT->footer();
