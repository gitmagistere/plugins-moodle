<?php
/**
 * @package    mod_coursebadges
 * @subpackage backup-moodle2
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/coursebadges/backup/moodle2/restore_coursebadges_stepslib.php'); // Because it exists (must)

/**
 * coursebadges restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_coursebadges_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_coursebadges_activity_structure_step('coursebadges_structure', 'coursebadges.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('coursebadges', ['intro'], 'coursebadges');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('COURSEBADGESVIEWBYID', '/mod/coursebadges/view.php?id=$1', 'course_module');

        return $rules;

    }

}