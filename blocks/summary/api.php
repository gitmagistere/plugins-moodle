<?php

/**
 * Moodle summary api 
 * 
 *
 * @package    summary
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

define('EXPIRE_DEFAULT', 60);
define('EDITORS_SESSKEY', 'block_summary_editors_sesskey_');

if (!isset($CFG->block_summary_editors_lifetime)) {
    $CFG->block_summary_editors_lifetime = EXPIRE_DEFAULT;
}

$action = required_param('action', PARAM_ALPHANUM);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);
$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);


if (!isset($CFG->block_summary_editors_sesskey_prefix)) {
    $CFG->block_summary_editors_sesskey_prefix = EDITORS_SESSKEY;
}

if($action == "renew"){
    $record = $DB->get_record_sql("SELECT * FROM {block_summary_session} WHERE token = ?",array($CFG->block_summary_editors_sesskey_prefix.$sesskey));

    $expi = intval($record->value);
    if (time() < $expi) {
        $DB->execute("DELETE FROM {block_summary_editors} WHERE expire < ?", array(time()));

        $expire = time() + $CFG->block_summary_editors_lifetime;
        $DB->execute("INSERT INTO {block_summary_editors} (courseid, userid, expire) VALUES(?,?,?) 
ON DUPLICATE KEY UPDATE expire = ?", array($courseid, $userid, $expire, $expire));
    }
}elseif($action =="goback"){
    $DB->execute("DELETE FROM {block_summary_session} WHERE token = ?", array($CFG->block_summary_editors_sesskey_prefix.$sesskey));
    $DB->execute("DELETE FROM {block_summary_editors} WHERE userid = ?", array($userid));
}
//clean old sessions
$DB->execute("DELETE FROM {block_summary_editors} WHERE expire < ?", array(time()));
