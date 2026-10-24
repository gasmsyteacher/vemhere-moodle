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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/backup_mod_quiz_access_subplugin.class.php');

/**
 * Backs up a quiz's focus monitor setting (never the exit records: they are student data).
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_quizaccess_vemfocus_subplugin extends backup_mod_quiz_access_subplugin {
    /**
     * The focus-monitor setting of the quiz.
     */
    protected function define_quiz_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $settings = new backup_nested_element('quizaccess_vemfocus', null, ['mode', 'maxexits']);
        $subplugin->add_child($wrapper);
        $wrapper->add_child($settings);
        $settings->set_source_table('quizaccess_vemfocus', ['quizid' => backup::VAR_ACTIVITYID]);
        return $subplugin;
    }
}
