<?php

require_once($CFG->dirroot.'/course/format/modular/lib.php');

class format_modular_helper {
    private $courseid;
    private $filters;

    private $tree;

    public function __construct($courseid)
    {
        $this->courseid = $courseid;
        $this->tree = null;
    }

    public function get_main_menu()
    {
        $first_level_section = $this->get_sections(array('parentid' => null));

        return $first_level_section;

    }

    public function get_module_menu_tree($moduleid)
    {
        $sections = $this->get_all_sections_tree();

        // search top level parent if exists
        $parentid = $sections['nodes'][$moduleid]->parentid;
        $sections['nodes'][$moduleid]->current = true;

        while(isset($sections['nodes'][$parentid]) && $sections['nodes'][$parentid]->parentid > 0){
            $sections['nodes'][$parentid]->deploy = true;
            $parentid = $sections['nodes'][$parentid]->parentid;
        }

        if($parentid == 0){
            return $sections['nodes'][$moduleid];
        }

        $sections['nodes'][$parentid]->deploy = true;
        return $sections['nodes'][$parentid];
    }

    public function get_all_sections_tree($current = 0)
    {
        if($this->tree != null){
            return $this->tree;
        }

        $sections = $this->get_sections();

        $tree = array(
            'root' => array(),

            // used for fast access
            'nodes' => array(),
            format_modular::$INTRO_TYPE => array(),
            format_modular::$MODULE_TYPE => array(),
            format_modular::$END_TYPE => array(),

            'total' =>array(
                format_modular::$INTRO_TYPE => 0,
                format_modular::$MODULE_TYPE => 0,
                format_modular::$END_TYPE => 0,
            )
        );

        // avalaible colors (zero based), +1 for the hidden section
        $colorCount = 8;
        $currentColor = 0;
        $currentsection = null;

        foreach($sections as $section){
            $section->children = array();

            if($section->parentid == 0 && !isset($tree['nodes'][$section->numsection])){
                if($section->type == format_modular::$MODULE_TYPE){
                    if($section->visible){
                        $section->color = ($currentColor % $colorCount);
                        $currentColor++;
                    }else{
                        $section->color = $colorCount;
                    }
                }

                if($section->numsection == $current){
                    $currentsection = $section->numsection;
                }

                $tree['root'][$section->numsection] = $section;
                $tree['nodes'][$section->numsection] =& $tree['root'][$section->numsection];
                $tree[$section->type][$section->numsection] =& $tree['root'][$section->numsection];
            }else if($section->parentid != 0){
                if($section->type == format_modular::$MODULE_TYPE){
                    $section->color = $tree['nodes'][$section->parentid]->color;
                }

                $tree['nodes'][$section->parentid]->children[$section->numsection] = $section;
                $tree['nodes'][$section->numsection] =& $tree['nodes'][$section->parentid]->children[$section->numsection];

                // override visibility from parent
                if($section->parentid && !$tree['nodes'][$section->parentid]->uservisible){
                    $tree['nodes'][$section->numsection]->uservisible = false;
                }

                // override hasNavigation from parent
                if($section->parentid && !$tree['nodes'][$section->parentid]->hasNavigation){
                    $tree['nodes'][$section->numsection]->hasNavigation = false;
                }

                if($section->numsection == $current){
                    $currentsection = $section->numsection;
                }
            }

            $tree['total'][$section->type]++;
        }

        if($currentsection){
            $tree['nodes'][$currentsection]->current = true;


            while(($parent = $tree['nodes'][$currentsection]->parentid)){
                $tree['nodes'][$parent]->deploy = true;
                $currentsection = $parent;
            }
        }

        $this->tree = $tree;

        return $tree;
    }

    public function get_sections($filters = array())
    {
        global $DB;

        $this->filters = $filters;

        $sqlfilters = '';
        if(isset($filters['id'])){
            $sqlfilters .= ' AND cs.id='.$filters['id'];
        }

        if(isset($filters['cmid'])){
            $sqlfilters .= ' AND (cs.sequence = "'.$filters['cmid'].'"
                OR cs.sequence LIKE "%,'.$filters['cmid'].',%"
            OR cs.sequence LIKE "'.$filters['cmid'].',%"
            OR cs.sequence LIKE "%,'.$filters['cmid'].'")';
        }

        if(isset($filters['numsection'])){
            $sqlfilters .= ' AND cs.section='.$filters['numsection'];
        }

        $sectioninfo = $DB->get_records_sql('SELECT 
  CONCAT(cs.id, cfo.name) id,
  cs.id sectionid,
  cs.name sectionname,
  cs.section numsection,
  cs.course courseid,
  cfo.name,
  cfo.value,
  cs.visible
FROM {course_sections} cs
INNER JOIN {course_format_options} cfo ON cfo.sectionid=cs.id
WHERE cfo.courseid = ? AND cfo.courseid=cs.course
AND cfo.format="modular"
AND cs.section > 0 '
.$sqlfilters.
' ORDER BY cs.section', array($this->courseid));

        $format = course_get_format($this->courseid);

        $modinfo = get_fast_modinfo($this->courseid);
        $coursesections = $modinfo->get_section_info_all();

        $sections = array();
        foreach($sectioninfo as $info){
            if(!isset($sections[$info->sectionid])){
                $section = new stdClass();
                $section->id = $info->sectionid;
                $section->name = !empty($info->sectionname) ? $info->sectionname : $format->get_default_section_name($info->numsection);
                $section->numsection = $info->numsection;
                $section->sectionid = $info->sectionid;
                $section->visible = $info->visible;

                // uservisible and visible are different, because uservisible take into any visibility restrictions (group and so on)
                $section->uservisible = $coursesections[$info->numsection]->uservisible;
                // Allow to get the information about whether or not the "eye" icon was checked
                $section->available = $coursesections[$info->numsection]->availableinfo !== '';
                $section->courseid = $info->courseid;

                $sections[$section->id] = $section;
            }

            $sections[$info->sectionid]->{$info->name} = $info->value;
        }

        $sections = array_filter($sections, array($this, 'filter_course_format_options'));

        $this->filters = null;

        return $sections;
    }

    public function filter_course_format_options($val)
    {
        $ret = true;

        if(isset($this->filters['parentid'])){
            $ret &= ($val->parentid == $this->filters['parentid']);
        }

        if(isset($this->filters['type'])){
            $ret &=  ($val->type == $this->filters['type']);
        }

        return $ret;
    }

    public function get_last_parent_section($section)
    {
        $tree = $this->get_all_sections_tree();

        $current = $section;

        while($current->parentid){
            $current = $tree['nodes'][$current->parentid];
        }

        return $current;
    }

    public function get_nearest_section($current, $target, $canseehiddensection, $inc)
    {
        $tree = $this->get_all_sections_tree();

        if(!array_key_exists($target, $tree['nodes'])){
            return false;
        }

        $current = $tree['nodes'][$current];
        $target = $tree['nodes'][$target];

        // can't have a different type
        if($current->type != $target->type){
            return false;
        }

        // can't be see
        if(!$target->uservisible && !$canseehiddensection){
            // try to find the 'next' nearest
            return $this->get_nearest_section($current->numsection, $target->numsection+$inc, $canseehiddensection, $inc);
        }

        // if current is intro or end
        if($current->type != format_modular::$MODULE_TYPE){
            return $target;
        }

        // if tocheck is a root module and it's not my parent
        if((!$target->parentid && $target->id != $current->parentid)){
            return false;
        }

        // if i'm a root module and $inc is in 'reverse mode'
        // i can't have a previous module
        if($current->parentid == 0 && $inc < 0){
            return false;
        }

        // if target has a content
        if($target->hasContent){
            return $target;
        }

        // tocheck hasn't a content
        // so find the nearest available and section
        $nearest = $target->numsection;
        $lastparent = $current->parentid;

        do{
            $target = $tree['nodes'][$nearest];

            // if different type
            if($current->type != $target->type){
                return false;
            }

            if(!$target->visible && !$canseehiddensection){
                $nearest += $inc;
                $lastparent = $target->parentid;
                continue;
            }

            // if current is intro or end
            if($current->type != format_modular::$MODULE_TYPE){
                return $target;
            }

            // if it's a root module and dont have content
            if(!$target->parentid && !$target->hasContent){
                return false;
            }

            // if tocheck if a root module and isn't my parent
            if(!$target->parentid && $target->id != $lastparent){
                return false;
            }

            // if is visible (or user can see hidden sections) and has content
            if(($target->visible || $canseehiddensection) && $target->hasContent){
                return $target;
            }

            $nearest += $inc;
            $lastparent = $target->parentid;

        }while($nearest > 1  || $nearest < count($tree['nodes']));

        return false;
    }

    public function get_section($num)
    {
        $tree = $this->get_all_sections_tree();

        return $tree['nodes'][$num];
    }
}
