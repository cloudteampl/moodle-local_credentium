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
 * Library functions for the local_credentium plugin.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Log a message for Credentium plugin debugging.
 * Uses error_log to prevent leaking sensitive data in web output.
 *
 * @param string $message The message to log
 * @param mixed $data Optional data to include (will be JSON encoded)
 */
function local_credentium_log($message, $data = null) {
    // Check if debug logging is enabled
    if (!get_config('local_credentium', 'debuglog')) {
        return;
    }

    $logmessage = '[Credentium] ' . $message;
    if ($data !== null) {
        $logmessage .= ' | Data: ' . json_encode($data);
    }
    error_log($logmessage);
}

/**
 * Extend navigation to add Credentium settings to course settings.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param context $context The course context
 */
function local_credentium_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/credentium:managecourse', $context)) {
        $url = new moodle_url('/local/credentium/course_settings.php', ['id' => $course->id]);
        $navigation->add(
            get_string('coursesettings', 'local_credentium'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'credentium',
            new pix_icon('i/settings', '')
        );
    }
}

/**
 * Get course configuration for Credentium.
 *
 * @param int $courseid The course ID
 * @return stdClass|false Course configuration or false if not found
 */
function local_credentium_get_course_config($courseid) {
    global $DB;
    return $DB->get_record('local_credentium_course_config', ['courseid' => $courseid]);
}

/**
 * Save course configuration for Credentium.
 *
 * @param stdClass $config The configuration object
 * @return bool Success status
 */
function local_credentium_save_course_config($config) {
    global $DB;

    local_credentium_log('save_course_config - Input config', $config);

    $existing = $DB->get_record('local_credentium_course_config', ['courseid' => $config->courseid]);

    local_credentium_log('save_course_config - Existing record', $existing);

    if ($existing) {
        $config->id = $existing->id;
        $config->timemodified = time();

        local_credentium_log('save_course_config - Updating record', $config);

        $result = $DB->update_record('local_credentium_course_config', $config);

        $updated = $DB->get_record('local_credentium_course_config', ['id' => $config->id]);
        local_credentium_log('save_course_config - After update', $updated);

        return $result;
    } else {
        $config->timecreated = time();
        $config->timemodified = time();

        local_credentium_log('save_course_config - Inserting new record', $config);

        return $DB->insert_record('local_credentium_course_config', $config);
    }
}

/**
 * Check if Credentium is enabled globally.
 *
 * @return bool True if enabled
 */
function local_credentium_is_enabled() {
    return (bool)get_config('local_credentium', 'enabled');
}

/**
 * Check if Credentium is enabled for a specific course.
 *
 * @param int $courseid The course ID
 * @return bool True if enabled
 */
function local_credentium_is_enabled_for_course($courseid) {
    if (!local_credentium_is_enabled()) {
        return false;
    }

    $config = local_credentium_get_course_config($courseid);
    return $config && $config->enabled;
}

/**
 * Encrypt an API key for secure storage.
 *
 * @param string $key The plain text API key
 * @return string The encrypted API key
 */
function local_credentium_encrypt_key($key) {
    if (empty($key)) {
        return '';
    }

    try {
        return \core\encryption::encrypt($key);
    } catch (\Exception $e) {
        // If encryption fails, log error and return plain text (fallback for compatibility)
        local_credentium_log('Encryption failed, storing plain text', ['error' => $e->getMessage()]);
        return $key;
    }
}

/**
 * Decrypt an API key from secure storage.
 *
 * @param string $encryptedkey The encrypted API key
 * @return string The plain text API key
 */
function local_credentium_decrypt_key($encryptedkey) {
    if (empty($encryptedkey)) {
        return '';
    }

    try {
        return \core\encryption::decrypt($encryptedkey);
    } catch (\Exception $e) {
        // If decryption fails, assume it's already plain text (backward compatibility)
        local_credentium_log('Decryption failed, assuming plain text', ['error' => $e->getMessage()]);
        return $encryptedkey;
    }
}

/**
 * Get category configuration for Credentium.
 *
 * @param int $categoryid The category ID
 * @return stdClass|false Category configuration or false if not found
 */
function local_credentium_get_category_config($categoryid) {
    global $DB;
    $config = $DB->get_record('local_credentium_category_config', ['categoryid' => $categoryid]);

    if ($config && !empty($config->apikey)) {
        // Decrypt API key on retrieval
        $config->apikey = local_credentium_decrypt_key($config->apikey);
    }

    return $config;
}

/**
 * Save category configuration for Credentium.
 *
 * @param stdClass $config The configuration object
 * @return bool Success status
 */
function local_credentium_save_category_config($config) {
    global $DB;

    // Encrypt API key before storage
    if (!empty($config->apikey)) {
        $config->apikey = local_credentium_encrypt_key($config->apikey);
    }

    local_credentium_log('save_category_config', ['categoryid' => $config->categoryid, 'enabled' => $config->enabled]);

    $existing = $DB->get_record('local_credentium_category_config', ['categoryid' => $config->categoryid]);

    if ($existing) {
        $config->id = $existing->id;
        $config->timemodified = time();
        $result = $DB->update_record('local_credentium_category_config', $config);
    } else {
        $config->timecreated = time();
        $config->timemodified = time();
        $result = $DB->insert_record('local_credentium_category_config', $config);
    }

    return $result;
}

/**
 * Resolve category configuration for a course by walking up the category tree.
 *
 * @param int $courseid The course ID
 * @return stdClass|null Category configuration or null if none found
 */
function local_credentium_resolve_category_config($courseid) {
    global $DB;

    // Check if category mode is enabled
    if (!get_config('local_credentium', 'categorymode')) {
        // Fall back to global config
        return (object)[
            'categoryid' => null,
            'apiurl' => get_config('local_credentium', 'apiurl'),
            'apikey' => get_config('local_credentium', 'apikey'),
            'paused' => 0,
            'ratelimit' => null,
            'enabled' => get_config('local_credentium', 'enabled'),
        ];
    }

    // Static cache to avoid repeated queries in same request
    static $cache = [];
    if (isset($cache[$courseid])) {
        return $cache[$courseid];
    }

    // Get course record
    $course = $DB->get_record('course', ['id' => $courseid], 'id, category', MUST_EXIST);

    if (empty($course->category)) {
        // Site-level course, use global config
        $result = (object)[
            'categoryid' => null,
            'apiurl' => get_config('local_credentium', 'apiurl'),
            'apikey' => get_config('local_credentium', 'apikey'),
            'paused' => 0,
            'ratelimit' => null,
            'enabled' => get_config('local_credentium', 'enabled'),
        ];
        $cache[$courseid] = $result;
        return $result;
    }

    // Walk up category tree to find config
    $category = $DB->get_record('course_categories', ['id' => $course->category], 'id, parent');
    $maxdepth = 10; // Prevent infinite loops
    $depth = 0;

    while ($category && $depth < $maxdepth) {
        $catconfig = local_credentium_get_category_config($category->id);

        if ($catconfig && $catconfig->enabled) {
            $cache[$courseid] = $catconfig;
            return $catconfig;
        }

        // Move to parent category
        if ($category->parent > 0) {
            $category = $DB->get_record('course_categories', ['id' => $category->parent], 'id, parent');
            $depth++;
        } else {
            break;
        }
    }

    // No category config found, fall back to global
    $result = (object)[
        'categoryid' => null,
        'apiurl' => get_config('local_credentium', 'apiurl'),
        'apikey' => get_config('local_credentium', 'apikey'),
        'paused' => 0,
        'ratelimit' => null,
        'enabled' => get_config('local_credentium', 'enabled'),
    ];
    $cache[$courseid] = $result;
    return $result;
}

/**
 * Resolve complete course configuration including category inheritance.
 *
 * @param int $courseid The course ID
 * @return stdClass|null Complete course configuration or null if not configured
 */
function local_credentium_resolve_course_config($courseid) {
    $courseconfig = local_credentium_get_course_config($courseid);

    if (!$courseconfig) {
        return null; // Course not configured
    }

    // Check if course inherits from category
    if (!empty($courseconfig->inherit_category)) {
        $categoryconfig = local_credentium_resolve_category_config($courseid);

        // Merge: course settings + category credentials
        $courseconfig->apiurl = $categoryconfig->apiurl;
        $courseconfig->apikey = $categoryconfig->apikey;
        $courseconfig->paused = $categoryconfig->paused ?? 0;
        $courseconfig->ratelimit = $categoryconfig->ratelimit ?? null;
        $courseconfig->categoryid = $categoryconfig->categoryid ?? null;
    }

    return $courseconfig;
}

/**
 * Extend navigation to add Credentium settings to category settings.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context_coursecat $context The category context
 */
function local_credentium_extend_navigation_category_settings(navigation_node $navigation, context_coursecat $context) {
    // Only show if category mode is enabled
    if (!get_config('local_credentium', 'categorymode')) {
        return;
    }

    if (has_capability('local/credentium:managecategory', $context)) {
        $categoryid = $context->instanceid;
        $url = new moodle_url('/local/credentium/category_settings.php', ['id' => $categoryid]);
        $navigation->add(
            get_string('categorysettings', 'local_credentium'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'credentium_category',
            new pix_icon('i/settings', '')
        );
    }
}