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
 * What the plugin declares leaves the site.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    public function test_vemhere_is_declared_with_what_it_receives(): void {
        $items = provider::get_metadata(new collection('local_vemhere'))->get_collection();
        $this->assertCount(1, $items);
        $this->assertSame('vemhere', $items[0]->get_name());
        $this->assertEqualsCanonicalizing(
            ['firstname', 'lastname', 'email', 'idnumber', 'enrolments', 'grades'],
            array_keys($items[0]->get_privacy_fields())
        );
    }
}
