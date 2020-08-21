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
 * format_magistere_topics
 *
 * @package    format_magistere_topics
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2013 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/format/magistere_topics/lib.php'); // For format_grid static constants.

if ($ADMIN->fulltree) {

    // Container alignment.
    $name = 'magistere_topics/canrestrictblocktosection';
    $title = get_string('canrestrictblocktosection', 'format_magistere_topics');
    $description = get_string('canrestrictblocktosection_desc', 'format_magistere_topics');
    $default = false;
    $settings->add(new admin_setting_configcheckbox($name, $title, $description, $default));
}
