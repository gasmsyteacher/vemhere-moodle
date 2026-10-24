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
 * Backup task.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/vemhere/backup/moodle2/backup_vemhere_stepslib.php');

/**
 * Backs up one Attendance (Vemhere).
 */
class backup_vemhere_activity_task extends backup_activity_task {
    /**
     * No settings of its own.
     */
    protected function define_my_settings() {
    }

    /**
     * Its one step.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_vemhere_activity_structure_step('vemhere_structure', 'vemhere.xml'));
    }

    /**
     * Links to the activity inside a course are rewritten on restore.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');
        $content = preg_replace("/($base\/mod\/vemhere\/index.php\?id\=)([0-9]+)/", '$@VEMHEREINDEX*$2@$', $content);
        return preg_replace("/($base\/mod\/vemhere\/view.php\?id\=)([0-9]+)/", '$@VEMHEREVIEWBYID*$2@$', $content);
    }
}
