<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * View page renderable.
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooproof\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * View page renderable class.
 */
class view_page implements renderable, templatable {

    /** @var object The mooproof instance */
    protected $mooproof;

    /** @var int Remaining submissions */
    protected $remaining;

    /**
     * Constructor.
     *
     * @param object $mooproof The mooproof instance
     * @param int $remaining Remaining submissions (-1 if unlimited)
     */
    public function __construct($mooproof, $remaining) {
        $this->mooproof = $mooproof;
        $this->remaining = $remaining;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        // Remaining submissions
        $data->show_remaining = ($this->remaining >= 0);
        if ($data->show_remaining) {
            $data->submissions_remaining_text = get_string('submissionsremaining', 'mooproof', $this->remaining);
        }

        // Labels and text
        $data->submit_paper_heading = get_string('submitpaper', 'mooproof');
        $data->paste_text_label = get_string('pastetext', 'mooproof');
        $data->upload_file_label = get_string('uploadfile', 'mooproof');
        $data->paste_placeholder = get_string('pasteplaceholder', 'mooproof');
        $data->word_count_label = get_string('wordcount', 'mooproof');
        $data->upload_desc = get_string('uploaddesc', 'mooproof');
        $data->select_file_label = get_string('selectfile', 'mooproof');
        $data->submit_button_label = get_string('submitforproofing', 'mooproof');
        $data->submit_disabled = ($this->remaining === 0);
        $data->results_heading = get_string('proofingresults', 'mooproof');
        $data->ask_questions_heading = get_string('askquestions', 'mooproof');
        $data->chat_placeholder = get_string('chatplaceholder', 'mooproof');
        $data->send_button_label = get_string('sendmessage', 'mooproof');
        $data->chat_warning = get_string('chatsessionwarning', 'mooproof');
        $data->reset_button_label = get_string('submitanother', 'mooproof');
        $data->proofing_message = get_string('proofing', 'mooproof');

        return $data;
    }
}
