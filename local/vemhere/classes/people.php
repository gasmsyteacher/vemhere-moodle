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

namespace local_vemhere;

/**
 * Finding a Moodle account the way Vemhere names people: ID number (the registration number), then email.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class people {
    /**
     * A live account by ID number, else by email; none when either is ambiguous.
     *
     * @param string $idnumber
     * @param string $email
     * @return \stdClass|null
     */
    public static function find(string $idnumber, string $email): ?\stdClass {
        global $DB, $CFG;
        if (trim($idnumber) !== '') {
            $found = $DB->get_records('user', ['deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id,
                'idnumber' => trim($idnumber)], '', 'id', 0, 2);
            if (count($found) === 1) {
                return reset($found);
            }
        }
        if (trim($email) !== '') {
            $found = $DB->get_records_select(
                'user',
                'deleted = 0 AND mnethostid = :host AND LOWER(email) = LOWER(:email)',
                ['host' => $CFG->mnet_localhost_id, 'email' => trim($email)],
                '',
                'id',
                0,
                2
            );
            if (count($found) === 1) {
                return reset($found);
            }
        }
        return null;
    }
}
