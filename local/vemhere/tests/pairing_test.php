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
 * The pairing key Vemhere makes.
 *
 * @package    local_vemhere
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_vemhere\pairing
 */
final class pairing_test extends \advanced_testcase {
    /**
     * A key as Vemhere encodes it (apps/moodle_sync/pairing.py::encode_key).
     *
     * @param string $url
     * @param string $secret
     * @return string
     */
    private function key(string $url, string $secret): string {
        return pairing::PREFIX . rtrim(strtr(base64_encode(json_encode(
            ['u' => $url, 'c' => $secret],
            JSON_UNESCAPED_SLASHES
        )), '+/', '-_'), '=');
    }

    public function test_a_key_gives_the_door_and_the_secret(): void {
        [$url, $secret] = pairing::decode($this->key('https://uba.vemhere.com/moodle/pair/door/', 's3cret'));
        $this->assertSame('https://uba.vemhere.com/moodle/pair/door/', $url);
        $this->assertSame('s3cret', $secret);
    }

    public function test_spaces_from_a_paste_are_ignored(): void {
        $key = $this->key('https://x.example/moodle/pair/door/', 'abc');
        [$url] = pairing::decode(" \n" . chunk_split($key, 20, "\n"));
        $this->assertSame('https://x.example/moodle/pair/door/', $url);
    }

    public function test_something_else_is_refused(): void {
        $this->expectException(\moodle_exception::class);
        pairing::decode('not a key');
    }

    public function test_a_key_for_a_non_web_address_is_refused(): void {
        $this->expectException(\moodle_exception::class);
        pairing::decode($this->key('file:///etc/passwd', 'abc'));
    }
}
