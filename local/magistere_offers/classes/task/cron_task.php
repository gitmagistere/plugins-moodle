<?php
/**
 * A scheduled task for Magistere Offers cron.
 *
 *
 * @package    local_magistere_offers
 * @copyright  2019 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_magistere_offers\task;

class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'local_magistere_offers');
    }

    /**
     * Run forum cron.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/magistere_offers/lib.php');
        $task = new \NotificationNewCourses("course");
        $task->notification_cron();

        $task = new \NotificationNewCourses("formation");
        $task->notification_cron();
    }

}