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
$papertext = required_param('papertext', PARAM_RAW);
$filename = optional_param('filename', '', PARAM_TEXT);

// If a filename is provided, the papertext contains file data that needs parsing
if (!empty($filename)) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Check if data is base64 encoded (for binary files from JavaScript)
    if ($ext !== 'txt') {
        // Decode base64 data
        $papertext = base64_decode($papertext);
        if ($papertext === false) {
            echo json_encode(array(
                'error' => 'Failed to decode uploaded file'
            ));
            die();
        }
    }
    
    // Save the data to a temporary file
    $tempfile = tempnam(sys_get_temp_dir(), 'mooproof_');
    file_put_contents($tempfile, $papertext);
    
    try {
        // Use document parser to extract text
        $papertext = \mod_mooproof\document_parser::extract_text($tempfile, $filename);
    } catch (\Exception $e) {
        // Clean up temp file
        @unlink($tempfile);
        
        // Return error to user
        echo json_encode(array(
            'error' => $e->getMessage()
        ));
        die();
    }
    
    // Clean up temp file
    @unlink($tempfile);
}


// Get mooproof instance
$mooproof = $DB->get_record('mooproof', array('id' => $mooproofid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('mooproof', $mooproofid, $mooproof->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

// Check capability
require_capability('mod/mooproof:submit', $context);

// Automatic cleanup: Delete records older than 7 days
$cleanuptime = time() - (7 * 86400); // 7 days ago
$DB->delete_records_select('mooproof_usage', 'lastsubmission < ?', array($cleanuptime));

// Check rate limiting
$ratelimit_enabled = $mooproof->ratelimit_enable;
if ($ratelimit_enabled) {
    $ratelimit_period = $mooproof->ratelimit_period;
    $ratelimit_count = intval($mooproof->ratelimit_count);
    
    // Get or create usage record
    $usage = $DB->get_record('mooproof_usage', 
        array('mooproofid' => $mooproofid, 'userid' => $USER->id));
    
    $now = time();
    $period_seconds = ($ratelimit_period === 'hour') ? 3600 : 86400;
    
    if ($usage) {
        // Check if we need to reset the counter
        if (($now - $usage->firstsubmission) >= $period_seconds) {
            // Period has expired, reset counter
            $usage->submissioncount = 0;
            $usage->firstsubmission = $now;
            $usage->lastsubmission = $now;
            $DB->update_record('mooproof_usage', $usage);
        } else {
            // Check if limit reached
            if ($usage->submissioncount >= $ratelimit_count) {
                $period_string = get_string('ratelimitreached_' . $ratelimit_period, 'mooproof');
                echo json_encode(array(
                    'error' => get_string('ratelimitreached', 'mooproof', 
                        array('limit' => $ratelimit_count, 'period' => $period_string)),
                    'remaining' => 0
                ));
                die();
            }
        }
    } else {
        // Create new usage record
        $usage = new stdClass();
        $usage->mooproofid = $mooproofid;
        $usage->userid = $USER->id;
        $usage->submissioncount = 0;
        $usage->firstsubmission = $now;
        $usage->lastsubmission = $now;
        $usage->id = $DB->insert_record('mooproof_usage', $usage);
    }
}

// Check word count
$wordcount = str_word_count($papertext);
$maxwords = intval($mooproof->maxwords);
if ($maxwords > 0 && $wordcount > $maxwords) {
    echo json_encode(array(
        'error' => get_string('wordlimitexceeded', 'mooproof', 
            array('count' => $wordcount, 'max' => $maxwords))
    ));
    die();
}

// Build the proofing prompt
$gradelevel = $mooproof->gradelevel;
$instructions = !empty($mooproof->proofinstructions) ? 
    $mooproof->proofinstructions : 
    get_string('defaultinstructions', 'mooproof');

// Replace {gradelevel} placeholder in instructions
$instructions = str_replace('{gradelevel}', $gradelevel, $instructions);

$prompt = $instructions . "\n\n";
$prompt .= "Grade Level: " . $gradelevel . "\n\n";
$prompt .= "Paper to proof:\n\n";
$prompt .= $papertext;

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
        $feedback = $response->get_response_data()['generatedcontent'] ?? '';
        
        // Save submission record
        $submission = new stdClass();
        $submission->mooproofid = $mooproofid;
        $submission->userid = $USER->id;
        $submission->papertext = $papertext;
        $submission->feedback = $feedback;
        $submission->filename = $filename;
        $submission->wordcount = $wordcount;
        $submission->gradelevel = $gradelevel;
        $submission->timecreated = time();
        $DB->insert_record('mooproof_submissions', $submission);
        
        // Update usage counter if rate limiting is enabled
        if ($ratelimit_enabled && isset($usage)) {
            $usage->submissioncount++;
            $usage->lastsubmission = time();
            $DB->update_record('mooproof_usage', $usage);
            
            $remaining = $ratelimit_count - $usage->submissioncount;
        } else {
            $remaining = -1; // Unlimited
        }
        
        // Return success response
        echo json_encode(array(
            'success' => true,
            'feedback' => trim($feedback),
            'remaining' => $remaining,
            'wordcount' => $wordcount
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
