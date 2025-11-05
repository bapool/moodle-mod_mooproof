<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
    
    return true;
}
