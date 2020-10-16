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

namespace local_coursehub\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider .
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,

    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table(
            'local_coursehub_course',
            [
                'username' => 'privacy:metadata:username',
                'firstname' => 'privacy:metadata:firstname',
                'lastname' => 'privacy:metadata:lastname',
                'email' => 'privacy:metadata:email',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:local_coursehub_course'
        );

        $items->add_database_table(
            'local_coursehub_published',
            [
                'userid' => 'privacy:metadata:username',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:local_coursehub_published'
        );


        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $CFG,$DB;
        $contextlist = new contextlist();

        require_once ($CFG->dirroot."/local/coursehub/CourseHub.php");
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

        $hub = \CourseHub::instance();
        if($hub->isNoConfig()){
            return;
        }
        if($hub->isMaster()){
            $aca = $CFG->academie_name;
        }else{
            $aca = $hub->getMaster();
        }

        if(array_key_exists($aca,get_magistere_academy_config())) {
            $user = $DB->get_record("user",["id"=> $userid]);

            if($user){
                $existuser = \databaseConnection::instance()->get($aca)->execute(
                    'SELECT lcc.username FROM {local_coursehub_course} lcc
                WHERE  lcc.username = :username',
                    ['username' => $user->username]
                );

                if($existuser){
                    $contextlist->add_user_context($userid);
                }
            }

        }



        $sql = "
               SELECT ctx.id FROM {local_coursehub_published} lcp
               JOIN {context} ctx ON ctx.instanceid =  lcp.userid AND ctx.contextlevel = :userlevel
               WHERE lcp.userid = :userid";
        $params = [
            'userid' => $userid,
            'userlevel' => CONTEXT_USER,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $CFG,$DB;
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            require_once ($CFG->dirroot."/local/coursehub/CourseHub.php");
            require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

            $hub = \CourseHub::instance();
            if($hub->isNoConfig()){
                return;
            }
            if($hub->isMaster()){
                $aca = $CFG->academie_name;
            }else{
                $aca = $hub->getMaster();
            }

            if(array_key_exists($aca,get_magistere_academy_config())) {
                $user = $DB->get_record("user",["id"=> $context->instanceid]);

                $existusers = \databaseConnection::instance()->get($aca)->execute(
                    'SELECT lcc.username FROM {local_coursehub_course} lcc
                    WHERE  lcc.username = :username',
                    ['username' => $user->username]
                );

                if(count($existusers) > 0){
                    $userlist->add_user($user->id);
                }
            }

            $sql = "
                SELECT lcp.userid
                FROM  {local_coursehub_published} lcp
                WHERE lcp.userid = :uid";

            $params = [
                'uid'      => $context->instanceid,
            ];

            $userlist->add_from_sql('userid', $sql, $params);

        }
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB,$CFG;

        $userid = $contextlist->get_user()->id;
        $username = $contextlist->get_user()->username;
        $context = \context_user::instance($userid);

        require_once ($CFG->dirroot."/local/coursehub/CourseHub.php");
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

        $hub = \CourseHub::instance();
        if($hub->isNoConfig()){
            return;
        }
        if($hub->isMaster()){
            $aca = $CFG->academie_name;
        }else{
            $aca = $hub->getMaster();
        }
        if(array_key_exists($aca,get_magistere_academy_config())) {
            $t_user_rne = \databaseConnection::instance()->get($aca)->get_records('local_coursehub_course', ['username' => $username]);
            if ($t_user_rne) {
                $t_user_rnedata = [];
                foreach ($t_user_rne as $record) {
                    $t_user_rnedata[] = (object) [
                        'username' => $record->username,
                        'firstname' => $record->firstname,
                        'lastname' => $record->lastname,
                        'email' => $record->email,
                        'timecreated' => $record->timecreated,
                        'timemodified' => $record->timemodified,
                    ];
                }
                writer::with_context($context)->export_data([get_string('pluginname', 'local_coursehub'),get_string('privacy:metadata:local_coursehub_course', 'local_coursehub')], (object) $t_user_rnedata);
            }
        }


        $progress_complete = $DB->get_records('local_coursehub_published', ['userid' => $userid]);
        // Get the user's contacts.
        if ($progress_complete) {
            $progress_completedata = [];
            foreach ($progress_complete as $record) {
                $progress_completedata[] = (object) [
                    'userid' => $record->userid,
                    'timecreated' => $record->timecreated,
                    'timemodified' => $record->timemodified
                ];
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_coursehub'),get_string('privacy:metadata:local_coursehub_published', 'local_coursehub')], (object) $progress_completedata);
        }
    }

    protected static function anonymeUser($userid,$username,$aca){
        $params = [
            "username" => $username,
            "newusername" => get_config("tool_dataprivacy",'anonymoususername')."_".$userid,
            "newfirstname" => get_config("tool_dataprivacy",'anonymousfirstname')."_".$userid,
            "newlastname" => get_config("tool_dataprivacy",'anonymouslastname')."_".$userid,
            "newemail" => get_config("tool_dataprivacy",'anonymousemail')."_".$userid."@".get_config("tool_dataprivacy",'anonymousemail').".lan"

        ];
        \databaseConnection::instance()->get($aca)->execute("UPDATE {local_coursehub_course} SET username = :newusername, firstname = :newfirstname, lastname = :newlastname, email = :newemail WHERE  username = :username ",$params);

    }
    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        global $DB,$CFG;

        if (!$context instanceof \context_user) {
            return;
        }

        require_once ($CFG->dirroot."/local/coursehub/CourseHub.php");
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

        $hub = \CourseHub::instance();
        if($hub->isNoConfig()){
            return;
        }

        if($hub->isMaster()){
            $aca = $CFG->academie_name;
        }else{
            $aca = $hub->getMaster();
        }

        $user = $DB->get_record('user',["id" => $context->instanceid]);
        if($user){
            static::anonymeUser($user->id,$user->username,$aca);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $CFG;

        if (empty($contextlist->count())) {
            return;
        }

        require_once ($CFG->dirroot."/local/coursehub/CourseHub.php");
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

        $hub = \CourseHub::instance();
        if($hub->isNoConfig()){
            return;
        }

        if($hub->isMaster()){
            $aca = $CFG->academie_name;
        }else{
            $aca = $hub->getMaster();
        }

        if(array_key_exists($aca,get_magistere_academy_config())){
            $username = $contextlist->get_user()->username;
            $userid = $contextlist->get_user()->id;

            static::anonymeUser($userid,$username,$aca);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        global $DB,$CFG;

        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        require_once ($CFG->dirroot."/local/coursehub/CourseHub.php");
        require_once($CFG->dirroot.'/local/magisterelib/databaseConnection.php');

        $hub = \CourseHub::instance();
        if($hub->isNoConfig()){
            return;
        }

        if($hub->isMaster()){
            $aca = $CFG->academie_name;
        }else{
            $aca = $hub->getMaster();
        }

        if(array_key_exists($aca,get_magistere_academy_config())){
            $userids = $userlist->get_userids();

            foreach ($userids as $userid) {
                $user = $DB->get_record('user',["id" => $userid]);

                static::anonymeUser($user->id,$user->username,$aca);

            }
        }


    }
}
