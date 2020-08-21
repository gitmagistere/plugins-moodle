<?php

function xmldb_block_summary_upgrade($oldversion=0) {
    global $DB;

    $dbman  = $DB->get_manager();
    $result = true;

    
    
    if ($oldversion < 2017060700)
    {

        // Define table progress_activities to be created
        $table = new xmldb_table('block_summary');

        // Adding fields to table progress_activities
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('parentid', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
		$table->add_field('weight', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        
        // Adding keys to table progress_activities
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('unique_cw', XMLDB_KEY_UNIQUE, array('courseid', 'weight'));

        // Conditionally launch create table for progress_activities
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // progress savepoint reached
        upgrade_block_savepoint(true, 2017060700, 'summary');
    }
    
    if ($oldversion < 2020050700)
    {
        
        // Define table block_summary_editors to be created
        $table = new xmldb_table('block_summary_editors');
        
        // Adding fields to table block_summary_editors
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expire', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        
        
        // Adding keys to table block_summary_editors
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid_userid', XMLDB_KEY_UNIQUE, array('courseid', 'userid'));
        
        // Conditionally launch create table for block_summary_editors
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // progress savepoint reached
        upgrade_block_savepoint(true, 2020050700, 'summary');
    }

    if ($oldversion < 2020061202){
        // Define table block_summary_editors to be created
        $table = new xmldb_table('block_summary_session');
        // Adding fields to table block_summary_editors
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('token', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_summary_editors
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // progress savepoint reached
        upgrade_block_savepoint(true, 2020061202, 'summary');
    }

    return $result;
}