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
 * Provides the information to restore H5P Submission submissions
 *
 * @package assignsubmission_h5p
 * @copyright  2025 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_assignsubmission_h5p_subplugin extends restore_subplugin {
    /**
     * Returns array the paths to be handled by the subplugin at assignment level
     * @return array
     */
    protected function define_submission_subplugin_structure() {
        $paths = [];

        $elename = $this->get_namefor('submission');

        // We used get_recommended_name() so this works.
        $elepath = $this->get_pathfor('/submission_h5p');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Processes one assignsubmission_h5p element
     *
     * @param mixed $data
     */
    public function process_assignsubmission_h5p_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldsubmissionid = $data->submission;
        $data->assignment = $this->get_new_parentid('assign');
        // The mapping is set in the restore for the core assign activity
        // when a submission node is processed.
        $data->submission = $this->get_mappingid('submission', $data->submission);

        $DB->insert_record('assignsubmission_h5p', $data);

        // TODO: restore files if necessary. Substitute 'fileareaname' with a correct filearea name.
        $this->add_related_files('assignsubmission_h5p', 'fileareaname', 'submission', null, $oldsubmissionid);
    }
}
