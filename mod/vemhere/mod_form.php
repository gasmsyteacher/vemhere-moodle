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
 * The form a teacher sees: a name, already filled in. Nothing to configure.
 *
 * @package    mod_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Add or edit form.
 */
class mod_vemhere_mod_form extends moodleform_mod {
    /**
     * The form.
     */
    public function definition() {
        $form = $this->_form;
        $form->addElement('text', 'name', get_string('name'), ['size' => 48]);
        $form->setType('name', PARAM_TEXT);
        $form->setDefault('name', get_string('defaultname', 'vemhere'));
        $form->addRule('name', null, 'required', null, 'client');
        $form->addElement('static', 'howitworks', '', get_string('howitworks', 'vemhere'));
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
