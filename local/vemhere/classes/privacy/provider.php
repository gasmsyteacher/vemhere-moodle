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

namespace local_vemhere\privacy;

use core_privacy\local\metadata\collection;

/**
 * The plugin stores nothing about a person in Moodle. Through its web
 * services it gives the Vemhere institution this site is paired with the
 * people of the courses Vemhere manages, their enrolments and their grades.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * What leaves this site, and where to.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link('vemhere', [
            'firstname' => 'privacy:metadata:vemhere:firstname',
            'lastname' => 'privacy:metadata:vemhere:lastname',
            'email' => 'privacy:metadata:vemhere:email',
            'idnumber' => 'privacy:metadata:vemhere:idnumber',
            'enrolments' => 'privacy:metadata:vemhere:enrolments',
            'grades' => 'privacy:metadata:vemhere:grades',
        ], 'privacy:metadata:vemhere');
        return $collection;
    }
}
