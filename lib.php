<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Callback implementations for H5P Submission
 *
 * @package assignsubmission_h5p
 * @copyright  2025 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pluginfile implementation for H5P Submission.
 *
 * @param [type] $course
 * @param [type] $cm
 * @param [type] $context
 * @param [type] $filearea
 * @param [type] $args
 * @param [type] $forcedownload
 * @param array $options
 * @return void
 */
function assignsubmission_h5p_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login();

    if (!has_capability('mod/assign:grade', $context)) {
        
    }

    // Todo: View own submission.

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/assignsubmission_h5p/$filearea/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || !$file->is_readable()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
