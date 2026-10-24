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

/**
 * Focus report of one quiz: per attempt, how often and how long the student left the quiz.
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/quiz:viewreports', $context);

$PAGE->set_url('/mod/quiz/accessrule/vemfocus/report.php', ['cmid' => $cmid]);
$PAGE->set_title(get_string('report', 'quizaccess_vemfocus'));
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->disable();

$userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
$rows = $DB->get_records_sql("
    SELECT qa.id, qa.attempt, qa.state, qa.timestart, qa.userid, $userfields,
           COUNT(e.id) AS exits, COALESCE(SUM(e.awayseconds), 0) AS away,
           MAX(CASE WHEN e.action = 'submit' THEN 1 ELSE 0 END) AS submitted
      FROM {quiz_attempts} qa
      JOIN {user} u ON u.id = qa.userid
 LEFT JOIN {quizaccess_vemfocus_exit} e ON e.attemptid = qa.id
     WHERE qa.quiz = :quiz AND qa.preview = 0
  GROUP BY qa.id, qa.attempt, qa.state, qa.timestart, qa.userid, $userfields
  ORDER BY COUNT(e.id) DESC, u.lastname, qa.attempt", ['quiz' => $cm->instance]);

$fmt = fn(int $s) => sprintf('%d:%02d', intdiv($s, 60), $s % 60);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'quizaccess_vemfocus') . ': ' . format_string($cm->name));
if (!$rows) {
    echo $OUTPUT->notification(get_string('reportnone', 'quizaccess_vemfocus'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('name'), get_string('reportattempt', 'quizaccess_vemfocus'), get_string('status'),
        get_string('reportexits', 'quizaccess_vemfocus'), get_string('reportaway', 'quizaccess_vemfocus'),
        get_string('reportsubmitted', 'quizaccess_vemfocus'), get_string('reporttimes', 'quizaccess_vemfocus')];
    foreach ($rows as $r) {
        $times = [];
        foreach ($DB->get_records('quizaccess_vemfocus_exit', ['attemptid' => $r->id], 'timecreated') as $e) {
            $times[] = userdate($e->timecreated, '%H:%M:%S') . ' (' . $e->awayseconds . ' s)';
        }
        $table->data[] = [fullname($r), $r->attempt, get_string('state' . $r->state, 'quiz'), $r->exits, $fmt((int) $r->away),
            $r->submitted ? get_string('yes') : '', implode(', ', $times)];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
