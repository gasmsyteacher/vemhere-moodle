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
 * Backup: the activity's own row (its grades travel with Moodle's gradebook).
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The structure of one Attendance (Vemhere) in a backup.
 */
class backup_vemhere_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $vemhere = new backup_nested_element('vemhere', ['id'],
            ['name', 'intro', 'introformat', 'grade', 'timecreated', 'timemodified']);
        $vemhere->set_source_table('vemhere', ['id' => backup::VAR_ACTIVITYID]);
        return $this->prepare_activity_structure($vemhere);
    }
}
