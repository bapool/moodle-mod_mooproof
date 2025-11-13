<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External service for submitting papers for proofreading.
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooproof\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;

/**
 * External service for submitting papers.
 */
class submit_paper extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'mooproofid' => new external_value(PARAM_INT, 'MooProof instance ID'),
            'papertext' => new external_value(PARAM_RAW, 'Paper text or file content'),
            'filename' => new external_value(PARAM_TEXT, 'Filename if uploaded', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Submit paper for proofreading.
     *
     * @param int $mooproofid MooProof instance ID
     * @param string $papertext Paper text
     * @param string $filename Optional filename
     * @return array Result data
     */
    public static function execute($mooproofid, $papertext, $filename = '') {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'mooproofid' => $mooproofid,
            'papertext' => $papertext,
            'filename' => $filename,
        ]);

        // Get mooproof instance.
        $mooproof = $DB->get_record('mooproof', ['id' => $params['mooproofid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mooproof', $params['mooproofid'], $mooproof->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Validate context and check capability.
        self::validate_context($context);
        require_capability('mod/mooproof:submit', $context);

        // Handle file parsing if filename provided.
        if (!empty($params['filename'])) {
            $ext = strtolower(pathinfo($params['filename'], PATHINFO_EXTENSION));

            if ($ext !== 'txt') {
                $papertext = base64_decode($params['papertext']);
                if ($papertext === false) {
                    return [
                        'success' => false,
                        'error' => 'Failed to decode uploaded file',
                        'feedback' => '',
                        'remaining' => -1,
                        'wordcount' => 0,
                    ];
                }
            } else {
                $papertext = $params['papertext'];
            }

            $tempfile = tempnam(sys_get_temp_dir(), 'mooproof_');
            file_put_contents($tempfile, $papertext);

            try {
                $papertext = \mod_mooproof\document_parser::extract_text($tempfile, $params['filename']);
            } catch (\Exception $e) {
                @unlink($tempfile);
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'feedback' => '',
                    'remaining' => -1,
                    'wordcount' => 0,
                ];
            }

            @unlink($tempfile);
        } else {
            $papertext = $params['papertext'];
        }

        // Automatic cleanup.
        $cleanuptime = time() - (7 * 86400);
        $DB->delete_records_select('mooproof_usage', 'lastsubmission < ?', [$cleanuptime]);

        // Check rate limiting.
        $remaining = -1;
        if ($mooproof->ratelimit_enable) {
            $usage = $DB->get_record('mooproof_usage',
                ['mooproofid' => $params['mooproofid'], 'userid' => $USER->id]);

            $now = time();
            $periodseconds = ($mooproof->ratelimit_period === 'hour') ? 3600 : 86400;

            if ($usage) {
                if (($now - $usage->firstsubmission) >= $periodseconds) {
                    $usage->submissioncount = 0;
                    $usage->firstsubmission = $now;
                    $usage->lastsubmission = $now;
                    $DB->update_record('mooproof_usage', $usage);
                } else {
                    if ($usage->submissioncount >= intval($mooproof->ratelimit_count)) {
                        $periodstring = get_string('ratelimitreached_' . $mooproof->ratelimit_period, 'mooproof');
                        return [
                            'success' => false,
                            'error' => get_string('ratelimitreached', 'mooproof',
                                ['limit' => $mooproof->ratelimit_count, 'period' => $periodstring]),
                            'feedback' => '',
                            'remaining' => 0,
                            'wordcount' => 0,
                        ];
                    }
                }
            } else {
                $usage = new \stdClass();
                $usage->mooproofid = $params['mooproofid'];
                $usage->userid = $USER->id;
                $usage->submissioncount = 0;
                $usage->firstsubmission = $now;
                $usage->lastsubmission = $now;
                $usage->id = $DB->insert_record('mooproof_usage', $usage);
            }
        }

        // Check word count.
        $wordcount = str_word_count($papertext);
        $maxwords = intval($mooproof->maxwords);
        if ($maxwords > 0 && $wordcount > $maxwords) {
            return [
                'success' => false,
                'error' => get_string('wordlimitexceeded', 'mooproof',
                    ['count' => $wordcount, 'max' => $maxwords]),
                'feedback' => '',
                'remaining' => -1,
                'wordcount' => $wordcount,
            ];
        }

        // Build proofing prompt.
        $gradelevel = $mooproof->gradelevel;
        $instructions = !empty($mooproof->proofinstructions) ?
            $mooproof->proofinstructions :
            get_string('defaultinstructions', 'mooproof');

        $instructions = str_replace('{gradelevel}', $gradelevel, $instructions);

        $prompt = $instructions . "\n\n";
        $prompt .= "Grade Level: " . $gradelevel . "\n\n";
        $prompt .= "Paper to proof:\n\n";
        $prompt .= $papertext;

        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $context->id,
                userid: $USER->id,
                prompttext: $prompt
            );

            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            if ($response->get_success()) {
                $feedback = $response->get_response_data()['generatedcontent'] ?? '';

                // Save submission record.
                $submission = new \stdClass();
                $submission->mooproofid = $params['mooproofid'];
                $submission->userid = $USER->id;
                $submission->papertext = $papertext;
                $submission->feedback = $feedback;
                $submission->filename = $params['filename'];
                $submission->wordcount = $wordcount;
                $submission->gradelevel = $gradelevel;
                $submission->timecreated = time();
                $DB->insert_record('mooproof_submissions', $submission);

                // Update usage counter.
                if ($mooproof->ratelimit_enable && isset($usage)) {
                    $usage->submissioncount++;
                    $usage->lastsubmission = time();
                    $DB->update_record('mooproof_usage', $usage);
                    $remaining = intval($mooproof->ratelimit_count) - $usage->submissioncount;
                }

                return [
                    'success' => true,
                    'error' => '',
                    'feedback' => trim($feedback),
                    'remaining' => $remaining,
                    'wordcount' => $wordcount,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->get_errormessage() ?: 'AI generation failed',
                    'feedback' => '',
                    'remaining' => -1,
                    'wordcount' => 0,
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error: ' . $e->getMessage(),
                'feedback' => '',
                'remaining' => -1,
                'wordcount' => 0,
            ];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether submission was successful'),
            'error' => new external_value(PARAM_TEXT, 'Error message if any'),
            'feedback' => new external_value(PARAM_RAW, 'AI-generated feedback'),
            'remaining' => new external_value(PARAM_INT, 'Remaining submissions (-1 if unlimited)'),
            'wordcount' => new external_value(PARAM_INT, 'Word count of submission'),
        ]);
    }
}
