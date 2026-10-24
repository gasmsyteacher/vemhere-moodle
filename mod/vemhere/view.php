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

/**
 * The activity's page: the course's next session, and the way to it in Vemhere.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('vemhere', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$vemhere = $DB->get_record('vemhere', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/vemhere:view', $context);

\mod_vemhere\event\course_module_viewed::create(['objectid' => $vemhere->id, 'context' => $context])->trigger();

$PAGE->set_url('/mod/vemhere/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($vemhere->name));
$PAGE->set_heading(format_string($course->fullname));

$summary = \local_vemhere\vemhere_api::summary((int) $course->id);
$teaches = has_capability('moodle/course:manageactivities', context_course::instance($course->id));

echo $OUTPUT->header();
if (!$summary || empty($summary['linked'])) {
    echo $OUTPUT->notification(get_string('notlinked', 'vemhere'), \core\output\notification::NOTIFY_INFO, false);
} else {
    if (!empty($summary['open'])) {
        echo $OUTPUT->notification(get_string('opennow', 'vemhere'), \core\output\notification::NOTIFY_SUCCESS, false);
    } else if (!empty($summary['next'])) {
        echo html_writer::tag('p', get_string('nextsession', 'vemhere', userdate(strtotime($summary['next']))));
    } else {
        echo html_writer::tag('p', get_string('nonext', 'vemhere'));
    }
    $buttons = [];
    if ($teaches) {
        $buttons[] = $OUTPUT->single_button(
            new moodle_url($summary['url']),
            get_string('openinvemhere', 'vemhere'),
            'get',
            ['type' => single_button::BUTTON_PRIMARY]
        );
    } else {
        $buttons[] = $OUTPUT->single_button(
            new moodle_url($summary['checkin_url']),
            get_string('checkin', 'vemhere'),
            'get',
            ['type' => single_button::BUTTON_PRIMARY]
        );
        $buttons[] = $OUTPUT->single_button(new moodle_url($summary['url']), get_string('myattendance', 'vemhere'), 'get');
    }
    echo html_writer::div(implode(' ', $buttons), 'd-flex gap-2');
}
echo $OUTPUT->footer();
