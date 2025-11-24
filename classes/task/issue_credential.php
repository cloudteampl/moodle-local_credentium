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
 * Adhoc task to issue a credential.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc task to issue a credential.
 */
class issue_credential extends \core\task\adhoc_task {
    
    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/credentium/lib.php');

        mtrace('Credentium: Starting issue_credential task execution');

        $data = $this->get_custom_data();
        if (empty($data->issuanceid)) {
            mtrace('Error: No issuance ID provided.');
            return;
        }

        mtrace('Credentium: Processing issuance ID: ' . $data->issuanceid);

        // Get issuance record.
        $issuance = $DB->get_record('local_credentium_issuances', ['id' => $data->issuanceid]);
        if (!$issuance) {
            mtrace("Error: Issuance record {$data->issuanceid} not found.");
            return;
        }

        // Check if already issued.
        if ($issuance->status === 'issued') {
            mtrace("Credential already issued for issuance {$issuance->id}.");
            return;
        }

        // Get user and course.
        $user = $DB->get_record('user', ['id' => $issuance->userid]);
        $course = $DB->get_record('course', ['id' => $issuance->courseid]);

        if (!$user || !$course) {
            mtrace("Error: User or course not found for issuance {$issuance->id}.");
            $this->mark_failed($issuance, 'USER_OR_COURSE_NOT_FOUND', 'User or course not found');
            return;
        }

        // Get resolved course config with category inheritance
        mtrace("Resolving course configuration for course {$course->id}...");
        $courseconfig = local_credentium_resolve_course_config($course->id);
        if (!$courseconfig) {
            mtrace("ERROR: Course config not found for course {$course->id}.");
            $this->mark_failed($issuance, 'COURSE_CONFIG_NOT_FOUND', 'Course configuration not found');
            return;
        }

        mtrace("Course config resolved:");
        mtrace("  - Template ID: " . ($courseconfig->templateid ?? 'NULL'));
        mtrace("  - Send Grade: " . ($courseconfig->sendgrade ?? 0));
        mtrace("  - Has API URL: " . (!empty($courseconfig->apiurl) ? 'YES' : 'NO'));
        mtrace("  - Has API Key: " . (!empty($courseconfig->apikey) ? 'YES' : 'NO'));
        mtrace("  - Category ID: " . ($courseconfig->categoryid ?? 'global'));
        mtrace("  - Paused: " . ($courseconfig->paused ?? 0));

        // Check if category is paused
        if (!empty($courseconfig->paused)) {
            mtrace("Issuance paused for category {$courseconfig->categoryid}. Rescheduling in 1 hour.");
            $task = new issue_credential();
            $task->set_custom_data([
                'issuanceid' => $issuance->id,
                'categoryid' => $issuance->categoryid
            ]);
            $task->set_next_run_time(time() + 3600);
            \core\task\manager::queue_adhoc_task($task);
            return;
        }

        // Check rate limit
        if (!empty($courseconfig->ratelimit)) {
            if (!$this->check_rate_limit($courseconfig->categoryid, $courseconfig->ratelimit)) {
                mtrace("Rate limit exceeded for category {$courseconfig->categoryid}. Rescheduling in 10 minutes.");
                $task = new issue_credential();
                $task->set_custom_data([
                    'issuanceid' => $issuance->id,
                    'categoryid' => $issuance->categoryid
                ]);
                $task->set_next_run_time(time() + 600);
                \core\task\manager::queue_adhoc_task($task);
                return;
            }
        }

        // Check if we need to wait for grade
        $sendgrade = $courseconfig && !empty($courseconfig->sendgrade);

        // ALWAYS try to refresh grade if NULL and sendgrade is enabled
        // This handles cases where:
        // - Grade aggregation was delayed at completion time
        // - Course config was changed to enable sendgrade after completion
        // - Grade was manually added/updated after completion
        if ($sendgrade && is_null($issuance->grade)) {
            require_once($CFG->libdir . '/gradelib.php');
            require_once($CFG->dirroot . '/grade/querylib.php');

            mtrace("Attempting to fetch grade from gradebook (attempt {$issuance->attempts})...");
            $course_grade = \grade_get_course_grade($user->id, $issuance->courseid);

            if ($course_grade && isset($course_grade->grade)) {
                // Update the grade in the issuance record
                $issuance->grade = $course_grade->grade;
                $DB->update_record('local_credentium_issuances', $issuance);
                mtrace("✓ Grade fetched and updated: {$course_grade->grade}");
                // Continue with credential issuance below
            } else {
                // Grade still not available
                mtrace("✗ Grade not yet available in gradebook");

                // Use exponential backoff with longer delays: 1m → 5m → 15m → 30m → 1h
                // Max 10 attempts (configurable)
                $maxGradeAttempts = 10;

                if ($issuance->attempts < $maxGradeAttempts) {
                    // Exponential backoff: 60s, 300s (5m), 900s (15m), 1800s (30m), 3600s (1h), then cap at 1h
                    $delays = [60, 300, 900, 1800, 3600];
                    $delayIndex = min($issuance->attempts, count($delays) - 1);
                    $delay = $delays[$delayIndex];

                    // Update attempts
                    $issuance->attempts++;
                    $DB->update_record('local_credentium_issuances', $issuance);

                    $task = new issue_credential();
                    $task->set_custom_data([
                        'issuanceid' => $issuance->id,
                        'categoryid' => $issuance->categoryid
                    ]);
                    $task->set_next_run_time(time() + $delay);
                    \core\task\manager::queue_adhoc_task($task);

                    mtrace("Rescheduled grade fetch for issuance {$issuance->id} in {$delay} seconds (attempt {$issuance->attempts}/{$maxGradeAttempts})");
                    return;
                } else {
                    // Exceeded max grade fetch attempts - fail with clear error
                    mtrace("ERROR: Grade is required but not available after {$maxGradeAttempts} attempts");
                    $this->mark_failed(
                        $issuance,
                        'GRADE_NOT_AVAILABLE',
                        "Grade is required by course configuration but is not available in gradebook after {$maxGradeAttempts} retry attempts.",
                        $user,
                        $course
                    );
                    return;
                }
            }
        }

        // Increment attempt counter.
        $issuance->attempts++;
        $issuance->timemodified = time();
        $DB->update_record('local_credentium_issuances', $issuance);

        try {
            // Initialize API client with category-specific credentials
            mtrace("Initializing API client...");
            $client = new \local_credentium\api\client(
                $courseconfig->apiurl ?? null,
                $courseconfig->apikey ?? null,
                $courseconfig->categoryid ?? null
            );
            mtrace("API client initialized successfully.");
            
            // Prepare additional data.
            $additionaldata = [];

            // Check if we should send grade
            if ($courseconfig && !empty($courseconfig->sendgrade)) {
                // Format grade for API - convert to percentage string
                if (!is_null($issuance->grade)) {
                    $gradeitem = \grade_item::fetch_course_item($course->id);
                    if ($gradeitem && $gradeitem->grademax > 0) {
                        $percentage = round(($issuance->grade / $gradeitem->grademax) * 100, 2);
                        $additionaldata['grade'] = $percentage . '%';
                    } else {
                        // Fallback to raw grade
                        $additionaldata['grade'] = (string)$issuance->grade;
                    }
                }
                // If grade is null and sendgrade is enabled, we already handled retry above
            }
            
            // Add completion date.
            $completion = new \completion_info($course);
            if ($completion->is_enabled() && $completion->is_course_complete($user->id)) {
                $ccompletion = new \completion_completion(['userid' => $user->id, 'course' => $course->id]);
                if ($ccompletion->timecompleted) {
                    $additionaldata['completion_date'] = $ccompletion->timecompleted;
                }
            }
            
            // Issue credential.
            mtrace("Calling API to issue credential with template ID: {$issuance->templateid}");
            $response = $client->issue_credential($issuance->templateid, $user, $course, $additionaldata);
            
            // Update issuance record.
            $issuance->credentialid = $response->credential_id;
            $issuance->status = 'issued';
            $issuance->timeissued = time();
            $issuance->errorcode = null;
            $issuance->errormessage = null;
            $DB->update_record('local_credentium_issuances', $issuance);
            
            // Trigger event.
            $context = \context_course::instance($course->id);
            $event = \local_credentium\event\credential_issued::create_from_issuance($issuance, $context);
            $event->trigger();
            
            // Send notification to user.
            $this->send_notification($user, $course, true);
            
            mtrace("Successfully issued credential {$response->credential_id} for user {$user->id} in course {$course->id}.");
            
        } catch (\Exception $e) {
            $errorcode = 'API_ERROR';
            $errormessage = $e->getMessage();

            mtrace("==================================================");
            mtrace("ERROR: Failed to issue credential for issuance {$issuance->id}");
            mtrace("Error Type: " . get_class($e));
            mtrace("Error Message: {$errormessage}");
            mtrace("Error Code: {$errorcode}");

            // For moodle_exception, access debuginfo as public property
            if ($e instanceof \moodle_exception) {
                if (!empty($e->debuginfo)) {
                    mtrace("Debug Info:");
                    mtrace($e->debuginfo);
                }
                if (!empty($e->a)) {
                    mtrace("Additional Info (a): " . (is_string($e->a) ? $e->a : print_r($e->a, true)));
                }
            }

            // Show previous exception if exists - THIS IS WHERE THE ACTUAL API ERROR IS!
            $prev = $e->getPrevious();
            if ($prev) {
                mtrace("--- Previous Exception (ACTUAL API ERROR) ---");
                mtrace("Type: " . get_class($prev));
                mtrace("Message: " . $prev->getMessage());
                if ($prev instanceof \moodle_exception) {
                    mtrace("Error Code: " . $prev->errorcode);
                    if (!empty($prev->debuginfo)) {
                        mtrace("=== API DEBUG INFO ===");
                        mtrace($prev->debuginfo);
                        mtrace("======================");
                    }
                    if (!empty($prev->a)) {
                        mtrace("Additional Info (a): " . (is_string($prev->a) ? $prev->a : print_r($prev->a, true)));
                    }
                }
            } else {
                mtrace("No previous exception found");
            }

            mtrace("==================================================");
            
            // Check if we should retry.
            if ($issuance->attempts < 3) {
                // Update status to retrying.
                $issuance->status = 'retrying';
                $issuance->errorcode = $errorcode;
                $issuance->errormessage = $errormessage;
                $DB->update_record('local_credentium_issuances', $issuance);
                
                // Schedule retry with exponential backoff.
                $delay = min(300 * pow(2, $issuance->attempts - 1), 3600); // Max 1 hour.
                $task = new issue_credential();
                $task->set_custom_data([
                    'issuanceid' => $issuance->id,
                    'categoryid' => $issuance->categoryid
                ]);
                $task->set_next_run_time(time() + $delay);
                \core\task\manager::queue_adhoc_task($task);

                mtrace("Scheduled retry for issuance {$issuance->id} in {$delay} seconds.");
            } else {
                // Mark as failed after max attempts and send notification.
                $this->mark_failed($issuance, $errorcode, $errormessage, $user, $course);
            }
        }
    }
    
    /**
     * Mark issuance as failed and send notification.
     *
     * @param \stdClass $issuance The issuance record
     * @param string $errorcode Error code
     * @param string $errormessage Error message
     * @param \stdClass $user The user object
     * @param \stdClass $course The course object
     */
    private function mark_failed($issuance, $errorcode, $errormessage, $user = null, $course = null) {
        global $DB;
        
        $issuance->status = 'failed';
        $issuance->errorcode = $errorcode;
        $issuance->errormessage = $errormessage;
        $issuance->timemodified = time();
        $DB->update_record('local_credentium_issuances', $issuance);
        
        // Trigger event.
        $context = \context_course::instance($issuance->courseid);
        $event = \local_credentium\event\credential_failed::create_from_issuance($issuance, $context, $errormessage);
        $event->trigger();
        
        // Send notification after the event is triggered and state is persisted
        if ($user && $course) {
            $this->send_notification($user, $course, false);
        }
    }
    
    /**
     * Send notification to user about credential issuance.
     *
     * @param \stdClass $user The user
     * @param \stdClass $course The course
     * @param bool $success Whether the issuance was successful
     */
    private function send_notification($user, $course, $success) {
        $message = new \core\message\message();
        $message->component = 'local_credentium';
        $message->name = 'credentialissuance';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = get_string('pluginname', 'local_credentium');
        
        if ($success) {
            $message->fullmessage = get_string('notification:credentialissued', 'local_credentium', $course->fullname);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = get_string('notification:credentialissued', 'local_credentium', $course->fullname);
            $message->notification = 1;
        } else {
            $message->fullmessage = get_string('notification:credentialfailed', 'local_credentium', $course->fullname);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = get_string('notification:credentialfailed', 'local_credentium', $course->fullname);
            $message->notification = 1;
        }
        
        message_send($message);
    }

    /**
     * Check if rate limit is exceeded for a category.
     *
     * @param int|null $categoryid The category ID (null for global)
     * @param int $limit The rate limit (credentials per hour)
     * @return bool True if within limit, false if exceeded
     */
    private function check_rate_limit($categoryid, $limit) {
        global $DB;

        // Count credentials issued in the last hour for this category
        $onehourago = time() - 3600;

        // Handle NULL categoryid comparison for cross-database compatibility
        if ($categoryid === null) {
            $sql = "SELECT COUNT(*)
                    FROM {local_credentium_issuances}
                    WHERE categoryid IS NULL
                      AND status = 'issued'
                      AND timeissued >= :onehourago";
            $count = $DB->count_records_sql($sql, ['onehourago' => $onehourago]);
        } else {
            $sql = "SELECT COUNT(*)
                    FROM {local_credentium_issuances}
                    WHERE categoryid = :categoryid
                      AND status = 'issued'
                      AND timeissued >= :onehourago";
            $count = $DB->count_records_sql($sql, [
                'categoryid' => $categoryid,
                'onehourago' => $onehourago
            ]);
        }

        mtrace("Rate limit check: {$count}/{$limit} credentials issued in last hour for category " .
               ($categoryid ?? 'global'));

        return $count < $limit;
    }
}