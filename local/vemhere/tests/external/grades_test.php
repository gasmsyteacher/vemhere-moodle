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
 * A course's results for Vemhere's gradebook.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\external\grades
 */
final class grades_test extends \advanced_testcase {
    public function test_items_grades_and_people_come_back(): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_user(['idnumber' => 'UBa24S001']);
        $gen->enrol_user($student->id, $course->id, 'student');
        $item = new \grade_item(['courseid' => $course->id, 'itemtype' => 'manual', 'itemname' => 'Test 1',
            'grademax' => 20, 'gradetype' => GRADE_TYPE_VALUE], false);
        $item->insert();
        $item->update_final_grade($student->id, 15, 'manual', '<p>Good <b>work</b>.</p>');

        $result = \core_external\external_api::clean_returnvalue(grades::execute_returns(), grades::execute($course->id));

        $this->assertSame(['Test 1'], array_column($result['items'], 'name'));    // Totals stay in Moodle.
        $this->assertEquals(15.0, $result['grades'][0]['finalgrade']);
        $this->assertSame('Good work.', $result['grades'][0]['feedback']);
        $this->assertSame('UBa24S001', array_column($result['people'], 'idnumber', 'id')[$student->id]);
    }
}
