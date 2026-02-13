<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup activity task for mod_mooproof
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mooproof/backup/moodle2/backup_mooproof_stepslib.php');
require_once($CFG->dirroot . '/mod/mooproof/backup/moodle2/backup_mooproof_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the MooProof instance
 */
class backup_mooproof_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the mooproof.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_mooproof_activity_structure_step('mooproof_structure', 'mooproof.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually has URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of mooproofs
        $search = "/(" . $base . "\/mod\/mooproof\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MOOPROOFINDEX*$2@$', $content);

        // Link to mooproof view by moduleid
        $search = "/(" . $base . "\/mod\/mooproof\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MOOPROOFVIEWBYID*$2@$', $content);

        return $content;
    }
}
