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

use contenttype_h5p\contenttype as h5pcontenttype;
use core_h5p\editor as h5peditor;
use core_h5p\player as h5pplayer;

/**
 * Main class for H5P Submission submission plugin
 *
 * @package     assignsubmission_h5p
 * @copyright   2025 ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_h5p extends assign_submission_plugin {
    /** @var h5peditor */
    private h5peditor $h5peditor;

    /**
     * Should return the name of this plugin type.
     *
     * @return string - the name
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_h5p');
    }

    /**
     * Remove all data stored in this plugin that is associated with the given submission.
     *
     * @param stdClass $submission record from assign_submission table
     * @return boolean
     */
    public function remove(stdClass $submission) {
        global $DB;

        $submissionid = $submission ? $submission->id : 0;
        if ($submissionid) {
            $h5pids = $DB->get_field('assignsubmission_h5p', 'h5pid', ['submission' => $submissionid]);
            $h5pframework = new core_h5p\framework();
            foreach ($h5pids as $h5pid) {
                $h5pframework->deleteContentData($h5pid);
                // ToDo: Check, whether it is necessary to delete files.
            }
            $fs = get_file_storage();
            $fs->delete_area_files($this->assignment->get_context()->id, 'assignsubmission_h5p', 'submissions', $submissionid);
            $DB->delete_records('assignsubmission_h5p', ['submission' => $submissionid]);
        }
        return true;
    }

    /**
     * Get the list of libraries
     *
     * @return array
     */
    public function get_libraries(): array {
        $choices = [];
        $contenttypeh5p = new h5pcontenttype();
        $libraries = $contenttypeh5p->get_contenttype_types();

        foreach ($libraries as $key => $library) {
            $choices[$library->key] = $library->typename;
        }

        return $choices;
    }

    /**
     * Get the list of libraries that are allowed
     *
     * @return array
     */
    public function get_filtered_libraries(): array {
        $choices = $this->get_libraries();
        $restricttypesconfig = $this->get_config('restricttypes');
        $restricttypes =!empty($restricttypesconfig);
        if (!$restricttypes) {
            return $choices;
        }
        $allowedtypesconfig = $this->get_config('allowedtypes');
        $allowedtypes = explode(',', $allowedtypesconfig);
        foreach ($choices as $key => $choice) {
            if (!in_array($key, $allowedtypes)) {
                unset($choices[$key]);
            }
        }
        return $choices;
    }

    /**
     * Add form elements for settings
     *
     * @param null|stdClass $submission record from assign_submission table or null if it is a new submission
     * @param MoodleQuickForm $mform
     * @param stdClass $data form data that can be modified
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $DB;

        $fs = get_file_storage();

        $mform->addElement('hidden', 'h5pid');
        $mform->setType('h5pid', PARAM_INT);

        $choices = $this->get_filtered_libraries();
        $mform->addElement(
            'select',
            'library',
            get_string('library', 'assignsubmission_h5p'),
            $choices
        );
        $mform->setType('library', PARAM_TEXT);

        $this->h5peditor = new h5peditor();

        if ($submission) {
            $currentsubmission = $DB->get_record('assignsubmission_h5p', ['submission' => $submission->id]);
            $data->h5pid = $currentsubmission ? $currentsubmission->h5pid : '';
        }

        if (!empty($data->h5pid)) {
            $pathnamehash = $DB->get_field('h5p', 'pathnamehash', ['id' => $data->h5pid]);
            $data->oldfile = $fs->get_file_by_hash($pathnamehash);
            $this->h5peditor->set_content($data->h5pid);
        } else {
            // Todo: Check whether storing userid has an effect on team submissions.
            $this->h5peditor->set_library(
                array_key_first($choices),
                $this->assignment->get_context()->id,
                'assignsubmission_h5p',
                'submissions',
                $data->userid,
                '/',
                'submission.h5p',
                $data->userid
            );
        }        

        $this->h5peditor->add_editor_to_form($mform);

        return true;
    }

    /**
     * Save data to the database and trigger plagiarism plugin,
     * if enabled, to scan the uploaded content via events trigger
     *
     * @param stdClass $submission record from assign_submission table
     * @param stdClass $data data from the form
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $DB;

        $currentsubmission = $DB->get_record('assignsubmission_h5p', ['submission' => $submission->id]);

        unset($data->id);
        if (!empty($submission->h5pid)) {
            $data->id = $data->h5pid;
        }

        $data->h5pid = $this->h5peditor->save_content($data);

        if ($currentsubmission) {
            $data->h5pid = $data->h5pid;
            $updatestatus = $DB->update_record('assignsubmission_h5p', $currentsubmission);
            return $updatestatus;
        } else {
            $currentsubmission = (object)[
                'h5pid' => $data->h5pid,
                'submission' => $submission->id,
                'assignment' => $this->assignment->get_instance()->id,
            ];
            $currentsubmission->id = $DB->insert_record('assignsubmission_h5p', $currentsubmission);
            return $currentsubmission->id > 0;
        }
    }

    /**
     * Determine if a submission is empty
     *
     * This is distinct from is_empty in that it is intended to be used to
     * determine if a submission made before saving is empty.
     *
     * @param stdClass $data data from the form
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        return trim($data->h5paction ?? '') === '';
    }

    /**
     * Is this assignment plugin empty? (ie no submission or feedback)
     *
     * @param stdClass $submission record from assign_submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        global $DB;
        $currentsubmission = $DB->get_record('assignsubmission_h5p', ['submission' => $submission->id]);
        return !$currentsubmission || empty($currentsubmission->h5pid);
    }

    /**
     * Display value in the submission status table
     *
     * @param stdClass $submission record from assign_submission table
     * @param bool $showviewlink Modifed to return whether or not to show a link to the full submission/feedback
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        global $DB;
        $currentsubmission = $DB->get_record('assignsubmission_h5p', ['submission' => $submission->id]);
        $h5p = $DB->get_record('h5p', ['id' => $currentsubmission->h5pid]);
        $json = json_decode($h5p->jsoncontent);
        $showviewlink = true;
        return $currentsubmission ? s($json->title) : '';
    }

    /**
     * Display the submission
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $DB, $OUTPUT;
        $currentsubmission = $DB->get_record('assignsubmission_h5p', ['submission' => $submission->id]);
        $fs = get_file_storage();
        $pathnamehash = $DB->get_field('h5p', 'pathnamehash', ['id' => $currentsubmission->h5pid]);
        $file = $fs->get_file_by_hash($pathnamehash);
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        $config = new stdClass();
        $player = new h5pplayer($url, $config, true, 'assignsubmission_h5p', true);

        return $OUTPUT->render_from_template(
            'assignsubmission_h5p/h5pview',
            ['content' => $player->display($url, $config, true, 'assignsubmission_h5p', true)]
        );
    }

    /**
     * Return the h5p file that belongs to this submission.
     *
     * @param stdClass $submission - For this is the submission data
     * @param stdClass $user - This is the user record for this submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        global $DB;
        $currentsubmission = $DB->get_record('assignsubmission_h5p', ['submission' => $submission->id]);
        $fs = get_file_storage();
        $pathnamehash = $DB->get_field('h5p', 'pathnamehash', ['id' => $currentsubmission->h5pid]);
        $file = $fs->get_file_by_hash($pathnamehash);
        $files = [$file->get_filename() => $file];
        return $files;
    }

    /**
     * Return a description of external params suitable for uploading an feedback comment from a webservice.
     *
     * Used in WebService mod_assign_save_submission
     *
     * @return array
     */
    public function get_external_parameters() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/externallib.php');

        return ['h5p' => new external_value(PARAM_RAW, 'The value for this submission.')];
    }

    /**
     * Add the settings
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $mform->addElement(
            'advcheckbox',
            'assignsubmission_h5p_restricttypes',
            get_string('restricttypes', 'assignsubmission_h5p')
        );
        $mform->setType('assignsubmission_h5p_restricttypes', PARAM_BOOL);

        $mform->addElement(
            'select',
            'assignsubmission_h5p_allowedtypes',
            get_string('allowedtypes', 'assignsubmission_h5p'),
            $this->get_libraries(),
            ['multiple' => true]
        );
        $mform->setType('assignsubmission_h5p_allowedtypes', PARAM_TEXT);
        $mform->addHelpButton('assignsubmission_h5p_allowedtypes', 'allowedtypes', 'assignsubmission_h5p');
        $mform->disabledIf('assignsubmission_h5p_allowedtypes', 'assignsubmission_h5p_restricttypes', 'notchecked');

        $mform->hideIf('assignsubmission_h5p_restricttypes', 'assignsubmission_h5p_enabled', 'notchecked');
        $mform->hideIf('assignsubmission_h5p_allowedtypes', 'assignsubmission_h5p_enabled', 'notchecked');
    }

    /**
     * Save the settings
     *
     * @param stdClass $formdata
     * @return bool
     */
    public function save_settings(stdClass $formdata) {
        $this->set_config('restricttypes', !empty($formdata->assignsubmission_h5p_restricttypes));
        $this->set_config('allowedtypes', implode(',', $formdata->assignsubmission_h5p_allowedtypes));
        return true;
    }
}
