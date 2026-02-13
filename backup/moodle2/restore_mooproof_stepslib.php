<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore steps for mod_mooproof
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one mooproof activity
 */
class restore_mooproof_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of restore_path_element
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('mooproof', '/activity/mooproof');

        if ($userinfo) {
            $paths[] = new restore_path_element('mooproof_usage', '/activity/mooproof/usages/usage');
            $paths[] = new restore_path_element('mooproof_submission', '/activity/mooproof/submissions/submission');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the mooproof restore
     *
     * @param array $data the data in object form
     */
    protected function process_mooproof($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the mooproof record
        $newitemid = $DB->insert_record('mooproof', $data);
        // Immediately after inserting record, call this
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process the mooproof_usage restore
     *
     * @param array $data the data in object form
     */
    protected function process_mooproof_usage($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mooproofid = $this->get_new_parentid('mooproof');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->firstsubmission = $this->apply_date_offset($data->firstsubmission);
        $data->lastsubmission = $this->apply_date_offset($data->lastsubmission);

        $newitemid = $DB->insert_record('mooproof_usage', $data);
        // No need to save this mapping as usage records are not referenced by id elsewhere
    }

    /**
     * Process the mooproof_submission restore
     *
     * @param array $data the data in object form
     */
    protected function process_mooproof_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mooproofid = $this->get_new_parentid('mooproof');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('mooproof_submissions', $data);
        // No need to save this mapping as submission records are not referenced by id elsewhere
    }

    /**
     * Once the database tables have been fully restored, restore the files
     */
    protected function after_execute() {
        // Add mooproof related files, no need to match by itemname (just internally handled context)
        // MooProof doesn't use file storage areas, so nothing to restore here
    }
}
