<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Scheduled task to clean up old MooProof submissions
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooproof\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Cleanup old submissions task
 *
 * Deletes submissions older than 60 days to maintain privacy and reduce database size.
 * Also cleans up orphaned records from deleted MooProof resources.
 */
class cleanup_old_submissions extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanupoldsubmissions', 'mod_mooproof');
    }

    /**
     * Execute the cleanup task
     */
    public function execute() {
        global $DB;

        // 60 days ago in Unix timestamp
        $retention_days = 60;
        $cutoff_time = time() - ($retention_days * 24 * 60 * 60);

        mtrace('Starting MooProof submissions cleanup...');
        mtrace('Deleting submissions older than ' . $retention_days . ' days (before ' . 
               userdate($cutoff_time) . ')');

        // Delete old submissions
        $old_count = $DB->count_records_select('mooproof_submissions', 
            'timecreated < :cutoff', ['cutoff' => $cutoff_time]);

        if ($old_count > 0) {
            $DB->delete_records_select('mooproof_submissions', 
                'timecreated < :cutoff', ['cutoff' => $cutoff_time]);
            mtrace("Deleted {$old_count} old submission(s)");
        } else {
            mtrace('No old submissions to delete');
        }

        // Clean up orphaned submissions (where mooproof resource was deleted)
        $sql = "SELECT ms.id
                  FROM {mooproof_submissions} ms
             LEFT JOIN {mooproof} m ON m.id = ms.mooproofid
                 WHERE m.id IS NULL";

        $orphaned_ids = $DB->get_fieldset_sql($sql);
        $orphaned_count = count($orphaned_ids);

        if ($orphaned_count > 0) {
            list($insql, $params) = $DB->get_in_or_equal($orphaned_ids);
            $DB->delete_records_select('mooproof_submissions', "id $insql", $params);
            mtrace("Deleted {$orphaned_count} orphaned submission(s) from deleted resources");
        } else {
            mtrace('No orphaned submissions to delete');
        }

        // Clean up orphaned usage records (where mooproof resource was deleted)
        $sql = "SELECT mu.id
                  FROM {mooproof_usage} mu
             LEFT JOIN {mooproof} m ON m.id = mu.mooproofid
                 WHERE m.id IS NULL";

        $orphaned_usage_ids = $DB->get_fieldset_sql($sql);
        $orphaned_usage_count = count($orphaned_usage_ids);

        if ($orphaned_usage_count > 0) {
            list($insql, $params) = $DB->get_in_or_equal($orphaned_usage_ids);
            $DB->delete_records_select('mooproof_usage', "id $insql", $params);
            mtrace("Deleted {$orphaned_usage_count} orphaned usage record(s) from deleted resources");
        } else {
            mtrace('No orphaned usage records to delete');
        }

        mtrace('MooProof cleanup complete');
    }
}
