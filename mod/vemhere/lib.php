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
 * The teacher's "Attendance (Vemhere)" activity: a grade column Vemhere fills, and a page that sends people to the course in Vemhere.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * What the module supports.
 *
 * @param string $feature
 * @return mixed
 */
function vemhere_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GROUPS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Add an instance.
 *
 * @param stdClass $data
 * @return int
 */
function vemhere_add_instance($data) {
    global $DB;
    $data->grade = 100;
    $data->timecreated = $data->timemodified = time();
    $data->id = $DB->insert_record('vemhere', $data);
    vemhere_grade_item_update($data);
    return $data->id;
}

/**
 * Update an instance.
 *
 * @param stdClass $data
 * @return bool
 */
function vemhere_update_instance($data) {
    global $DB;
    $data->id = $data->instance;
    $data->grade = 100;
    $data->timemodified = time();
    $DB->update_record('vemhere', $data);
    vemhere_grade_item_update($data);
    return true;
}

/**
 * Delete an instance and its grade column.
 *
 * @param int $id
 * @return bool
 */
function vemhere_delete_instance($id) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');
    if (!$vemhere = $DB->get_record('vemhere', ['id' => $id])) {
        return false;
    }
    grade_update('mod/vemhere', $vemhere->course, 'mod', 'vemhere', $vemhere->id, 0, null, ['deleted' => 1]);
    $DB->delete_records('vemhere', ['id' => $id]);
    return true;
}

/**
 * The grade column: 0 to 100, the attendance rate Vemhere writes.
 *
 * @param stdClass $vemhere
 * @param mixed $grades
 * @return int
 */
function vemhere_grade_item_update($vemhere, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $params = ['itemname' => $vemhere->name, 'gradetype' => GRADE_TYPE_VALUE, 'grademax' => 100, 'grademin' => 0];
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/vemhere', $vemhere->course, 'mod', 'vemhere', $vemhere->id, 0, $grades, $params);
}

/**
 * Grades come from Vemhere only; recalculating keeps the column.
 *
 * @param stdClass $vemhere
 * @param int $userid
 * @param bool $nullifnone
 */
function vemhere_update_grades($vemhere, $userid = 0, $nullifnone = true) {
    vemhere_grade_item_update($vemhere);
}
