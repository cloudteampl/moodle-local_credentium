<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the person identifier sent with each issuance.
 *
 * @package    local_credentium
 * @copyright  2026 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium;

use local_credentium\api\client;
use local_credentium\local\person_external_id;

/**
 * Tests for {@see \local_credentium\local\person_external_id} and the issue payload.
 *
 * @covers \local_credentium\local\person_external_id
 * @covers \local_credentium\api\client::build_issue_payload
 */
final class person_external_id_test extends \basic_testcase {
    /**
     * A Moodle user row as the issue task loads it.
     *
     * @return \stdClass
     */
    private static function user(): \stdClass {
        return (object) [
            'id' => 42,
            'username' => 'anowak',
            'idnumber' => '  S12345 ',
            'email' => 'anna@example.org',
            'firstname' => 'Anna',
            'lastname' => 'Nowak',
        ];
    }

    /**
     * Each setting picks its field; nothing is sent unless one is chosen.
     *
     * @param string $field Setting value.
     * @param string|null $expected Identifier to send.
     * @dataProvider fields_provider
     */
    public function test_from_user_reads_the_configured_field(string $field, ?string $expected): void {
        $this->assertSame($expected, person_external_id::from_user(self::user(), $field));
    }

    /**
     * Setting values and what they send for {@see user()}.
     *
     * @return array
     */
    public static function fields_provider(): array {
        return [
            'none' => ['', null],
            'idnumber, trimmed' => ['idnumber', 'S12345'],
            'username' => ['username', 'anowak'],
            'moodle id' => ['id', '42'],
            'unknown field' => ['email', null],
        ];
    }

    public function test_an_empty_field_sends_nothing(): void {
        $user = self::user();
        $user->idnumber = '   ';

        $this->assertNull(person_external_id::from_user($user, 'idnumber'));
    }

    public function test_a_value_longer_than_200_characters_is_not_sent(): void {
        $user = self::user();
        $user->idnumber = str_repeat('x', 201);

        // Truncating could give two people the same identifier, so nothing is sent instead.
        $this->assertNull(person_external_id::from_user($user, 'idnumber'));
    }

    public function test_unknown_setting_values_fall_back_to_none(): void {
        $this->assertSame('', person_external_id::normalise_field('email'));
        $this->assertSame('idnumber', person_external_id::normalise_field('idnumber'));
    }

    public function test_payload_without_the_setting_is_unchanged(): void {
        $payload = client::build_issue_payload('tmpl-1', self::user(), ['grade' => 5], '');

        $this->assertSame([
            'credentialTemplateId' => 'tmpl-1',
            'personEmail' => 'anna@example.org',
            'personGivenName' => 'Anna',
            'personFamilyName' => 'Nowak',
            'grade' => '5',
        ], $payload);
    }

    public function test_payload_carries_the_configured_identifier(): void {
        $payload = client::build_issue_payload('tmpl-1', self::user(), [], 'idnumber');

        $this->assertSame('S12345', $payload['personExternalId']);
    }
}
