<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
/**
 * 
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add mooproof instance
 */
function mooproof_add_instance($mooproof) {
    global $DB;
    
    $mooproof->timecreated = time();
    $mooproof->timemodified = time();
    
    $mooproof->id = $DB->insert_record('mooproof', $mooproof);
    
    return $mooproof->id;
}

/**
 * Update mooproof instance
 */
function mooproof_update_instance($mooproof) {
    global $DB;
    
    $mooproof->timemodified = time();
    $mooproof->id = $mooproof->instance;
    
    return $DB->update_record('mooproof', $mooproof);
}

/**
 * Delete mooproof instance
 */
function mooproof_delete_instance($id) {
    global $DB;
    
    if (!$mooproof = $DB->get_record('mooproof', array('id' => $id))) {
        return false;
    }
    
    // Delete usage records
    $DB->delete_records('mooproof_usage', array('mooproofid' => $id));
    
    // Delete submission records
    $DB->delete_records('mooproof_submissions', array('mooproofid' => $id));
    
    // Delete the instance
    $DB->delete_records('mooproof', array('id' => $id));
    
    return true;
}

/**
 * Supported features
 */
function mooproof_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        default:
            return null;
    }
}

/**
 * Serve the files from the mooproof file areas
 */
function mod_mooproof_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    
    if ($filearea !== 'submission') {
        return false;
    }
    
    require_login($course, false, $cm);
    
    $fs = get_file_storage();
    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = '/';
    
    $file = $fs->get_file($context->id, 'mod_mooproof', $filearea, $itemid, $filepath, $filename);
    
    if (!$file || $file->is_directory()) {
        return false;
    }
    
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
