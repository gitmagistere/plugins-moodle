<?php

require_once($CFG->dirroot.'/lib/badgeslib.php');

class Filters {
    public static function get_list_badges($courseid)
    {
        global $DB;

        return $DB->get_records_sql('SELECT b.id, b.name  
FROM {badge} b
WHERE b.courseid = ?
AND (b.status=? OR b.status=?)
ORDER BY b.name', [$courseid, BADGE_STATUS_ACTIVE, BADGE_STATUS_ACTIVE_LOCKED]);

    }

    public static function get_list_mod_badges($courseid)
    {
        global $DB;

        return $DB->get_records_sql('SELECT cb.id, cb.name, cm.id as cmid
        FROM {coursebadges} cb INNER JOIN {course_modules} cm ON cm.instance = cb.id AND cm.course = cb.course
        WHERE cb.course = ? AND cm.module  = (SELECT id from {modules} where name="coursebadges") 
        ORDER BY cb.name ASC', [$courseid]);
    }

    public static function get_groups_list($courseid)
    {
        global $DB, $USER;

        $context = context_course::instance($courseid);
        $course = get_course($courseid);

        $groupmode = groups_get_course_groupmode($course);
        if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
            return groups_get_all_groups($courseid, $USER->id);
        }

        return $DB->get_records('groups', ['courseid' => $courseid], 'name ASC', 'id,name');
    }
    
}