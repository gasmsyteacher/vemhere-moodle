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
 * Switch self-enrolment off on courses Vemhere manages (MP3, V-D58): nobody
 * enrols themselves on Moodle; Vemhere decides who is in.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lock_enrolment extends external_api {
    /** @var string[] Enrolment methods a person uses to enrol themselves. */
    const SELF_SERVE = ['self', 'guest'];

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id')),
        ]);
    }

    /**
     * Disable the self-serve methods on each course.
     *
     * @param array $courseids
     * @return array
     */
    public static function execute(array $courseids): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['courseids' => $courseids]);
        $locked = 0;
        foreach (array_unique($params['courseids']) as $courseid) {
            $context = \context_course::instance($courseid, IGNORE_MISSING);
            if (!$context) {
                continue;
            }
            self::validate_context($context);
            require_capability('enrol/manual:enrol', $context);
            [$insql, $inparams] = $DB->get_in_or_equal(self::SELF_SERVE, SQL_PARAMS_NAMED);
            $instances = $DB->get_records_select('enrol', "courseid = :courseid AND status = :on AND enrol $insql",
                ['courseid' => $courseid, 'on' => ENROL_INSTANCE_ENABLED] + $inparams);
            foreach ($instances as $instance) {
                if ($plugin = enrol_get_plugin($instance->enrol)) {
                    $plugin->update_status($instance, ENROL_INSTANCE_DISABLED);
                    $locked++;
                }
            }
        }
        return ['locked' => $locked];
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'locked' => new external_value(PARAM_INT, 'self-serve methods switched off'),
        ]);
    }
}
