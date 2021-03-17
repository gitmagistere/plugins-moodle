<?php

/**
 * Privacy Subsystem implementation for mod_completionmarker.
 *
 * @package    mod_completionmarker
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_completionmarker\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_completionmarker module does not store any data.
 *
 * @copyright  2020 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}
