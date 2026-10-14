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
 * The person identifier sent to Credentium with each issuance.
 *
 * @package    local_credentium
 * @copyright  2026 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\local;

/**
 * Picks the Moodle user field an administrator chose as the person identifier.
 *
 * Credentium treats issuances with the same identifier as one person, even when the
 * e-mail address changes (for example when a guest learner later becomes a student).
 */
final class person_external_id {
    /** Setting values: none (default), the user's ID number, username or Moodle user id. */
    public const FIELDS = ['', 'idnumber', 'username', 'id'];

    /** Longest identifier Credentium accepts. */
    public const MAX_LENGTH = 200;

    /**
     * Choices for the settings form, in the order of {@see FIELDS}.
     *
     * @return array setting value => label
     */
    public static function options(): array {
        return [
            '' => get_string('externalidfield_none', 'local_credentium'),
            'idnumber' => get_string('externalidfield_idnumber', 'local_credentium'),
            'username' => get_string('externalidfield_username', 'local_credentium'),
            'id' => get_string('externalidfield_id', 'local_credentium'),
        ];
    }

    /**
     * A stored or submitted setting value, or none when it is not one of {@see FIELDS}.
     *
     * @param string $field Setting value.
     * @return string
     */
    public static function normalise_field(string $field): string {
        return in_array($field, self::FIELDS, true) ? $field : '';
    }

    /**
     * The identifier to send for a user, or null when none is configured or the field is empty.
     *
     * A value longer than {@see MAX_LENGTH} is not sent: truncating it could give two people
     * the same identifier.
     *
     * @param \stdClass $user User record.
     * @param string $field Setting value.
     * @return string|null
     */
    public static function from_user(\stdClass $user, string $field): ?string {
        if ($field === '' || self::normalise_field($field) !== $field || !isset($user->{$field})) {
            return null;
        }

        $value = trim((string) $user->{$field});

        if ($value === '' || \core_text::strlen($value) > self::MAX_LENGTH) {
            return null;
        }

        return $value;
    }
}
