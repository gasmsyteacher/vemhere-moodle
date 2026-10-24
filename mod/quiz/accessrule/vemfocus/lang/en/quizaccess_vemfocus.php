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
 * Strings for quizaccess_vemfocus.
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Focus monitor (Vemhere)';
$string['description_record'] = 'During an attempt, Moodle records each time you leave the quiz page (another app or tab, or a locked screen) and for how long.';
$string['description_warn'] = 'During an attempt, Moodle records each time you leave the quiz page (another app or tab, or a locked screen) and warns you each time.';
$string['description_submit'] = 'During an attempt, Moodle records each time you leave the quiz page (another app or tab, or a locked screen). After {$a} exits, your attempt is submitted with the answers you have given.';
$string['maxexits'] = 'Exits allowed';
$string['maxexits_help'] = 'The attempt is submitted automatically when the student leaves the quiz this many times. Each exit before that shows a warning.';
$string['maxexitserror'] = 'Allow at least one exit.';
$string['mode'] = 'Focus monitor';
$string['mode_help'] = 'Records when a student leaves the quiz page during an attempt (switches app or tab, or locks the screen), and for how long. Students are told before they start. "Warn" shows a message each time; "Submit" also submits the attempt after the allowed number of exits. It cannot see a second device: it deters and gives evidence. Teachers\' previews are not monitored.';
$string['mode_off'] = 'Off';
$string['mode_record'] = 'Record exits only';
$string['mode_submit'] = 'Record, warn, and submit after too many exits';
$string['mode_warn'] = 'Record exits and warn the student';
$string['preflight_record'] = 'This quiz records each time you leave it during your attempt (another app or tab, or a locked screen), and for how long. Your lecturer can see this record.';
$string['preflight_warn'] = 'This quiz records each time you leave it during your attempt (another app or tab, or a locked screen), and warns you each time. Your lecturer can see this record.';
$string['preflight_submit'] = 'This quiz records each time you leave it during your attempt (another app or tab, or a locked screen). After {$a} exits, your attempt is submitted with the answers you have given. Your lecturer can see this record.';
$string['preflightaccept'] = 'I understand';
$string['preflightheader'] = 'Stay on the quiz';
$string['preflightrequired'] = 'Tick "I understand" to start.';
$string['privacy:metadata:quizaccess_vemfocus_exit'] = 'Each time a student leaves a monitored quiz during an attempt.';
$string['privacy:metadata:quizaccess_vemfocus_exit:action'] = 'What followed the exit: nothing, a warning, or the submission of the attempt.';
$string['privacy:metadata:quizaccess_vemfocus_exit:attemptid'] = 'The quiz attempt.';
$string['privacy:metadata:quizaccess_vemfocus_exit:awayseconds'] = 'How long the student was away, in seconds.';
$string['privacy:metadata:quizaccess_vemfocus_exit:timecreated'] = 'When the student came back to the quiz.';
$string['privacy:metadata:quizaccess_vemfocus_exit:userid'] = 'The student.';
$string['privacy:exitsheading'] = 'Times you left the quiz';
$string['report'] = 'Focus report';
$string['reportattempt'] = 'Attempt';
$string['reportaway'] = 'Time away';
$string['reportexits'] = 'Exits';
$string['reportlink'] = 'See the focus report.';
$string['reportnone'] = 'No monitored attempts yet.';
$string['reportsubmitted'] = 'Submitted by the monitor';
$string['reporttimes'] = 'Exits (time back, seconds away)';
$string['submitmessage'] = 'You left the quiz {$a->exits} times, the most allowed. Your attempt is now being submitted with the answers you have given.';
$string['submittitle'] = 'Attempt submitted';
$string['warnmessage'] = 'You left the quiz ({$a->exits} of {$a->max} exits allowed). Stay on this page until you finish; your lecturer can see each exit.';
$string['warnmessage_nomax'] = 'You left the quiz ({$a->exits} times so far). Stay on this page until you finish; your lecturer can see each exit.';
$string['warntitle'] = 'Please stay on the quiz';
