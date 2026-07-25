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
 * @return int|bool New record id on insert, true on update
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

        local_credentium_log('get_category_config - Retrieved and decrypted', [
            'categoryid' => $categoryid,
            'has_apiurl' => !empty($config->apiurl),
            'has_apikey' => !empty($config->apikey),
            'enabled' => $config->enabled ?? 0
        ]);
    } else {
        local_credentium_log('get_category_config - No config or empty apikey', [
            'categoryid' => $categoryid,
            'config_exists' => $config ? 'yes' : 'no'
        ]);
    }

    return $config;
}

/**
 * Save category configuration for Credentium.
 *
 * @param stdClass $config The configuration object
 * @return int|bool New record id on insert, true on update
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
        $globalconfig = (object)[
            'categoryid' => null,
            'apiurl' => get_config('local_credentium', 'apiurl'),
            'apikey' => get_config('local_credentium', 'apikey'),
            'paused' => 0,
            'ratelimit' => null,
            'enabled' => get_config('local_credentium', 'enabled'),
        ];

        local_credentium_log('resolve_category_config - Using global (category mode disabled)', [
            'courseid' => $courseid,
            'has_apiurl' => !empty($globalconfig->apiurl),
            'has_apikey' => !empty($globalconfig->apikey)
        ]);

        return $globalconfig;
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

    local_credentium_log('resolve_category_config - Walking category tree', [
        'courseid' => $courseid,
        'starting_category' => $category->id
    ]);

    while ($category && $depth < $maxdepth) {
        $catconfig = local_credentium_get_category_config($category->id);

        if ($catconfig && $catconfig->enabled) {
            // Check if this category has custom credentials or uses global
            if (!empty($catconfig->apiurl) && !empty($catconfig->apikey)) {
                // Category has custom credentials
                local_credentium_log('resolve_category_config - Found enabled category config with custom credentials', [
                    'courseid' => $courseid,
                    'categoryid' => $category->id,
                    'depth' => $depth
                ]);
                $cache[$courseid] = $catconfig;
                return $catconfig;
            } else {
                // Category is enabled but uses global credentials
                // Return global credentials with category's operational controls
                local_credentium_log('resolve_category_config - Found enabled category using global credentials', [
                    'courseid' => $courseid,
                    'categoryid' => $category->id,
                    'depth' => $depth
                ]);
                $result = (object)[
                    'categoryid' => $category->id,
                    'apiurl' => get_config('local_credentium', 'apiurl'),
                    'apikey' => get_config('local_credentium', 'apikey'),
                    'paused' => $catconfig->paused ?? 0,
                    'ratelimit' => $catconfig->ratelimit ?? null,
                    'enabled' => 1,
                ];
                $cache[$courseid] = $result;
                return $result;
            }
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
    local_credentium_log('resolve_category_config - No category config found, using global', [
        'courseid' => $courseid,
        'depth_searched' => $depth
    ]);

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
 * This function returns the complete configuration needed to issue credentials for a course.
 * Course settings (enabled, templateid, sendgrade) are stored at course level.
 * API credentials ALWAYS come from category or global level - NEVER from course level.
 *
 * @param int $courseid The course ID
 * @return stdClass|null Complete course configuration or null if not configured/enabled
 */
function local_credentium_resolve_course_config($courseid) {
    // Get course-level settings (enabled, templateid, sendgrade)
    $courseconfig = local_credentium_get_course_config($courseid);

    if (!$courseconfig || empty($courseconfig->enabled)) {
        local_credentium_log('resolve_course_config - Course not configured or disabled', [
            'courseid' => $courseid,
            'has_config' => $courseconfig ? 'yes' : 'no',
            'enabled' => $courseconfig->enabled ?? 0
        ]);
        return null; // Course not configured or explicitly disabled
    }

    // ALWAYS get API credentials from category or global (never from course level)
    $categoryconfig = local_credentium_resolve_category_config($courseid);

    // Merge: course settings (templateid, sendgrade) + category/global credentials (apiurl, apikey)
    $courseconfig->apiurl = $categoryconfig->apiurl;
    $courseconfig->apikey = $categoryconfig->apikey;
    $courseconfig->paused = $categoryconfig->paused ?? 0;
    $courseconfig->ratelimit = $categoryconfig->ratelimit ?? null;
    $courseconfig->categoryid = $categoryconfig->categoryid ?? null;

    local_credentium_log('resolve_course_config - Merged config', [
        'courseid' => $courseid,
        'has_apiurl' => !empty($courseconfig->apiurl),
        'has_apikey' => !empty($courseconfig->apikey),
        'categoryid' => $courseconfig->categoryid ?? 'global',
        'paused' => $courseconfig->paused ?? 0
    ]);

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

/**
 * Whether the claim feature (status checks + learner reminders) is enabled.
 *
 * Gated on the master switch too: a disabled integration must not keep nudging
 * learners about credentials it no longer manages.
 *
 * @return bool
 */
function local_credentium_claim_enabled() {
    return local_credentium_is_enabled() && (bool) get_config('local_credentium', 'claimenabled');
}

/**
 * Whether the claim reminder banner should be shown.
 *
 * An unset value counts as enabled: the setting lives on a custom moodleform, so
 * unlike an admin_setting it is never persisted at install time. Sites that
 * enabled the claim feature without (re)saving that form would otherwise silently
 * lose the banner — its most visible surface — with nothing to explain why.
 *
 * @return bool
 */
function local_credentium_show_banner() {
    $value = get_config('local_credentium', 'showbanner');
    return $value === false ? true : (bool) $value;
}

/**
 * The scheduled task that polls Credentium for claim statuses.
 *
 * @return \core\task\scheduled_task|null Null if the task is not registered (mid-install).
 */
function local_credentium_get_claim_sync_task() {
    $task = \core\task\manager::get_scheduled_task('local_credentium\task\sync_claim_status');
    return $task ?: null;
}

/**
 * The lock resource cron uses for the claim status-check task.
 *
 * Must match byte-for-byte what \core\task\manager uses, or a manual run would take a
 * different lock and happily overlap a scheduled one. Core stores and looks up
 * task_scheduled.classname through get_canonical_class_name() (which prepends a
 * backslash) and locks on that stored value, so derive it the same way rather than
 * hand-building the string. Covered by a test that compares it to the stored record.
 *
 * @return string Lock resource name.
 */
function local_credentium_claim_sync_lock_resource() {
    return \core\task\manager::get_canonical_class_name('local_credentium\task\sync_claim_status');
}

/**
 * Selectable claim status-check intervals, in minutes, keyed by minutes.
 *
 * Every option divides evenly into an hour (or is a whole number of hours) so it maps
 * onto a valid cron expression without drift.
 *
 * @return array Map of minutes to human-readable label.
 */
function local_credentium_claim_sync_interval_options() {
    $options = [];
    foreach ([5, 10, 15, 30, 60, 120, 240] as $minutes) {
        $options[$minutes] = format_time($minutes * MINSECS);
    }
    return $options;
}

/**
 * The interval the claim status-check task currently runs at.
 *
 * Read back from the task itself rather than from plugin config, so a schedule edited
 * directly under Server > Scheduled tasks is reported honestly.
 *
 * @return int|null Interval in minutes, or null when the schedule is a custom
 *                  expression that does not map onto a simple interval.
 */
function local_credentium_get_claim_sync_interval() {
    $task = local_credentium_get_claim_sync_task();
    if ($task === null) {
        return null;
    }
    $minute = trim($task->get_minute());
    $hour = trim($task->get_hour());

    if ($hour === '*' && preg_match('~^\*/(\d+)$~', $minute, $matches)) {
        return (int) $matches[1];
    }
    if ($minute === '0' && $hour === '*') {
        return (int) HOURMINS;
    }
    if ($minute === '0' && preg_match('~^\*/(\d+)$~', $hour, $matches)) {
        return ((int) $matches[1]) * HOURMINS;
    }
    return null;
}

/**
 * Rewrite the claim status-check task's schedule to run every $minutes.
 *
 * @param int $minutes Interval in minutes; must be one of the offered options.
 * @return bool True when the schedule was changed.
 */
function local_credentium_apply_claim_sync_interval($minutes) {
    $minutes = (int) $minutes;
    if (!array_key_exists($minutes, local_credentium_claim_sync_interval_options())) {
        return false;
    }
    $task = local_credentium_get_claim_sync_task();
    if ($task === null) {
        return false;
    }

    if ($minutes >= HOURMINS) {
        $hours = intdiv($minutes, (int) HOURMINS);
        $task->set_minute('0');
        $task->set_hour($hours === 1 ? '*' : '*/' . $hours);
    } else {
        $task->set_minute('*/' . $minutes);
        $task->set_hour('*');
    }
    $task->set_day('*');
    $task->set_month('*');
    $task->set_day_of_week('*');
    // Mark as customised so a later plugin upgrade does not silently reset the choice.
    $task->set_customised(true);

    \core\task\manager::configure_scheduled_task($task);
    return true;
}

/**
 * A "My credentials" action button that mints its URL server-side via claim.php.
 *
 * The button POSTs (with sesskey) to claim.php, which asks the API for a fresh claim
 * link and redirects to it. The link is minted at click time and only ever travels in
 * the redirect header, so an issued credential's single-use claim URL is never written
 * into the page. Both the "claim it" and the "view the claimed one" actions share this
 * form — they differ only in the label and emphasis.
 *
 * @param int $rowid Tracking row id.
 * @param string $labelkey Language string id for the button label.
 * @param string $buttonclass Bootstrap button variant class (e.g. 'btn-primary').
 * @return string HTML for the button.
 */
function local_credentium_claim_action_button(int $rowid, string $labelkey, string $buttonclass): string {
    $out = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => (new moodle_url('/local/credentium/claim.php'))->out(false),
        'target' => '_blank',
        // The new tab is redirected to the external wallet origin; noopener stops it
        // from reaching back into this page through window.opener.
        'rel' => 'noopener',
        'class' => 'm-0',
    ]);
    $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $rowid]);
    $out .= html_writer::tag(
        'button',
        get_string($labelkey, 'local_credentium'),
        ['type' => 'submit', 'class' => 'btn ' . $buttonclass . ' btn-sm']
    );
    $out .= html_writer::end_tag('form');
    return $out;
}

/**
 * Turn the last claim sync run's failure into advice the administrator can act on.
 *
 * Each failure kind needs a different response — widen the API key's scope, update
 * the plugin, lengthen the check interval, wait for Credentium to recover, or open
 * outbound HTTPS — and a report that only prints "HTTP 500 from POST /api/..." leaves
 * an admin unable to tell which one applies (or whether the site is even at fault).
 * The raw string is still shown alongside as the technical detail, because that is
 * what support tickets need.
 *
 * @param string $kind A \local_credentium\api\claim_client::FAIL_* value; anything
 *                     unrecognised (including '') falls back to generic advice.
 * @return string Translated advice, ready to render.
 */
function local_credentium_claim_run_error_advice($kind) {
    $known = [
        \local_credentium\api\claim_client::FAIL_AUTH => 'report_error_auth',
        // FAIL_CLIENT also covers a client that was never configured, which cannot be
        // reached today (claim_config rejects incomplete credentials before a client
        // is built) but would land here with the same "the request was wrong" advice.
        \local_credentium\api\claim_client::FAIL_CLIENT => 'report_error_client',
        \local_credentium\api\claim_client::FAIL_BUSY => 'report_error_busy',
        \local_credentium\api\claim_client::FAIL_SERVER => 'report_error_server',
        \local_credentium\api\claim_client::FAIL_NETWORK => 'report_error_network',
    ];
    $stringid = $known[(string) $kind] ?? 'report_error_generic';
    return get_string($stringid, 'local_credentium');
}

/**
 * The full "what went wrong and what to do" message for the last recorded claim sync run.
 *
 * Shared by the claim report page and the manual "Check status now" redirect so both
 * give the same diagnosis; a run that failed one way must never be explained two ways.
 *
 * @return string Translated message, ready to render.
 */
function local_credentium_claim_run_error_message() {
    $message = local_credentium_claim_run_error_advice(
        (string) get_config('local_credentium', 'claimlastrunerrorkind')
    );
    $pending = (int) get_config('local_credentium', 'claimlastrununanswered');
    if ($pending > 0) {
        $message .= ' ' . get_string('report_error_pending', 'local_credentium', $pending);
    }
    return $message;
}

/**
 * Tab row linking the two Credentium admin reports.
 *
 * @param string $current Active tab: 'issuances' or 'claims'.
 * @return string Rendered tab tree.
 */
function local_credentium_report_tabs(string $current): string {
    global $OUTPUT;
    $tabs = [
        new tabobject(
            'issuances',
            new moodle_url('/local/credentium/index.php'),
            get_string('report', 'local_credentium')
        ),
        new tabobject(
            'claims',
            new moodle_url('/local/credentium/claimreport.php'),
            get_string('claimreport', 'local_credentium')
        ),
    ];
    return $OUTPUT->tabtree($tabs, $current);
}

/**
 * Add a "My credentials" node to the user's own profile page.
 *
 * @param \core_user\output\myprofile\tree $tree The profile tree.
 * @param stdClass $user The user whose profile is shown.
 * @param bool $iscurrentuser Whether the profile belongs to the current user.
 * @param stdClass|null $course The current course (if any).
 * @return void
 */
function local_credentium_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    unset($course);
    if (!$iscurrentuser || !local_credentium_claim_enabled()) {
        return;
    }
    if (!has_capability('local/credentium:claim', \context_user::instance($user->id))) {
        return;
    }

    // Same rule as the user-menu entry: present for anyone with credentials to see,
    // counted only while something is still waiting to be claimed.
    if (\local_credentium\local\claimable::count_visible_for_user((int) $user->id) < 1) {
        return;
    }
    $label = get_string('nav_mycredentials', 'local_credentium');
    // A persistent pointer like the user-menu entry, so it counts claimable credentials
    // regardless of whether the banner was dismissed.
    $count = \local_credentium\local\claimable::count_claimable_for_user((int) $user->id);
    if ($count > 0) {
        $label = get_string('nav_mycredentials_count', 'local_credentium', $count);
    }

    $node = new \core_user\output\myprofile\node(
        'miscellaneous',
        'local_credentium',
        $label,
        null,
        new \moodle_url('/local/credentium/mycredentials.php')
    );
    $tree->add_node($node);
}