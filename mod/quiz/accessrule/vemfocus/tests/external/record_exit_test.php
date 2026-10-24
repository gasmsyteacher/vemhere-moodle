<?php
// This file is part of the Vemhere plugins for Moodle - https://vemhere.com
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

namespace quizaccess_vemfocus\external;

use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/vemfocus/rule.php');

/**
 * Recording an exit: warn, submit at the limit, and nothing outside the student's own monitored attempt.
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \quizaccess_vemfocus\external\record_exit
 */
final class record_exit_test extends \advanced_testcase {
    /**
     * A quiz with one question, the focus monitor in the given mode, and a student's attempt in progress.
     *
     * @param int $mode one of the rule's MODE_ constants; MODE_OFF stores no setting
     * @param int $maxexits exits allowed before the monitor submits
     * @return array [attempt record, student, quiz]
     */
    private function attempt(int $mode, int $maxexits = 3): array {
        global $DB;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $quiz = $gen->create_module('quiz', ['course' => $course->id]);
        $qgen = $gen->get_plugin_generator('core_question');
        $cat = $qgen->create_question_category(['contextid' => \context_course::instance($course->id)->id]);
        quiz_add_quiz_question($qgen->create_question('truefalse', null, ['category' => $cat->id])->id, $quiz);
        quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
        if ($mode) {
            $DB->insert_record('quizaccess_vemfocus', ['quizid' => $quiz->id, 'mode' => $mode, 'maxexits' => $maxexits]);
        }
        $student = $gen->create_and_enrol($course, 'student');
        $this->setUser($student);
        $attempt = quiz_prepare_and_start_new_attempt(quiz_settings::create($quiz->id, $student->id), 1, null);
        return [$attempt, $student, $quiz];
    }

    public function test_it_warns_then_submits_at_the_limit(): void {
        global $DB;
        $this->resetAfterTest();
        [$attempt] = $this->attempt(\quizaccess_vemfocus::MODE_SUBMIT, 3);
        $actions = [];
        foreach ([4, 0, 12] as $away) {
            $actions[] = record_exit::execute($attempt->id, $away);
        }
        $this->assertSame(['warn', 'warn', 'submit'], array_column($actions, 'action'));
        $this->assertSame([1, 2, 3], array_column($actions, 'exits'));
        $this->assertSame(3, $actions[0]['maxexits']);
        $this->assertEquals(16, $DB->get_field('quizaccess_vemfocus_exit', 'SUM(awayseconds)', ['attemptid' => $attempt->id]));
    }

    public function test_record_only_records_without_a_warning(): void {
        global $DB;
        $this->resetAfterTest();
        [$attempt] = $this->attempt(\quizaccess_vemfocus::MODE_RECORD);
        $r = record_exit::execute($attempt->id, 5);
        $this->assertSame(['exits' => 1, 'maxexits' => 0, 'action' => 'none'], $r);
        $this->assertSame(1, $DB->count_records('quizaccess_vemfocus_exit', ['attemptid' => $attempt->id]));
    }

    public function test_an_unmonitored_quiz_records_nothing(): void {
        global $DB;
        $this->resetAfterTest();
        [$attempt] = $this->attempt(\quizaccess_vemfocus::MODE_OFF);
        $this->assertSame('none', record_exit::execute($attempt->id, 5)['action']);
        $this->assertSame(0, $DB->count_records('quizaccess_vemfocus_exit'));
    }

    public function test_a_finished_attempt_records_nothing(): void {
        global $DB;
        $this->resetAfterTest();
        [$attempt] = $this->attempt(\quizaccess_vemfocus::MODE_SUBMIT);
        $DB->set_field('quiz_attempts', 'state', 'finished', ['id' => $attempt->id]);
        $this->assertSame(0, record_exit::execute($attempt->id, 5)['exits']);
        $this->assertSame(0, $DB->count_records('quizaccess_vemfocus_exit'));
    }

    public function test_a_long_absence_is_capped(): void {
        global $DB;
        $this->resetAfterTest();
        [$attempt] = $this->attempt(\quizaccess_vemfocus::MODE_RECORD);
        record_exit::execute($attempt->id, 999999);
        $this->assertEquals(record_exit::MAX_AWAY, $DB->get_field('quizaccess_vemfocus_exit', 'awayseconds', []));
    }

    public function test_another_students_attempt_is_refused(): void {
        $this->resetAfterTest();
        [$attempt, , $quiz] = $this->attempt(\quizaccess_vemfocus::MODE_SUBMIT);
        $other = $this->getDataGenerator()->create_and_enrol(get_course($quiz->course), 'student');
        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        record_exit::execute($attempt->id, 5);
    }

    public function test_privacy_deletes_one_students_exits(): void {
        global $DB;
        $this->resetAfterTest();
        [$attempt, $student, $quiz] = $this->attempt(\quizaccess_vemfocus::MODE_RECORD);
        record_exit::execute($attempt->id, 5);
        $exported = \quizaccess_vemfocus\privacy\provider::export_quizaccess_user_data(quiz_settings::create($quiz->id), $student);
        $this->assertCount(1, (array) $exported);
        \quizaccess_vemfocus\privacy\provider::delete_quizaccess_data_for_user(quiz_settings::create($quiz->id), $student);
        $this->assertSame(0, $DB->count_records('quizaccess_vemfocus_exit'));
    }
}
