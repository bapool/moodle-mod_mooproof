<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
/**
 * Privacy Subsystem implementation for mod_mooproof
 *
 * @package    mod_mooproof
 * @copyright  2026 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_mooproof_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    // Add chatmessagelimit field (version 2025110401)
    if ($oldversion < 2025110401) {
        $table = new xmldb_table('mooproof');
        $field = new xmldb_field('chatmessagelimit', XMLDB_TYPE_INTEGER, '10', null, 
                                  XMLDB_NOTNULL, null, '10', 'maxwords');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_mod_savepoint(true, 2025110401, 'mooproof');
    }

    // Add proofinstructionsformat field to support the Moodle editor (version 2026012801).
    if ($oldversion < 2026012801) {
        $table = new xmldb_table('mooproof');
        $field = new xmldb_field('proofinstructionsformat', XMLDB_TYPE_INTEGER, '4', null,
                                  XMLDB_NOTNULL, null, '1', 'proofinstructions');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026012801, 'mooproof');
    }

    // Add mooproof_chat_messages table for teacher history feature (version 2026012802).
    if ($oldversion < 2026012802) {
        $table = new xmldb_table('mooproof_chat_messages');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('submissionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('mooproofid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('role', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('submissionid', XMLDB_KEY_FOREIGN, ['submissionid'], 'mooproof_submissions', ['id']);
            $table->add_key('mooproofid', XMLDB_KEY_FOREIGN, ['mooproofid'], 'mooproof', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->add_index('submissionid-userid', XMLDB_INDEX_NOTUNIQUE, ['submissionid', 'userid']);

            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026012802, 'mooproof');
    }
    
    // Add completion field (version 2026022702)
    if ($oldversion < 2026022702) {
        $table = new xmldb_table('mooproof');
        $field = new xmldb_field('completionsubmit', XMLDB_TYPE_INTEGER, '1', null, 
                                  XMLDB_NOTNULL, null, '0', 'chatmessagelimit');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_mod_savepoint(true, 2026022702, 'mooproof');
    }
    
    return true;
}
