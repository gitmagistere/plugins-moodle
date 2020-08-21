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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');

class format_modular extends format_base {

    public static $MODULE_TYPE = 'module';
    public static $INTRO_TYPE = 'intro';
    public static $END_TYPE = 'end';

    public function __construct($format, $courseid)
    {
        parent::__construct($format, $courseid);

        if($this->courseid > 1){
            $this->update_block_summary();
        }
    }

    /**
     * Returns true if this course format uses sections
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Enable the ajax
     */
    public function supports_ajax() {
        // no support by default
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                array('context' => context_course::instance($this->courseid)));
        } else {
            return $this->get_default_section_name($section);
        }
    }

    public function get_default_section_name($section) {
        return parent::get_default_section_name($section);
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        if(!isset($section))$section = 1;
        $sectionid = $section;
        if(is_object($section)){
            $sectionid = $section->section;
        }

        $url = new moodle_url('/course/view.php', array('id' => $this->courseid, 'section' => $sectionid));

        return $url;
    }

    /**
     * Loads all of the course sections into the navigation
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // if section is specified in course/view.php, make sure it is expanded in navigation
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // check if there are callbacks to extend course navigation
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array('summary', 'search_forums', 'news_items', 'calendar_upcoming', 'recent_activity'),
        );
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    public function section_format_options($foreditform = false) {
        if($foreditform){
            // no options when request for form
            return array();
        }

        return array(
            'parentid' => array(
                'default' => null,
                'type' => PARAM_INT,
                'cache' => true
            ),
            'hasContent' => array(
                'default' => true,
                'type' => PARAM_BOOL,
                'cache' => true
            ),
            'type' => array(
                'default' => '',
                'type' => PARAM_TEXT,
                'cache' => true
            ),
            'hasNavigation' => array(
                'default' => true,
                'type' => PARAM_BOOL,
                'cache' => true
            ),
        );
    }

    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    public function update_course_format_options($data, $oldcourse = null)
    {

        if($oldcourse){
            if($oldcourse->format == 'topics' or $oldcourse->format == 'magistere_topics'){
                $this->move_topics_sections($data->id);
            }

            return parent::update_course_format_options($data, $oldcourse);
        }

        // if the course has been restored
        if(!isset($data->returnto)){
            return parent::update_course_format_options($data, $oldcourse);
        }

        // creation of the course
        $this->init_section();

        return parent::update_course_format_options($data, $oldcourse);
    }

    public function update_block_summary()
    {
        global $DB;

        $context = context_course::instance($this->get_course()->id);

        $blocksummary = $DB->get_record('block_instances', array('blockname' => 'summary', 'parentcontextid' => $context->id));

        if(!$blocksummary || $blocksummary->pagetypepattern == '*'){
            return;
        }

        $blocksummary->pagetypepattern = '*';
        $blocksummary->showinsubcontexts = 1;

        $DB->update_record('block_instances', $blocksummary);
    }

    public function update_section_format_options($data){
        if($data['parentid'] === null && $data['hasContent'] === null && $data['type'] === null){
            return false;
        }

        return parent::update_section_format_options($data);
    }

    public function move_section_to_intro($courseid)
    {
        global $DB;

        course_create_sections_if_missing($this->get_course(), 0);
        course_create_sections_if_missing($this->get_course(), 1);

        $section0 = $DB->get_record('course_sections', array('course' => $this->get_courseid(), 'section' => 0));
        $section0->visible = 0;
        $DB->update_record('course_sections', $section0);

        $sections = $DB->get_records('course_sections', array('course' => $courseid));

        foreach($sections as $section)
        {
            $this->update_format_options(array(
                'type' => format_modular::$INTRO_TYPE,
                'hasContent' => 1,
                'parentid' => null
            ), $section->id);
        }
    }

    public function init_section()
    {
        global $DB;

        // hide section 0
        course_create_sections_if_missing($this->get_course(), 0);
        $section0 = $DB->get_record('course_sections', array('course' => $this->get_courseid(), 'section' => 0));
        $section0->visible = 0;
        $DB->update_record('course_sections', $section0);

        $sections = array(
            array(
                'name' => 'Accueil',
                'type' => format_modular::$INTRO_TYPE
            ),
            array(
                'name' => 'Module 1',
                'type' => format_modular::$MODULE_TYPE,
            ),
            array(
                'name' => 'Module 2',
                'type' => format_modular::$MODULE_TYPE,
            ),
            array(
                'name' => 'Conclusion',
                'type' => format_modular::$END_TYPE,
            ),
            array(
                'name' => 'Page des formateurs',
                'type' => format_modular::$END_TYPE,
            )
        );

        $dbsec = new stdClass();
        $dbsec->course = $this->get_courseid();
        $dbsec->summaryformat = 1;
        $dbsec->visible = 1;

        $i = 1;
        foreach($sections as $section){
            $dbsec->name = $section['name'];
            $dbsec->section = $i;
            $i++;

            $sid = $DB->insert_record('course_sections', $dbsec);

            $this->update_format_options(array(
                'type' => $section['type'],
                'hasContent' => 1,
                'parentid' => null,
                'hasNavigation' => true
            ), $sid);
        }

    }

    public function move_blocks($courseid)
    {
        global $DB;

        $context = context_course::instance($courseid);

        $maxweight = $DB->get_record_sql('SELECT MAX(bi.defaultweight) max_defaultweight, MAX(bp.weight) max_weight 
FROM {block_instances} bi
LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
WHERE bi.parentcontextid = ? AND (bi.defaultregion = "side-pre" OR (bp.id IS NOT NULL AND bp.region = "side-pre"))',
            array($context->id));

        $maxweight = max($maxweight->max_defaultweight, $maxweight->max_weight);

        // update all blocks with default position set to 'side-post'
        $block_instances = $DB->get_records_sql(
            'SELECT bi.*
            FROM {block_instances} bi
            LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
            WHERE bp.blockinstanceid IS NULL AND bi.parentcontextid = ? AND bi.defaultregion = "side-post"'
        , array($context->id));

        foreach($block_instances as $key => $bi){
            $block_instances[$key]->defaultregion = 'side-pre';
            $block_instances[$key]->defaultweight = ++$maxweight;
            $DB->update_record('block_instances', $bi);
        }

        // update all blocks with custom position
        $block_instances = $DB->get_records_sql(
            'SELECT bp.*
            FROM {block_instances} bi
            LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
            WHERE bi.parentcontextid = ? AND bp.region = "side-post"'
            , array($context->id));

        foreach($block_instances as $key => $bi){
            $block_instances[$key]->region = 'side-pre';
            $block_instances[$key]->weight = ++$maxweight;
            $DB->update_record('block_positions', $bi);
        }
    }

    public function update_section($data)
    {
        global $DB;

        if(($sameweight = $DB->get_record('course_sections', array('course' => $data->course, 'section' => $data->section ))) && $sameweight->id != $data->id)
        {
            $sameweight->section *= -1;
            $DB->update_record('course_sections', $sameweight);
        }

        $DB->update_record('course_sections', $data);

        $this->update_format_options(array(
            'type' => $data->type,
            'hasContent' => $data->hasContent,
            'parentid' => $data->parentid,
            'hasNavigation' => $data->hasNavigation
        ), $data->id);
    }

    public function create_section($data)
    {
        global $DB;

        if(($sameweight = $DB->get_record('course_sections', array('course' => $data->course, 'section' => $data->section ))) && $sameweight->id != $data->id)
        {
            $sameweight->section *= -1;
            $DB->update_record('course_sections', $sameweight);
        }

        $sid = $DB->insert_record('course_sections', $data);

        $this->update_format_options(array(
            'type' => $data->type,
            'hasContent' => $data->hasContent,
            'parentid' => $data->parentid
        ), $sid);

        return $sid;
    }

    public function update_format_options($data, $sectionid = null)
    {
        return parent::update_format_options($data, $sectionid);
    }

    public function move_to_intro($sectionid, $parentid, $hascontent=1)
    {
        $data = array(
            'type' => format_modular::$INTRO_TYPE,
            'hascontent' => $hascontent,
            'parentid' => $parentid,
            'hasNavigation' => true
        );

        $this->update_format_options($data, $sectionid);
    }

    public function move_to_module($sectionid, $parentid, $hascontent=1)
    {
        $data = array(
            'type' => format_modular::$MODULE_TYPE,
            'hascontent' => $hascontent,
            'parentid' => $parentid,
            'hasNavigation' => true
        );

        $this->update_format_options($data, $sectionid);
    }

    public function move_to_end($sectionid, $parentid, $hascontent=1)
    {
        $data = array(
            'type' => format_modular::$END_TYPE,
            'hascontent' => $hascontent,
            'parentid' => $parentid,
            'hasNavigation' => true
        );

        $this->update_format_options($data, $sectionid);
    }

    public function move_topics_sections($newcourseid)
    {
        global $DB;

        $tree = array(
            'tree' => array(),
            'nodes' => array()
        );

        $tree['nodes'] = $DB->get_records_sql('SELECT
          cs.id,
          cs.name,
          cs.section numsection,
          bs.parentid,
          bs.weight 
        FROM {course_sections} cs 
        INNER JOIN {block_summary} bs ON bs.sectionid=cs.id
        WHERE cs.course=?
        ORDER BY bs.parentid, bs.weight', array($newcourseid));


        if(count($tree['nodes']) <= 0){
            $tree['nodes'] = $this->get_course_tree_when_empty($newcourseid);

        }

        foreach($tree['nodes'] as &$section){
            if($section->parentid == null){
                $tree['tree'][] = $section;
                continue;
            }

            if(!isset($tree['nodes'][$section->parentid]->child)){
                $tree['nodes'][$section->parentid]->child = array();
            }

            $tree['nodes'][$section->parentid]->child[] = $section;
        }

        $introsection = array_slice($tree['tree'], 0, 1);
        $modulesection = array_slice($tree['tree'], 1, -1);
        $endsection = array_slice($tree['tree'], -1, 1);


        $nodes = $introsection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $tree['nodes'][$node->parentid]->numsection : null);

            $this->move_to_intro($node->id, $parentid);

            if(isset($node->child)){
                $nodes = array_merge($nodes, $node->child);
            }
        }

        $nodes = $modulesection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $tree['nodes'][$node->parentid]->numsection : null);

            $this->move_to_module($node->id, $parentid);

            if(isset($node->child)){
                $nodes = array_merge($nodes, $node->child);
            }
        }

        $nodes = $endsection;
        for($i = 0; $i < count($nodes); $i++){
            $node = $nodes[$i];
            $parentid = ($node->parentid > 0 ? $tree['nodes'][$node->parentid]->numsection : null);

            $this->move_to_end($node->id, $parentid);

            if(isset($node->child)){
                $nodes = array_merge($nodes, $node->child);
            }
        }

    }
    function get_course_tree_when_empty($courseid)
    {
        global $DB;
        $sections = $DB->get_records_sql('
SELECT cs.id, cs.id AS sectionid, cs.course AS courseid, bs.parentid, bs.weight, cs.name, cs.visible, cs.section
FROM {course_sections} cs
LEFT JOIN {block_summary} bs ON (cs.id = bs.sectionid)
WHERE cs.course = '.$courseid.' AND cs.section > 0  ORDER BY bs.parentid,cs.section ASC');

        // Get section details
        $modinfo = get_fast_modinfo($courseid);
        $coursesections = $modinfo->get_section_info_all();

        $sec = array();
        foreach($sections AS $key=>$section)
        {
            $sec[$key] = new stdClass();
            $sec[$key]->id = $section->sectionid;
            $sec[$key]->parentid = $section->parentid;
            $sec[$key]->section = $section->section;
            $sec[$key]->courseid = $section->courseid;
            $sec[$key]->name = $section->name;
            $sec[$key]->visible = $section->visible;
            $sec[$key]->uservisible = $coursesections[$section->section]->uservisible;
            $sec[$key]->weight = $section->section;
        }



        foreach($sec AS $key=>$se)
        {
            if ($sec[$key]->parentid != null && $sec[$key]->parentid > 0)
            {
                if (isset($sec[$sec[$key]->parentid]->children) && !is_array($sec[$sec[$key]->parentid]->children))
                {
                    $sec[$sec[$key]->parentid]->children = array();
                }

                if(!$sec[$sec[$key]->parentid]->uservisible && $sec[$key]->uservisible){
                    $sec[$key]->uservisible = false;
                }


                $sec[$sec[$key]->parentid]->children[$key] = $sec[$key];
                unset($sec[$key]);
            }

        }

        return $sec;
    }

}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_modular_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'modular'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}