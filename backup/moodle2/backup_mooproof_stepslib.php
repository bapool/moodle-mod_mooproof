<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup steps for mod_mooproof
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete mooproof structure for backup, with file and id annotations
 */
class backup_mooproof_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Define each element separated
        $mooproof = new backup_nested_element('mooproof', array('id'), array(
            'name', 'intro', 'introformat', 'gradelevel', 'proofinstructions',
            'ratelimit_enable', 'ratelimit_period', 'ratelimit_count',
            'maxwords', 'chatmessagelimit', 'temperature',
            'timecreated', 'timemodified'));

        $usages = new backup_nested_element('usages');

        $usage = new backup_nested_element('usage', array('id'), array(
            'userid', 'submissioncount', 'firstsubmission', 'lastsubmission'));

        $submissions = new backup_nested_element('submissions');

        $submission = new backup_nested_element('submission', array('id'), array(
            'userid', 'papertext', 'feedback', 'filename', 'wordcount',
            'gradelevel', 'timecreated'));

        // Build the tree
        $mooproof->add_child($usages);
        $usages->add_child($usage);

        $mooproof->add_child($submissions);
        $submissions->add_child($submission);

        // Define sources
        $mooproof->set_source_table('mooproof', array('id' => backup::VAR_ACTIVITYID));

        $usage->set_source_table('mooproof_usage', array('mooproofid' => backup::VAR_PARENTID));

        $submission->set_source_table('mooproof_submissions', array('mooproofid' => backup::VAR_PARENTID));

        // Define id annotations
        $usage->annotate_ids('user', 'userid');
        $submission->annotate_ids('user', 'userid');

        // Define file annotations
        // MooProof doesn't store files in file areas, so no file annotations needed

        // Return the root element (mooproof), wrapped into standard activity structure
        return $this->prepare_activity_structure($mooproof);
    }
}
