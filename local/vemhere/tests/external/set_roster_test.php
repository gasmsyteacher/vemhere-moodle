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
 * Making a course's roster what Vemhere says it is.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\external\set_roster
 */
final class set_roster_test extends \advanced_testcase {
    /** @var \stdClass */
    private $course;

    /**
     * A course with self-enrolment on.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $self = $DB->get_record('enrol', ['courseid' => $this->course->id, 'enrol' => 'self']);
        enrol_get_plugin('self')->update_status($self, ENROL_INSTANCE_ENABLED);
    }

    /**
     * Whether ``$userid`` holds an active enrolment in the course.
     *
     * @param int $userid
     * @return bool
     */
    private function active(int $userid): bool {
        return is_enrolled(\context_course::instance($this->course->id), $userid, '', true);
    }

    /**
     * Run the function and clean its answer.
     *
     * @param array $members
     * @param bool $complete
     * @return array
     */
    private function roster(array $members, bool $complete = true): array {
        return \core_external\external_api::clean_returnvalue(
            set_roster::execute_returns(),
            set_roster::execute($this->course->id, $members, $complete)
        );
    }

    public function test_listed_people_are_in_and_an_unlisted_student_is_suspended(): void {
        $gen = $this->getDataGenerator();
        $listed = $gen->create_user(['idnumber' => 'UBa24S001']);
        $selfenrolled = $gen->create_user();
        $lecturer = $gen->create_user();
        $gen->enrol_user($selfenrolled->id, $this->course->id, 'student', 'self');
        $gen->enrol_user($lecturer->id, $this->course->id, 'editingteacher');

        $result = $this->roster([
            ['idnumber' => 'UBa24S001', 'email' => '', 'role' => 'student', 'active' => true],
            ['idnumber' => 'NOBODY', 'email' => '', 'role' => 'student', 'active' => true],
        ]);

        $this->assertTrue($this->active($listed->id));
        $this->assertFalse($this->active($selfenrolled->id));
        $this->assertTrue($this->active($lecturer->id));          // A lecturer not listed is left alone.
        $this->assertSame(['NOBODY'], $result['missing']);
    }

    public function test_one_person_at_a_time_touches_nobody_else(): void {
        $gen = $this->getDataGenerator();
        $other = $gen->create_user();
        $gen->enrol_user($other->id, $this->course->id, 'student', 'self');
        $one = $gen->create_user(['email' => 'one@example.com']);
        $this->roster([['idnumber' => '', 'email' => 'ONE@example.com', 'role' => 'student', 'active' => true]], false);
        $this->assertTrue($this->active($one->id));
        $this->assertTrue($this->active($other->id));
    }

    public function test_an_empty_roster_suspends_every_student(): void {
        $gen = $this->getDataGenerator();
        $student = $gen->create_user();
        $gen->enrol_user($student->id, $this->course->id, 'student');
        $this->roster([]);
        $this->assertFalse($this->active($student->id));
    }
}
