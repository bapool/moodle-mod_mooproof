<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

declare(strict_types=1);

namespace mod_mooproof\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the mooproof activity.
 *
 * Class for defining mod_mooproof's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given mooproof instance and a user.
 *
 * @package    mod_mooproof
 * @copyright  2026 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $userid = $this->userid;
        $cm = $this->cm;

        // Get mooproof instance
        $mooproof = $DB->get_record('mooproof', ['id' => $cm->instance], '*', MUST_EXIST);

        if ($rule === 'completionsubmit') {
            // Check if user has submitted a paper to this mooproof instance
            $submitted = $DB->record_exists('mooproof_submissions', [
                'mooproofid' => $mooproof->id,
                'userid' => $userid,
            ]);
            return $submitted ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }

        return COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionsubmit'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionsubmit' => get_string('completionsubmit', 'mod_mooproof'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionsubmit',
        ];
    }
}
