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

namespace assignsubmission_h5p\h5p;

/**
 * Class canedit
 *
 * @package    assignsubmission_h5p
 * @copyright  2025 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class canedit {
    /**
     * This method returns false to prevent users from editing H5P files that bypasses the submission process.
     *
     * @param \stored_file $file The H5P file to check.
     *
     * @return boolean Whether the user can edit or not the given file.
     */
    public static function can_edit_content(\stored_file $file): bool {
        return false;
    }
}
