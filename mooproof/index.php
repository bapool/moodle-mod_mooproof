<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
/**
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course ID

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

// Trigger course_module_instance_list_viewed event.
$event = \mod_mooproof\event\course_module_instance_list_viewed::create(array(
    'context' => context_course::instance($course->id)
));
$event->trigger();

$PAGE->set_url('/mod/mooproof/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('modulenameplural', 'mooproof'));

if (!$mooproofs = get_all_instances_in_course('mooproof', $course)) {
    notice(get_string('nomorproofs', 'mooproof'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

$table->head = array(
    get_string('name'),
    get_string('intro', 'mooproof')
);

foreach ($mooproofs as $mooproof) {
    $link = html_writer::link(
        new moodle_url('/mod/mooproof/view.php', array('id' => $mooproof->coursemodule)),
        format_string($mooproof->name)
    );
    
    $intro = format_module_intro('mooproof', $mooproof, $mooproof->coursemodule);
    
    $table->data[] = array($link, $intro);
}

echo html_writer::table($table);

echo $OUTPUT->footer();
