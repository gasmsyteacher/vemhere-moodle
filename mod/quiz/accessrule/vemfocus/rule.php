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

use mod_quiz\form\preflight_check_form;
use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

/**
 * Focus monitor: records when a student leaves the quiz during an attempt, and (per quiz) warns or submits.
 *
 * Off by default on every quiz. Students are told before they start and must acknowledge it.
 * Teachers' previews are never monitored. Nothing leaves the Moodle site.
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_vemfocus extends access_rule_base {
    /** @var int not monitored (default). */
    const MODE_OFF = 0;
    /** @var int record exits only; the student is never interrupted. */
    const MODE_RECORD = 1;
    /** @var int record and warn the student each time. */
    const MODE_WARN = 2;
    /** @var int record, warn, and submit the attempt after the allowed number of exits. */
    const MODE_SUBMIT = 3;

    /** @var string[] mode => string key suffix. */
    const MODES = [
        self::MODE_OFF => 'off',
        self::MODE_RECORD => 'record',
        self::MODE_WARN => 'warn',
        self::MODE_SUBMIT => 'submit',
    ];

    /**
     * The rule for this quiz, or null when the focus monitor is off.
     *
     * @param quiz_settings $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->vemfocusmode)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * What students read on the quiz page; teachers also get a link to the report.
     */
    public function description() {
        $mode = (int) $this->quiz->vemfocusmode;
        $text = get_string('description_' . self::MODES[$mode], 'quizaccess_vemfocus', (int) $this->quiz->vemfocusmaxexits);
        if (has_capability('mod/quiz:viewreports', $this->quizobj->get_context())) {
            $url = new moodle_url('/mod/quiz/accessrule/vemfocus/report.php', ['cmid' => $this->quizobj->get_cmid()]);
            $text .= ' ' . html_writer::link($url, get_string('reportlink', 'quizaccess_vemfocus'));
        }
        return $text;
    }

    /**
     * Students confirm the rule once per session before an attempt.
     *
     * @param int|null $attemptid
     */
    public function is_preflight_check_required($attemptid) {
        global $SESSION;
        return empty($SESSION->vemfocusaccepted[$this->quiz->id]);
    }

    /**
     * The notice and the "I understand" box before an attempt.
     *
     * @param preflight_check_form $quizform
     * @param MoodleQuickForm $mform
     * @param int|null $attemptid
     */
    public function add_preflight_check_form_fields(preflight_check_form $quizform, MoodleQuickForm $mform, $attemptid) {
        $mode = (int) $this->quiz->vemfocusmode;
        $mform->addElement('header', 'vemfocusheader', get_string('preflightheader', 'quizaccess_vemfocus'));
        $mform->addElement(
            'static',
            'vemfocusmessage',
            '',
            get_string('preflight_' . self::MODES[$mode], 'quizaccess_vemfocus', (int) $this->quiz->vemfocusmaxexits)
        );
        $mform->addElement('checkbox', 'vemfocusaccept', '', get_string('preflightaccept', 'quizaccess_vemfocus'));
    }

    /**
     * "I understand" must be ticked.
     *
     * @param array $data
     * @param array $files
     * @param array $errors
     * @param int|null $attemptid
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        if (empty($data['vemfocusaccept'])) {
            $errors['vemfocusaccept'] = get_string('preflightrequired', 'quizaccess_vemfocus');
        }
        return $errors;
    }

    /**
     * Remember the confirmation for this session.
     *
     * @param int|null $attemptid
     */
    public function notify_preflight_check_passed($attemptid) {
        global $SESSION;
        $SESSION->vemfocusaccepted[$this->quiz->id] = true;
    }

    /**
     * Ask again before the next attempt.
     */
    public function current_attempt_finished() {
        global $SESSION;
        unset($SESSION->vemfocusaccepted[$this->quiz->id]);
    }

    /**
     * Loads the monitor on the attempt and summary pages of a student's own attempt in progress.
     *
     * @param moodle_page $page
     */
    public function setup_attempt_page($page) {
        global $DB, $USER;
        $script = basename($page->url->get_path());
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if (!$attemptid || !in_array($script, ['attempt.php', 'summary.php'])) {
            return;
        }
        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'id, quiz, userid, preview, state');
        if (
            !$attempt || $attempt->preview || $attempt->userid != $USER->id || $attempt->state !== 'inprogress'
                || $attempt->quiz != $this->quiz->id
        ) {
            return;
        }
        $page->requires->strings_for_js(
            ['warntitle', 'warnmessage', 'warnmessage_nomax', 'submittitle', 'submitmessage'],
            'quizaccess_vemfocus'
        );
        $page->requires->js_call_amd('quizaccess_vemfocus/monitor', 'init', [[
            'attemptid' => (int) $attempt->id,
            'mode' => (int) $this->quiz->vemfocusmode,
            'maxexits' => (int) $this->quiz->vemfocusmaxexits,
            'processurl' => (new moodle_url('/mod/quiz/processattempt.php'))->out(false),
        ]]);
    }

    /**
     * The mode and the exits allowed, in the quiz settings.
     *
     * @param mod_quiz_mod_form $quizform
     * @param MoodleQuickForm $mform
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $options = [];
        foreach (self::MODES as $mode => $key) {
            $options[$mode] = get_string('mode_' . $key, 'quizaccess_vemfocus');
        }
        $mform->addElement('select', 'vemfocusmode', get_string('mode', 'quizaccess_vemfocus'), $options);
        $mform->addHelpButton('vemfocusmode', 'mode', 'quizaccess_vemfocus');
        $mform->setDefault('vemfocusmode', self::MODE_OFF);
        $mform->addElement('text', 'vemfocusmaxexits', get_string('maxexits', 'quizaccess_vemfocus'), ['size' => 3]);
        $mform->setType('vemfocusmaxexits', PARAM_INT);
        $mform->setDefault('vemfocusmaxexits', 3);
        $mform->addHelpButton('vemfocusmaxexits', 'maxexits', 'quizaccess_vemfocus');
        $mform->hideIf('vemfocusmaxexits', 'vemfocusmode', 'neq', self::MODE_SUBMIT);
    }

    /**
     * At least one exit must be allowed when the monitor submits.
     *
     * @param array $errors
     * @param array $data
     * @param array $files
     * @param mod_quiz_mod_form $quizform
     */
    public static function validate_settings_form_fields(array $errors, array $data, $files, mod_quiz_mod_form $quizform) {
        if ((int) ($data['vemfocusmode'] ?? 0) === self::MODE_SUBMIT && (int) ($data['vemfocusmaxexits'] ?? 0) < 1) {
            $errors['vemfocusmaxexits'] = get_string('maxexitserror', 'quizaccess_vemfocus');
        }
        return $errors;
    }

    /**
     * Store the quiz setting; Off removes it.
     *
     * @param \stdClass $quiz
     */
    public static function save_settings($quiz) {
        global $DB;
        $mode = (int) ($quiz->vemfocusmode ?? 0);
        $record = $DB->get_record('quizaccess_vemfocus', ['quizid' => $quiz->id]);
        if (!$mode) {
            if ($record) {
                $DB->delete_records('quizaccess_vemfocus', ['id' => $record->id]);
            }
            return;
        }
        $data = (object) ['quizid' => $quiz->id, 'mode' => $mode, 'maxexits' => max(1, (int) ($quiz->vemfocusmaxexits ?? 3))];
        if ($record) {
            $data->id = $record->id;
            $DB->update_record('quizaccess_vemfocus', $data);
        } else {
            $DB->insert_record('quizaccess_vemfocus', $data);
        }
    }

    /**
     * Remove the quiz setting.
     *
     * @param \stdClass $quiz
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_vemfocus', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_vemfocus_exit', ['quizid' => $quiz->id]);
    }

    /**
     * Load the setting with the quiz.
     *
     * @param int $quizid
     */
    public static function get_settings_sql($quizid) {
        return [
            'COALESCE(vemfocus.mode, 0) AS vemfocusmode, COALESCE(vemfocus.maxexits, 3) AS vemfocusmaxexits',
            'LEFT JOIN {quizaccess_vemfocus} vemfocus ON vemfocus.quizid = quiz.id',
            [],
        ];
    }
}
