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
 * Calls to Vemhere, signed with the token Vemhere holds (HMAC-SHA256 over "<ts>." + body).
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vemhere_api {
    /**
     * What "Attendance (Vemhere)" shows for a course, or null when Vemhere
     * cannot be asked (not paired, unreachable, refused).
     *
     * @param int $courseid
     * @return array|null
     */
    public static function summary(int $courseid): ?array {
        $cache = \cache::make('local_vemhere', 'summary');
        if (($hit = $cache->get($courseid)) !== false) {
            return $hit;
        }
        $answer = self::post('course/summary/', ['course_id' => $courseid]);
        if ($answer !== null) {
            $cache->set($courseid, $answer);
        }
        return $answer;
    }

    /**
     * POST a signed JSON body to a path beside the pairing door.
     *
     * @param string $path relative to the institution's /moodle/
     * @param array $payload
     * @return array|null
     */
    private static function post(string $path, array $payload): ?array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/filelib.php');
        $door = (string) get_config('local_vemhere', 'door');
        $service = $DB->get_record('external_services', ['shortname' => setup::SERVICE]);
        $userid = $DB->get_field('user', 'id', ['username' => setup::USERNAME, 'deleted' => 0]);
        if ($door === '' || !$service || !$userid || !get_config('local_vemhere', 'pairedat')) {
            return null;
        }
        $token = $DB->get_field_sql('SELECT token FROM {external_tokens} WHERE userid = ? AND externalserviceid = ?
                                   ORDER BY timecreated DESC', [$userid, $service->id], IGNORE_MULTIPLE);
        if (!$token) {
            return null;
        }
        $url = preg_replace('#pair/door/?$#', '', $door) . $path;
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = time();
        $curl = new \curl();
        $curl->setHeader(['Content-Type: application/json', 'X-Vem-Timestamp: ' . $timestamp,
            'X-Vem-Signature: ' . hash_hmac('sha256', $timestamp . '.' . $body, $token)]);
        $response = $curl->post($url, $body, ['CURLOPT_TIMEOUT' => 8, 'CURLOPT_FOLLOWLOCATION' => false]);
        if ((int) ($curl->get_info()['http_code'] ?? 0) !== 200) {
            return null;
        }
        $data = json_decode((string) $response, true);
        return is_array($data) ? $data : null;
    }
}
