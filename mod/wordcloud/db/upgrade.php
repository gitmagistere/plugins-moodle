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
 * @package    mod_wordcloud
 * @copyright  2021 TCS
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_wordcloud_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2021030403) {
        $dbman = $DB->get_manager();
        
        // Add column
        $table = new xmldb_table('wordcloud');
        $field = new xmldb_field('wordsallowed', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, 0, 'instructions');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // wordcloud savepoint reached.
        upgrade_mod_savepoint(true, 2021030403, 'wordcloud');
    }
    if ($oldversion < 2021042302) {
        $dbman = $DB->get_manager();
        
        $table = new xmldb_table('wordcloud');
        $field = new xmldb_field('wordmaxlenght', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, 0);
        
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // wordcloud savepoint reached.
        upgrade_mod_savepoint(true, 2021042302, 'wordcloud');
    }

    return true;
}
