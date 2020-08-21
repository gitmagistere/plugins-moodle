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


/**
 * Summary block edition page
 *
 * @package    block_summary
 * @subpackage block_summary
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

ini_set("mysql.trace_mode", "0");

// Include required files
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/blocks/summary/lib.php');


define('EDITORS_SESSKEY', 'block_summary_editors_sesskey_');
define('SESSKEY_EXPIRE', 3600);
define('REFRESH_DELAY', 30000);
if (!isset($CFG->block_summary_editors_sesskey_prefix)) {
    $CFG->block_summary_editors_sesskey_prefix = EDITORS_SESSKEY;
}
if (!isset($CFG->block_summary_editors_sesskey_expire)) {
    $CFG->block_summary_editors_sesskey_expire = SESSKEY_EXPIRE;
}
if (!isset($CFG->block_summary_editors_refresh_delay)) {
    $CFG->block_summary_editors_refresh_delay = REFRESH_DELAY;
}

// Gather form data
$courseid= required_param('id', PARAM_INT);


// for showing alert  when there are many editors at a time
$sesskey = random_string(15);

//mmcached_set($CFG->block_summary_editors_sesskey_prefix.$sesskey, time() + $CFG->block_summary_editors_sesskey_expire);
$block_summary_session = new stdClass();
$block_summary_session->token = $CFG->block_summary_editors_sesskey_prefix.$sesskey;
$block_summary_session->value = time() + $CFG->block_summary_editors_sesskey_expire;

$DB->insert_record('block_summary_session',$block_summary_session);

$apiurl = $CFG->wwwroot . '/blocks/summary/api.php';
$courseurl = (new moodle_url('/course/view.php', array(
    'id' => $courseid,
)))->out();

$editors_sql = $DB->get_records_sql(
"SELECT CONCAT(u.firstname, ' ', u.lastname) as name
FROM {block_summary_editors} bse INNER JOIN {user} u ON bse.userid = u.id
WHERE bse.userid != ? AND bse.expire > ? AND bse.courseid = ?", array($USER->id, time(), $courseid));

$editors = [];
foreach($editors_sql AS $editor_sql) {
    $editors[] = $editor_sql->name;
}

$PAGE->requires->js_call_amd('block_summary/editors_token', 'init', array($courseid, $USER->id, $sesskey, $apiurl, $courseurl, $CFG->block_summary_editors_refresh_delay, $editors));

// Determine course and context
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

if ($course->format == 'flexpage')
{
	echo 'Error: flexpage course';
	die;
}

$requiredfile = null;
if($course->format == 'modular'){
    $requiredfile = $CFG->dirroot.'/blocks/summary/edit/edit_modular.php';
}else {
    $requiredfile = $CFG->dirroot.'/blocks/summary/edit/edit_thematique.php';
}

require_once($requiredfile);


// editors modal
echo 
'
<div id="dialog-editors" title="Avertissement d\'édition simultanée" style="display:none">
<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>
Attention ! Les utilisateurs suivants modifient actuellement la structure du parcours :
<div id="editors-list">
    <ul>
    </ul>
</div>
<br/>Vous risquez de perdre des données en éditant simultanément. Que voulez-vous faire ?
</p>
</div> 
';
