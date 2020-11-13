<?php

require_once($CFG->dirroot.'/lib/badgeslib.php');
require_once($CFG->dirroot.'/mod/coursebadges/overview/utils.php');

class ParticipantsOverviewData {

    private $cmid;
    private $cm;
    private $courseid;
    private $startindex;
    private $endindex;
    private $sortorder;

    private $data;
    private $resultcount;
    private $badgeId;
    private $courseBadgeModId;
    private $groupid;
    private $status;
    private $username;

    const ALL_BADGES = -1;
    const EARNED_BADGES = 0;
    const SELECTED_BADGES = 1;


    public function __construct($cmid, $courseid, $startindex, $endindex, $sortorder)
    {
        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->cm = $this->get_coursemodule_by_instanceid();
        $this->startindex = $startindex;
        $this->endindex = $endindex;
        $this->sortorder = $sortorder;

        $this->data = [];
        $this->resultcount = 0;

        $this->badgeId = 0;
        $this->courseBadgeModId = 0;
        $this->groupid = 0;
        $this->status = self::ALL_BADGES;
        $this->username = [];

    }

    public function executeSQL()
    {
        global $DB, $USER;

        $whereclauses = [];
        $params = [];

        $wheresqlselectedbadges = '';
        $wheresqlearnedbadges = '';
        if($this->badgeId){
            $whereclauses[] = '(users.selectedbadgeids=:badgeid1 OR users.earnedbadgeids=:badgeid2)';

            $params['badgeid1'] = $this->badgeId;
            $params['badgeid2'] = $this->badgeId;
        }

        if(count($this->username)){
            $c = [];

            foreach($this->username as $s){
                $c[] = 'users.lastname LIKE "%'.$s.'%"';
                $c[] = 'users.firstname LIKE "%'.$s.'%"';
            }

            $whereclauses[] = '('.implode($c, ' OR ').')';
        }

        $context = context_module::instance($this->cmid);

        if($this->groupid){
            $whereclauses[] = 'users.groupids=:groupid';
            $params['groupid'] = $this->groupid;
        } else if(groups_get_activity_groupmode($this->cm) == SEPARATEGROUPS
            && !has_capability('moodle/site:accessallgroups', $context)){
            if($allowedgroups = groups_get_all_groups($this->cm->course, $USER->id, $this->cm->groupingid, 'g.id')){
                $allowedgroupids = array_keys($allowedgroups);
                if(count($allowedgroups > 1)){
                    $whereclausegroup = [];
                    foreach ($allowedgroupids as $allowedgroupid){
                        $whereclausegroup[] = 'users.groupids = '.$allowedgroupid;
                    }
                    $whereclauses[] = '('.implode($whereclausegroup, ' OR ').')';
                } else {
                    $allowedgroupids = implode(", ", $allowedgroupids);
                    $whereclauses[] = 'users.groupids = :groupids';
                    $params['groupids'] = $allowedgroupids;
                }
            }
        }

        if($this->status == self::EARNED_BADGES){
            // desactivate the sql part of the selected badges with a false condition
            $wheresqlselectedbadges .= ' AND 1=0';
        }

        if($this->status == self::SELECTED_BADGES){
            // desactivate the sql part of the earned badges with a false condition
            $wheresqlearnedbadges .= ' AND 1=0';
        }

        $wheresql = '';
        if(count($whereclauses)){
            $wheresql = 'WHERE ';
            $wheresql .= implode(' AND ', $whereclauses);
        }

        $params['cmid1'] = $this->cmid;
        $params['cmid2'] = $this->cmid;

        $sql = '-- MAIN REQUEST TO GROUP ALL THE DATA
SELECT SQL_CALC_FOUND_ROWS
    users.id,
	users.lastname,
	users.firstname,

	GROUP_CONCAT(DISTINCT users.groupids) groupids,
	GROUP_CONCAT(DISTINCT users.groupnames ORDER BY users.groupnames ASC) groupnames,

	GROUP_CONCAT(DISTINCT users.selectedbadgeids ORDER BY users.selectedbadgeids ASC) selectedbadgeids,
	GROUP_CONCAT(DISTINCT users.selectedbadgenames) selectedbadgenames,
	
	COUNT(DISTINCT users.selectedbadgeids) selectedbadgescount,

	GROUP_CONCAT(DISTINCT users.earnedbadgeids ORDER BY users.earnedbadgeids ASC) earnedbadgeids,
	GROUP_CONCAT(DISTINCT users.earnedbadgenames) earnedbadgenames,
	
	COUNT(DISTINCT users.earnedbadgeids) earnedbadgescount,
	(COUNT(DISTINCT users.earnedbadgeids)/(COUNT(DISTINCT users.selectedbadgeids)+COUNT(DISTINCT users.earnedbadgeids))) percent
	
FROM (
    -- FIND ALL THE USER GROUPS
    SELECT 
        u.id,
        u.lastname,
        u.firstname,
    
        groups.id groupids,
        groups.name groupnames,
        
        NULL selectedbadgeids,
        NULL selectedbadgenames,
        NULL selectedmodnames,
        NULL selectedmodid,
    
        NULL earnedbadgeids,
        NULL earnedbadgenames
    FROM mdl_user u
    INNER JOIN mdl_user_enrolments ue ON ue.userid=u.id
    INNER JOIN mdl_enrol e ON e.id=ue.enrolid
    LEFT JOIN (
        SELECT g.id, g.courseid, g.name, gm.userid
        FROM mdl_groups_members gm
        INNER JOIN mdl_groups g ON g.id=gm.groupid
    ) groups ON (groups.userid=u.id AND groups.courseid=e.courseid)
    WHERE e.courseid=:courseid1
UNION
    -- FIND ALL THE BADGES SELECTED BY THE USER
    SELECT 
        u.id,
        u.lastname,
        u.firstname,

        groups.id groupids,
        groups.name groupnames,
        
        csb.badgeid selectedbadgeids,
        b.name selectedbadgenames,
        cb.name selectedmodnames,
        cb.id selectedmodid,

        NULL earnedbadgeids,
        NULL earnedbadgenames
    FROM {enrol} e
    INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
    INNER JOIN {user} u ON u.id=ue.userid
    INNER JOIN {coursebadges_usr_select_bdg} cuc ON cuc.userid=u.id
    INNER JOIN {coursebadges_available_bdg} csb ON csb.id=cuc.selectionbadgeid
    INNER JOIN {coursebadges} cb ON cb.id=csb.coursebadgeid
    INNER JOIN {course_modules} cm ON cm.instance=cb.id
    INNER JOIN {badge} b ON b.id=csb.badgeid
    LEFT JOIN {badge_issued} bi ON (bi.badgeid=b.id AND bi.userid=u.id)
    LEFT JOIN (
        SELECT g.id, g.courseid, g.name, gm.userid
        FROM mdl_groups_members gm
        INNER JOIN mdl_groups g ON g.id=gm.groupid
    ) groups ON (groups.userid=u.id AND groups.courseid=e.courseid)
    WHERE e.courseid=:courseid2 AND bi.id IS NULL 
    AND cm.module=(SELECT id FROM {modules} WHERE name = "coursebadges") AND cm.id=:cmid1'.$wheresqlselectedbadges.'
UNION
    -- FIND ALL THE BADGES EARNED BY THE USER
    SELECT 
        u.id,
        u.lastname,
        u.firstname,

        groups.id groupids,
        groups.name groupnames,
        
        NULL selectedbadgeids,
        NULL selectedbadgenames,
        NULL selectedmodnames,
        NULL selectedmodid,
        
        csb.badgeid earnedbadgeids,
        b.name earnedbadgenames
    FROM {enrol} e
    INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
    INNER JOIN {user} u ON u.id=ue.userid
    INNER JOIN {coursebadges_usr_select_bdg} cuc ON cuc.userid=u.id
    INNER JOIN {coursebadges_available_bdg} csb ON csb.id=cuc.selectionbadgeid
    INNER JOIN {coursebadges} cb ON cb.id=csb.coursebadgeid
    INNER JOIN {course_modules} cm ON cm.instance=cb.id
    INNER JOIN {badge} b ON b.id=csb.badgeid
    INNER JOIN {badge_issued} bi ON bi.badgeid=b.id
    LEFT JOIN (
        SELECT g.id, g.courseid, g.name, gm.userid
        FROM mdl_groups_members gm
        INNER JOIN mdl_groups g ON g.id=gm.groupid
    ) groups ON (groups.userid=u.id AND groups.courseid=e.courseid)
    WHERE e.courseid=:courseid3 AND bi.userid=u.id
    AND cm.module=(SELECT id FROM {modules} WHERE name = "coursebadges") AND cm.id=:cmid2'.$wheresqlearnedbadges.'
) users
'.$wheresql.'
GROUP BY users.id 
ORDER BY '.$this->sortorder.'
LIMIT '.$this->startindex.','.$this->endindex;

        $params['courseid1'] = $this->courseid;
        $params['courseid2'] = $this->courseid;
        $params['courseid3'] = $this->courseid;

        $results = $DB->get_records_sql($sql, $params);

        $this->resultcount = $DB->get_record_sql('SELECT FOUND_ROWS() as nbtotal')->nbtotal;

        $results = $this->process_results($results);

        return $results;
    }

    public function setBadgeId($badgeId){
        $this->badgeId = $badgeId;
    }

    public function setCourseBadgeModId($courseBadgeModId){
        $this->courseBadgeModId = $courseBadgeModId;
    }

    public function setGroupId($groupId){
        $this->groupid = $groupId;
    }

    public function setStatus($status){
        if(!in_array($status, [self::SELECTED_BADGES, self::EARNED_BADGES])){
            return;
        }

        $this->status = $status;
    }

    public function setUserName($username){
        $username = trim($username);

        $this->username = explode(' ', $username);
    }

    private function get_coursemodule_by_instanceid(){
        return get_coursemodule_from_id('coursebadges', $this->cmid, $this->courseid);
    }

    private function process_results($results)
    {
        $context = context_course::instance($this->courseid);
        $contextid = $context->id;
        
        $processedResults = [];

        foreach($results as $user)
        {
            $user->earnedbadgeids = ($user->earnedbadgeids ? explode(',', $user->earnedbadgeids) : []);
            $user->selectedbadgeids = ($user->selectedbadgeids ? explode(',', $user->selectedbadgeids) : []);

            $pu = new stdClass();
            $pu->firstname = $user->firstname;
            $pu->lastname = $user->lastname;

            $pu->earnedbadges = [];
            $pu->selectedbadges = [];
            foreach($user->earnedbadgeids as $bid){
                $earnedBadge = new stdClass();
                $earnedBadge->img_url = Utils::get_img_url_badge($bid, $contextid)->out(false);
                if (has_capability('moodle/badges:viewbadges', $context)) {
                    $earnedBadge->badge_url = Utils::get_url_badge($bid)->out(false);
                }
                $pu->earnedbadges[] = $earnedBadge;
                $pu->selectedbadges[] = $earnedBadge;
            }

            
            foreach($user->selectedbadgeids as $bid){
                $selectedBadge = new stdClass();
                $selectedBadge->img_url = Utils::get_img_url_badge($bid, $contextid)->out(false);
                if (has_capability('moodle/badges:viewbadges', $context)) {
                    $selectedBadge->badge_url = Utils::get_url_badge($bid)->out(false);
                }
                $pu->selectedbadges[] = $selectedBadge;
            }

            $pu->badgeearnedcount = $user->earnedbadgescount;
            $pu->badgetotal = (count($user->selectedbadgeids) + count($user->earnedbadgeids));
            $pu->badgepercent = 0;
            if($pu->badgetotal){
                $pu->badgepercent = ceil($pu->badgeearnedcount/$pu->badgetotal*100);
            }

            $user->groupnames = ($user->groupnames ? explode(',', $user->groupnames) : []);
            $pu->groupnames = [];
            foreach($user->groupnames as $group){
                $g = new stdClass();
                $g->name = $group;
                $pu->groupnames[] = $g;
            }

            $processedResults[] = $pu;
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
            'id' => [
                'key' => true,
                'create' => false,
                'edit' => false,
                'list' => false,
                'sorting' => false,
            ],
            'lastname' => [
                'title' => get_string('lastnamecolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'firstname' => [
                'title' => get_string('firstnamecolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'groupnames' => [
                'title' => get_string('groupnamecolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'earnedbadges' => [
                'title' => get_string('earnedbadgescolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            ],
            'selectedbadges' => [
                'title' => get_string('selectedbadgescolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            ],
            'percent' => [
                'title' => get_string('percentcolumn', 'mod_coursebadges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ]
        ];
    }
}