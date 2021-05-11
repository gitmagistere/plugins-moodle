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
 *
 * Provides some custom settings for the wordcloud module
 *
 * @package    mod_wordcloud
 * @copyright  2021 TCS
 *
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('wordcloud_config',
        '<strong>'.get_string('pluginconfig', 'wordcloud').'</strong>', ''));

    $settings->add(new admin_setting_configtext('wordcloud/wordmaxlenght', get_string('wordmaxlenght', 'wordcloud'),
        get_string('wordmaxlenghtsetting', 'wordcloud'), 30, PARAM_INT));

    $settings->add(new admin_setting_configtext('wordcloud/maxwordsallowed', get_string('maxwordsallowed', 'wordcloud'),
        get_string('maxwordsallowedsetting', 'wordcloud'), 10, PARAM_INT));

}
