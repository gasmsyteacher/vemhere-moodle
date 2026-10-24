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
 * Attendance rates into the course's "Attendance (Vemhere)".
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\external\push_attendance
 */
final class push_attendance_test extends \advanced_testcase {
    public function test_without_the_activity_nothing_is_written(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $result = push_attendance::execute($course->id, [['idnumber' => 'X', 'email' => '', 'grade' => 50]]);
        $this->assertFalse($result['module']);
    }

    public function test_rates_land_in_its_column_by_registration_number(): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_user(['idnumber' => 'UBa24S001']);
        $gen->enrol_user($student->id, $course->id, 'student');
        $vemhere = $gen->create_module('vemhere', ['course' => $course->id]);

        $result = push_attendance::execute($course->id, [
            ['idnumber' => 'UBa24S001', 'email' => '', 'grade' => 75.5],
            ['idnumber' => 'NOBODY', 'email' => '', 'grade' => 10],
        ]);

        $this->assertSame([true, 1, ['NOBODY']], [$result['module'], $result['written'], $result['missing']]);
        $grades = grade_get_grades($course->id, 'mod', 'vemhere', $vemhere->id, $student->id);
        $this->assertEquals(75.5, $grades->items[0]->grades[$student->id]->grade);
    }
}
