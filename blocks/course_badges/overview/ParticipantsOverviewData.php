<?php

require_once($CFG->dirroot.'/lib/badgeslib.php');
require_once($CFG->dirroot.'/blocks/course_badges/lib.php');

class ParticipantsOverviewData {

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


    public function __construct($courseid, $startindex, $endindex, $sortorder)
    {
        $this->courseid = $courseid;
        $this->startindex = $startindex;
        $this->endindex = $endindex;
        $this->sortorder = $sortorder;

        if(strpos($this->sortorder, 'modname') !== false){
            if(strpos($this->sortorder, 'ASC') !== false){
                $this->sortorder = 'earnedmodnames ASC, selectedmodnames ASC';
            }else{
                $this->sortorder = 'earnedmodnames DESC, selectedmodnames DESC';
            }
        }

        $this->data = [];
        $this->resultcount = 0;

        $this->badgeId = 0;
        $this->courseBadgeModId = 0;
        $this->groupid = 0;
        $this->status = self::ALL_BADGES;
        $this->username = [];
        $this->roleIds = [];

    }

    public function executeSQL()
    {
        global $DB, $USER;

        $whereclauses = [];
        $params = [];

        if($this->badgeId){
            $whereclauses[] = '(users.selectedbadgeids=:badgeid1 OR users.earnedbadgeids=:badgeid2 OR users.earnednotselectbadgeids=:badgeid3) ';
            $params['badgeid1'] = $this->badgeId;
            $params['badgeid2'] = $this->badgeId;
            $params['badgeid3'] = $this->badgeId;
        }

        if($this->courseBadgeModId){
            $whereclauses[] = '(users.earnedmodid=:modid1 OR users.selectedmodid=:modid2)';
            $params['modid1'] = $this->courseBadgeModId;
            $params['modid2'] = $this->courseBadgeModId;
        }
        
        if(count($this->username)){
            $c = [];

            foreach($this->username as $s){
                $c[] = 'users.lastname LIKE "%'.$s.'%"';
                $c[] = 'users.firstname LIKE "%'.$s.'%"';
            }

            $whereclauses[] = '('.implode($c, ' OR ').')';
        }

        $context = context_course::instance($this->courseid);
        $course = get_course($this->courseid);

        if($this->groupid){
            $whereclauses[] = 'users.groupids=:groupid';
            $params['groupid'] = $this->groupid;
        } else if(groups_get_course_groupmode($course) == SEPARATEGROUPS
            && !has_capability('moodle/site:accessallgroups', $context)){
            if($allowedgroups = groups_get_all_groups($this->courseid, $USER->id, 0, 'g.id')){
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

        $wheresqlselectedbadges = '';
        if($this->status == self::EARNED_BADGES){
            $wheresqlselectedbadges = ' AND bi.id IS NOT NULL';
        }

        $wheresqlearnedbadges = '';
        if($this->status == self::SELECTED_BADGES){
            // desactivate the sql part of the earned badges with a false condition
            $wheresqlearnedbadges = ' AND 1=0';
            $wheresqlselectedbadges = ' AND bi.id IS NULL';
        }
        
        $wheresql = '';
        if(count($whereclauses)){
            $wheresql = 'WHERE ';
            $wheresql .= implode(' AND ', $whereclauses);
        }

        $sql = '-- MAIN REQUEST TO GROUP ALL THE DATA
SELECT SQL_CALC_FOUND_ROWS
    users.id,
	users.lastname,
	users.firstname,

	GROUP_CONCAT(DISTINCT users.groupids) groupids,
	GROUP_CONCAT(DISTINCT users.groupnames ORDER BY users.groupnames ASC) groupnames,

	GROUP_CONCAT(DISTINCT users.selectedbadgeids ORDER BY users.selectedbadgeids ASC) selectedbadgeids,
	GROUP_CONCAT(DISTINCT users.selectedbadgenames) selectedbadgenames,
	GROUP_CONCAT(DISTINCT users.selectedmodnames) selectedmodnames,
	
	COUNT(DISTINCT users.selectedbadgeids) selectedbadgescount,

    GROUP_CONCAT(DISTINCT users.earnednotselectbadgeids ORDER BY users.earnednotselectbadgeids ASC) earnednotselectbadgeids,
	GROUP_CONCAT(DISTINCT users.earnedbadgeids  ORDER BY users.earnedbadgeids ASC) earnedbadgeids,
	GROUP_CONCAT(DISTINCT users.earnedbadgenames) earnedbadgenames,
	GROUP_CONCAT(DISTINCT users.earnedmodnames) earnedmodnames,
	
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
    
        NULL earnednotselectbadgeids,
        NULL earnedbadgeids,
        NULL earnedbadgenames,
        NULL earnedmodnames,
        NULL earnedmodid
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

        NULL earnednotselectbadgeids,
        NULL earnedbadgeids,
        NULL earnedbadgenames,
        NULL earnedmodnames,
        NULL earnedmodid
    FROM {enrol} e
    INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
    INNER JOIN {user} u ON u.id=ue.userid
    INNER JOIN {coursebadges_usr_select_bdg} cuc ON cuc.userid=u.id
    INNER JOIN {coursebadges_available_bdg} csb ON csb.id=cuc.selectionbadgeid
    INNER JOIN {coursebadges} cb ON cb.id=csb.coursebadgeid
    INNER JOIN {badge} b ON b.id=csb.badgeid AND b.courseid = e.courseid
    LEFT JOIN {badge_issued} bi ON (bi.badgeid=b.id AND bi.userid=u.id)
    LEFT JOIN (
        SELECT g.id, g.courseid, g.name, gm.userid
        FROM mdl_groups_members gm
        INNER JOIN mdl_groups g ON g.id=gm.groupid
    ) groups ON (groups.userid=u.id AND groups.courseid=e.courseid)
    WHERE e.courseid=:courseid2'.$wheresqlselectedbadges.'
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
        
        NULL earnednotselectbadgeids,
        b.id earnedbadgeids,
        b.name earnedbadgenames,
        cb.name earnedmodnames,
        cb.id earnedmodid
    FROM {enrol} e
    INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
    INNER JOIN {user} u ON u.id=ue.userid

    INNER JOIN {coursebadges_usr_select_bdg} cuc ON cuc.userid=u.id
    INNER JOIN {coursebadges_available_bdg} csb ON csb.id=cuc.selectionbadgeid
    INNER JOIN {coursebadges} cb ON cb.id=csb.coursebadgeid
    INNER JOIN {badge} b ON b.id=csb.badgeid AND b.courseid = e.courseid
    INNER JOIN {badge_issued} bi ON bi.badgeid=b.id
    
    LEFT JOIN (
        SELECT g.id, g.courseid, g.name, gm.userid
        FROM mdl_groups_members gm
        INNER JOIN mdl_groups g ON g.id=gm.groupid
    ) groups ON (groups.userid=u.id AND groups.courseid=e.courseid)
    WHERE e.courseid=:courseid3 AND bi.userid=u.id'.$wheresqlearnedbadges.'
UNION
    -- FIND ALL THE BADGES EARNED BUT NOT SELECTED BY THE USER 
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

        bi.badgeid earnednotselectbadgeids,
        NULL earnedbadgeids,
        NULL earnedbadgenames,
        NULL earnedmodnames,
        NULL earnedmodid
    FROM {enrol} e
    INNER JOIN {user_enrolments} ue ON ue.enrolid=e.id
    INNER JOIN {user} u ON u.id=ue.userid
    INNER JOIN {badge_issued} bi ON bi.userid=u.id
    INNER JOIN {badge} b ON b.id=bi.badgeid AND b.courseid = e.courseid

    -- we need to avoid the selected badge for this course, we use a left join and filter it in the where clause (with cuc.userid IS NULL)
    LEFT JOIN {coursebadges_usr_select_bdg} cuc ON cuc.userid=u.id  AND cuc.selectionbadgeid IN 
    (SELECT ca.id from {coursebadges_available_bdg} ca INNER JOIN {coursebadges} cb ON ca.coursebadgeid = cb.id where ca.badgeid = b.id AND cb.course = e.courseid)

    LEFT JOIN (
        SELECT g.id, g.courseid, g.name, gm.userid
        FROM mdl_groups_members gm
        INNER JOIN mdl_groups g ON g.id=gm.groupid
    ) groups ON (groups.userid=u.id AND groups.courseid=e.courseid)
    WHERE e.courseid=:courseid4 AND cuc.userid IS NULL'.$wheresqlearnedbadges.'
) users
'.$wheresql.'
GROUP BY users.id 
ORDER BY '.$this->sortorder.'
LIMIT '.$this->startindex.','.$this->endindex;

        $params['courseid1'] = $this->courseid;
        $params['courseid2'] = $this->courseid;
        $params['courseid3'] = $this->courseid;
        $params['courseid4'] = $this->courseid;

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
    
    public function setRoleIds($roleIds) {
        $this->roleIds = $roleIds;
    }

    private function process_results($results)
    {
        $context = context_course::instance($this->courseid);
        $contextid = $context->id;

        $processedResults = [];

        foreach($results as $user)
        {
            // check if user has the right role
            $context = context_course::instance($this->courseid);
            $roles = get_user_roles($context, $user->id);
            $roleskey = array_keys($roles);
            $hasRightRole = false;
            foreach ($roleskey as $rolekey) {
                if (in_array($roles[$rolekey]->roleid, $this->roleIds)) {
                    $hasRightRole = true;
                    break;
                }
            }
            if (!$hasRightRole) continue;
            
            // user has the right role, let's proceed
            $user->earnednotselectbadgeids = ($user->earnednotselectbadgeids ? explode(',', $user->earnednotselectbadgeids) : []);
            $user->earnedbadgeids = ($user->earnedbadgeids ? explode(',', $user->earnedbadgeids) : []);
            $user->selectedbadgeids = ($user->selectedbadgeids ? explode(',', $user->selectedbadgeids) : []);

            $pu = new stdClass();
            $pu->firstname = $user->firstname;
            $pu->lastname = $user->lastname;

            $pu->allearnedbadgeids = [];

            $user->allearnedbadgeids = array_merge($user->earnednotselectbadgeids, $user->earnedbadgeids);
            foreach($user->allearnedbadgeids as $bid){
                $earnedBadge = new stdClass();
                $earnedBadge->img_url = get_img_url_badge($bid, $contextid)->out(false);
                if (has_capability('moodle/badges:viewbadges', $context)) {
                    $earnedBadge->badge_url = get_url_badge($bid)->out(false);
                }
                $pu->allearnedbadgeids[] = $earnedBadge;
            }

            $pu->selectedbadges = [];
            foreach($user->selectedbadgeids as $bid){
                $selectedBadge = new stdClass();
                $selectedBadge->img_url = get_img_url_badge($bid, $contextid)->out(false);
                if (has_capability('moodle/badges:viewbadges', $context)) {
                    $selectedBadge->badge_url = get_url_badge($bid)->out(false);
                }
                $pu->selectedbadges[] = $selectedBadge;
            }

            $pu->badgeearnedcount = $user->earnedbadgescount;
            $pu->badgetotal = count($user->selectedbadgeids);

            $pu->badgepercent = 0;
            if($pu->badgetotal){
                $pu->badgepercent = ceil($pu->badgeearnedcount/$pu->badgetotal*100);
            }

            // deduplicate modname
            $user->earnedmodnames = ($user->earnedmodnames ? explode(',', $user->earnedmodnames) : []);
            $user->selectedmodnames = ($user->selectedmodnames ? explode(',', $user->selectedmodnames) : []);

            $modnames = array_unique(array_merge($user->earnedmodnames, $user->selectedmodnames));
            $pu->modnames = [];
            foreach($modnames as $mod){
                $m = new stdClass();
                $m->name = $mod;
                $pu->modnames[] = $m;
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
                'title' => get_string('lastnamecolumn', 'block_course_badges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'firstname' => [
                'title' => get_string('firstnamecolumn', 'block_course_badges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'groupnames' => [
                'title' => get_string('groupnamecolumn', 'block_course_badges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'earnedbadges' => [
                'title' => get_string('earnedbadgescolumn', 'block_course_badges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
                'listClass'=> "jt_separatorcol",
            ],
            'selectedbadges' => [
                'title' => get_string('selectedbadgescolumn', 'block_course_badges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => false,
            ],
            'percent' => [
                'title' => get_string('percentcolumn', 'block_course_badges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
            'modname' => [
                'title' => get_string('modcolumn', 'block_course_badges'),
                'key' => false,
                'create' => false,
                'edit' => false,
                'list' => true,
                'sorting' => true,
            ],
        ];
    }
}