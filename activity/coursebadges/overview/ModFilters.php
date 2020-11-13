<?php

require_once($CFG->dirroot.'/lib/badgeslib.php');

class ModFilters {
    public static function get_list_badges($cmid)
    {
        global $DB;

        return $DB->get_records_sql('SELECT b.id, b.name  
FROM {badge} b
INNER JOIN {coursebadges_available_bdg} csb ON csb.badgeid=b.id
INNER JOIN {course_modules} cm ON cm.instance=csb.coursebadgeid
WHERE cm.module=(SELECT id FROM {modules} WHERE name = "coursebadges")
AND cm.id=?
AND (b.status=? OR b.status=?)
ORDER BY b.name', [$cmid, BADGE_STATUS_ACTIVE, BADGE_STATUS_ACTIVE_LOCKED]);

    }

    public static function get_list_mod_badges($courseid)
    {
        global $DB;

        return $DB->get_records('coursebadges', ['course' => $courseid], 'name ASC', 'id,name');
    }

    public static function get_groups_list($courseid, $cmid=null)
    {
        global $DB, $USER;
        
        if (!$cm = get_coursemodule_from_id('coursebadges', $cmid, $courseid)) {
            print_error('invalidcoursemodule');
        }

        $context = context_module::instance($cm->id);

        $groupmode = groups_get_activity_groupmode($cm);
        if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
            return groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
        }

        return $DB->get_records('groups', ['courseid' => $courseid], 'name ASC', 'id,name');
    }
    
}