<?php

/**
 * Definition of Magistere Offers scheduled tasks.
 *
 * @package   local_magistere_offers
 * @category  task
 * @copyright 2019 TCS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_magistere_offers\task\cron_task',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '3'
    )
);
