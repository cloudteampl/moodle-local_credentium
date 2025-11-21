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
 * Privacy provider tests for local_credentium.
 *
 * @package    local_credentium
 * @category   test
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @package    local_credentium
 * @category   test
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * Test getting the context for the user ID related to this plugin.
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();

        // Create a user and course.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create an issuance record.
        global $DB;
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->courseid = $course->id;
        $record->templateid = 'test-template-123';
        $record->credentialid = 'test-credential-456';
        $record->status = 'issued';
        $record->timecreated = time();
        $record->timeissued = time();
        $DB->insert_record('local_credentium_issuances', $record);

        // Get contexts.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        $coursecontext = \context_course::instance($course->id);
        $this->assertEquals($coursecontext->id, $contextlist->get_contextids()[0]);
    }

    /**
     * Test exporting user data.
     */
    public function test_export_user_data() {
        $this->resetAfterTest();

        // Create a user and course.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create an issuance record.
        global $DB;
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->courseid = $course->id;
        $record->templateid = 'test-template-123';
        $record->credentialid = 'test-credential-456';
        $record->status = 'issued';
        $record->timecreated = time();
        $record->timeissued = time();
        $DB->insert_record('local_credentium_issuances', $record);

        // Export data.
        $coursecontext = \context_course::instance($course->id);
        $contextlist = new \core_privacy\local\request\approved_contextlist($user, 'local_credentium', [$coursecontext->id]);

        provider::export_user_data($contextlist);

        // Verify export.
        $writer = \core_privacy\local\request\writer::with_context($coursecontext);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([get_string('pluginname', 'local_credentium')]);
        $this->assertNotEmpty($data);
        $this->assertNotEmpty($data->issuances);
    }

    /**
     * Test deleting user data.
     */
    public function test_delete_data_for_user() {
        $this->resetAfterTest();

        // Create a user and course.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create an issuance record.
        global $DB;
        $record = new \stdClass();
        $record->userid = $user->id;
        $record->courseid = $course->id;
        $record->templateid = 'test-template-123';
        $record->credentialid = 'test-credential-456';
        $record->status = 'issued';
        $record->timecreated = time();
        $record->timeissued = time();
        $DB->insert_record('local_credentium_issuances', $record);

        // Delete data.
        $coursecontext = \context_course::instance($course->id);
        $contextlist = new \core_privacy\local\request\approved_contextlist($user, 'local_credentium', [$coursecontext->id]);

        provider::delete_data_for_user($contextlist);

        // Verify deletion.
        $count = $DB->count_records('local_credentium_issuances', ['userid' => $user->id, 'courseid' => $course->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test deleting data for all users in context.
     */
    public function test_delete_data_for_all_users_in_context() {
        $this->resetAfterTest();

        // Create users and course.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create issuance records for both users.
        global $DB;
        foreach ([$user1, $user2] as $user) {
            $record = new \stdClass();
            $record->userid = $user->id;
            $record->courseid = $course->id;
            $record->templateid = 'test-template-123';
            $record->credentialid = 'test-credential-' . $user->id;
            $record->status = 'issued';
            $record->timecreated = time();
            $record->timeissued = time();
            $DB->insert_record('local_credentium_issuances', $record);
        }

        // Delete all data in context.
        $coursecontext = \context_course::instance($course->id);
        provider::delete_data_for_all_users_in_context($coursecontext);

        // Verify deletion.
        $count = $DB->count_records('local_credentium_issuances', ['courseid' => $course->id]);
        $this->assertEquals(0, $count);
    }
}
