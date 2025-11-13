<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * View page for mod_mooproof.
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
    throw new moodle_exception('missingidandcmid', 'mooproof');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/mooproof:view', $context);

// Trigger course_module_viewed event.
$event = \mod_mooproof\event\course_module_viewed::create(array(
    'objectid' => $mooproof->id,
    'context' => $context
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mooproof', $mooproof);
$event->trigger();

// Set up the page
$PAGE->set_url('/mod/mooproof/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($mooproof->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Pass language strings to JavaScript
$PAGE->requires->strings_for_js(array(
    'error',
    'unsupportedfiletype',
    'selected',
    'fileuploadedextract',
    'pleaseentertext',
    'pleaseselectfile',
    'wordlimitexceededtitle',
    'failedconnectproofing',
    'limitreached',
    'maxquestionsreached',
    'failedconnectchat',
    'questionsremaining',
    'submissionsremaining'
), 'mooproof');

$PAGE->requires->js_call_amd('mod_mooproof/proof', 'init', array($mooproof->id, $mooproof->maxwords, $mooproof->chatmessagelimit));

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

// Output starts here
echo $OUTPUT->header();

// Display resource intro
if ($mooproof->intro) {
    echo $OUTPUT->box(format_module_intro('mooproof', $mooproof, $cm->id), 'generalbox mod_introbox', 'mooproofintro');
}

// Use renderer to display the main interface
$renderer = $PAGE->get_renderer('mod_mooproof');
$viewpage = new \mod_mooproof\output\view_page($mooproof, $remaining);
echo $renderer->render_view_page($viewpage);

echo $OUTPUT->footer();
