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
 * Every "Attendance (Vemhere)" in a course.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);
$PAGE->set_url('/mod/vemhere/index.php', ['id' => $id]);
$PAGE->set_title(get_string('modulenameplural', 'vemhere'));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
$rows = [];
foreach (get_all_instances_in_course('vemhere', $course) as $instance) {
    $rows[] = [html_writer::link(new moodle_url('/mod/vemhere/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name))];
}
$table = new html_table();
$table->head = [get_string('name')];
$table->data = $rows;
echo $rows ? html_writer::table($table) : $OUTPUT->notification(get_string('noinstances', 'vemhere'), 'info');
echo $OUTPUT->footer();
