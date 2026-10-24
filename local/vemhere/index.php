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
 * Connect this Moodle to Vemhere with a pairing key.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_vemhere');
$url = new moodle_url('/local/vemhere/index.php');
$form = new \local_vemhere\form\connect($url);

if ($data = $form->get_data()) {
    try {
        $answer = \local_vemhere\pairing::connect($data->pairingkey);
        redirect(
            $url,
            get_string('connected', 'local_vemhere', s($answer['institution'] ?? '')),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (moodle_exception $e) {
        redirect($url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_vemhere'));

$pairedat = get_config('local_vemhere', 'pairedat');
if ($pairedat) {
    echo $OUTPUT->notification(get_string('status_connected', 'local_vemhere', (object) [
        'institution' => s(get_config('local_vemhere', 'institution')),
        'date' => userdate($pairedat),
    ]), \core\output\notification::NOTIFY_SUCCESS, false);
    $missing = array_filter(explode(',', (string) get_config('local_vemhere', 'missing')));
    if ($missing) {
        echo $OUTPUT->notification(
            get_string('status_missing', 'local_vemhere', s(implode(', ', $missing))),
            \core\output\notification::NOTIFY_WARNING,
            false
        );
    }
} else {
    echo html_writer::tag('p', get_string('intro', 'local_vemhere'));
}
$form->display();
echo $OUTPUT->footer();
