<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
/*
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_mooproof_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;
        
        $mform = $this->_form;

        // General section
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Resource name
        $mform->addElement('text', 'name', get_string('proofname', 'mooproof'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        // Introduction
        $this->standard_intro_elements();

        // Grade Level Selection
        $gradelevels = array(
            '3' => get_string('grade3', 'mooproof'),
            '4' => get_string('grade4', 'mooproof'),
            '5' => get_string('grade5', 'mooproof'),
            '6' => get_string('grade6', 'mooproof'),
            '7' => get_string('grade7', 'mooproof'),
            '8' => get_string('grade8', 'mooproof'),
            '9' => get_string('grade9', 'mooproof'),
            '10' => get_string('grade10', 'mooproof'),
            '11' => get_string('grade11', 'mooproof'),
            '12' => get_string('grade12', 'mooproof'),
        );

        $mform->addElement('select', 'gradelevel', get_string('gradelevel', 'mooproof'), $gradelevels);
        $mform->addHelpButton('gradelevel', 'gradelevel', 'mooproof');
        $mform->setDefault('gradelevel', '9');

        // Proofing Instructions
        $mform->addElement('textarea', 'proofinstructions', 
                          get_string('proofinstructions', 'mooproof'),
                          array('rows' => 5, 'cols' => 60));
        $mform->setType('proofinstructions', PARAM_TEXT);
        $mform->addHelpButton('proofinstructions', 'proofinstructions', 'mooproof');
        $mform->setDefault('proofinstructions', get_string('defaultinstructions', 'mooproof'));

        // Rate Limiting Header
        $mform->addElement('header', 'ratelimitheader', get_string('ratelimiting', 'mooproof'));

        // Enable Rate Limiting
        $mform->addElement('advcheckbox', 'ratelimit_enable', 
                          get_string('ratelimit_enable', 'mooproof'));
        $mform->addHelpButton('ratelimit_enable', 'ratelimit_enable', 'mooproof');
        $mform->setDefault('ratelimit_enable', 1);

        // Rate Limit Period
        $periods = array(
            'hour' => get_string('period_hour', 'mooproof'),
            'day' => get_string('period_day', 'mooproof'),
        );
        $mform->addElement('select', 'ratelimit_period', 
                          get_string('ratelimit_period', 'mooproof'), 
                          $periods);
        $mform->setDefault('ratelimit_period', 'day');
        $mform->addHelpButton('ratelimit_period', 'ratelimit_period', 'mooproof');
        $mform->hideIf('ratelimit_period', 'ratelimit_enable');

        // Rate Limit Count
        $mform->addElement('text', 'ratelimit_count', 
                          get_string('ratelimit_count', 'mooproof'));
        $mform->setType('ratelimit_count', PARAM_INT);
        $mform->setDefault('ratelimit_count', 5);
        $mform->addHelpButton('ratelimit_count', 'ratelimit_count', 'mooproof');
        $mform->hideIf('ratelimit_count', 'ratelimit_enable');

        // Advanced Settings Header
        $mform->addElement('header', 'advancedheader', get_string('advancedsettings', 'mooproof'));
        $mform->setExpanded('advancedheader', false);

        // Max Words
        $mform->addElement('text', 'maxwords', 
                          get_string('maxwords', 'mooproof'));
        $mform->setType('maxwords', PARAM_INT);
        $mform->setDefault('maxwords', 5000);
        $mform->addHelpButton('maxwords', 'maxwords', 'mooproof');

	// Chat Message Limit
        $mform->addElement('text', 'chatmessagelimit', 
                          get_string('chatmessagelimit', 'mooproof'));
        $mform->setType('chatmessagelimit', PARAM_INT);
        $mform->setDefault('chatmessagelimit', 10);
        $mform->addHelpButton('chatmessagelimit', 'chatmessagelimit', 'mooproof');
        
        // Temperature (Creativity)
        $temperatures = array(
            '0.3' => '0.3 - Very Focused',
            '0.5' => '0.5 - Balanced',
            '0.7' => '0.7 - Standard',
        );
        $mform->addElement('select', 'temperature', 
                          get_string('temperature', 'mooproof'), 
                          $temperatures);
        $mform->setDefault('temperature', '0.5');
        $mform->addHelpButton('temperature', 'temperature', 'mooproof');

        // Standard coursemodule elements
        $this->standard_coursemodule_elements();

        // Buttons
        $this->add_action_buttons();
    }
}
