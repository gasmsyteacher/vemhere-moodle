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
 * A course's results for Vemhere's gradebook (MP5, V-D60): its grade items
 * (activities and manual items; totals stay in Moodle), every grade with its
 * feedback, and the people enrolled with their ID number (the registration
 * number) and email, so Vemhere matches results without Moodle's own user
 * search, which shows a service account nobody.
 *
 * Read only.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grades extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
        ]);
    }

    /**
     * The course's items, grades and people.
     *
     * @param int $courseid
     * @return array
     */
    public static function execute(int $courseid): array {
        global $DB;
        ['courseid' => $courseid] = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('moodle/grade:viewall', $context);
        require_capability('moodle/course:viewparticipants', $context);

        $items = $DB->get_records_select('grade_items', "courseid = :c AND itemtype IN ('mod', 'manual')",
            ['c' => $courseid], 'sortorder', 'id, itemname, itemtype, itemmodule, grademax, hidden');
        $grades = [];
        if ($items) {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($items), SQL_PARAMS_NAMED);
            $rs = $DB->get_recordset_select('grade_grades', "itemid $insql AND finalgrade IS NOT NULL", $params,
                '', 'id, itemid, userid, finalgrade, feedback');
            foreach ($rs as $g) {
                $grades[] = ['itemid' => (int) $g->itemid, 'userid' => (int) $g->userid,
                    'finalgrade' => (float) $g->finalgrade,
                    'feedback' => \core_text::substr(trim(strip_tags((string) $g->feedback)), 0, 500)];
            }
            $rs->close();
        }
        $people = $DB->get_records_sql(
            "SELECT DISTINCT u.id, u.idnumber, u.email
               FROM {user} u
               JOIN {user_enrolments} ue ON ue.userid = u.id
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE e.courseid = :c AND u.deleted = 0", ['c' => $courseid]);

        return [
            'items' => array_values(array_map(fn($i) => [
                'id' => (int) $i->id,
                'name' => (string) ($i->itemname ?? ''),
                'itemtype' => (string) $i->itemtype,
                'itemmodule' => (string) ($i->itemmodule ?? ''),
                'grademax' => (float) $i->grademax,
                'hidden' => (bool) $i->hidden,
            ], $items)),
            'grades' => $grades,
            'people' => array_values(array_map(fn($u) => [
                'id' => (int) $u->id, 'idnumber' => (string) $u->idnumber, 'email' => (string) $u->email,
            ], $people)),
        ];
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'items' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'grade item id'),
                'name' => new external_value(PARAM_RAW, 'item name'),
                'itemtype' => new external_value(PARAM_ALPHA, 'mod or manual'),
                'itemmodule' => new external_value(PARAM_RAW, 'module, for an activity'),
                'grademax' => new external_value(PARAM_FLOAT, 'maximum grade'),
                'hidden' => new external_value(PARAM_BOOL, 'hidden from students in Moodle'),
            ])),
            'grades' => new external_multiple_structure(new external_single_structure([
                'itemid' => new external_value(PARAM_INT, 'grade item id'),
                'userid' => new external_value(PARAM_INT, 'user id'),
                'finalgrade' => new external_value(PARAM_FLOAT, 'grade'),
                'feedback' => new external_value(PARAM_RAW, 'feedback, as plain text'),
            ])),
            'people' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'user id'),
                'idnumber' => new external_value(PARAM_RAW, 'ID number (registration number)'),
                'email' => new external_value(PARAM_RAW, 'email'),
            ])),
        ]);
    }
}
