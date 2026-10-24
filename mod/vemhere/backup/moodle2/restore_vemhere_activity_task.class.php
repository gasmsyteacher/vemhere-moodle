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
 * Restore task.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/vemhere/backup/moodle2/restore_vemhere_stepslib.php');

/**
 * Restores one Attendance (Vemhere).
 */
class restore_vemhere_activity_task extends restore_activity_task {
    /**
     * No settings of its own.
     */
    protected function define_my_settings() {
    }

    /**
     * Its one step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_vemhere_activity_structure_step('vemhere_structure', 'vemhere.xml'));
    }

    /**
     * Text fields that may hold links.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('vemhere', ['intro'], 'vemhere')];
    }

    /**
     * Link rules matching encode_content_links.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('VEMHEREVIEWBYID', '/mod/vemhere/view.php?id=$1', 'course_module'),
            new restore_decode_rule('VEMHEREINDEX', '/mod/vemhere/index.php?id=$1', 'course'),
        ];
    }

    /**
     * No log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * No course log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
