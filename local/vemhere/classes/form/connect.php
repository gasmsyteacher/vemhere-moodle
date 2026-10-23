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

namespace local_vemhere\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * One field: the pairing key from Vemhere.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connect extends \moodleform {
    /**
     * The form.
     */
    protected function definition() {
        $form = $this->_form;
        $form->addElement('textarea', 'pairingkey', get_string('pairingkey', 'local_vemhere'),
            ['rows' => 3, 'cols' => 60, 'class' => 'text-monospace']);
        $form->setType('pairingkey', PARAM_RAW_TRIMMED);
        $form->addRule('pairingkey', null, 'required', null, 'client');
        $form->addHelpButton('pairingkey', 'pairingkey', 'local_vemhere');
        $this->add_action_buttons(false, get_string('connect', 'local_vemhere'));
    }
}
