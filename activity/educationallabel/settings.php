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
 * Resource module admin settings and defaults
 *
 * @package    mod_educationallabel
 * @copyright  2017 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('educationallabel/dndmedia',
        get_string('dndmedia', 'mod_educationallabel'), get_string('configdndmedia', 'mod_educationallabel'), 1));

    $settings->add(new admin_setting_configtext('educationallabel/dndresizewidth',
        get_string('dndresizewidth', 'mod_educationallabel'), get_string('configdndresizewidth', 'mod_educationallabel'), 400, PARAM_INT, 6));

    $settings->add(new admin_setting_configtext('educationallabel/dndresizeheight',
        get_string('dndresizeheight', 'mod_educationallabel'), get_string('configdndresizeheight', 'mod_educationallabel'), 400, PARAM_INT, 6));
}
