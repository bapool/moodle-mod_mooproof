<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore activity task for mod_mooproof
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mooproof/backup/moodle2/restore_mooproof_stepslib.php');
require_once($CFG->dirroot . '/mod/mooproof/backup/moodle2/restore_mooproof_settingslib.php');

/**
 * Restore task for the mooproof activity module
 *
 * Provides all the settings and steps to perform complete restore of the activity.
 */
class restore_mooproof_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // MooProof only has one structure step
        $this->add_step(new restore_mooproof_activity_structure_step('mooproof_structure', 'mooproof.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('mooproof', array('intro'), 'mooproof');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('MOOPROOFVIEWBYID', '/mod/mooproof/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('MOOPROOFINDEX', '/mod/mooproof/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * mooproof logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('mooproof', 'add', 'view.php?id={course_module}', '{mooproof}');
        $rules[] = new restore_log_rule('mooproof', 'update', 'view.php?id={course_module}', '{mooproof}');
        $rules[] = new restore_log_rule('mooproof', 'view', 'view.php?id={course_module}', '{mooproof}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('mooproof', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
