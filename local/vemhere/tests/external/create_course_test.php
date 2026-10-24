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

/**
 * A course a head of department makes in Vemhere.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\external\create_course
 */
final class create_course_test extends \advanced_testcase {
    public function test_it_is_hidden_with_self_enrolment_off_and_a_retry_returns_it(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $dept = $this->getDataGenerator()->create_category();
        $first = create_course::execute($dept->id, 'Waves', 'PHY305', 'vemhere:uba:3');
        $again = create_course::execute($dept->id, 'Waves', 'PHY305', 'vemhere:uba:3');
        $this->assertTrue($first['created']);
        $this->assertSame([$first['id'], false], [$again['id'], $again['created']]);
        $course = $DB->get_record('course', ['id' => $first['id']]);
        $this->assertSame([(int) $dept->id, 0], [(int) $course->category, (int) $course->visible]);
        foreach ($DB->get_records_select('enrol', "courseid = ? AND enrol IN ('self', 'guest')", [$course->id]) as $e) {
            $this->assertEquals(ENROL_INSTANCE_DISABLED, $e->status);
        }
    }

    public function test_a_short_name_in_use_is_refused(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $dept = $this->getDataGenerator()->create_category();
        $this->getDataGenerator()->create_course(['shortname' => 'PHY305']);
        $this->expectException(\moodle_exception::class);
        create_course::execute($dept->id, 'Waves', 'PHY305', 'vemhere:uba:4');
    }
}
