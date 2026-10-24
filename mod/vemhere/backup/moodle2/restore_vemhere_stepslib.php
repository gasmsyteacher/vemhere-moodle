<?php
// This file is part of the Vemhere plugin for Moodle - https://vemhere.com
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Restore: the activity's own row.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The structure of one Attendance (Vemhere) when restoring.
 */
class restore_vemhere_activity_structure_step extends restore_activity_structure_step {
    /**
     * The paths to read.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        return $this->prepare_activity_structure([new restore_path_element('vemhere', '/activity/vemhere')]);
    }

    /**
     * Insert the row into the new course.
     *
     * @param array $data
     */
    protected function process_vemhere($data) {
        global $DB;
        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = time();
        $this->apply_activity_instance($DB->insert_record('vemhere', $data));
    }
}
