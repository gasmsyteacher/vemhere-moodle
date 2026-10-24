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
 * One category's courses, enrolments, managers and people.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\external\import_category
 */
final class import_category_test extends \advanced_testcase {
    public function test_a_department_comes_back_whole(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $dept = $gen->create_category(['name' => 'Physics']);
        $course = $gen->create_course(['category' => $dept->id, 'shortname' => 'PHY201']);
        $lecturer = $gen->create_user(['idnumber' => 'STAFF-17']);
        $active = $gen->create_user(['idnumber' => 'UBa24S001']);
        $suspended = $gen->create_user(['idnumber' => 'UBa24S002']);
        $head = $gen->create_user();
        $gen->enrol_user($lecturer->id, $course->id, 'editingteacher');
        $gen->enrol_user($active->id, $course->id, 'student');
        $gen->enrol_user($suspended->id, $course->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);
        role_assign(
            $DB->get_field('role', 'id', ['shortname' => 'manager']),
            $head->id,
            \context_coursecat::instance($dept->id)->id
        );

        $result = \core_external\external_api::clean_returnvalue(
            import_category::execute_returns(),
            import_category::execute($dept->id)
        );

        $this->assertSame(['PHY201'], array_column($result['courses'], 'shortname'));
        $roles = [];
        foreach ($result['enrolments'] as $e) {
            $roles[$e['userid']] = [$e['role'], $e['active']];
        }
        $this->assertSame(['teacher', true], $roles[$lecturer->id]);
        $this->assertSame(['student', true], $roles[$active->id]);
        $this->assertSame(['student', false], $roles[$suspended->id]);
        $this->assertSame([(int) $head->id], array_column($result['managers'], 'userid'));
        $numbers = array_column($result['people'], 'idnumber', 'id');
        $this->assertSame('UBa24S001', $numbers[$active->id]);
    }

    public function test_someone_without_the_capability_is_refused(): void {
        $this->resetAfterTest();
        $dept = $this->getDataGenerator()->create_category();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        import_category::execute($dept->id);
    }
}
