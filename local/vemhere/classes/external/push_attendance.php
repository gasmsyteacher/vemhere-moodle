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

namespace local_vemhere\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Write each person's attendance rate into the course's "Attendance (Vemhere)"
 * activity (MP6). People are found by ID number, then email.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class push_attendance extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'grades' => new external_multiple_structure(new external_single_structure([
                'idnumber' => new external_value(PARAM_RAW, 'ID number', VALUE_DEFAULT, ''),
                'email' => new external_value(PARAM_RAW, 'email', VALUE_DEFAULT, ''),
                'grade' => new external_value(PARAM_FLOAT, 'attendance rate, 0 to 100'),
            ]), 'rates', VALUE_DEFAULT, []),
        ]);
    }

    /**
     * Write the rates.
     *
     * @param int $courseid
     * @param array $grades
     * @return array
     */
    public static function execute(int $courseid, array $grades = []): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid, 'grades' => $grades]);
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/grade:edit', $context);

        if (!$DB->get_manager()->table_exists('vemhere')
                || !($instance = $DB->get_record('vemhere', ['course' => $params['courseid']], '*', IGNORE_MULTIPLE))) {
            return ['module' => false, 'written' => 0, 'missing' => []];
        }
        $rows = [];
        $missing = [];
        foreach ($params['grades'] as $g) {
            $user = \local_vemhere\people::find($g['idnumber'], $g['email']);
            if (!$user) {
                $missing[] = $g['idnumber'] !== '' ? $g['idnumber'] : $g['email'];
                continue;
            }
            $rows[$user->id] = (object) ['userid' => $user->id,
                'rawgrade' => max(0, min(100, round((float) $g['grade'], 2)))];
        }
        if ($rows) {
            grade_update('mod/vemhere', $params['courseid'], 'mod', 'vemhere', $instance->id, 0, $rows);
        }
        return ['module' => true, 'written' => count($rows), 'missing' => $missing];
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'module' => new external_value(PARAM_BOOL, 'the course has an Attendance (Vemhere)'),
            'written' => new external_value(PARAM_INT, 'rates written'),
            'missing' => new external_multiple_structure(new external_value(PARAM_RAW, 'ID number or email not found')),
        ]);
    }
}
