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

    /** @var array Backoff delays for grade retry: 15s → 60s → 180s → 600s → 1800s (cap at 30min). */
    private const GRADE_RETRY_DELAYS = [15, 60, 180, 600, 1800];

    /** @var int Max retry attempts. Total max wait: ~44 minutes (15 + 60 + 180 + 600 + 1800 = 2655s). */
    private const MAX_GRADE_ATTEMPTS = 5;

    /** @var int Seconds tolerance for freshness check. */
    private const FRESHNESS_TOLERANCE = 60;

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

        // Check if we need to wait for grade.
        $sendgrade = $courseconfig && !empty($courseconfig->sendgrade);

        // Grade freshness logic: never trust grade at event time, always verify freshness.
        if ($sendgrade) {
            require_once($CFG->libdir . '/gradelib.php');

            // Execution number is attempts + 1 (attempts = previous executions).
            $executionnum = $issuance->attempts + 1;

            // 1. Maybe regrade (conditional - only on first execution or when course needs it).
            $this->maybe_regrade_course($course->id, $user->id, $executionnum);

            // 2. Check freshness with tolerance AND needsupdate signal.
            $timecompleted = $issuance->timecompleted ?? $issuance->timecreated;
            $gradecheck = $this->check_grade_freshness($user->id, $course->id, $timecompleted, $executionnum);

            mtrace("Grade check: fresh=" . ($gradecheck['fresh'] ? 'true' : 'false') .
                   ", reason={$gradecheck['reason']}" .
                   ", grade={$gradecheck['grade']}" .
                   ", grademax={$gradecheck['grademax']}" .
                   ", gradepass={$gradecheck['gradepass']}" .
                   ", timemodified={$gradecheck['timemodified']}" .
                   ", completion={$timecompleted}" .
                   ", needsupdate=" . ($gradecheck['needsupdate'] ? 'true' : 'false') .
                   ", execution={$executionnum}" .
                   ", tolerance=" . self::FRESHNESS_TOLERANCE . "s");

            // Handle NO_GRADE_ITEM case - fail immediately with clear message.
            if ($gradecheck['reason'] === 'NO_GRADE_ITEM') {
                mtrace("ERROR: No course grade item found - cannot fetch grade");
                $this->mark_failed(
                    $issuance,
                    'NO_GRADE_ITEM',
                    "No course grade item exists for this course. Cannot send grade with credential.",
                    $user,
                    $course
                );
                return;
            }

            if (!$gradecheck['fresh']) {
                // Grade not fresh - retry with backoff.
                if ($issuance->attempts < self::MAX_GRADE_ATTEMPTS) {
                    $delayindex = min($issuance->attempts, count(self::GRADE_RETRY_DELAYS) - 1);
                    $delay = self::GRADE_RETRY_DELAYS[$delayindex];
                    $this->schedule_retry($issuance, $gradecheck['reason'], $delay);
                    return;
                } else {
                    // Max attempts exceeded.
                    $totalwait = $this->calculate_total_wait($issuance->attempts);
                    $this->mark_failed($issuance, 'GRADE_TIMEOUT',
                        "Grade not ready after {$issuance->attempts} attempts (~{$totalwait}s / " .
                        round($totalwait / 60) . " min wait). " .
                        "Reason: {$gradecheck['reason']}, " .
                        "Grade timemodified: {$gradecheck['timemodified']}, completion: {$timecompleted}, " .
                        "needsupdate: " . ($gradecheck['needsupdate'] ? 'true' : 'false'),
                        $user, $course);
                    return;
                }
            }

            // 3. Grade is fresh - store raw points value.
            // NOTE: finalgrade is in POINTS, not percentage.
            // Conversion to percentage happens later when sending to API.
            $issuance->grade = $gradecheck['grade'];
            $issuance->timemodified = time();
            $DB->update_record('local_credentium_issuances', $issuance);

            // Log percentage for clarity.
            $percentage = ($gradecheck['grademax'] > 0)
                ? round(($gradecheck['grade'] / $gradecheck['grademax']) * 100, 2)
                : $gradecheck['grade'];
            mtrace("Grade is fresh ({$gradecheck['reason']}): {$gradecheck['grade']} points = {$percentage}%");

            // 4. Log warning if grade is below passing threshold.
            // NOTE: We do NOT fail here because:
            // - course_completed can fire for non-grade completion configs (activity completion only)
            // - gradepass may not be set on the course
            // - The completion criteria already determined the user "passed" the course
            // This is informational only; the credential will still be issued.
            if ($gradecheck['gradepass'] !== null && $gradecheck['gradepass'] > 0 &&
                $gradecheck['grade'] < $gradecheck['gradepass']) {
                mtrace("WARNING: Grade {$gradecheck['grade']} is below gradepass {$gradecheck['gradepass']}. " .
                       "Issuing credential anyway (course completion criteria may not require grade threshold).");
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

        // Count credentials issued in the last hour for this category.
        $onehourago = time() - 3600;

        // Handle NULL categoryid comparison for cross-database compatibility.
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

    /**
     * Force grade recalculation - only on first execution or when course needs it.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param int $executionnum Current execution number (1-based)
     * @return bool True if regrade was performed
     */
    private function maybe_regrade_course(int $courseid, int $userid, int $executionnum): bool {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        // Only regrade on first execution, or if course explicitly needs it.
        if ($executionnum > 1 && !grade_needs_regrade_final_grades($courseid)) {
            mtrace("Skipping regrade - not needed (execution {$executionnum})");
            return false;
        }

        $gradeitem = \grade_item::fetch_course_item($courseid);
        if ($gradeitem) {
            grade_regrade_final_grades($courseid, $userid, $gradeitem);
            mtrace("Forced grade recalculation (execution {$executionnum})");
            return true;
        }
        return false;
    }

    /**
     * Check if grade is ready for credential issuance.
     *
     * Freshness is determined by:
     * 1. If no grade item exists → fail immediately (NO_GRADE_ITEM)
     * 2. If no grade exists → not fresh (NO_GRADE)
     * 3. If timemodified >= (timecompleted - tolerance) → fresh (grade updated around completion)
     * 4. If gradebook is settled (no needsupdate) AND we've retried at least once → fresh
     *    (handles cron-delayed completion where grade was finalized before completion event)
     * 5. Otherwise → not fresh, needs retry
     *
     * The "at least one retry" requirement for case 4 prevents accepting stale grades
     * on the very first execution, giving aggregation a chance to run.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param int $timecompleted Completion-event timestamp from issuance record
     * @param int $executionnum Current execution number (1-based)
     * @return array ['fresh' => bool, 'grade' => float|null, 'grademax' => float|null,
     *                'timemodified' => int|null, 'gradepass' => float|null, 'needsupdate' => bool,
     *                'reason' => string]
     */
    private function check_grade_freshness(int $userid, int $courseid, int $timecompleted, int $executionnum): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $gradeitem = \grade_item::fetch_course_item($courseid);
        if (!$gradeitem) {
            return [
                'fresh' => false,
                'grade' => null,
                'grademax' => null,
                'timemodified' => null,
                'gradepass' => null,
                'needsupdate' => false,
                'reason' => 'NO_GRADE_ITEM'
            ];
        }

        $graderecord = $DB->get_record('grade_grades', [
            'userid' => $userid,
            'itemid' => $gradeitem->id
        ]);

        // Case 1: No grade record or null finalgrade.
        if (!$graderecord || $graderecord->finalgrade === null) {
            return [
                'fresh' => false,
                'grade' => null,
                'grademax' => $gradeitem->grademax,
                'timemodified' => null,
                'gradepass' => $gradeitem->gradepass,
                'needsupdate' => (bool)$gradeitem->needsupdate,
                'reason' => 'NO_GRADE'
            ];
        }

        // Check if gradebook needs regrade (needsupdate flag).
        // Use grade_needs_regrade_final_grades for consistency with regrade logic.
        $needsupdate = grade_needs_regrade_final_grades($courseid);

        // Apply tolerance: grade is fresh if modified within FRESHNESS_TOLERANCE seconds before completion.
        $thresholdtime = $timecompleted - self::FRESHNESS_TOLERANCE;
        $timemodifiedfresh = ($graderecord->timemodified >= $thresholdtime);

        // Case 2: timemodified is within tolerance of completion.
        if ($timemodifiedfresh) {
            return [
                'fresh' => true,
                'grade' => $graderecord->finalgrade,
                'grademax' => $gradeitem->grademax,
                'timemodified' => $graderecord->timemodified,
                'gradepass' => $gradeitem->gradepass,
                'needsupdate' => $needsupdate,
                'reason' => 'TIMEMODIFIED_FRESH'
            ];
        }

        // Case 3: Gradebook is settled (no needsupdate) AND we've retried at least once.
        // This handles cron-delayed completion: grade was finalized at T0, completion fires at T1 >> T0.
        // Requiring executionnum > 1 prevents accepting stale grades on first run.
        if (!$needsupdate && $executionnum > 1) {
            return [
                'fresh' => true,
                'grade' => $graderecord->finalgrade,
                'grademax' => $gradeitem->grademax,
                'timemodified' => $graderecord->timemodified,
                'gradepass' => $gradeitem->gradepass,
                'needsupdate' => $needsupdate,
                'reason' => 'GRADEBOOK_SETTLED'
            ];
        }

        // Case 4: Not fresh - needs retry.
        $reason = $needsupdate ? 'GRADEBOOK_NEEDS_UPDATE' : 'TIMEMODIFIED_STALE_FIRST_RUN';
        return [
            'fresh' => false,
            'grade' => $graderecord->finalgrade,
            'grademax' => $gradeitem->grademax,
            'timemodified' => $graderecord->timemodified,
            'gradepass' => $gradeitem->gradepass,
            'needsupdate' => $needsupdate,
            'reason' => $reason
        ];
    }

    /**
     * Calculate total wait time for given number of attempts.
     *
     * @param int $attempts Number of attempts completed
     * @return int Total seconds waited
     */
    private function calculate_total_wait(int $attempts): int {
        $total = 0;
        $lastindex = count(self::GRADE_RETRY_DELAYS) - 1;
        for ($i = 0; $i < $attempts; $i++) {
            $delayindex = min($i, $lastindex);
            $total += self::GRADE_RETRY_DELAYS[$delayindex];
        }
        return $total;
    }

    /**
     * Schedule retry using reschedule_or_queue to avoid duplicates.
     *
     * IMPORTANT: Task identity must match observer's queued task exactly:
     * - Same customdata structure
     * - Same userid (0 = system)
     * - Same component
     * - Same fully-qualified classname
     *
     * @param \stdClass $issuance The issuance record
     * @param string $reason The reason for retry
     * @param int $delay Delay in seconds
     */
    private function schedule_retry(\stdClass $issuance, string $reason, int $delay): void {
        global $DB;

        // Update attempts counter (attempts = number of executions so far).
        $issuance->attempts++;
        $issuance->status = 'retrying';
        $issuance->timemodified = time();
        $DB->update_record('local_credentium_issuances', $issuance);

        // Create task with IDENTICAL identity to what observer queued.
        // Use fully-qualified class name for correct task identity matching.
        $task = new \local_credentium\task\issue_credential();
        $task->set_custom_data([
            'issuanceid' => $issuance->id,
            'categoryid' => $issuance->categoryid
        ]);
        $task->set_userid(0);  // Must match observer.
        $task->set_component('local_credentium');  // Must match observer.
        $task->set_next_run_time(time() + $delay);

        // Reschedule existing or queue new (won't duplicate).
        \core\task\manager::reschedule_or_queue_adhoc_task($task);

        mtrace("Scheduled retry for issuance {$issuance->id} in {$delay}s (reason: {$reason}, attempts: {$issuance->attempts})");
    }
}