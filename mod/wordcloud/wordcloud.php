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

// Library of functions and constants for module wordcloud.

/**
 * @package mod_wordcloud
 * @copyright  2021 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class wordcloud {
    
    public $activity;
    public $cm;
    public $course;
    
    public const ERROR_INVALID_WORD = -4;
    public const ERROR_WORD_TOO_LONG = -5;
    public const ERROR_WORD_ALREADY_EXIST = -6;
    public const ERROR_NO_WORD_FOUND = -7;
    public const ERROR_NEW_WORD_IS_THE_SAME = -8;
    
    /***
     * 
     * @param integer $id Activity ID
     */
    function __construct($id) {
        global $DB;
        $this->activity = $DB->get_record('wordcloud', array('id'=>$id), '*', MUST_EXIST); 
        $this->cm = get_coursemodule_from_instance('wordcloud', $id, 0, false, MUST_EXIST);
        $this->course = get_course($this->activity->course);
    }
    
    function update_modifiedtime(){
        global $DB;
        $DB->execute('UPDATE {wordcloud} SET timemodified = ? WHERE id = ?',array(time(),$this->activity->id));
    }
    
    function add_word($userid, $word, $groupid = 0) {
        global $DB;
        
        $word = strtolower(trim($word));
        
        if (strlen($word) < 1) {
            return self::ERROR_INVALID_WORD;
        }
        
        if (strlen($word) > get_config('wordcloud', 'wordmaxlenght')) {
            return self::ERROR_WORD_TOO_LONG;
        }
        
        if ( $DB->record_exists_sql('SELECT * FROM {wordcloud_words} ww WHERE ww.`word` LIKE ? COLLATE utf8_bin AND ww.wcid = ? AND ww.userid = ? AND ww.groupid = ?', array($word,$this->activity->id, $userid, $groupid)) ) {
            return self::ERROR_WORD_ALREADY_EXIST;
        }
        
        $wc_word = new stdClass();
        $wc_word->wcid = $this->activity->id;
        $wc_word->userid = $userid;
        $wc_word->groupid = $groupid;
        $wc_word->word = $word;
        $wc_word->timecreated = time();
        $wc_word->timemodified = time();
        
        $return = $DB->insert_record('wordcloud_words', $wc_word, false);
        
        $this->update_modifiedtime();
        
        return $return;
    }
    
    
    function add_words($userid, array $words, $groupid = 0) {
        $return = true;
        foreach ($words AS $word) {
            $return = $return && $this->add_word($userid, $word, $groupid);
        }
        return $return;
    }
    
    function get_user_words($userid, $groupid = 0) {
        global $DB;
        
        return $DB->get_records('wordcloud_words', array('wcid'=>$this->activity->id,'userid'=>$userid,'groupid'=>$groupid));
    }
    
    
    function rename_word($oldword, $newword, $groupid = 0) {
        global $DB;
        
	    $newword = strtolower(trim($newword));
	       
	    if ($oldword == $newword) {
	        return self::ERROR_NEW_WORD_IS_THE_SAME;
	    }

        $words = $DB->get_records_sql('SELECT id, userid, word FROM {wordcloud_words} ww WHERE ww.`word` LIKE ? COLLATE utf8_bin AND ww.wcid = ? AND ww.groupid = ?', array($oldword,$this->activity->id,$groupid));
        
        if ($words === false){
            return self::ERROR_NO_WORD_FOUND;
        }
        
        if (count($words) == 0) {
            return self::ERROR_NO_WORD_FOUND;
        }
        
        foreach ($words AS $word) {
            $new_words = $DB->get_records_sql('SELECT id, word FROM {wordcloud_words} ww WHERE ww.`word` LIKE ? COLLATE utf8_bin AND ww.wcid = ? AND ww.groupid = ? AND userid = ?', array($newword,$this->activity->id,$groupid, $word->userid));
            
            if (count($new_words)>0){
                $DB->delete_records('wordcloud_words',array('id'=>$word->id));
            }else{
                $word->word = $newword;
                $DB->update_record('wordcloud_words', $word);
            }
        }
        
        $this->update_modifiedtime();
        return true;
    }
    
    function simulate_rename_word($oldword, $newword, $groupid = 0) {
        global $DB;
        
        $words = $DB->get_records_sql('SELECT id, userid, word FROM {wordcloud_words} ww WHERE ww.`word` LIKE ? COLLATE utf8_bin AND ww.wcid = ? AND ww.groupid = ?', array($oldword,$this->activity->id,$groupid));
        if ($words === false){
            return self::ERROR_NO_WORD_FOUND;
        }
        
        if (count($words) == 0) {
            return self::ERROR_NO_WORD_FOUND;
        }
        
        $current_words = $DB->get_records_sql('SELECT id, userid, word FROM {wordcloud_words} ww WHERE ww.`word` LIKE ? COLLATE utf8_bin AND ww.wcid = ? AND ww.groupid = ?', array($newword,$this->activity->id,$groupid));
        $oldweight = count($words);
        $newweight = count($current_words);
        $fusion = count($current_words)>0;
        $renamed = 0;
        $deleted = 0;
        if ($newweight > 0) {
            foreach ($words AS $word) {
                foreach($current_words AS $current_word) {
                    if ($word->userid == $current_word->userid){
                        $deleted++;
                        continue 2;
                    }
                }
                $renamed++;
            }
        }else{
            $newweight = $oldweight;
        }
        $newweight += $renamed;
        return array($fusion,$newweight,$renamed,$deleted);
    }
    
    function get_cloud_words($groupid=0) {
        global $DB;
        
        $words = $DB->get_records_sql('SELECT word, count(id) AS nb, (SELECT LENGTH(word) FROM {wordcloud_words} ww2 WHERE ww2.wcid = ww.wcid AND ww2.groupid = ww.groupid ORDER BY LENGTH(word) DESC LIMIT 1) AS maxlen FROM {wordcloud_words} ww WHERE ww.wcid = ? AND ww.groupid = ? GROUP BY ww.`word` COLLATE utf8_bin ORDER BY nb DESC, timecreated DESC LIMIT 250', array($this->activity->id,$groupid));
        
        //$word_count = count($words);
        
        $maxnb = 0;
        $minnb = 9999;
        foreach($words AS $word) {
            if (round(log1p($word->nb),2) > $maxnb){$maxnb = round(log1p($word->nb),2);}
            if (round(log1p($word->nb),2) < $minnb){$minnb = round(log1p($word->nb),2);}
        }
        
        $cwords = array();
        
        foreach($words AS $word) {
            $cword = new stdClass();
            $cword->text = $word->word;
            $cword->count = $word->nb;
            $cword->max = $word->maxlen;
            $cword->size = 20+$this->normalize(round(log1p($word->nb),2),$minnb,$maxnb)*35;
            $cwords[] = $cword;
        }
        return $cwords;
    }
    
    function normalize($value,$minvalue,$maxvalue){
        if ($minvalue == $maxvalue){return $value;}
        return ($value-$minvalue)/($maxvalue-$minvalue);
    }
    
    
    function get_cloud_users($groupid=0) {
        global $DB;
        
        $users = $DB->get_records_sql('SELECT ww.userid AS id, IFNULL(u.firstname,"Anonyme") AS firstname, IFNULL(u.lastname,"Anonyme") AS lastname FROM {wordcloud_words} ww LEFT JOIN {user} u ON (u.id = ww.userid) WHERE ww.wcid = ? AND ww.groupid = ? GROUP BY ww.userid', array($this->activity->id,$groupid));
        
        return $users;
    }
    
    function get_cloud_word_info($word,$groupid=0) {
        global $DB, $OUTPUT;
        
        $result = $DB->get_records_sql('SELECT ww.userid, ww.id, ww.userid,
IFNULL(u.firstname,"Anonyme") AS firstname,
IFNULL(u.lastname,"Anonyme") AS lastname,
(SELECT COUNT(id) FROM {wordcloud_words} ww2 WHERE ww2.wcid = ww.wcid AND ww2.word COLLATE utf8_bin = ww.word AND ww2.groupid = ww.groupid) AS nb
FROM {wordcloud_words} ww
LEFT JOIN {user} u ON (u.id = ww.userid)
WHERE ww.wcid = ?
AND ww.word COLLATE utf8_bin = ?
AND ww.groupid = ?
GROUP BY ww.userid', array($this->activity->id,$word,$groupid));
        
        
        $info = new stdClass();
        $info->word = $word;
        $info->weight = 0;
        $info->users = array();
        $info->usershtml = array();
        
        foreach ($result AS $user) {
            if ($info->weight == 0){$info->weight = $user->nb;}
            if ($user->userid>0) {
                $info->users[] = $user->firstname.' '.$user->lastname;
                $user_rec = $DB->get_record('user', array('id'=>$user->userid));
                $info->usershtml[] = $OUTPUT->user_picture($user_rec, array('size' => 35, 'courseid' => $this->course->id, 'includefullname' => true));
            }else{
                $info->usershtml[] = $info->users[] = $user->firstname.' '.$user->lastname;
            }
        }
        
        return $info;
    }
    
    
    function get_cloud_users_words($groupid=0) {
        global $DB;
        
        $words = $DB->get_records_sql('SELECT CONCAT(ww.userid,"_",ww.id) AS id, CONCAT(IFNULL(u.firstname,"Anonyme")," ",IFNULL(u.lastname,"Anonyme")) AS user, ww.word, ww.timecreated FROM {wordcloud_words} ww LEFT JOIN {user} u ON (u.id = ww.userid) WHERE ww.wcid = ? AND ww.groupid = ? ORDER BY ww.word ASC, u.lastname ASC', array($this->activity->id,$groupid));
        
        return $words;
    }
    
    
    function remove_word($word,$groupid=0) {
        global $DB;
        
        $words = $DB->get_records_sql('SELECT * FROM {wordcloud_words} ww WHERE ww.wcid = ? AND ww.groupid = ? AND ww.word COLLATE utf8_bin = ?', array($this->activity->id,$groupid,$word));
        
        $DB->execute('DELETE FROM {wordcloud_words} WHERE wcid = ? AND groupid = ? AND word COLLATE utf8_bin = ?', array($this->activity->id,$groupid,$word));
        
        $this->update_modifiedtime();
        
        return count($words);
    }
    
    
    
    function is_started() {
        return $this->activity->timestart==0 || ($this->activity->timestart>0 && time() > $this->activity->timestart);
    }
    
    function has_ended() {
        return $this->activity->timeend>0 && time() > $this->activity->timeend;
    }
    
    
    function get_name(){
        return $this->activity->name;
    }
    
    function get_intro(){
        return format_module_intro('wordcloud',$this->activity,$this->cm->id);
    }
    
    function get_instructions(){
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');
        $context = context_module::instance($this->cm->id);
        $options = array('noclean' => true, 'para' => false, 'filter' => true, 'context' => $context, 'overflowdiv' => true);
        $instructions = file_rewrite_pluginfile_urls($this->activity->instructions, 'pluginfile.php', $context->id, 'mod_wordcloud', 'instructions', null);
        return trim(format_text($instructions, $this->activity->introformat, $options, null));
    }   
    
    function is_editor(){
        $cmcontext = context_module::instance($this->cm->id);
        return has_capability('mod/wordcloud:manageword', $cmcontext);
    }
    
    function get_user_groups(){
        
        $course_groups = groups_get_all_groups($this->cm->course,0,$this->cm->groupingid);
        
        if ($this->course->groupmodeforce == 1) {
            $this->cm->groupmode = $this->course->groupmode;
        }
        
        if ($this->cm->groupmode > 0)
        {
            $can_viewallgroups = false;
            if ( $this->cm->groupmode == 2 ) {$can_viewallgroups=true;}
            
            $cmcontext = context_module::instance($this->cm->id);
            if ($this->is_editor() && has_capability('moodle/site:accessallgroups', $cmcontext)){
                $can_viewallgroups = true;
            }
            
            // ##### Group management
            $user_group = false;
            $user_groups = groups_get_user_groups($this->course->id);
            
            // The user is member of at least one group of the grouping. We select the first
            if (count($user_groups[$this->cm->groupingid]) > 0)
            {
                $user_group = array_values($user_groups[$this->cm->groupingid])[0];
            }else if ($can_viewallgroups) // The user is not a member of a group but can see all groups of the grouping. We select the first
            {
                $user_group = array_values($course_groups)[0]->id;
            }
            
            
            // If no groups have been found.
            // The use has nothing to do here
            if ($user_group == false)
            {
                return array();
            }
            
            $available_groups_m = $available_groups_nm = array();
            foreach($course_groups as $key=>$group)
            {
                if ( in_array($group->id,$user_groups[$this->cm->groupingid]))
                {
                    $group->member = true;
                    $available_groups_m[] = $group;
                }
                else{
                    $group->member = false;
                    $available_groups_nm[] = $group;
                }
                
            }
            
            // Sort the list by membership and name
            function cmp($a, $b)
            {
                return strcmp($a->name, $b->name);
            }
            
            usort($available_groups_m, "cmp");
            $available_groups = $available_groups_m;
            usort($available_groups_nm, "cmp");
            if ($can_viewallgroups && count($available_groups_nm) > 0)
            {
                if (count($available_groups_m) > 0)
                {
                    $available_groups = array_merge($available_groups_m,$available_groups_nm);
                }else{
                    $available_groups = $available_groups_nm;
                }
            }
            
            return $available_groups;
        }
        return array();
    }
    
    function reset_cloud(){
        global $DB;
        $DB->delete_records('wordcloud_words', array('wcid'=>$this->activity->id));
    }
    
    
}





































