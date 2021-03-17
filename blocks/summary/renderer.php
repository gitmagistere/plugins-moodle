<?php


require_once($CFG->dirroot.'/course/format/modular/lib.php');


class block_summary_renderer extends plugin_renderer_base {

    private $moduleBackground = array(
        'module-color-1',
        'module-color-2',
        'module-color-3',
        'module-color-4',
        'module-color-5',
        'module-color-6',
        'module-color-7',
        'module-color-8'
    );

    public function main_menu($tree, $courseid, $canseehiddensections)
    {
        $html = '';
        $openpartmodule = false;
        $firstrow = true;
        $classes = ' module-right';

        foreach($tree['root'] as $id => $section){
            if($section->type == format_modular::$MODULE_TYPE && !$openpartmodule){
                $html .= '<div class="module-part"><hr/>';
                $openpartmodule = !$openpartmodule;
            }else if($section->type == format_modular::$END_TYPE && $openpartmodule){
                $html .= '<hr/></div>';
                $openpartmodule = !$openpartmodule;
            }else if (!$firstrow && !$openpartmodule){
                $html .= '<hr/>';
            }

            $thtml = $this->render_node($section, $courseid, $canseehiddensections, $classes);
            if (!empty($thtml))
            {
                $html .= $thtml;
                $classes = ($classes==' module-left'?' module-right':' module-left');
            }
            $firstrow = false;
        }

        return '<ul>'.$html.'</ul>';
    }

    private function render_node($node, $courseid, $canseehiddensections, $classes)
    {
        /* 3900 THE 11/08/2020 */
        if((!$node->visible || !$node->uservisible) && !$canseehiddensections) {
            return '';
        }
        /* 3900 */

        $url = new moodle_url('/course/view.php', array('id' => $courseid, 'section' => $node->numsection));
        if($node->visible == 0) {// && $node->type != format_modular::$MODULE_TYPE){
            $node->name = $node->name.' (cachées)';
            $namehtml = html_writer::link($url, $node->name, array('class' => 'hideClass'));
        } else {
            $namehtml = html_writer::link($url, $node->name);
        }

        if($node->type == format_modular::$MODULE_TYPE){
            $color = $this->get_module_color($node);
            $completed = '';
            if (section_is_completed($node->sectionid))
            {
                $completed = '<a class="summary-modular-module-completed" title="Vous avez achevé cette étape en cochant la case sur la page correspondante"><i class="fa fa-check"></i></a> ';
            }
            return '<li class="module '.$color.''.$classes.'">'.$namehtml.$completed.'</li>';
        }

        if(empty($node->children)){
            $ret = '<span class="arrow fa fa-caret-right"></span>'.$namehtml;
            if(isset($node->current)){
                $ret = '<b>'.$ret.'</b>';
            }

            return '<li>'.$ret.'</li>';
        }

        $arrow = '';
        if(isset($node->deploy)){
            $arrow = '<span class="arrow fa fa-caret-down"></span>';
        }else {
            $arrow = '<span class="arrow fa fa-caret-right"></span>';
        }

        $html = $arrow.$namehtml;
        if(isset($node->current)){
            $html = '<b>'.$html.'</b>';
        }

        $style = (isset($node->current) || isset($node->deploy) ? '' :  ' style="display: none;"');

        $html .= '<ol'.$style.'>';

        foreach($node->children as $id => $node){
            $html .= $this->render_node($node, $courseid, $canseehiddensections, '');
        }

        $html .= '</ol>';

        return '<li>'.$html.'</li>';
    }

    public function module_menu($tree, $moduleslist, $canseehiddensections)
    {
        $courseurl = new moodle_url('/course/view.php', array('id' => $tree->courseid));

        $homereturn = '<div class="homereturn"><a href="'.$courseurl.'">'.get_string('homereturnlabel', 'block_summary').'</a></div>';

        $htmlsubelm = array();
        foreach($tree->children as $id => $section){
            $htmlsubelm[] = $this->render_module_menu_item($section, $canseehiddensections);
        }

        $html = '<ul>'.implode('<hr/>', $htmlsubelm).'</ul>';

        if(empty($moduleslist)){
            return $html.$homereturn;
        }

        // if the modules list contains only me don't display list of other module
        if(count($moduleslist) == 1 && reset($moduleslist)->id == $tree->id){
            return $html.$homereturn;
        }

        $html .= '<hr/>';

        $options = array();

        foreach($moduleslist as $module){
            if((!$module->visible || !$module->uservisible) && !$canseehiddensections || $tree->id == $module->id){
                continue;
            }

            $url = new moodle_url('/course/view.php', array('id' => $module->courseid, 'section' => $module->numsection));

            $options[$url->raw_out(false)] = $module->name;
        }

        $select = html_writer::select($options, 'jumpmodule', '', array('' => get_string('jumptoanothermodule', 'block_summary')));

        return $html.'<div class="jumpto">'.$select.'</div>'.$homereturn;
    }

    public function render_module_menu_item($item, $canseehiddensections)
    {
        /* 3865 MD 14/04/2020  ->  3893 MD 23/04/2020*/
        /* Added "(!$item->uservisible && !$item->available)" to prevent display of sections when not authorized to navigate to it */
        if(((!$item->visible || (!$item->uservisible && !$item->available)) && !$canseehiddensections)
            || (empty($item->children) && !$item->hasContent)){
            return '';
        }

        $url = new moodle_url('/course/view.php', array('id' => $item->courseid, 'section' => $item->numsection));

         /* 3560 ARO 02/01/2020 */
        $hide = "";
        $hideclass = null;
        if(!$item->visible){
            $hide = " (cachée)";
            $hideclass = array('class' => 'hideClass');
        }
        /* 3560 */

        $namehtml = html_writer::link($url, $item->name.$hide  , $hideclass);

        $completed = '';
        if (section_is_completed($item->sectionid))
        {
            $completed = '<a class="summary-modular-submodule-completed" title="Vous avez achevé cette étape en cochant la case sur la page correspondante"><i class="fa fa-check"></i></a> ';
        }

        if(empty($item->children)){
            $ret = $completed.'<i class="arrow fa fa-caret-right"></i> '.$namehtml;

            if(isset($item->current)){
                $ret = '<b>'.$ret.'</b>';
            }

            return '<li>'.$ret.'</li>';
        }

        $html = '';

        foreach($item->children as $id => $node){
            $html .= $this->render_module_menu_item($node, $canseehiddensections);
        }

        $class = 'arrow fa';
        if(isset($item->deploy) || isset($item->current)){
            $class .= ' fa-caret-down';
            $html = '<ol>'.$html.'</ol>';
        }else{
            $class .= ' fa-caret-right';
            $html = '<ol style="display: none;">'.$html.'</ol>';
        }

        if(isset($item->current)){

            return '<li>'.$completed.' <b><i class="'.$class.'"></i>'.$namehtml.'</b>'.$html.'</li>';
        }

        return '<li>'.$completed.' <i class="'.$class.'"></i>'.$namehtml.$html.'</li>';

    }

    public function get_module_color($section)
    {
        if(!isset($section->color)){
            return '';
        }

        if(!$section->visible){
            return 'module-color-hidden';
        }

        return $this->moduleBackground[$section->color];
    }
    protected function block_header(block_contents $bc) {

        $title = '';
        if ($bc->title) {
            $attributes = array();
            if ($bc->blockinstanceid) {
                $attributes['id'] = 'instance-'.$bc->blockinstanceid.'-header';
            }
            $title = html_writer::tag('h2', $bc->title, $attributes);
        }

        $blockid = null;
        if (isset($bc->attributes['id'])) {
            $blockid = $bc->attributes['id'];
        }
        $controlshtml = $this->block_controls($bc->controls, $blockid);

        $output = '';
        if ($title || $controlshtml) {
            $output .= html_writer::tag('div', html_writer::tag('div', html_writer::tag('div', '', array('class'=>'block_action')). $title . $controlshtml, array('class' => 'title')), array('class' => 'header'));
        }
        return $output;
    }
}
