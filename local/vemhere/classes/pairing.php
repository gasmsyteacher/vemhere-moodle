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
 * Pairing with Vemhere (V-D57). The key from Vemhere carries its door address
 * and a secret; the request is signed with the secret, which never travels.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pairing {
    /** @var string Prefix of a version 1 key. */
    const PREFIX = 'VHP1.';

    /**
     * The door address and secret inside a key.
     *
     * @param string $key
     * @return array [string $url, string $secret]
     */
    public static function decode(string $key): array {
        $key = preg_replace('/\s+/', '', $key);
        if (strpos($key, self::PREFIX) !== 0) {
            throw new \moodle_exception('badkey', 'local_vemhere');
        }
        $body = strtr(substr($key, strlen(self::PREFIX)), '-_', '+/');
        $data = json_decode((string) base64_decode($body . str_repeat('=', (4 - strlen($body) % 4) % 4), true), true);
        if (!is_array($data) || empty($data['u']) || empty($data['c']) || !preg_match('#^https?://#', $data['u'])) {
            throw new \moodle_exception('badkey', 'local_vemhere');
        }
        return [$data['u'], $data['c']];
    }

    /**
     * Tell Vemhere this plugin is going away, signed with the token Vemhere
     * holds (revoked right after). Best effort: an uninstall never waits on it.
     */
    public static function notify_unpair(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/filelib.php');
        $door = get_config('local_vemhere', 'door');
        $service = $DB->get_record('external_services', ['shortname' => setup::SERVICE]);
        $userid = $DB->get_field('user', 'id', ['username' => setup::USERNAME, 'deleted' => 0]);
        if (!$door || !$service || !$userid) {
            return;
        }
        $token = $DB->get_field_sql('SELECT token FROM {external_tokens}
                                      WHERE userid = ? AND externalserviceid = ?
                                   ORDER BY timecreated DESC', [$userid, $service->id], IGNORE_MULTIPLE);
        if (!$token) {
            return;
        }
        $body = json_encode(['site_url' => $CFG->wwwroot], JSON_UNESCAPED_SLASHES);
        $timestamp = time();
        try {
            $curl = new \curl();
            $curl->setHeader([
                'Content-Type: application/json',
                'X-Vem-Action: unpair',
                'X-Vem-Timestamp: ' . $timestamp,
                'X-Vem-Signature: ' . hash_hmac('sha256', $timestamp . '.' . $body, $token),
            ]);
            $curl->post($door, $body, ['CURLOPT_TIMEOUT' => 10, 'CURLOPT_FOLLOWLOCATION' => false]);
        } catch (\Throwable $e) {
            debugging('Vemhere did not hear about the uninstall: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Pair with the Vemhere institution the key belongs to.
     *
     * @param string $key
     * @return array Vemhere's answer: institution, missing_functions
     */
    public static function connect(string $key): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        [$url, $secret] = self::decode($key);

        setup::ensure_service_user();
        setup::enable_web_services();
        $token = setup::issue_token();

        $plugin = \core_plugin_manager::instance()->get_plugin_info('local_vemhere');
        $body = json_encode([
            'site_url' => $CFG->wwwroot,
            'site_name' => format_string(get_site()->fullname),
            'token' => $token,
            'plugin_version' => $plugin ? (string) $plugin->release : '',
            'moodle_release' => $CFG->release,
        ], JSON_UNESCAPED_SLASHES);
        $timestamp = time();

        $curl = new \curl();
        $curl->setHeader([
            'Content-Type: application/json',
            'X-Vem-Timestamp: ' . $timestamp,
            'X-Vem-Signature: ' . hash_hmac('sha256', $timestamp . '.' . $body, $secret),
        ]);
        $response = $curl->post($url, $body, ['CURLOPT_TIMEOUT' => 30, 'CURLOPT_FOLLOWLOCATION' => false]);
        $status = (int) ($curl->get_info()['http_code'] ?? 0);
        $answer = json_decode((string) $response, true);

        if ($status !== 200 || empty($answer['paired'])) {
            setup::revoke_tokens($token);     // Only the token Vemhere did not keep.
            $reason = is_array($answer) && !empty($answer['error']) ? $answer['error'] : ($curl->error ?: "HTTP $status");
            throw new \moodle_exception('connectfailed', 'local_vemhere', '', s($reason));
        }
        setup::revoke_tokens(null, $token);    // Vemhere holds the new one now.
        set_config('door', $url, 'local_vemhere');
        set_config('institution', (string) ($answer['institution'] ?? ''), 'local_vemhere');
        set_config('pairedat', time(), 'local_vemhere');
        set_config('missing', implode(',', $answer['missing_functions'] ?? []), 'local_vemhere');
        return $answer;
    }
}
