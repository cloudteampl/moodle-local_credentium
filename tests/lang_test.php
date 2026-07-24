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
 * Language pack completeness tests for local_credentium.
 *
 * @package    local_credentium
 * @category   test
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium;

defined('MOODLE_INTERNAL') || die();

/**
 * Language pack completeness tests class.
 *
 * The plugin ships English and Polish. A string that exists in English but not in Polish
 * is invisible in testing (Moodle silently falls back to English), while a string that is
 * missing from both is rendered to the user as a raw [[stringkey]] placeholder. Both classes
 * of defect are caught here rather than in production.
 *
 * @package    local_credentium
 * @category   test
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lang_test extends \advanced_testcase {

    /** @var string[] Languages the plugin ships. English is the reference. */
    private const LANGS = ['en', 'pl'];

    /**
     * Keys whose translation may legitimately be identical to the English source,
     * because the term is the same word in both languages.
     *
     * @var string[]
     */
    private const ALLOW_IDENTICAL = [
        'status',
    ];

    /**
     * Load the raw string table for one language.
     *
     * The files are included directly rather than read through get_string_manager(),
     * because the string manager transparently falls back to English for missing keys,
     * which is exactly what this test needs to detect.
     *
     * @param string $lang Language directory name.
     * @return array<string, string> String key => translated value.
     */
    private function load_strings(string $lang): array {
        global $CFG;

        $file = $CFG->dirroot . '/local/credentium/lang/' . $lang . '/local_credentium.php';
        $this->assertFileExists($file, "Missing language pack: {$lang}");

        $string = [];
        include($file);

        $this->assertNotEmpty($string, "Language pack {$lang} defines no strings.");

        return $string;
    }

    /**
     * Every English string must have a translation in every other shipped language.
     */
    public function test_all_languages_define_the_same_keys(): void {
        $reference = $this->load_strings('en');

        foreach (self::LANGS as $lang) {
            if ($lang === 'en') {
                continue;
            }

            $translated = $this->load_strings($lang);

            $missing = array_diff(array_keys($reference), array_keys($translated));
            $this->assertSame([], array_values($missing),
                "Strings missing from the '{$lang}' language pack: " . implode(', ', $missing));

            $orphaned = array_diff(array_keys($translated), array_keys($reference));
            $this->assertSame([], array_values($orphaned),
                "Strings in '{$lang}' with no English counterpart: " . implode(', ', $orphaned));
        }
    }

    /**
     * A translation identical to the English source is almost always a copy-paste
     * placeholder rather than a real translation.
     */
    public function test_translations_are_not_copies_of_the_english_source(): void {
        $reference = $this->load_strings('en');

        foreach (self::LANGS as $lang) {
            if ($lang === 'en') {
                continue;
            }

            $translated = $this->load_strings($lang);

            $untranslated = [];
            foreach ($translated as $key => $value) {
                if (in_array($key, self::ALLOW_IDENTICAL, true)) {
                    continue;
                }
                if (isset($reference[$key]) && $reference[$key] === $value) {
                    $untranslated[] = $key;
                }
            }

            $this->assertSame([], $untranslated,
                "Untranslated strings in '{$lang}' (identical to English): " . implode(', ', $untranslated)
                . '. Translate them, or add the key to lang_test::ALLOW_IDENTICAL if the term is genuinely the same.');
        }
    }

    /**
     * A translation that drops a {$a} placeholder silently loses information
     * (a course name, a count) that the English string conveys.
     */
    public function test_translations_keep_their_placeholders(): void {
        $reference = $this->load_strings('en');

        foreach (self::LANGS as $lang) {
            if ($lang === 'en') {
                continue;
            }

            $translated = $this->load_strings($lang);

            foreach ($reference as $key => $value) {
                if (!isset($translated[$key])) {
                    continue; // Reported by test_all_languages_define_the_same_keys().
                }

                preg_match_all('/\{\$a(->\w+)?\}/', $value, $expected);
                preg_match_all('/\{\$a(->\w+)?\}/', $translated[$key], $actual);

                sort($expected[0]);
                sort($actual[0]);

                $this->assertSame($expected[0], $actual[0],
                    "Placeholder mismatch for '{$key}' in '{$lang}'.");
            }
        }
    }

    /**
     * Moodle renders the label of a message provider from the magic
     * 'messageprovider:<name>' string key. db/messages.php cannot declare it, so a
     * provider added without its string shows up as [[messageprovider:name]] on the
     * notification preferences page.
     */
    public function test_every_message_provider_has_a_label(): void {
        global $CFG;

        $messageproviders = [];
        include($CFG->dirroot . '/local/credentium/db/messages.php');

        $this->assertNotEmpty($messageproviders, 'db/messages.php declares no message providers.');

        foreach (self::LANGS as $lang) {
            $strings = $this->load_strings($lang);

            foreach (array_keys($messageproviders) as $provider) {
                $this->assertArrayHasKey('messageprovider:' . $provider, $strings,
                    "Message provider '{$provider}' has no label in the '{$lang}' language pack.");
            }
        }
    }

    /**
     * Capability names are resolved the same magic way, from 'credentium:<capability>'.
     */
    public function test_every_capability_has_a_label(): void {
        global $CFG;

        $capabilities = [];
        include($CFG->dirroot . '/local/credentium/db/access.php');

        $this->assertNotEmpty($capabilities, 'db/access.php declares no capabilities.');

        foreach (self::LANGS as $lang) {
            $strings = $this->load_strings($lang);

            foreach (array_keys($capabilities) as $capability) {
                // 'local/credentium:manage' resolves to the 'credentium:manage' string key.
                $key = substr($capability, strpos($capability, '/') + 1);
                $this->assertArrayHasKey($key, $strings,
                    "Capability '{$capability}' has no label in the '{$lang}' language pack.");
            }
        }
    }

    /**
     * Scheduled tasks resolve their display name through get_name() in the task class;
     * this checks the strings those classes ask for actually exist.
     */
    public function test_every_scheduled_task_has_a_name(): void {
        global $CFG;

        $tasks = [];
        include($CFG->dirroot . '/local/credentium/db/tasks.php');

        $this->assertNotEmpty($tasks, 'db/tasks.php declares no scheduled tasks.');

        foreach ($tasks as $task) {
            $instance = new $task['classname']();
            $name = $instance->get_name();

            $this->assertNotEmpty($name, "Scheduled task {$task['classname']} has an empty name.");
            $this->assertStringNotContainsString('[[', $name,
                "Scheduled task {$task['classname']} resolves to a missing string: {$name}");
        }
    }
}
