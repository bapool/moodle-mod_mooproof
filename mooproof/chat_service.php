<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once('lib.php');

$PAGE->set_context(context_system::instance());

require_login();

$mooproofid = required_param('mooproofid', PARAM_INT);
$message = required_param('message', PARAM_TEXT);
$papertext = required_param('papertext', PARAM_RAW);
$feedback = required_param('feedback', PARAM_RAW);
$chathistory = optional_param('chathistory', '', PARAM_RAW);

// Get mooproof instance
$mooproof = $DB->get_record('mooproof', array('id' => $mooproofid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('mooproof', $mooproofid, $mooproof->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

// Check capability
require_capability('mod/mooproof:submit', $context);

// Parse chat history
$history = array();
if (!empty($chathistory)) {
    $history = json_decode($chathistory, true);
    if (!is_array($history)) {
        $history = array();
    }
}

// Check message limit
$chatmessagelimit = intval($mooproof->chatmessagelimit);
$messagecount = 0;
foreach ($history as $msg) {
    if ($msg['role'] === 'user') {
        $messagecount++;
    }
}

if ($messagecount >= $chatmessagelimit) {
    echo json_encode(array(
        'error' => get_string('chatlimitreached', 'mooproof'),
        'remaining' => 0
    ));
    die();
}

// Build the chat prompt with context
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
$prompt .= substr($papertext, 0, 3000); // Limit to avoid token overload
$prompt .= "\n\n";

$prompt .= "FEEDBACK PROVIDED:\n";
$prompt .= $feedback;
$prompt .= "\n\n";

// Add chat history
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

// Add current question
$prompt .= "Student: " . $message . "\nTutor:";

try {
    // Create AI action using Moodle's core AI system
    $action = new \core_ai\aiactions\generate_text(
        contextid: $context->id,
        userid: $USER->id,
        prompttext: $prompt
    );
    
    // Get AI manager and process the action
    $manager = \core\di::get(\core_ai\manager::class);
    $response = $manager->process_action($action);
    
    if ($response->get_success()) {
        $reply = $response->get_response_data()['generatedcontent'] ?? '';
        
        $remaining = $chatmessagelimit - $messagecount - 1;
        
        // Return success response
        echo json_encode(array(
            'success' => true,
            'reply' => trim($reply),
            'remaining' => $remaining
        ));
    } else {
        // Return error from AI system
        echo json_encode(array(
            'error' => $response->get_errormessage() ?: 'AI generation failed'
        ));
    }
    
} catch (Exception $e) {
    echo json_encode(array(
        'error' => 'Error: ' . $e->getMessage()
    ));
}
