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
 * Blog Menu Block page.
 *
 * @package    block
 * @subpackage blog_menu
 * @copyright  2009 Nicolas Connault
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/summary/lib.php');
/**
 * The blog menu block class
 */
class block_summary extends block_base {
	
	private $canseehiddensection = null;

	private $modularmenu = null;

	private $modularhelper = null;

	private $modularcurrentsection = null;

	private $modularparentsection = null;

    function init() {
        global $COURSE, $PAGE,$CFG;

        $this->title = get_string('pluginname', 'block_summary');

        if($COURSE->format == 'modular'){
            require_once($CFG->dirroot.'/course/format/modular/format_modular_helper.php');

            if ($PAGE->cm != null)
            {
                $search = array('cmid' => $PAGE->cm->id);
            } else {
                $sectionnum = optional_param('section', 1, PARAM_INT);
                $search = array('numsection' => $sectionnum);
            }

            $this->modularhelper = new format_modular_helper($COURSE->id);
            
            $section = $this->modularhelper->get_sections($search);

            if(!$section){
                $this->modularmenu = $this->modularhelper->get_all_sections_tree();
                $this->title = get_string('summarytitle', 'block_summary');
                return;
            }

            $section = reset($section);
            $this->modularmenu = $this->modularhelper->get_all_sections_tree($section->numsection);


            $this->modularcurrentsection = $this->modularmenu['nodes'][$section->numsection];

            if($this->modularcurrentsection->type != format_modular::$MODULE_TYPE){
                $this->title = get_string('summarytitle', 'block_summary');
            }else{
                $this->modularparentsection = $this->modularhelper->get_last_parent_section($this->modularcurrentsection);
                $this->title = get_string('modulesummarytitle', 'block_summary');
            }

        }
    }

    function instance_allow_multiple() {
        return false;
    }
    
    function instance_can_be_hidden(){
    	return false;
    }
    
    function applicable_formats() {
        return array('all' => true, 'my' => false, 'tag' => false);
    }

    function html_attributes() {
        global $COURSE;

        $attributes = parent::html_attributes();

        if($COURSE->format != 'modular'){
            return $attributes;
        }

        $output = $this->page->get_renderer('block_summary');

        $attributes['class'] .= ' '.$output->get_module_color($this->modularcurrentsection);

        return $attributes;
    }

    function instance_create()
    {
    	global $DB, $COURSE;
    	
		$sqlparams = array('blockname' => 'summary', 'parentcontextid' => context_course::instance($COURSE->id)->id);
		
   		$block_instance_record = $DB->get_record('block_instances', $sqlparams);
   		
   		if($block_instance_record != null){
   			$do = new stdclass();
   			$do->defaultregion = BLOCK_POS_LEFT;
   			$do->defaultweight = $this->get_last_weight() -1;
   			$do->pagetypepattern = '*';
   			$do->subpagepattern = null;
   			$do->showinsubcontexts = 1;
   			$do->id = $block_instance_record->id;
   			 
   			$DB->update_record('block_instances', $do);
   			
   			$this->instance = $DB->get_record('block_instances', $sqlparams);
   		}
   		
   		return true;
    }
    
    function instance_delete()
    {
    	global $DB;
    	parent::instance_delete();
    	
    	list($context, $course, $cm) = get_context_info_array($this->context->id);
    	
    	if($course != null){
            $DB->delete_records('block_summary', array('courseid' => $course->id));
    		$DB->delete_records('block_summary_cache', array('courseid' => $course->id));
    	}
    }


    function get_content() {
        global $PAGE, $DB, $COURSE;
        
        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('ui');
        
        
        list($context, $course, $cm) = get_context_info_array($this->context->id);
        
        $this->canseehiddensection = has_capability('block/summary:canseehiddensections', $context);

        
        $this->content = new stdClass();
        $this->content->text = '';

        if($COURSE->format == 'modular'){

            if($this->modularparentsection){
                $parenturl = new moodle_url('/course/view.php', array('id' => $COURSE->id, 'section' => $this->modularparentsection->numsection));

                $subheader = html_writer::link($parenturl, $this->modularparentsection->name);
                if($this->modularcurrentsection->id == $this->modularparentsection->id){
                    $subheader = '<b>'.$subheader.'</b>';
                }
                $this->content->text .= '<div class="moduletitle">'.$subheader.'</div>';
            }
            
            $this->content->text .= $this->generate_content_for_format_modular();

            if (has_capability('block/summary:managepages', $context))
            {
                $a = html_writer::tag('a', '<i class="fa fa-pencil"></i>&Eacute;dition',array('href'=>new moodle_url('/blocks/summary/edit.php',array('id'=>$COURSE->id))));
                $li = html_writer::tag('li', $a);
                $this->content->text .= html_writer::tag('ul', $li,array('class'=>'editbutton'));
            }

            $this->content->text .= $this->get_js();
        }else if ($COURSE->format == 'topics' || $COURSE->format == 'magistere_topics' )
        {
        	$this->generate_content_for_other_format($context);
        }
        else{
        	$this->content = new stdClass();
        	$this->content->text = html_writer::tag('p', get_string('warning_message', 'block_summary'));
        } 
        
        return $this->content;
    }

    private function generate_content_for_other_format($context){
        global $DB, $COURSE;

        if ($this->page->cm != null)
        {
            $section = $DB->get_record_sql('SELECT * FROM {course_sections} WHERE course = "'.$COURSE->id.'" AND sequence LIKE "%'.$this->page->cm->id.'%"');

            $currentsectionid= $section->section;
        }
        else{
            $currentsectionid = optional_param('section', 0, PARAM_INT);
        }


        $tree = get_course_tree($COURSE->id);


        foreach($tree as $key=>$section)
        {
            if ($section->uservisible == 0 && !$section->availableinfo && !$this->canseehiddensection)
            {
                unset($tree[$key]);
                continue;
            }
            $tree[$key]->page_link = new moodle_url('/course/view.php', array('id' => $section->courseid, 'section' => $section->weight));
            $tree[$key]->is_current = ($currentsectionid==$section->weight);

            if (strlen($tree[$key]->name) < 1)
            {
                $tree[$key]->name = '(Section '.$section->weight.')';
            }

            $tree[$key]->name .= $this->setVisible($section);
            $tree[$key]->name .= $this->setRestriction($section);

            if (isset($section->children) && count($section->children) > 0)
            {
                foreach($section->children as $key2=>$child)
                {
                    if ($child->uservisible == 0 && !$child->availableinfo && !$this->canseehiddensection)
                    {
                        unset($tree[$key]->children[$key2]);
                        continue;
                    }
                    $tree[$key]->children[$key2]->page_link = new moodle_url('/course/view.php', array('id' => $child->courseid, 'section' => $child->weight));
                    $tree[$key]->children[$key2]->is_current = ($currentsectionid==$child->weight);

                    if (strlen($tree[$key]->children[$key2]->name) < 1)
                    {
                        $tree[$key]->children[$key2]->name = '(Section '.$child->weight.')';
                    }

                    $tree[$key]->children[$key2]->name .= $this->setVisible($child);
                    $tree[$key]->children[$key2]->name .= $this->setRestriction($child);

                }
            }



        }


        $this->content = new stdClass();

        $sum_structure_diplay = '';
        foreach($tree as $page){
            $sum_structure_diplay .= $this->display_page_structure($page);
        }

        $this->content->text = html_writer::tag('ul', $sum_structure_diplay);

        if (has_capability('block/summary:managepages', $context))
        {
            $a = html_writer::tag('a', '<i class="fa fa-pencil"></i>&Eacute;dition',array('href'=>new moodle_url('/blocks/summary/edit.php',array('id'=>$COURSE->id))));
            $li = html_writer::tag('li', $a);
            $this->content->text .= html_writer::tag('ul', $li,array('class'=>'editbutton'));
        }
        $this->content->text .= $this->get_js();
    }

    private function setActive($section){
        if ($section->visible == 0)
        {
            return '<span><i class="fas fa-circle"></i></span>';;
        }
    }
    private function setVisible($section){
        if ($section->visible == 0)
        {
            return '<span><i class="eyeicon ' . get_config('block_summary', 'slasheye') . '"></i></span>';;
        }
    }
    private function setRestriction($section){
        if($section->visible != 0 && $this->canseehiddensection){
            $availabilities_infos_json = get_fast_modinfo($section->courseid)->get_section_info($section->section)->availability;
            if(isset($availabilities_infos_json)){
                $availabilities_infos = json_decode($availabilities_infos_json);
                if(isset($availabilities_infos)){
                    if(count($availabilities_infos->c) > 0 ){
                        return '<span><i class="lockicon ' . get_config('block_summary', 'lock') . '"></i></span>';
                    }
                }
            }
        }
        return "";
    }

    private function isCompleted($node){
        global $DB;
        $module = $DB->get_record('modules',array('name'=>'completionmarker'));
        return $module && section_is_completed($node->sectionid);
    }


    private function setCompleted($is_completed,$node){
        if ($is_completed  && has_capability('block/summary:canseecompletion', $this->context))
        {
            return '<a class="check-icon-link" title="Vous avez achevé cette étape en cochant la case sur la page correspondante"><i class="fa fa-check"></i></a> ';
        }
        return "";
    }

    private function generate_content_for_format_modular(){
        global $COURSE;

        $output = $this->page->get_renderer('block_summary');

        if($this->modularcurrentsection->type != format_modular::$MODULE_TYPE){
            return $output->main_menu($this->modularmenu, $COURSE->id, $this->canseehiddensection);
        }

        $menu = $this->modularhelper->get_module_menu_tree($this->modularcurrentsection->numsection);

        $modulelist = $this->modularmenu[format_modular::$MODULE_TYPE];

        return $output->module_menu($menu, $modulelist, $this->canseehiddensection);
    }

    private function display_page_structure($node){
    	global $DB, $CFG, $OUTPUT,$COURSE;

    	$is_section_completed = $this->isCompleted($node);

    	$children_content = '';
    	$children_is_current = false;
    	
    	if (isset($node->children))
    	{
	    	foreach($node->children as $child){
	    		$children_is_current |= $child->is_current;
	    		$children_content .= $this->display_node($child);
	    	}
    	}

   		$display_children = (($node->is_current || $children_is_current) ? 'display: block' : 'display: none');
    	
    	$ch = '';
    	
    	if(!empty($children_content)){
    		$ch = html_writer::tag('ul', $children_content, array('style' => $display_children));
    	}

    	$class_link = $this->get_class_for_link($node);
    	 
    	$toggleSelector = '';
        $completed = "";
    	if($class_link != null){
            $icon = "<i class='plusicon " . get_config('block_summary', 'plus') . "'></i>";
    	    if($class_link['class'] == 'summary_arrow arrow_expand'){
                $icon = "<i class='minusicon " . get_config('block_summary', 'minus') . "'></i>";

            }
    		$toggleSelector = html_writer::tag('a', $icon, $class_link);
    	}


        $active_class = array();



        $currentsection = optional_param('section', 0, PARAM_INT);
        if($node->is_current || ($currentsection == 0 && $node->section == 1) ){
            $active_class[] = 'is_active';
        }else{
            $completed = $this->setCompleted($is_section_completed,$node);
        }
        if($is_section_completed && has_capability('block/summary:canseecompletion', $this->context)){
            $active_class[] = 'completed';
        }
        if(strpos($children_content, 'sub is_active') !== false) $active_class[] = 'child_is_active';
        if ($this->canseehiddensection && isset($node->visible) && $node->visible == 0) $active_class[] = 'hidden_section';




    	$link = html_writer::tag('a', $node->name.$completed, array('href' => $node->page_link->out(false) ));

    	$p = html_writer::tag('p', $link . $toggleSelector);



    	$name = html_writer::tag('li', $p . $ch,array('class'=> join(' ',$active_class) , 'onClick' => 'event.stopPropagation(); window.location = "' . $node->page_link->out(false) . '"'));
    	
    	return $name;
    }
    
    public function display_node($node){
        global $DB;
        $is_section_completed = $this->isCompleted($node);
    	$class_link = $this->get_class_for_link($node);
    	
    	$toggleSelector = '';
    	
    	if($class_link != null){
			$toggleSelector = html_writer::tag('a', '', $class_link);
    	}

    	
    	$link = html_writer::tag('a', $node->name, array('href' => $node->page_link->out(false)));

        $active_class = array('sub');
        $completed = "";
        if($node->is_current){
            $active_class[] = 'is_active';
        }else{
            $completed = $this->setCompleted($is_section_completed,$node);
        }

        if($is_section_completed && has_capability('block/summary:canseecompletion', $this->context)){
            $active_class[] = 'completed';
        }

        if ($this->canseehiddensection && isset($node->visible) && $node->visible == 0) $active_class[] = 'hidden_section';

    	$p = html_writer::tag('p', $toggleSelector . $link.$completed);

        return html_writer::tag('li', $p , array('class' => join(' ',$active_class), 'onClick' => 'event.stopPropagation(); window.location = "' . $node->page_link->out(false) . '"'));
    }
    
    public function get_class_for_link($node, $force_to_expand = false){
    	
    	if(empty($node->children)){
    		return null;
    	}
    	
    	$class = array('href' => 'javascript:void(0)', 'class' => 'summary_arrow arrow_expand');
    	
    	if($force_to_expand){
    		return $class;
    	}
    	
    	if($node->is_current){
    			return $class;
    	}else{
    		$children_is_current = false;
    		
    		foreach($node->children as $child){
    			if($child->is_current){
    				$children_is_current = true;
    				break;
    			}
    		}
    		
    		if($children_is_current){
    			$class['class'] = 'summary_arrow arrow_expand';
    		}else{
    			$class['class'] = 'summary_arrow arrow_noexpand';
    		}
    		
    		return $class;
    	}
    	
    	return null;
    }
    
    public function get_js(){
    	return "
    		<script type='text/javascript'>
	    		$(function(){
	    			$('.summary_arrow').click(function(e){
	    			    e.stopPropagation();
    					if(!$(this).hasClass('arrow_active')){
    						$(this).toggleClass(function(){
    							if($(this).hasClass('arrow_expand')){
    								$(this).removeClass('arrow_expand');
    								return 'arrow_noexpand';
    							}else{
    								$(this).removeClass('arrow_noexpand');
    								return 'arrow_expand';
    							}
    						});
    					}
    					$(this).find('i').toggleClass('minusicon " . get_config('block_summary', 'minus') . "');
    					$(this).find('i').toggleClass('plusicon " . get_config('block_summary', 'plus') . "');
    					$(this).parent().next('ul').toggle();
    				});
	    			
	    			$('.block_summary .arrow').click(function(){
	    			    var ol = $(this).parents('li').first().find('ol:first');
	    			    
	    			    if(ol.length == 0){
	    			        return;
	    			    }
	    			    
	    			    $(this).toggleClass('fa-caret-down');
	    			    $(this).toggleClass('fa-caret-right');
	    			    ol.toggle();
	    			});
	    			
	    			$('.block_summary .jumpto').change(function(){
	    			    var val = $(this).find('option:selected').val();
	    			    
	    			    if(val == ''){
	    			        return;
	    			    }
	    			    
	    			    window.location = val;
	    			})
	    		});
    		</script>
    	";
    }
    
    private function get_last_weight(){
    	global $DB, $PAGE;    	

    	$contextid = $PAGE->context->id;
    	
    	$sql = "SELECT * FROM(
				SELECT bi.defaultweight as weight FROM mdl_block_instances bi WHERE bi.parentcontextid = $contextid AND bi.defaultregion = 'side-pre'
				UNION
				SELECT bp.weight FROM mdl_block_positions bp WHERE bp.contextid = $contextid AND bp.region = 'side-pre') res
				ORDER by res.weight";
    	
    	$min_weight_record = $DB->get_records_sql($sql);

        $min = -11;
        if(isset($min_weight_record[0])){
            $min = 	$min_weight_record[0]->weight - 1;
        }

        return $min;
    }
    
    private function get_pages($courseid){
    	global $DB;

    	return $DB->get_records('format_flexpage_page', array('courseid' => $courseid, 'display' => 2), 'parentid, weight');
    }

    function has_config() {
        return true;
    }
    
}
