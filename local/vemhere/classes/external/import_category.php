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
 * Everything Vemhere needs from one category, in one call (MP2, V-D58): its
 * courses, who is enrolled in each and as what, the category's managers, and
 * those people's names, email and ID number (the registration number).
 *
 * Read only. Courses in sub-categories come with their own call.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_category extends external_api {
    /** @var string[] Role archetypes Vemhere reads as a lecturer. */
    const TEACHING = ['editingteacher', 'teacher'];

    /** @var string[] Role archetypes Vemhere reads as head of the department. */
    const MANAGING = ['manager', 'coursecreator'];

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Moodle course category id'),
        ]);
    }

    /**
     * One category's courses, enrolments, managers and people.
     *
     * @param int $categoryid
     * @return array
     */
    public static function execute(int $categoryid): array {
        global $DB;
        ['categoryid' => $categoryid] = self::validate_parameters(self::execute_parameters(),
            ['categoryid' => $categoryid]);
        $context = \context_coursecat::instance($categoryid);
        self::validate_context($context);
        require_capability('moodle/course:viewparticipants', $context);
        require_capability('moodle/user:viewalldetails', $context);

        $courses = $DB->get_records('course', ['category' => $categoryid], 'sortorder',
            'id, shortname, fullname, idnumber, category, visible');
        $userids = [];
        $enrolments = [];

        if ($courses) {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED);
            $now = time();

            // Roles each person holds in each course.
            $roles = [];
            $sql = "SELECT ra.id, ctx.instanceid AS courseid, ra.userid, r.archetype
                      FROM {role_assignments} ra
                      JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :courselevel
                      JOIN {role} r ON r.id = ra.roleid
                     WHERE ctx.instanceid $insql";
            foreach ($DB->get_recordset_sql($sql, $params + ['courselevel' => CONTEXT_COURSE]) as $ra) {
                $roles[$ra->courseid][$ra->userid][] = $ra->archetype;
            }

            $sql = "SELECT ue.id, e.courseid, ue.userid, ue.status AS uestatus, e.status AS estatus,
                           ue.timestart, ue.timeend
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                      JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
                     WHERE e.courseid $insql";
            $seen = [];
            foreach ($DB->get_recordset_sql($sql, $params) as $ue) {
                $held = $roles[$ue->courseid][$ue->userid] ?? [];
                if (array_intersect(self::TEACHING, $held)) {
                    $role = 'teacher';
                } else if (in_array('student', $held)) {
                    $role = 'student';
                } else {
                    continue;      // Guests, observers and custom roles stay in Moodle.
                }
                $active = (int) $ue->uestatus === ENROL_USER_ACTIVE
                    && (int) $ue->estatus === ENROL_INSTANCE_ENABLED
                    && (!$ue->timestart || $ue->timestart <= $now)
                    && (!$ue->timeend || $ue->timeend > $now);
                $key = $ue->courseid . ':' . $ue->userid;
                // A person enrolled twice (manual and self) is active if either is.
                if (isset($seen[$key])) {
                    $enrolments[$seen[$key]]['active'] = $enrolments[$seen[$key]]['active'] || $active;
                    continue;
                }
                $seen[$key] = count($enrolments);
                $enrolments[] = ['courseid' => (int) $ue->courseid, 'userid' => (int) $ue->userid,
                    'role' => $role, 'active' => $active];
                $userids[$ue->userid] = true;
            }
        }

        $managers = [];
        $sql = "SELECT DISTINCT ra.userid
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.contextid = :contextid AND r.archetype IN ('manager', 'coursecreator')";
        foreach ($DB->get_fieldset_sql($sql, ['contextid' => $context->id]) as $userid) {
            $managers[] = ['userid' => (int) $userid];
            $userids[$userid] = true;
        }

        $people = [];
        if ($userids) {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($userids), SQL_PARAMS_NAMED);
            foreach ($DB->get_records_select('user', "id $insql AND deleted = 0", $params, 'id',
                    'id, email, firstname, lastname, idnumber, suspended') as $u) {
                $people[] = ['id' => (int) $u->id, 'email' => (string) $u->email,
                    'firstname' => (string) $u->firstname, 'lastname' => (string) $u->lastname,
                    'idnumber' => (string) $u->idnumber, 'suspended' => (bool) $u->suspended];
            }
        }

        return [
            'courses' => array_values(array_map(fn($c) => [
                'id' => (int) $c->id, 'shortname' => (string) $c->shortname,
                'fullname' => (string) $c->fullname, 'idnumber' => (string) $c->idnumber,
                'category' => (int) $c->category, 'visible' => (bool) $c->visible,
            ], $courses)),
            'enrolments' => $enrolments,
            'managers' => $managers,
            'people' => $people,
        ];
    }

    /**
     * Return shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'course id'),
                'shortname' => new external_value(PARAM_RAW, 'short name'),
                'fullname' => new external_value(PARAM_RAW, 'full name'),
                'idnumber' => new external_value(PARAM_RAW, 'course ID number'),
                'category' => new external_value(PARAM_INT, 'category id'),
                'visible' => new external_value(PARAM_BOOL, 'shown to students'),
            ])),
            'enrolments' => new external_multiple_structure(new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'userid' => new external_value(PARAM_INT, 'user id'),
                'role' => new external_value(PARAM_ALPHA, 'teacher or student'),
                'active' => new external_value(PARAM_BOOL, 'enrolment active now'),
            ])),
            'managers' => new external_multiple_structure(new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'manager or course creator of this category'),
            ])),
            'people' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'user id'),
                'email' => new external_value(PARAM_RAW, 'email'),
                'firstname' => new external_value(PARAM_RAW, 'first name'),
                'lastname' => new external_value(PARAM_RAW, 'last name'),
                'idnumber' => new external_value(PARAM_RAW, 'ID number (registration number)'),
                'suspended' => new external_value(PARAM_BOOL, 'account suspended'),
            ])),
        ]);
    }
}
