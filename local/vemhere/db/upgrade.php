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
 * Upgrade steps.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs when a newer version is installed over an older one.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_vemhere_upgrade($oldversion) {
    // Every release may give the service role a capability its new functions
    // check; setting the role up again is idempotent.
    \local_vemhere\setup::ensure_service_user();
    upgrade_plugin_savepoint(true, 2026102401, 'local', 'vemhere');
    return true;
}
