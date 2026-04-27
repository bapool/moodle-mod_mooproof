<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
/**
 *
 * @package    mod_mooproof
 * @copyright  2026 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add mooproof instance.
 */
function mooproof_add_instance($mooproof) {
    global $DB;

    $mooproof->timecreated  = time();
    $mooproof->timemodified = time();

    // Extract text and format from the editor element.
    if (isset($mooproof->proofinstructions_editor)) {
        $mooproof->proofinstructions       = $mooproof->proofinstructions_editor['text'];
        $mooproof->proofinstructionsformat = $mooproof->proofinstructions_editor['format'];
        unset($mooproof->proofinstructions_editor);
    }

    $mooproof->id = $DB->insert_record('mooproof', $mooproof);

    return $mooproof->id;
}

/**
 * Update mooproof instance.
 */
function mooproof_update_instance($mooproof) {
    global $DB;

    $mooproof->timemodified = time();
    $mooproof->id           = $mooproof->instance;

    // Extract text and format from the editor element.
    if (isset($mooproof->proofinstructions_editor)) {
        $mooproof->proofinstructions       = $mooproof->proofinstructions_editor['text'];
        $mooproof->proofinstructionsformat = $mooproof->proofinstructions_editor['format'];
        unset($mooproof->proofinstructions_editor);
    }

    return $DB->update_record('mooproof', $mooproof);
}

/**
 * Delete mooproof instance and all associated student data.
 *
 * Called by Moodle when a teacher deletes the activity from the course.
 */
function mooproof_delete_instance($id) {
    global $DB;

    if (!$mooproof = $DB->get_record('mooproof', array('id' => $id))) {
        return false;
    }

    // Delete chat messages for all submissions belonging to this instance.
    $submissionids = $DB->get_fieldset_select(
        'mooproof_submissions', 'id', 'mooproofid = ?', array($id)
    );
    if (!empty($submissionids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($submissionids);
        $DB->delete_records_select('mooproof_chat_messages', "submissionid $insql", $inparams);
    }

    // Delete submission records.
    $DB->delete_records('mooproof_submissions', array('mooproofid' => $id));

    // Delete usage records.
    $DB->delete_records('mooproof_usage', array('mooproofid' => $id));

    // Delete the instance itself.
    $DB->delete_records('mooproof', array('id' => $id));

    return true;
}

/**
 * Supported features.
 */
function mooproof_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Adds the MooProof reset options to the course reset form.
 *
 * Called by Moodle when building the Course Reset form. Adds a checkbox
 * so teachers can choose to wipe student submission data on reset.
 *
 * @param MoodleQuickForm $mform The course reset form.
 */
function mooproof_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'mooproofheader', get_string('pluginname', 'mooproof'));
    $mform->addElement('checkbox', 'reset_mooproof_submissions',
        get_string('resetsubmissions', 'mooproof'));
}

/**
 * Default values for the course reset form checkboxes.
 *
 * @param stdClass $course The course object.
 * @return array Associative array of default values.
 */
function mooproof_reset_course_form_defaults($course) {
    return array('reset_mooproof_submissions' => 1);
}

/**
 * Performs the actual data deletion when a course is reset.
 *
 * Deletes all student submissions, chat messages, and usage records for
 * every MooProof activity in the course when the teacher checks the
 * "Delete all MooProof submissions and chat history" option on the reset form.
 *
 * @param stdClass $data The reset form data, including course id.
 * @return array Status array required by Moodle reset API.
 */
function mooproof_reset_userdata($data) {
    global $DB;

    $status = array();
    $componentstr = get_string('pluginname', 'mooproof');

    if (!empty($data->reset_mooproof_submissions)) {

        // Get all mooproof instance IDs in this course.
        $instanceids = $DB->get_fieldset_select(
            'mooproof', 'id', 'course = ?', array($data->courseid)
        );

        if (!empty($instanceids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($instanceids);

            // Delete chat messages linked to submissions in this course.
            $submissionids = $DB->get_fieldset_select(
                'mooproof_submissions', 'id', "mooproofid $insql", $inparams
            );
            if (!empty($submissionids)) {
                list($subsql, $subparams) = $DB->get_in_or_equal($submissionids);
                $DB->delete_records_select('mooproof_chat_messages',
                    "submissionid $subsql", $subparams);
            }

            // Delete all submissions for this course.
            $DB->delete_records_select('mooproof_submissions',
                "mooproofid $insql", $inparams);

            // Delete all usage records for this course.
            $DB->delete_records_select('mooproof_usage',
                "mooproofid $insql", $inparams);
        }

        $status[] = array(
            'component' => $componentstr,
            'item'      => get_string('resetsubmissions', 'mooproof'),
            'error'     => false,
        );
    }

    return $status;
}

/**
 * Serve the files from the mooproof file areas.
 */
function mod_mooproof_pluginfile($course, $cm, $context, $filearea, $args,
                                  $forcedownload, array $options = array()) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea !== 'submission') {
        return false;
    }

    require_login($course, false, $cm);

    $fs       = get_file_storage();
    $itemid   = array_shift($args);
    $filename = array_pop($args);
    $filepath = '/';

    $file = $fs->get_file($context->id, 'mod_mooproof', $filearea,
                          $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

