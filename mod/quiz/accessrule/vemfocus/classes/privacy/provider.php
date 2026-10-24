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

namespace quizaccess_vemfocus\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_userlist;
use mod_quiz\quiz_settings;

/**
 * Privacy: the exit records are personal data of the student; export and delete them with the quiz's data.
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \mod_quiz\privacy\quizaccess_provider,
    \mod_quiz\privacy\quizaccess_user_provider {
    /**
     * The exit records this plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('quizaccess_vemfocus_exit', [
            'attemptid' => 'privacy:metadata:quizaccess_vemfocus_exit:attemptid',
            'userid' => 'privacy:metadata:quizaccess_vemfocus_exit:userid',
            'awayseconds' => 'privacy:metadata:quizaccess_vemfocus_exit:awayseconds',
            'action' => 'privacy:metadata:quizaccess_vemfocus_exit:action',
            'timecreated' => 'privacy:metadata:quizaccess_vemfocus_exit:timecreated',
        ], 'privacy:metadata:quizaccess_vemfocus_exit');
        return $collection;
    }

    /**
     * One student's exits from one quiz, for a data export.
     *
     * @param quiz_settings $quiz
     * @param \stdClass $user
     * @return \stdClass
     */
    public static function export_quizaccess_user_data(quiz_settings $quiz, \stdClass $user): \stdClass {
        global $DB;
        $exits = [];
        foreach (
            $DB->get_records(
                'quizaccess_vemfocus_exit',
                ['quizid' => $quiz->get_quizid(), 'userid' => $user->id],
                'timecreated'
            ) as $e
        ) {
            $exits[] = ['attempt' => $e->attemptid, 'came_back' => \core_privacy\local\request\transform::datetime($e->timecreated),
                'seconds_away' => $e->awayseconds, 'action' => $e->action];
        }
        return $exits ? (object) [get_string('privacy:exitsheading', 'quizaccess_vemfocus') => $exits] : new \stdClass();
    }

    /**
     * Delete every student's exits from one quiz.
     *
     * @param quiz_settings $quiz
     */
    public static function delete_quizaccess_data_for_all_users_in_context(quiz_settings $quiz) {
        global $DB;
        $DB->delete_records('quizaccess_vemfocus_exit', ['quizid' => $quiz->get_quizid()]);
    }

    /**
     * Delete one student's exits from one quiz.
     *
     * @param quiz_settings $quiz
     * @param \stdClass $user
     */
    public static function delete_quizaccess_data_for_user(quiz_settings $quiz, \stdClass $user) {
        global $DB;
        $DB->delete_records('quizaccess_vemfocus_exit', ['quizid' => $quiz->get_quizid(), 'userid' => $user->id]);
    }

    /**
     * Delete some students' exits from one quiz.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_quizaccess_data_for_users(approved_userlist $userlist) {
        global $DB;
        $cm = get_coursemodule_from_id('quiz', $userlist->get_context()->instanceid);
        if (!$cm) {
            return;
        }
        [$in, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select(
            'quizaccess_vemfocus_exit',
            "quizid = :quizid AND userid $in",
            ['quizid' => $cm->instance] + $params
        );
    }
}
