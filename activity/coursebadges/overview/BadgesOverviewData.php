<?php

require_once($CFG->dirroot.'/lib/badgeslib.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/utils.php');

class BadgesOverviewData {

    private $cmid;
    private $courseid;
    private $startindex;
    private $endindex;
    private $sortorder;

    private $data;
    private $resultcount;
    private $courseBadgeModId;

    public function __construct($cmid, $courseid, $startindex, $endindex, $sortorder)
    {
        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->startindex = $startindex;
        $this->endindex = $endindex;
        $this->sortorder = $sortorder;

        $this->sortorder = str_replace('percent', '(badgeearned/badgetotal)', $this->sortorder);

        $this->data = [];
        $this->resultcount = 0;

        $this->courseBadgeModId = 0;
    }

    public function executeSQL()
    {
        global $DB;

        $whereclauses = [];
        $params = [];

        $wheresql = '';
        if(count($whereclauses)){
            $wheresql = implode(' AND ', $whereclauses);
            $wheresql = $wheresql. ' AND ';
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS 
    CONCAT(b.id, "-", IFNULL(cb.id, 0)) uid,
    b.id,
    b.name,
    b.description,
    cm.id as cmid,
    
    (SELECT COUNT(*) 
FROM {coursebadges_usr_select_bdg} cuc
INNER JOIN {coursebadges_available_bdg}  csb ON csb.id=cuc.selectionbadgeid
INNER JOIN {badge_issued} bi ON bi.badgeid=csb.badgeid
INNER JOIN {user} u ON u.id = cuc.userid
INNER JOIN {user_enrolments} ue ON ue.userid=u.id
INNER JOIN {enrol} e ON ue.enrolid = e.id
WHERE csb.badgeid=b.id AND bi.userid=cuc.userid AND e.courseid = b.courseid
GROUP BY csb.badgeid) badgeearned,

    (SELECT COUNT(*) 
FROM {coursebadges_usr_select_bdg} cuc
INNER JOIN {coursebadges_available_bdg}  csb ON csb.id=cuc.selectionbadgeid
INNER JOIN {user} u ON u.id = cuc.userid
INNER JOIN {user_enrolments} ue ON ue.userid=u.id
INNER JOIN {enrol} e ON ue.enrolid = e.id
WHERE csb.badgeid=b.id AND e.courseid = b.courseid
GROUP BY csb.badgeid) badgetotal,
    
    cb.id coursebadgeid
FROM {badge} b
LEFT JOIN {coursebadges_available_bdg} csb ON csb.badgeid=b.id
LEFT JOIN {coursebadges} cb ON cb.id=csb.coursebadgeid
LEFT JOIN mdl_course_modules cm ON cm.instance=cb.id
WHERE '.$wheresql.'cm.id=? AND cm.module=(SELECT id FROM {modules} WHERE name="coursebadges")  
AND (b.status=? OR b.status=?)
ORDER BY '.$this->sortorder.' 
LIMIT '.$this->startindex.','.$this->endindex;

        $params[] = $this->cmid;
        $params[] = BADGE_STATUS_ACTIVE;
        $params[] = BADGE_STATUS_ACTIVE_LOCKED;

        $results = $DB->get_records_sql($sql, $params);

        $this->resultcount = $DB->get_record_sql('SELECT FOUND_ROWS() as nbtotal')->nbtotal;

        $results = $this->process_results($results);

        return $results;
    }

    private function process_results($results)
    {
        $context = context_course::instance($this->courseid);
        $contextid = $context->id;

        $processedResults = [];

        foreach($results as $badge)
        {
            if(!isset($processedResults[$badge->id])){
                $processedResults[$badge->id] = $badge;
                $processedResults[$badge->id]->mods = [];

                $badgeearned = ($badge->badgeearned ? $badge->badgeearned : 0);
                $badgetotal = ($badge->badgetotal ? $badge->badgetotal : 0);
                $badgepercent = 0;

                if($badgetotal){
                    $badgepercent = ceil($badgeearned / $badgetotal * 100);
                }

                $processedResults[$badge->id]->badgeearnedcount = $badgeearned;
                $processedResults[$badge->id]->urlearnedbadge = Utils::get_url_earned_badge_participant($badge->cmid, $badge->id)->out(false);
                $processedResults[$badge->id]->badgetotal = $badgetotal;
                $processedResults[$badge->id]->urlselectedbadge = Utils::get_url_selected_badge_participant($badge->cmid, $badge->id)->out(false);
                $processedResults[$badge->id]->badgepercent = $badgepercent;
                
                $processedResults[$badge->id]->badgetotal = $badgetotal;
                

                $processedResults[$badge->id]->imgurl = Utils::get_img_url_badge($badge->id, $contextid)->out(false);
                
                if (has_capability('moodle/badges:viewbadges', $context)) {
                    $processedResults[$badge->id]->badgeurl = Utils::get_url_badge($badge->id)->out(false);
                }
                
            }
        }

        return $processedResults;
    }

    public function getResultCount()
    {
        return $this->resultcount;
    }

    public static function getJTableColumns()
    {
        return [
            'imgurl' => [
                'title' => get_string('imagecolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            ],
            'name' => [
                'title' => get_string('namecolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'description' => [
                'title' => get_string('descriptioncolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            ],
            'percent' => [
                'title' => get_string('ratiocolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ]
        ];
    }

}