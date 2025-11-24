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
 * Library function tests for local_credentium.
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
 * Library function tests class.
 *
 * @package    local_credentium
 * @category   test
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Test encryption and decryption of API keys.
     */
    public function test_encrypt_decrypt_apikey() {
        $this->resetAfterTest();

        $original = 'test-api-key-12345';

        // Encrypt.
        $encrypted = local_credentium_encrypt_apikey($original);
        $this->assertNotEquals($original, $encrypted);

        // Decrypt.
        $decrypted = local_credentium_decrypt_apikey($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    /**
     * Test category config save and get.
     */
    public function test_category_config() {
        $this->resetAfterTest();

        // Create a category.
        $category = $this->getDataGenerator()->create_category();

        // Create config.
        $config = new \stdClass();
        $config->categoryid = $category->id;
        $config->enabled = 1;
        $config->apiurl = 'https://test.credentium.com';
        $config->apikey = 'test-key-123';
        $config->paused = 0;
        $config->ratelimit = 100;

        // Save.
        $result = local_credentium_save_category_config($config);
        $this->assertTrue($result);

        // Get.
        $retrieved = local_credentium_get_category_config($category->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals(1, $retrieved->enabled);
        $this->assertEquals('https://test.credentium.com', $retrieved->apiurl);
        $this->assertEquals('test-key-123', $retrieved->apikey); // Should be decrypted
        $this->assertEquals(100, $retrieved->ratelimit);
    }

    /**
     * Test course config save and get.
     */
    public function test_course_config() {
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create config.
        $config = new \stdClass();
        $config->courseid = $course->id;
        $config->enabled = 1;
        $config->templateid = 'template-123';
        $config->sendgrade = 1;
        $config->mingrade = 70;
        $config->inherit_category = 1;

        // Save.
        $result = local_credentium_save_course_config($config);
        $this->assertTrue($result);

        // Get.
        $retrieved = local_credentium_get_course_config($course->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals(1, $retrieved->enabled);
        $this->assertEquals('template-123', $retrieved->templateid);
        $this->assertEquals(1, $retrieved->sendgrade);
        $this->assertEquals(70, $retrieved->mingrade);
    }

    /**
     * Test category config resolution (inheritance).
     */
    public function test_resolve_category_config() {
        $this->resetAfterTest();

        // Create category hierarchy: parent -> child
        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);

        // Configure parent category.
        $config = new \stdClass();
        $config->categoryid = $parent->id;
        $config->enabled = 1;
        $config->apiurl = 'https://parent.credentium.com';
        $config->apikey = 'parent-key';
        local_credentium_save_category_config($config);

        // Resolve child category (should inherit from parent).
        $resolved = local_credentium_resolve_category_config($child->id);
        $this->assertNotNull($resolved);
        $this->assertEquals($parent->id, $resolved->categoryid);
        $this->assertEquals('https://parent.credentium.com', $resolved->apiurl);
        $this->assertEquals('parent-key', $resolved->apikey);
    }
}
