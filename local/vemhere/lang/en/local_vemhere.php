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
 * English strings.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['badkey'] = 'This is not a Vemhere pairing key. Copy it again from Vemhere.';
$string['cachedef_summary'] = 'What Vemhere says about a course, for a minute';
$string['connect'] = 'Connect';
$string['connected'] = 'Connected to {$a} on Vemhere.';
$string['connectfailed'] = 'Vemhere refused the connection: {$a}';
$string['intro'] = 'Paste the pairing key your Vemhere administrator made.';
$string['pairingkey'] = 'Pairing key';
$string['pairingkey_help'] = 'In Vemhere: Moodle, then Make a pairing key. It works once, for 15 minutes.';
$string['pluginname'] = 'Vemhere';
$string['privacy:metadata:vemhere'] = 'The Vemhere institution this site is paired with. It decides who is enrolled in the courses it manages and keeps their attendance; it reads what follows through this plugin\'s web services.';
$string['privacy:metadata:vemhere:email'] = 'The email address, to match the person in Vemhere.';
$string['privacy:metadata:vemhere:enrolments'] = 'The person\'s enrolments and roles in the courses Vemhere manages.';
$string['privacy:metadata:vemhere:firstname'] = 'The first name.';
$string['privacy:metadata:vemhere:grades'] = 'The person\'s grades and feedback in those courses.';
$string['privacy:metadata:vemhere:idnumber'] = 'The ID number, used as the registration number.';
$string['privacy:metadata:vemhere:lastname'] = 'The last name.';
$string['servicerole'] = 'Vemhere service';
$string['serviceroledesc'] = 'Used by the Vemhere plugin. Holds only what Vemhere calls.';
$string['status_connected'] = 'Connected to {$a->institution} on Vemhere since {$a->date}.';
$string['status_missing'] = 'Vemhere cannot call: {$a}';
