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
 * Event observer for local_credentium.
 *
 * @package    local_credentium
 * @copyright  2025 CloudTeam Sp. z o.o.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_credentium;

defined('MOODLE_INTERNAL') || die();

class observer {
    
    public static function course_completed(\core\event\course_completed $event) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/credentium/lib.php');

        if (!get_config('local_credentium', 'enabled')) {
            return;
        }

        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        // Validate event data.
        if (empty($courseid) || empty($userid)) {
            local_credentium_log('Invalid event data - missing courseid or userid');
            return;
        }

        // Get resolved course config with category inheritance.
        $config = local_credentium_resolve_course_config($courseid);

        if (!$config || !$config->enabled) {
            return;
        }

        // Check if category config is paused.
        if (!empty($config->paused)) {
            local_credentium_log('Issuance skipped - category is paused', [
                'courseid' => $courseid,
                'categoryid' => $config->categoryid ?? null
            ]);
            return;
        }

        // Always use completion trigger now.
        if ($config->issuancetrigger !== 'completion') {
            return;
        }

        // Acquire lock to prevent race condition duplicates.
        // NOTE: Requires site to have a working lock backend (DB lock is default and works fine).
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_credentium');
        $lockkey = "issue_{$courseid}_{$userid}";
        $lock = $lockfactory->get_lock($lockkey, 10); // 10 second timeout.

        if (!$lock) {
            local_credentium_log('Could not acquire lock, skipping', [
                'courseid' => $courseid,
                'userid' => $userid
            ]);
            return;
        }

        try {
            // Check for existing pending/retrying/issued record (inside lock).
            $existing = $DB->get_record_select('local_credentium_issuances',
                'userid = :userid AND courseid = :courseid AND status IN (:s1, :s2, :s3)',
                ['userid' => $userid, 'courseid' => $courseid,
                 's1' => 'pending', 's2' => 'retrying', 's3' => 'issued']
            );

            if ($existing) {
                local_credentium_log('Skipping duplicate issuance', [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'existing_status' => $existing->status
                ]);
                return;
            }

            // Create issuance record.
            // IMPORTANT: Never fetch grade at event time - it may be stale.
            // Grade will ONLY be fetched in the background task after freshness check.
            $eventdata = $event->get_data();
            $timecompleted = $eventdata['timecreated']; // Event timestamp.

            $issuance = new \stdClass();
            $issuance->userid = $userid;
            $issuance->courseid = $courseid;
            $issuance->templateid = $config->templateid;
            $issuance->status = 'pending';
            $issuance->attempts = 0;
            $issuance->grade = null;  // Never store grade at event time.
            $issuance->timecompleted = $timecompleted;
            $issuance->categoryid = $config->categoryid ?? null;
            $issuance->timecreated = time();
            $issuance->timemodified = time();

            $issuance->id = $DB->insert_record('local_credentium_issuances', $issuance);

            // Queue task with consistent identity for reschedule matching.
            // Use fully-qualified class name for correct task identity.
            $task = new \local_credentium\task\issue_credential();
            $task->set_custom_data([
                'issuanceid' => $issuance->id,
                'categoryid' => $issuance->categoryid
            ]);
            $task->set_userid(0);  // Consistent: always run as admin/system.
            $task->set_component('local_credentium');  // Explicit component.
            $task->set_next_run_time(time() + 15);  // Always 15s delay for grade aggregation.

            \core\task\manager::queue_adhoc_task($task);

            local_credentium_log('Queued credential issuance task', [
                'issuanceid' => $issuance->id,
                'userid' => $userid,
                'courseid' => $courseid,
                'timecompleted' => $timecompleted
            ]);

        } finally {
            $lock->release();
        }
    }

    /**
     * Handle user deletion event.
     *
     * When a user is deleted from Moodle, automatically delete all their
     * credential issuance records to comply with GDPR right to erasure.
     *
     * @param \core\event\user_deleted $event The user deletion event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/credentium/lib.php');

        $userid = $event->objectid;

        if (empty($userid)) {
            return;
        }

        // Delete all credential issuance records for this user
        $count = $DB->count_records('local_credentium_issuances', ['userid' => $userid]);

        if ($count > 0) {
            $deleted = $DB->delete_records('local_credentium_issuances', ['userid' => $userid]);

            if ($deleted) {
                local_credentium_log('User deletion: Deleted credential issuance records', [
                    'userid' => $userid,
                    'count' => $count
                ]);
            }
        }
    }

}