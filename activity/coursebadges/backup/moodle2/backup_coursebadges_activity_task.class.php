<?php
/**
 * Defines backup_coursebadges_activity_task class
 *
 * @package     mod_coursebadges
 * @category    backup
 * @copyright   TCS
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/coursebadges/backup/moodle2/backup_coursebadges_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the coursebadges instance
 */
class backup_coursebadges_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the forum.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_coursebadges_activity_structure_step('coursebadges structure', 'coursebadges.xml'));
    }

    /**
     * Encodes URLs to the view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to coursebadges view by moduleid
        $search="/(".$base."\/mod\/coursebadges\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@COURSEBADGESVIEWBYID*$2@$', $content);

        return $content;
    }
}
