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
 * Make a course's roster what Vemhere says it is (MP3, V-D58).
 *
 * Everyone listed is enrolled through manual enrolments, with their role, active
 * or suspended as listed. With complete (the default), a student enrolled by any
 * method and not listed is suspended: Vemhere decides who is in. Without it,
 * only the people listed change (one enrolment at a time). A teacher not listed is left alone, so
 * a Moodle manager's own staff are never locked out by mistake. People are
 * found by ID number first, then email.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_roster extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'members' => new external_multiple_structure(new external_single_structure([
                'idnumber' => new external_value(PARAM_RAW, 'ID number (registration number)', VALUE_DEFAULT, ''),
                'email' => new external_value(PARAM_RAW, 'email', VALUE_DEFAULT, ''),
                'role' => new external_value(PARAM_ALPHA, 'student or teacher'),
                'active' => new external_value(PARAM_BOOL, 'active, or suspended'),
            ]), 'the roster; empty suspends every student', VALUE_DEFAULT, []),
            'complete' => new external_value(PARAM_BOOL,
                'true: this is the whole roster, so students not listed are suspended; '
                . 'false: apply only the people listed', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Apply the roster.
     *
     * @param int $courseid
     * @param array $members
     * @param bool $complete
     * @return array
     */
    public static function execute(int $courseid, array $members = [], bool $complete = true): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(),
            ['courseid' => $courseid, 'members' => $members, 'complete' => $complete]);
        $course = get_course($params['courseid']);
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('enrol/manual:enrol', $context);
        require_capability('enrol/manual:unenrol', $context);

        $manual = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        if (!$instance) {
            $instance = $DB->get_record('enrol', ['id' => $manual->add_default_instance($course)], '*', MUST_EXIST);
        }
        $roleids = [
            'student' => (int) (array_key_first(get_archetype_roles('student')) ?? 0),
            'teacher' => (int) (array_key_first(get_archetype_roles('editingteacher')) ?? 0),
        ];

        $listed = [];
        $missing = [];
        $enrolled = 0;
        $suspended = 0;
        foreach ($params['members'] as $m) {
            $user = self::find_user($m['idnumber'], $m['email']);
            if (!$user) {
                $missing[] = $m['idnumber'] !== '' ? $m['idnumber'] : $m['email'];
                continue;
            }
            $listed[$user->id] = true;
            $roleid = $roleids[$m['role']] ?? 0;
            if (!$roleid) {
                continue;
            }
            if ($m['active']) {
                $manual->enrol_user($instance, $user->id, $roleid, 0, 0, ENROL_USER_ACTIVE);
                $enrolled++;
            } else if ($DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
                $manual->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
                $suspended++;
            }
        }

        // Students Vemhere did not list: suspended, by whichever method enrolled them.
        $studentroles = array_keys(get_archetype_roles('student'));
        if ($params['complete'] && $studentroles) {
            [$rolesql, $roleparams] = $DB->get_in_or_equal($studentroles, SQL_PARAMS_NAMED);
            $sql = "SELECT ue.id, ue.userid, e.id AS enrolid, e.enrol
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE e.courseid = :courseid AND ue.status = :active
                       AND EXISTS (SELECT 1 FROM {role_assignments} ra
                                    WHERE ra.userid = ue.userid AND ra.contextid = :ctx AND ra.roleid $rolesql)";
            $rows = $DB->get_records_sql($sql, ['courseid' => $course->id, 'active' => ENROL_USER_ACTIVE,
                'ctx' => $context->id] + $roleparams);
            foreach ($rows as $row) {
                if (isset($listed[$row->userid])) {
                    continue;
                }
                $plugin = enrol_get_plugin($row->enrol);
                $inst = $DB->get_record('enrol', ['id' => $row->enrolid], '*', MUST_EXIST);
                if ($plugin) {
                    $plugin->update_user_enrol($inst, $row->userid, ENROL_USER_SUSPENDED);
                    $suspended++;
                }
            }
        }
        return ['enrolled' => $enrolled, 'suspended' => $suspended, 'missing' => $missing];
    }

    /**
     * A live account by ID number, else by email; none when either is ambiguous.
     *
     * @param string $idnumber
     * @param string $email
     * @return \stdClass|null
     */
    private static function find_user(string $idnumber, string $email): ?\stdClass {
        global $DB, $CFG;
        $base = ['deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id];
        if (trim($idnumber) !== '') {
            $found = $DB->get_records('user', $base + ['idnumber' => trim($idnumber)], '', 'id', 0, 2);
            if (count($found) === 1) {
                return reset($found);
            }
        }
        if (trim($email) !== '') {
            $found = $DB->get_records_select('user', 'deleted = 0 AND mnethostid = :host AND LOWER(email) = LOWER(:email)',
                ['host' => $CFG->mnet_localhost_id, 'email' => trim($email)], '', 'id', 0, 2);
            if (count($found) === 1) {
                return reset($found);
            }
        }
        return null;
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'enrolled' => new external_value(PARAM_INT, 'listed and active'),
            'suspended' => new external_value(PARAM_INT, 'suspended: listed inactive, or a student not listed'),
            'missing' => new external_multiple_structure(new external_value(PARAM_RAW, 'ID number or email with no Moodle account')),
        ]);
    }
}
