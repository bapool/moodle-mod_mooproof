<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Renderer for mod_mooproof.
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooproof\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;

/**
 * Renderer class for mooproof module.
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the main view page.
     *
     * @param view_page $page The view page renderable
     * @return string HTML output
     */
    public function render_view_page(view_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('mod_mooproof/view_page', $data);
    }
}
