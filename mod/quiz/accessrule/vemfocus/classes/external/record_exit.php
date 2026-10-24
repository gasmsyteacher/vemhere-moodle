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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_quiz\quiz_settings;

/**
 * Records one exit from the student's own attempt in progress and says what happens next.
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class record_exit extends external_api {
    /** @var int an absence longer than this is stored as this (a phone left locked for hours is one long exit). */
    const MAX_AWAY = 86400;

    /**
     * Parameters: the attempt and how long the student was away.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Quiz attempt id'),
            'awayseconds' => new external_value(PARAM_INT, 'Seconds away from the quiz page'),
        ]);
    }

    /**
     * Record one exit and say what happens next.
     *
     * @param int $attemptid
     * @param int $awayseconds
     * @return array
     */
    public static function execute(int $attemptid, int $awayseconds): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/quiz/accessrule/vemfocus/rule.php');
        ['attemptid' => $attemptid, 'awayseconds' => $awayseconds] =
            self::validate_parameters(self::execute_parameters(), ['attemptid' => $attemptid, 'awayseconds' => $awayseconds]);

        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'id, quiz, userid, preview, state', MUST_EXIST);
        $quizobj = quiz_settings::create($attempt->quiz);
        self::validate_context($quizobj->get_context());
        if ($attempt->userid != $USER->id) {
            throw new \moodle_exception('notyourattempt', 'quiz');
        }
        $settings = $DB->get_record('quizaccess_vemfocus', ['quizid' => $attempt->quiz]);
        if ($attempt->preview || $attempt->state !== 'inprogress' || !$settings || !$settings->mode) {
            return ['exits' => 0, 'maxexits' => 0, 'action' => 'none'];   // Not monitored: record nothing.
        }

        $exits = $DB->count_records('quizaccess_vemfocus_exit', ['attemptid' => $attempt->id]) + 1;
        $action = 'none';
        if ($settings->mode == \quizaccess_vemfocus::MODE_WARN) {
            $action = 'warn';
        } else if ($settings->mode == \quizaccess_vemfocus::MODE_SUBMIT) {
            $action = $exits >= $settings->maxexits ? 'submit' : 'warn';
        }
        $DB->insert_record('quizaccess_vemfocus_exit', (object) [
            'quizid' => $attempt->quiz, 'attemptid' => $attempt->id, 'userid' => $USER->id,
            'awayseconds' => max(0, min(self::MAX_AWAY, $awayseconds)), 'action' => $action, 'timecreated' => time(),
        ]);
        $max = $settings->mode == \quizaccess_vemfocus::MODE_SUBMIT ? (int) $settings->maxexits : 0;
        return ['exits' => $exits, 'maxexits' => $max, 'action' => $action];
    }

    /**
     * Exits so far, exits allowed, and the action for the page.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'exits' => new external_value(PARAM_INT, 'Exits so far in this attempt'),
            'maxexits' => new external_value(PARAM_INT, 'Exits allowed before the attempt is submitted (0 = no limit)'),
            'action' => new external_value(PARAM_ALPHA, 'none, warn or submit'),
        ]);
    }
}
