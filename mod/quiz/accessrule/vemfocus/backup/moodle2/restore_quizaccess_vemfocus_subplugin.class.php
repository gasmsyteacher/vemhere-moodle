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

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');

/**
 * Restores a quiz's focus monitor setting.
 *
 * @package    quizaccess_vemfocus
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quizaccess_vemfocus_subplugin extends restore_mod_quiz_access_subplugin {
    /**
     * The focus-monitor setting of the quiz.
     */
    protected function define_quiz_subplugin_structure() {
        $paths = [];
        $elepath = $this->get_pathfor('/quizaccess_vemfocus');
        $paths[] = new restore_path_element('quizaccess_vemfocus', $elepath);
        return $paths;
    }

    /**
     * Restore the focus-monitor setting of a quiz.
     *
     * @param array $data
     */
    public function process_quizaccess_vemfocus($data) {
        global $DB;
        $data = (object) $data;
        $data->quizid = $this->get_new_parentid('quiz');
        if (!$DB->record_exists('quizaccess_vemfocus', ['quizid' => $data->quizid])) {
            $DB->insert_record('quizaccess_vemfocus', $data);
        }
    }
}
