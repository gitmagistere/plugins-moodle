<?php

function xmldb_local_coursehub_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if($oldversion < 2020012000) {
        // Define table local_coursehub_course to be created
        $table = new xmldb_table('local_coursehub_course');
        
        if (!$dbman->field_exists($table->getName(), 'summary')) {
            $dbman->add_field($table, new xmldb_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null, 'shortname'));
        }
        upgrade_plugin_savepoint(true, 2020012000, 'local', 'coursehub');
    }
    
    if($oldversion < 2020040100) {
        // Define table local_coursehub_course to be created
        $table = new xmldb_table('local_coursehub_course');
        
        if (!$dbman->field_exists($table->getName(), 'isalocalsession')) {
            $dbman->add_field($table, new xmldb_field('isalocalsession', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'isasession'));
        }
        upgrade_plugin_savepoint(true, 2020040100, 'local', 'coursehub');
    }
    
    if($oldversion < 2020091000) {
        // Define table local_coursehub_course to be created
        $table = new xmldb_table('local_coursehub_slave');
        
        if (!$dbman->field_exists($table->getName(), 'mastertoken')) {
            $dbman->add_field($table, new xmldb_field('mastertoken', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, '0', 'token'));
        }
        upgrade_plugin_savepoint(true, 2020091000, 'local', 'coursehub');
    }
    
    
    return true;
}