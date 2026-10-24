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

namespace mod_vemhere;

/**
 * The activity and its grade column.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::vemhere_add_instance
 * @covers     ::vemhere_delete_instance
 */
final class lib_test extends \advanced_testcase {
    public function test_adding_it_makes_a_column_out_of_100_and_deleting_removes_it(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/vemhere/lib.php');
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $vemhere = $this->getDataGenerator()->create_module('vemhere', ['course' => $course->id]);
        $item = $DB->get_record('grade_items', ['courseid' => $course->id, 'itemmodule' => 'vemhere']);
        $this->assertEquals(100, $item->grademax);
        $this->assertSame('Attendance (Vemhere)', $item->itemname);
        course_delete_module($vemhere->cmid);
        $this->assertFalse($DB->record_exists('vemhere', ['id' => $vemhere->id]));
    }

    public function test_it_supports_grades_and_backup(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/vemhere/lib.php');
        $this->assertTrue(vemhere_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertTrue(vemhere_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertFalse(vemhere_supports(FEATURE_GROUPS));
    }
}
