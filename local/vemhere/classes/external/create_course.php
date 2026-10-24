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
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Create a course a head of department made in Vemhere (MP4, V-D58).
 *
 * Vemhere's only way to create a course: in the category Vemhere mapped to the
 * department, hidden until its teacher opens it, with self-enrolment and guest
 * access off. Vemhere never edits or deletes a course.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_course extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'category the department maps to'),
            'fullname' => new external_value(PARAM_TEXT, 'course name'),
            'shortname' => new external_value(PARAM_TEXT, 'course code'),
            'idnumber' => new external_value(PARAM_RAW, 'Vemhere reference', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Create it.
     *
     * @param int $categoryid
     * @param string $fullname
     * @param string $shortname
     * @param string $idnumber
     * @return array
     */
    public static function execute(int $categoryid, string $fullname, string $shortname, string $idnumber = ''): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        $params = self::validate_parameters(self::execute_parameters(), ['categoryid' => $categoryid,
            'fullname' => $fullname, 'shortname' => $shortname, 'idnumber' => $idnumber]);
        $context = \context_coursecat::instance($params['categoryid']);
        self::validate_context($context);
        require_capability('moodle/course:create', $context);

        // Asked twice (a retried request), the same course comes back.
        if ($params['idnumber'] !== '' && ($existing = $DB->get_record('course', ['idnumber' => $params['idnumber']]))) {
            return ['id' => (int) $existing->id, 'created' => false];
        }
        if ($DB->record_exists('course', ['shortname' => $params['shortname']])) {
            throw new \moodle_exception('shortnametaken', '', '', $params['shortname']);
        }
        $course = create_course((object) [
            'category' => $params['categoryid'],
            'fullname' => $params['fullname'],
            'shortname' => $params['shortname'],
            'idnumber' => $params['idnumber'],
            'visible' => 0,
        ]);
        $instances = $DB->get_records_select('enrol', "courseid = :c AND enrol IN ('self', 'guest')", ['c' => $course->id]);
        foreach ($instances as $instance) {
            if ($plugin = enrol_get_plugin($instance->enrol)) {
                $plugin->update_status($instance, ENROL_INSTANCE_DISABLED);
            }
        }
        return ['id' => (int) $course->id, 'created' => true];
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'course id'),
            'created' => new external_value(PARAM_BOOL, 'false when it already existed for this reference'),
        ]);
    }
}
