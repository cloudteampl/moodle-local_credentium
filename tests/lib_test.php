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
 * @covers ::local_credentium_encrypt_key
 * @covers ::local_credentium_decrypt_key
 * @covers ::local_credentium_get_global_apikey
 * @covers ::local_credentium_set_global_apikey
 * @covers ::local_credentium_save_category_config
 * @covers ::local_credentium_get_category_config
 * @covers ::local_credentium_save_course_config
 * @covers ::local_credentium_get_course_config
 * @covers ::local_credentium_resolve_category_config
 */
final class lib_test extends \advanced_testcase {
    /**
     * Test encryption and decryption of API keys.
     */
    public function test_encrypt_decrypt_key(): void {
        $this->resetAfterTest();

        $original = 'test-api-key-12345';

        // Encrypt.
        $encrypted = local_credentium_encrypt_key($original);
        $this->assertNotEquals($original, $encrypted);

        // Decrypt.
        $decrypted = local_credentium_decrypt_key($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    /**
     * The global API key must be stored encrypted and read back decrypted.
     */
    public function test_global_apikey_round_trip(): void {
        $this->resetAfterTest();

        local_credentium_set_global_apikey('pub.secret-123');

        // At rest the config value must not be the plain key (encryption is
        // available under PHPUnit; production falls back to plain text only
        // when \core\encryption cannot operate at all).
        $this->assertNotEquals('pub.secret-123', get_config('local_credentium', 'apikey'));
        $this->assertEquals('pub.secret-123', local_credentium_get_global_apikey());
    }

    /**
     * A plain-text key stored by a pre-3.0.1 version must still be readable.
     */
    public function test_global_apikey_plaintext_fallback(): void {
        $this->resetAfterTest();

        // Simulate the legacy storage format: raw set_config, no encryption.
        set_config('apikey', 'legacy-plain-key', 'local_credentium');

        $this->assertEquals('legacy-plain-key', local_credentium_get_global_apikey());
    }

    /**
     * Clearing the key must not store an encrypted empty string.
     */
    public function test_global_apikey_clear(): void {
        $this->resetAfterTest();

        local_credentium_set_global_apikey('pub.secret');
        local_credentium_set_global_apikey('');

        $this->assertEquals('', get_config('local_credentium', 'apikey'));
        $this->assertEquals('', local_credentium_get_global_apikey());
    }

    /**
     * The v3.0.1 upgrade expression must turn a legacy plain-text key into
     * ciphertext that the accessor still resolves to the original key.
     */
    public function test_global_apikey_upgrade_migration(): void {
        $this->resetAfterTest();

        // Legacy storage: raw plaintext, as every pre-3.0.1 version saved it.
        set_config('apikey', 'pub.legacy-secret', 'local_credentium');

        // The exact expression db/upgrade.php applies at savepoint 2026072501.
        $raw = get_config('local_credentium', 'apikey');
        set_config('apikey', local_credentium_encrypt_key(local_credentium_decrypt_key($raw)), 'local_credentium');

        $this->assertNotEquals('pub.legacy-secret', get_config('local_credentium', 'apikey'));
        $this->assertEquals('pub.legacy-secret', local_credentium_get_global_apikey());

        // Re-running the migration (an interrupted-upgrade resume) must be harmless.
        $raw = get_config('local_credentium', 'apikey');
        set_config('apikey', local_credentium_encrypt_key(local_credentium_decrypt_key($raw)), 'local_credentium');
        $this->assertEquals('pub.legacy-secret', local_credentium_get_global_apikey());
    }

    /**
     * Test category config save and get.
     */
    public function test_category_config(): void {
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
        // Returns the new record id on insert and true on update; both are truthy.
        $result = local_credentium_save_category_config($config);
        $this->assertNotEmpty($result);

        // Get.
        $retrieved = local_credentium_get_category_config($category->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals(1, $retrieved->enabled);
        $this->assertEquals('https://test.credentium.com', $retrieved->apiurl);
        $this->assertEquals('test-key-123', $retrieved->apikey); // Should be decrypted.
        $this->assertEquals(100, $retrieved->ratelimit);
    }

    /**
     * Test course config save and get.
     */
    public function test_course_config(): void {
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
        // Returns the new record id on insert and true on update; both are truthy.
        $result = local_credentium_save_course_config($config);
        $this->assertNotEmpty($result);

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
    public function test_resolve_category_config(): void {
        $this->resetAfterTest();

        // Category-tree resolution only happens in category mode; without it the
        // resolver deliberately answers with the global configuration.
        set_config('categorymode', 1, 'local_credentium');

        // Create category hierarchy: parent -> child.
        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);

        // Configure parent category.
        $config = new \stdClass();
        $config->categoryid = $parent->id;
        $config->enabled = 1;
        $config->apiurl = 'https://parent.credentium.com';
        $config->apikey = 'parent-key';
        local_credentium_save_category_config($config);

        // The resolver takes a COURSE id and walks up from the course's category.
        $course = $this->getDataGenerator()->create_course(['category' => $child->id]);

        // Resolve for a course in the child category (should inherit from parent).
        $resolved = local_credentium_resolve_category_config($course->id);
        $this->assertNotNull($resolved);
        $this->assertEquals($parent->id, $resolved->categoryid);
        $this->assertEquals('https://parent.credentium.com', $resolved->apiurl);
        $this->assertEquals('parent-key', $resolved->apikey);
    }
}
