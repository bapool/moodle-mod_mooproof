<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
/**
 * Privacy Subsystem implementation for mod_mooproof
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/mooproof/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID

if ($id) {
    $cm = get_coursemodule_from_id('mooproof', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $mooproof = $DB->get_record('mooproof', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('missingidandcmid', 'mooproof');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/mooproof:view', $context);

// Set up the page
$PAGE->set_url('/mod/mooproof/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($mooproof->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Include JavaScript
$PAGE->requires->js_call_amd('mod_mooproof/proof', 'init', array($mooproof->id, $mooproof->maxwords, $mooproof->chatmessagelimit));

// Output starts here
echo $OUTPUT->header();

// Display resource name and intro
echo $OUTPUT->heading(format_string($mooproof->name));

if ($mooproof->intro) {
    echo $OUTPUT->box(format_module_intro('mooproof', $mooproof, $cm->id), 'generalbox mod_introbox', 'mooproofintro');
}

// Check rate limiting
$remaining = -1;
if ($mooproof->ratelimit_enable) {
    $usage = $DB->get_record('mooproof_usage', 
        array('mooproofid' => $mooproof->id, 'userid' => $USER->id));
    
    if ($usage) {
        $now = time();
        $period_seconds = ($mooproof->ratelimit_period === 'hour') ? 3600 : 86400;
        
        // Check if period has expired
        if (($now - $usage->firstsubmission) >= $period_seconds) {
            $remaining = intval($mooproof->ratelimit_count);
        } else {
            $remaining = intval($mooproof->ratelimit_count) - intval($usage->submissioncount);
        }
    } else {
        $remaining = intval($mooproof->ratelimit_count);
    }
}

// Display the proofing interface
echo '<div class="mooproof-container">';

// Show submissions remaining
if ($remaining >= 0) {
    echo '<div class="alert alert-info">';
    echo get_string('submissionsremaining', 'mooproof', $remaining);
    echo '</div>';
}

// Input method selection
echo '<div class="mooproof-input-section">';
echo '<h3>' . get_string('submitpaper', 'mooproof') . '</h3>';

// Tab buttons
echo '<div class="mooproof-tabs">';
echo '<button class="mooproof-tab active" data-tab="paste">' . get_string('pastetext', 'mooproof') . '</button>';
echo '<button class="mooproof-tab" data-tab="upload">' . get_string('uploadfile', 'mooproof') . '</button>';
echo '</div>';

// Paste tab
echo '<div class="mooproof-tab-content active" id="paste-tab">';
echo '<textarea id="mooproof-text-input" class="mooproof-textarea" placeholder="' . 
     get_string('pasteplaceholder', 'mooproof') . '" rows="15"></textarea>';
echo '<div class="mooproof-word-count">Word count: <span id="word-count">0</span></div>';
echo '</div>';

// Upload tab
echo '<div class="mooproof-tab-content" id="upload-tab">';
echo '<div class="mooproof-upload-area" id="upload-area">';
echo '<p>' . get_string('uploaddesc', 'mooproof') . '</p>';
echo '<input type="file" id="file-input" style="display:none;" accept=".txt,.docx">';
echo '<button class="btn btn-secondary" id="select-file-btn">' . get_string('selectfile', 'mooproof') . '</button>';
echo '<div id="file-name"></div>';
echo '</div>';
echo '</div>';

// Submit button
echo '<div class="mooproof-submit-section">';
echo '<button id="mooproof-submit" class="btn btn-primary"';
if ($remaining === 0) {
    echo ' disabled';
}
echo '>' . get_string('submitforproofing', 'mooproof') . '</button>';
echo '</div>';

echo '</div>'; // End input section

// Results section
echo '<div class="mooproof-results-section" id="results-section" style="display:none;">';
echo '<h3>' . get_string('proofingresults', 'mooproof') . '</h3>';
echo '<div id="proofing-results" class="mooproof-results"></div>';

// Chat section - appears after feedback
echo '<div class="mooproof-chat-section" id="chat-section" style="display:none;">';
echo '<h4>' . get_string('askquestions', 'mooproof') . '</h4>';
echo '<div class="mooproof-chat-messages" id="chat-messages"></div>';
echo '<div class="mooproof-chat-input-area">';
echo '<textarea id="chat-input" class="mooproof-chat-input" placeholder="' . 
     get_string('chatplaceholder', 'mooproof') . '" rows="2"></textarea>';
echo '<div class="mooproof-chat-controls">';
echo '<button id="chat-send" class="btn btn-primary btn-sm">' . 
     get_string('sendmessage', 'mooproof') . '</button>';
echo '<span id="chat-remaining" class="mooproof-chat-remaining"></span>';
echo '</div>';
echo '</div>';
echo '<div class="alert alert-info mooproof-chat-warning">' . 
     get_string('chatsessionwarning', 'mooproof') . '</div>';
echo '</div>';

echo '<button id="mooproof-reset" class="btn btn-secondary">' . get_string('submitanother', 'mooproof') . '</button>';
echo '</div>';

// Loading indicator
echo '<div id="loading-indicator" style="display:none;">';
echo '<div class="mooproof-loading">';
echo '<div class="spinner-border" role="status"></div>';
echo '<p>' . get_string('proofing', 'mooproof') . '</p>';
echo '</div>';
echo '</div>';

echo '</div>'; // End container

echo $OUTPUT->footer();
