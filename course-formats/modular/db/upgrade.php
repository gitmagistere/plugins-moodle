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

defined('MOODLE_INTERNAL') || die;

function xmldb_format_modular_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    /*
     * update parenid to the num section instead of the real id (for backup / restore support)
    */
/*
    if ($oldversion < 2018100501) {

        $parentidtoupgrade = $DB->get_records_sql('SELECT
cfo.*,
cs.section as parentidsecnum
FROM mdl_course_format_options cfo
INNER JOIN mdl_course_sections cs ON cs.id=cfo.value
WHERE cfo.format="modular" AND cfo.name="parentid" AND cfo.value > 200');

            foreach ($parentidtoupgrade as $section) {
                $section->value = $section->parentidsecnum;
                $DB->update_record('course_format_options', $section);
            }

            upgrade_plugin_savepoint(true, 2018100501, 'format', 'modular');
    }
*/

    /*
     * Add the new attribute 'hasNavigation'
     */
    if ($oldversion < 2019082901) {

        $sectionToUpdate = $DB->get_recordset_sql('SELECT cfo.id, cfo.courseid, cfo.sectionid, cfo.format
FROM mdl_course_format_options cfo
WHERE cfo.format="modular"
GROUP BY cfo.courseid, cfo.sectionid');

        $objectToInsert = array();
        $i = 0;

        foreach ($sectionToUpdate as $section) {
            unset($section->id);
            $section->name = 'hasNavigation';
            $section->value = 1;

            $objectToInsert[] = $section;
            $i++;

            if($i > 1000){
                $DB->insert_records('course_format_options', $objectToInsert);
                $objectToInsert = array();
                $i = 0;
            }
        }

        if(count($objectToInsert)){
            $DB->insert_records('course_format_options', $objectToInsert);
        }

        upgrade_plugin_savepoint(true, 2019082901, 'format', 'modular');
    }
    if($oldversion < 2020061500){

        // Define table block_summary_editors to be created
        $table = new xmldb_table('format_modular_bck');
        // Adding fields to table block_summary_editors
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('blockinstanceid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_summary_editors
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // progress savepoint reached
        upgrade_plugin_savepoint(true, 2020061500, 'format', 'modular');
    }
    return true;
}
