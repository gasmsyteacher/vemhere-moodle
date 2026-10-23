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
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
