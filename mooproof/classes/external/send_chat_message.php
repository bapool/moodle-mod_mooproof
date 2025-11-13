<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External service for sending chat messages about feedback.
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
 * External service for chat messages.
 */
class send_chat_message extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'mooproofid' => new external_value(PARAM_INT, 'MooProof instance ID'),
            'message' => new external_value(PARAM_TEXT, 'User message'),
            'papertext' => new external_value(PARAM_RAW, 'Original paper text'),
            'feedback' => new external_value(PARAM_RAW, 'Feedback received'),
            'chathistory' => new external_value(PARAM_RAW, 'Chat history as JSON', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Send chat message.
     *
     * @param int $mooproofid MooProof instance ID
     * @param string $message User message
     * @param string $papertext Paper text
     * @param string $feedback Feedback text
     * @param string $chathistory Chat history JSON
     * @return array Result data
     */
    public static function execute($mooproofid, $message, $papertext, $feedback, $chathistory = '') {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'mooproofid' => $mooproofid,
            'message' => $message,
            'papertext' => $papertext,
            'feedback' => $feedback,
            'chathistory' => $chathistory,
        ]);

        // Get mooproof instance.
        $mooproof = $DB->get_record('mooproof', ['id' => $params['mooproofid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('mooproof', $params['mooproofid'], $mooproof->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Validate context and check capability.
        self::validate_context($context);
        require_capability('mod/mooproof:submit', $context);

        // Parse chat history.
        $history = [];
        if (!empty($params['chathistory'])) {
            $history = json_decode($params['chathistory'], true);
            if (!is_array($history)) {
                $history = [];
            }
        }

        // Check message limit.
        $chatmessagelimit = intval($mooproof->chatmessagelimit);
        $messagecount = 0;
        foreach ($history as $msg) {
            if ($msg['role'] === 'user') {
                $messagecount++;
            }
        }

        if ($messagecount >= $chatmessagelimit) {
            return [
                'success' => false,
                'error' => get_string('chatlimitreached', 'mooproof'),
                'reply' => '',
                'remaining' => 0,
            ];
        }

        // Build chat prompt.
        $gradelevel = $mooproof->gradelevel;
        $instructions = !empty($mooproof->proofinstructions) ?
            $mooproof->proofinstructions :
            get_string('defaultinstructions', 'mooproof');

        $prompt = "You are a helpful writing tutor assisting a grade {$gradelevel} student. ";
        $prompt .= "The student submitted a paper for proofreading and received feedback. ";
        $prompt .= "Now they have questions about the feedback. Answer their questions clearly and helpfully.\n\n";
        $prompt .= "IMPORTANT INSTRUCTIONS:\n";
        $prompt .= $instructions . "\n";
        $prompt .= "Remember: Do NOT rewrite the student's paper for them, even if they ask. ";
        $prompt .= "Only provide guidance, suggestions, and explanations. The student must do their own writing.\n\n";

        $prompt .= "ORIGINAL PAPER:\n";
        $prompt .= substr($params['papertext'], 0, 3000);
        $prompt .= "\n\n";

        $prompt .= "FEEDBACK PROVIDED:\n";
        $prompt .= $params['feedback'];
        $prompt .= "\n\n";

        // Add chat history.
        if (!empty($history)) {
            $prompt .= "CONVERSATION SO FAR:\n";
            foreach ($history as $msg) {
                if ($msg['role'] === 'user') {
                    $prompt .= "Student: " . $msg['content'] . "\n";
                } else if ($msg['role'] === 'assistant') {
                    $prompt .= "Tutor: " . $msg['content'] . "\n";
                }
            }
        }

        // Add current question.
        $prompt .= "Student: " . $params['message'] . "\nTutor:";

        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $context->id,
                userid: $USER->id,
                prompttext: $prompt
            );

            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            if ($response->get_success()) {
                $reply = $response->get_response_data()['generatedcontent'] ?? '';
                $remaining = $chatmessagelimit - $messagecount - 1;

                return [
                    'success' => true,
                    'error' => '',
                    'reply' => trim($reply),
                    'remaining' => $remaining,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->get_errormessage() ?: 'AI generation failed',
                    'reply' => '',
                    'remaining' => 0,
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error: ' . $e->getMessage(),
                'reply' => '',
                'remaining' => 0,
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
            'success' => new external_value(PARAM_BOOL, 'Whether message was successful'),
            'error' => new external_value(PARAM_TEXT, 'Error message if any'),
            'reply' => new external_value(PARAM_RAW, 'AI-generated reply'),
            'remaining' => new external_value(PARAM_INT, 'Remaining messages'),
        ]);
    }
}
