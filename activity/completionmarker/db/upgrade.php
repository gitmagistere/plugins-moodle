<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_completionmarker_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if($oldversion < 2019121200){
        $table = new xmldb_table('completionmarker');

        // Adding field to table choicegroup
        $newField = $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $newField)) {
            $dbman->add_field($table, $newField);
        }

        upgrade_mod_savepoint(true, 2019121200, 'completionmarker');
    }


    return true;
}
