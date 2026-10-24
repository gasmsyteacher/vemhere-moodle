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

namespace local_vemhere;

/**
 * The service user, role and token the plugin makes in place of the admin.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\setup
 */
final class setup_test extends \advanced_testcase {
    public function test_the_user_and_role_are_made_once(): void {
        global $DB;
        $this->resetAfterTest();
        $user = setup::ensure_service_user();
        setup::ensure_service_user();
        $this->assertSame(1, $DB->count_records('user', ['username' => setup::USERNAME, 'deleted' => 0]));
        $roleid = $DB->get_field('role', 'id', ['shortname' => setup::ROLE]);
        $context = \context_system::instance();
        $this->assertTrue(user_has_role_assignment($user->id, $roleid, $context->id));
        $this->assertTrue(has_capability('enrol/manual:enrol', $context, $user));
        $student = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->assertTrue($DB->record_exists('role_allow_assign', ['roleid' => $roleid, 'allowassign' => $student]));
    }

    public function test_web_services_and_rest_are_switched_on(): void {
        $this->resetAfterTest();
        set_config('enablewebservices', 0);
        set_config('webserviceprotocols', '');
        setup::enable_web_services();
        $this->assertEquals(1, get_config('core', 'enablewebservices'));
        $this->assertContains('rest', explode(',', get_config('core', 'webserviceprotocols')));
    }

    public function test_a_new_token_keeps_the_old_one_until_it_is_withdrawn(): void {
        global $DB;
        $this->resetAfterTest();
        $old = setup::issue_token();
        $new = setup::issue_token();
        $this->assertNotSame($old, $new);
        $this->assertTrue($DB->record_exists('external_tokens', ['token' => $old]));
        setup::revoke_tokens(null, $new);
        $this->assertFalse($DB->record_exists('external_tokens', ['token' => $old]));
        $this->assertTrue($DB->record_exists('external_tokens', ['token' => $new]));
    }
}
