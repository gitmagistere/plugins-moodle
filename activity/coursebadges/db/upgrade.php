<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_coursebadges_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2020020402) {

        // Define field deleted to be added to forum_posts.
        $table = new xmldb_table('coursebadges');
        $field = new xmldb_field('completionvalidatedbadges', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'showawardedresults');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Forum savepoint reached.
        upgrade_mod_savepoint(true, 2020020402, 'coursebadges');
    }

    if ($oldversion < 2020020404) {
        // Conditionally launch add field deleted.
        $table = new xmldb_table('cb_selection_badges');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10');

        $index = new xmldb_index('userid');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_index($table, $index);
            $dbman->drop_field($table, $field);

            // Forum savepoint reached.
            upgrade_mod_savepoint(true, 2020020404, 'coursebadges');
        }
    }
    
    if ($oldversion < 2020020406) {
        $table = new xmldb_table('cb_notif_for_badges');
        $dbman->rename_table($table, "coursebadges_notification");
        
        $table = new xmldb_table('cb_selection_badges');
        $dbman->rename_table($table, "coursebadges_available_bdg");
        
        $table = new xmldb_table('cb_user_choices');
        $dbman->rename_table($table, "coursebadges_usr_select_bdg");
        
        upgrade_mod_savepoint(true, 2020020406, 'coursebadges');
    }

    if ($oldversion < 2020020407) {

        // Define field deleted to be added to forum_posts.
        $table = new xmldb_table('coursebadges');
        $field = new xmldb_field('completionvalidatedbadges', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'showawardedresults');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Forum savepoint reached.
        upgrade_mod_savepoint(true, 2020020407, 'coursebadges');
    }

    return true;
}
