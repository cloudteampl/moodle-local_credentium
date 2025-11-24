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
 * Observer tests for local_credentium.
 *
 * @package    local_credentium
 * @category   test
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/credentium/lib.php');

/**
 * Observer tests class.
 *
 * @package    local_credentium
 * @category   test
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer_test extends \advanced_testcase {

    /**
     * Test course completion observer creates issuance record.
     */
    public function test_course_completed_creates_issuance() {
        global $DB;
        $this->resetAfterTest();

        // Enable plugin.
        set_config('enabled', 1, 'local_credentium');

        // Create a course and user.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();

        // Enrol user.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Configure course for credentials.
        $config = new \stdClass();
        $config->courseid = $course->id;
        $config->enabled = 1;
        $config->templateid = 'test-template';
        $config->sendgrade = 0;
        $config->inherit_category = 1;
        local_credentium_save_course_config($config);

        // Trigger course completion event.
        $event = \core\event\course_completed::create([
            'objectid' => $course->id,
            'relateduserid' => $user->id,
            'context' => \context_course::instance($course->id),
            'courseid' => $course->id,
        ]);
        $event->trigger();

        // Check issuance record was created.
        $issuances = $DB->get_records('local_credentium_issuances', [
            'userid' => $user->id,
            'courseid' => $course->id,
        ]);

        $this->assertCount(1, $issuances);
        $issuance = reset($issuances);
        $this->assertEquals('pending', $issuance->status);
        $this->assertEquals('test-template', $issuance->templateid);
    }

    /**
     * Test observer does nothing when plugin disabled.
     */
    public function test_observer_disabled_plugin() {
        global $DB;
        $this->resetAfterTest();

        // Disable plugin.
        set_config('enabled', 0, 'local_credentium');

        // Create a course and user.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Trigger event.
        $event = \core\event\course_completed::create([
            'objectid' => $course->id,
            'relateduserid' => $user->id,
            'context' => \context_course::instance($course->id),
            'courseid' => $course->id,
        ]);
        $event->trigger();

        // No issuance should be created.
        $count = $DB->count_records('local_credentium_issuances', [
            'userid' => $user->id,
            'courseid' => $course->id,
        ]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test observer does nothing when course not configured.
     */
    public function test_observer_course_not_configured() {
        global $DB;
        $this->resetAfterTest();

        // Enable plugin.
        set_config('enabled', 1, 'local_credentium');

        // Create a course and user (no course config).
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Trigger event.
        $event = \core\event\course_completed::create([
            'objectid' => $course->id,
            'relateduserid' => $user->id,
            'context' => \context_course::instance($course->id),
            'courseid' => $course->id,
        ]);
        $event->trigger();

        // No issuance should be created.
        $count = $DB->count_records('local_credentium_issuances', [
            'userid' => $user->id,
            'courseid' => $course->id,
        ]);
        $this->assertEquals(0, $count);
    }
}
