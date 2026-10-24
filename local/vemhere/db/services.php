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
 * The Vemhere external service: exactly the functions Vemhere calls.
 *
 * Keep this list equal to FUNCTIONS in Vemhere's apps/moodle_sync/client.py.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_vemhere_import' => [
        'classname' => 'local_vemhere\\external\\import_category',
        'description' => 'One category\'s courses, enrolments, managers and people, for Vemhere.',
        'type' => 'read',
        'capabilities' => 'moodle/course:viewparticipants, moodle/user:viewalldetails',
    ],
    'local_vemhere_set_roster' => [
        'classname' => 'local_vemhere\\external\\set_roster',
        'description' => 'Make a course\'s roster what Vemhere says it is.',
        'type' => 'write',
        'capabilities' => 'enrol/manual:enrol, enrol/manual:unenrol',
    ],
    'local_vemhere_create_course' => [
        'classname' => 'local_vemhere\\external\\create_course',
        'description' => 'Create a course a head of department made in Vemhere, hidden, self-enrolment off.',
        'type' => 'write',
        'capabilities' => 'moodle/course:create',
    ],
    'local_vemhere_grades' => [
        'classname' => 'local_vemhere\\external\\grades',
        'description' => 'A course\'s grade items, grades and enrolled people, for Vemhere\'s gradebook.',
        'type' => 'read',
        'capabilities' => 'moodle/grade:viewall, moodle/course:viewparticipants',
    ],
    'local_vemhere_push_attendance' => [
        'classname' => 'local_vemhere\\external\\push_attendance',
        'description' => 'Write attendance rates into the course\'s Attendance (Vemhere) activity.',
        'type' => 'write',
        'capabilities' => 'moodle/grade:edit',
    ],
    'local_vemhere_lock_enrolment' => [
        'classname' => 'local_vemhere\\external\\lock_enrolment',
        'description' => 'Switch self-enrolment off on courses Vemhere manages.',
        'type' => 'write',
        'capabilities' => 'enrol/manual:enrol',
    ],
];

$services = [
    'Vemhere' => [
        'shortname' => 'local_vemhere',
        'functions' => [
            'core_webservice_get_site_info',
            'core_course_get_courses',
            'core_course_get_categories',
            'core_user_get_users_by_field',
            'core_user_create_users',
            'core_user_get_course_user_profiles',
            'core_enrol_get_users_courses',
            'enrol_manual_enrol_users',
            'enrol_manual_unenrol_users',
            'mod_assign_get_assignments',
            'core_grades_update_grades',
            'local_vemhere_import',
            'local_vemhere_set_roster',
            'local_vemhere_lock_enrolment',
            'local_vemhere_create_course',
            'local_vemhere_grades',
            'local_vemhere_push_attendance',
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
