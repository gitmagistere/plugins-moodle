<?php

require_once($CFG->dirroot.'/blocks/course_badges/lib.php');

class BadgesList
{
    const EARNED_BADGES = 0;
    const SELECTED_BADGES = 1;
    const AVAILABLE_BADGES = 3;

    private $courseid;
    private $coursecontext;

    function __construct($courseid)
    {
        $this->courseid = $courseid;
        $this->coursecontext = context_course::instance($courseid);
    }

    /**
     * Get the full list of badges for one user
     *
     * @param $userid
     * @return array the list of all badges of the user, sort by type.
     */
    function get_badges_list_for_user($userid)
    {
        global $DB;

        $badges = $DB->get_records_sql('
SELECT 
    b.id badgeid,
	b.name,
	GROUP_CONCAT(bi.id) badgeissuedid,
	GROUP_CONCAT(cbu.id) userchoiceid
FROM mdl_badge b
LEFT JOIN {badge_issued} bi ON (bi.badgeid=b.id AND bi.userid=:userid1) 
LEFT JOIN {coursebadges_available_bdg} csb ON csb.badgeid=b.id
LEFT JOIN {coursebadges_usr_select_bdg} cbu ON (cbu.selectionbadgeid=csb.id AND cbu.userid=:userid2)
WHERE b.courseid=:courseid 
AND (b.status=:active OR b.status=:activelocked)
GROUP BY b.id', [
        'userid1' => $userid,
        'courseid' => $this->courseid,
        'active' => BADGE_STATUS_ACTIVE,
        'activelocked' => BADGE_STATUS_ACTIVE_LOCKED,
        'userid2' => $userid,
        ]);

        $results = [
            self::AVAILABLE_BADGES => [],
            self::EARNED_BADGES => [],
            self::SELECTED_BADGES => []
        ];

        if(!$badges){
            return $results;
        }

        foreach($badges as $badge){
            $badge->imgurl = get_img_url_badge($badge->badgeid, $this->coursecontext->id);

            if($badge->badgeissuedid){
                $results[self::EARNED_BADGES][] = $badge;
                continue;
            }

            if($badge->userchoiceid){
                $results[self::SELECTED_BADGES][] = $badge;
                continue;
            }

            $results[self::AVAILABLE_BADGES][$badge->badgeid] = $badge;
        }

        return $results;

    }
}