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
 * Everything the Moodle admin used to do by hand for Vemhere: a service user,
 * its role, web services and REST switched on, and the token.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {
    /** @var string The service user's username. */
    const USERNAME = 'vemhere_service';

    /** @var string The service role's shortname. */
    const ROLE = 'vemhereservice';

    /** @var string The service's shortname in db/services.php. */
    const SERVICE = 'local_vemhere';

    /** @var string[] What the functions in db/services.php check, and nothing more. */
    const CAPABILITIES = [
        'webservice/rest:use',
        'moodle/course:view',
        'moodle/course:viewhiddencourses',
        'moodle/course:viewparticipants',
        'moodle/course:useremail',
        'moodle/category:viewhiddencategories',
        'moodle/user:viewdetails',
        'moodle/user:viewalldetails',
        'moodle/user:viewhiddendetails',
        'moodle/user:create',
        'moodle/role:assign',
        'enrol/manual:enrol',
        'enrol/manual:unenrol',
        'moodle/grade:edit',
        'moodle/grade:manage',
        'mod/assign:view',
    ];

    /** @var string[] Roles Vemhere enrols people as. */
    const ASSIGNABLE = ['student', 'teacher', 'editingteacher'];

    /**
     * The service user and its role, created once and repaired on every call.
     *
     * @return \stdClass the user record
     */
    public static function ensure_service_user(): \stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $DB->get_record('user', ['username' => self::USERNAME,
            'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0]);
        if (!$user) {
            $id = user_create_user((object) [
                'username' => self::USERNAME,
                'auth' => 'manual',
                'password' => random_string(40) . 'aA1!',
                'firstname' => 'Vemhere',
                'lastname' => 'Service',
                // The site's own no-reply address; this account never receives mail.
                'email' => $CFG->noreplyaddress ?? ('noreply@' . parse_url($CFG->wwwroot, PHP_URL_HOST)),
                'emailstop' => 1,
                'confirmed' => 1,
                'mnethostid' => $CFG->mnet_localhost_id,
            ], true, false);
            $user = $DB->get_record('user', ['id' => $id], '*', MUST_EXIST);
        }

        $context = \context_system::instance();
        $roleid = $DB->get_field('role', 'id', ['shortname' => self::ROLE]);
        if (!$roleid) {
            $roleid = create_role(get_string('servicerole', 'local_vemhere'), self::ROLE,
                get_string('serviceroledesc', 'local_vemhere'));
            set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        }
        foreach (self::CAPABILITIES as $capability) {
            if (get_capability_info($capability)) {
                assign_capability($capability, CAP_ALLOW, $roleid, $context->id, true);
            }
        }
        foreach (self::ASSIGNABLE as $shortname) {
            $target = $DB->get_field('role', 'id', ['shortname' => $shortname]);
            if ($target && !$DB->record_exists('role_allow_assign', ['roleid' => $roleid, 'allowassign' => $target])) {
                core_role_set_assign_allowed($roleid, $target);
            }
        }
        if (!user_has_role_assignment($user->id, $roleid, $context->id)) {
            role_assign($roleid, $user->id, $context->id);
        }
        accesslib_clear_all_caches(true);
        return $user;
    }

    /**
     * Web services on, REST among the protocols.
     */
    public static function enable_web_services(): void {
        set_config('enablewebservices', 1);
        $protocols = array_filter(explode(',', (string) get_config('core', 'webserviceprotocols')));
        if (!in_array('rest', $protocols)) {
            $protocols[] = 'rest';
            set_config('webserviceprotocols', implode(',', $protocols));
        }
    }

    /**
     * A fresh token for the service user; any earlier one stops working.
     *
     * @return string
     */
    public static function issue_token(): string {
        global $CFG, $DB;
        $service = $DB->get_record('external_services', ['shortname' => self::SERVICE], '*', MUST_EXIST);
        $user = self::ensure_service_user();
        if (!$DB->record_exists('external_services_users',
                ['externalserviceid' => $service->id, 'userid' => $user->id])) {
            $DB->insert_record('external_services_users', (object) [
                'externalserviceid' => $service->id, 'userid' => $user->id, 'timecreated' => time()]);
        }
        // Earlier tokens stay valid until Vemhere accepts this one (pairing::connect),
        // so a failed or mistaken Connect never cuts a working link.
        $permanent = defined('EXTERNAL_TOKEN_PERMANENT') ? EXTERNAL_TOKEN_PERMANENT : 0;
        if (class_exists('\\core_external\\util')) {
            return \core_external\util::generate_token($permanent, $service, $user->id,
                \context_system::instance(), 0, '');
        }
        require_once($CFG->libdir . '/externallib.php');     // Moodle 4.1.
        return external_generate_token($permanent, $service, $user->id, \context_system::instance(), 0, '');
    }

    /**
     * Tokens of the service user for this service stop working.
     *
     * @param string|null $only revoke just this token
     * @param string|null $except revoke all but this token
     */
    public static function revoke_tokens(?string $only = null, ?string $except = null): void {
        global $DB;
        $service = $DB->get_record('external_services', ['shortname' => self::SERVICE]);
        $userid = $DB->get_field('user', 'id', ['username' => self::USERNAME, 'deleted' => 0]);
        if (!$service || !$userid) {
            return;
        }
        $where = 'userid = :userid AND externalserviceid = :serviceid';
        $params = ['userid' => $userid, 'serviceid' => $service->id];
        if ($only !== null) {
            $where .= ' AND token = :only';
            $params['only'] = $only;
        }
        if ($except !== null) {
            $where .= ' AND token <> :except';
            $params['except'] = $except;
        }
        $DB->delete_records_select('external_tokens', $where, $params);
    }

    /**
     * Uninstall: the token, the user and the role go.
     */
    public static function remove_everything(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');
        self::revoke_tokens();
        if ($user = $DB->get_record('user', ['username' => self::USERNAME, 'deleted' => 0])) {
            delete_user($user);
        }
        if ($roleid = $DB->get_field('role', 'id', ['shortname' => self::ROLE])) {
            delete_role($roleid);
        }
    }
}
