<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External services for mod_mooproof.
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_mooproof_submit_paper' => [
        'classname'   => 'mod_mooproof\external\submit_paper',
        'methodname'  => 'execute',
        'description' => 'Submit a paper for AI proofreading',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'mod_mooproof_send_chat_message' => [
        'classname'   => 'mod_mooproof\external\send_chat_message',
        'methodname'  => 'execute',
        'description' => 'Send a chat message about feedback',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
